<?php
session_start();

$message = '';

ob_start(); // Start output buffering to capture output

// ================= DATABASE CONNECTION =================
$conn = new mysqli("localhost", "root", "", "hospital_data");
if ($conn->connect_error) { 
    die("Connection failed: ".$conn->connect_error); 
}

// ================= HANDLE FORM =================
if ($_SERVER["REQUEST_METHOD"]=="POST") {
    $formType = $_POST['form_type'] ?? '';

    // ---- SIGNUP ----
    if ($formType=="signup") {
        $name = $_POST['name'] ?? '';
        $age = $_POST['age'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['mob'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirmPassword'] ?? '';
        
        if ($password !== $confirm) {
            $message = "<p style='text-align:center;color:red;'>Passwords do not match.</p>";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO patients_account (Name, Age, Gender, Email, Mobile_Number, Password) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sissss", $name, $age, $gender, $email, $phone, $hashed_password);

            if ($stmt->execute()) {
                $message = "<p style='text-align:center;color:green;'>Patient account created successfully! You can now login.</p>";
            } else {
                if ($conn->errno == 1062) { // Duplicate entry
                    $message = "<p style='text-align:center;color:red;'>Email or Mobile Number already exists.</p>";
                } else {
                    $message = "<p style='text-align:center;color:red;'>Error saving data: ".$stmt->error."</p>";
                }
            }
            $stmt->close();
        }
    }

    // ---- LOGIN ----
    if ($formType=="login") {
        $user = $_POST['user'] ?? '';
        $submitted_password = $_POST['password'] ?? '';

        $sql = "SELECT id, Name, Password FROM patients_account WHERE Email=? OR Mobile_Number=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $user, $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $patient = $result->fetch_assoc();
            if (password_verify($submitted_password, $patient['Password'])) {
                $_SESSION['user_id'] = $patient['id'];
                $_SESSION['user_name'] = $patient['Name'];
                $_SESSION['user_type'] = 'patient';
                header("Location: main.php");
                exit();
            } else {
                $message = "<p style='text-align:center;color:red;'>Invalid email/mobile or password.</p>";
            }
        } else {
            $message = "<p style='text-align:center;color:red;'>Invalid email/mobile or password.</p>";
        }
        $stmt->close();
    }
}

$conn->close();
ob_end_flush(); // Send the output
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patient Login</title>
  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      font-family: 'Poppins', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
    }
    .login-container,.signInContainer {
      background-color: #ffffff;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      padding: 40px;
      width: 100%;
      max-width: 400px;
      text-align: center;
    }
    .signInContainer { display:none; }
    h2,h1 {color:#333;margin-bottom:30px;font-weight:600;}
    form input {
      width:100%;padding:12px;margin-bottom:15px;
      border:1px solid #ddd;border-radius:5px;font-size:16px;
    }
    form input:focus {border-color:#4a90e2;outline:none;}
    button {
      background:#4a90e2;color:white;border:none;padding:12px 0;width:100%;
      border-radius:5px;font-size:16px;cursor:pointer;
    }
    button:hover {background:#357abd;}
    #new {color:#4a90e2;text-decoration:underline;cursor:pointer;}
    a { color: #4a90e2; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .alert {
      padding: 10px;
      margin: 10px 0;
      border-radius: 5px;
      text-align: center;
    }
    .alert-success {background-color: #d4edda; color: #155724;}
    .alert-error {background-color: #f8d7da; color: #721c24;}
  </style>
</head>
<body>
  <!-- Login -->
  <div class="login-container">
    <h2>Patient Login</h2>
    <?php if(!empty($message)) echo $message; ?>
    <form method="POST" action="">
      <input type="hidden" name="form_type" value="login">
      <input type="text" name="user" placeholder="Email or Mobile Number" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <p>New Patient? <span id="new">Create an account</span></p>
    <p><a href="forgot_password.php?user_type=patient">Forgot Password?</a></p>
  </div>

  <!-- Signup -->
  <div class="signInContainer">
    <h1>Create Patient Account</h1>
    <?php if(!empty($message)) echo $message; ?>
    <form method="POST" action="">
      <input type="hidden" name="form_type" value="signup">
      <input type="text" placeholder="Enter Your Name" name="name" required>
      <input type="number" placeholder="Age" name="age" required>
      <input type="text" placeholder="Gender" name="gender" required>
      <input type="email" placeholder="Email" name="email" required>
      <input type="tel" placeholder="Mobile Number" name="mob" required>
      <input type="password" placeholder="Password" name="password" required>
      <input type="password" placeholder="Confirm Password" name="confirmPassword" required>
      <button type="submit">Create Account</button>
    </form>
  </div>

  <script>
    const NewAccount=document.getElementById("new");
    const signUp=document.querySelector(".signInContainer");
    const login=document.querySelector(".login-container");
    const pass=document.querySelector(".signInContainer input[name='password']");
    const cpass=document.querySelector(".signInContainer input[name='confirmPassword']");
    if(pass && cpass){
      cpass.addEventListener("input",()=>{cpass.setCustomValidity(pass.value!==cpass.value?"Passwords do not match":"");});
    }
    NewAccount.addEventListener("click", ()=>{ login.style.display="none"; signUp.style.display="block";});
  </script>
</body>
</html>