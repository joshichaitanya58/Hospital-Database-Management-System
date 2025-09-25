<?php
session_start();

$message = '';

ob_start();

// Database connect
$conn = new mysqli("localhost", "root", "", "hospital_data");
if ($conn->connect_error) { 
    die("Connection failed: ".$conn->connect_error); 
}

// Check if form submitted
if ($_SERVER["REQUEST_METHOD"]=="POST") {
    $formType = $_POST['form_type'] ?? '';

    // ---- SIGNUP ----
    if ($formType=="signup") {
        $name = $_POST['name'] ?? '';
        $specialization = $_POST['specialization'] ?? '';
        $licence_number = $_POST['licence_number'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['mob'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirmPassword'] ?? '';
        
        if ($password !== $confirm) {
            $message = "<p style='text-align:center;color:red;'>Passwords do not match.</p>";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Note: The column name 'Passward' is misspelled in your original code. 
            // I am using 'Password' here. Please ensure your database table `doctors_account`
            // has a column named `Password` (VARCHAR 255) and not `Passward`.
            $sql = "INSERT INTO doctors_account (Name, Specialization, Licence_Number, Email, Mobile_Number, Password) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $name, $specialization, $licence_number, $email, $phone, $hashed_password);

            if ($stmt->execute()) {
                $message = "<p style='text-align:center;color:green;'>Account created successfully! You can now login.</p>";
            } else {
                $message = "<p style='text-align:center;color:red;'>Error saving data: ".$stmt->error."</p>";
            }
            $stmt->close();
        }
    }

    // ---- LOGIN ----
    if ($formType=="login") {
        $user = $_POST['user'] ?? '';
        $submitted_password = $_POST['password'] ?? '';

        // Using 'Password' column. Make sure to rename 'Passward' in your DB.
        $sql = "SELECT id, Name, Password FROM doctors_account WHERE Email=? OR Mobile_Number=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $user, $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $doctor = $result->fetch_assoc();
            if (password_verify($submitted_password, $doctor['Password'])) {
                $_SESSION['doctor_id'] = $doctor['id'];
                $_SESSION['doctor_name'] = $doctor['Name'];
                $_SESSION['user_type'] = 'doctor';
                header("Location: doctor_dashboard.php");
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
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
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
  </style>
</head>
<body>
  <!-- Login -->
  <div class="login-container">
    <h2>Login</h2>
    <?php if(!empty($message)) echo $message; ?>
    <form method="POST" action="">
      <input type="hidden" name="form_type" value="login">
      <input type="text" name="user" placeholder="Email or Mobile Number" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <p>New User? <span id="new">Create an account</span></p>
    <p><a href="forgot_password.php?user_type=doctor">Forgot Password?</a></p>
  </div>

  <!-- Signup -->
  <div class="signInContainer">
    <h1>Create New Account</h1>
    <?php if(!empty($message)) echo $message; ?>
    <form method="POST" action="">
      <input type="hidden" name="form_type" value="signup">
      <input type="text" placeholder="Enter Your Name" name="name" required>
      <input type="text" placeholder="specialization" name="specialization" required>
      <input type="text" placeholder="licence_number" name="licence_number" required>
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