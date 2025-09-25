<?php
session_start();
$message = '';
$show_reset_form = false;
$user_type = $_GET['user_type'] ?? $_POST['user_type'] ?? '';
$email = '';

if (!in_array($user_type, ['patient', 'doctor'])) {
    die("Invalid user type specified.");
}

// DB connection
$conn = new mysqli("localhost", "root", "", "hospital_data");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 1: Check if email exists
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_email'])) {
    $email = $_POST['email'];
    $table = ($user_type === 'patient') ? 'patients_account' : 'doctors_account';

    $sql = "SELECT id FROM `$table` WHERE Email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $show_reset_form = true; // Email found, show the password reset form
    } else {
        $message = "<p style='text-align:center;color:red;'>No account found with that email address.</p>";
    }
    $stmt->close();
}

// Step 2: Reset the password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "<p style='text-align:center;color:red;'>Passwords do not match.</p>";
        $show_reset_form = true; // Keep showing the form
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $table = ($user_type === 'patient') ? 'patients_account' : 'doctors_account';

        $sql = "UPDATE `$table` SET Password = ? WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashed_password, $email);

        if ($stmt->execute()) {
            $login_page = ($user_type === 'patient') ? 'patientsLogin.php' : 'doctorsLogin.php';
            $message = "<p style='text-align:center;color:green;'>Password has been reset successfully. You can now <a href='{$login_page}'>login</a>.</p>";
            $show_reset_form = false;
        } else {
            $message = "<p style='text-align:center;color:red;'>An error occurred. Please try again.</p>";
            $show_reset_form = true; // Keep showing the form on error
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
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
    .container {
      background-color: #ffffff;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      padding: 40px;
      width: 100%;
      max-width: 400px;
      text-align: center;
    }
    h2 {color:#333;margin-bottom:30px;font-weight:600;}
    form input {
      width:100%;padding:12px;margin-bottom:15px;
      border:1px solid #ddd;border-radius:5px;font-size:16px;
      box-sizing: border-box;
    }
    form input:focus {border-color:#4a90e2;outline:none;}
    button {
      background:#4a90e2;color:white;border:none;padding:12px 0;width:100%;
      border-radius:5px;font-size:16px;cursor:pointer;
    }
    button:hover {background:#357abd;}
    a { color: #4a90e2; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Forgot Password</h2>
    <p>Reset password for a <?= htmlspecialchars(ucfirst($user_type)) ?> account.</p>
    
    <?php if(!empty($message)) echo $message; ?>

    <?php if ($show_reset_form): ?>
      <!-- Form to set new password -->
      <form method="POST" action="">
        <input type="hidden" name="user_type" value="<?= htmlspecialchars($user_type) ?>">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <p>Enter new password for <strong><?= htmlspecialchars($email) ?></strong></p>
        <input type="password" name="password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
        <button type="submit" name="reset_password">Reset Password</button>
      </form>
    <?php elseif (strpos($message, 'successfully') === false): ?>
      <!-- Form to enter email -->
      <form method="POST" action="">
        <input type="hidden" name="user_type" value="<?= htmlspecialchars($user_type) ?>">
        <input type="email" name="email" placeholder="Enter your registered Email" required>
        <button type="submit" name="check_email">Find Account</button>
      </form>
    <?php endif; ?>
    
    <p style="margin-top: 20px;">
      <a href="<?= ($user_type === 'patient') ? 'patientsLogin.php' : 'doctorsLogin.php' ?>">Back to Login</a>
    </p>
  </div>
</body>
</html>