<?php
// complete.php
@session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "hospital_data");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// Ensure completed_appointments table exists (safe)
$conn->query("
  CREATE TABLE IF NOT EXISTS completed_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100),
    Email VARCHAR(100),
    Phone VARCHAR(30),
    Date DATE,
    Time TIME,
    doctor_id INT,
    Message TEXT,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )
");

// Helper to redirect back
function go_back() {
  $back = $_SERVER['HTTP_REFERER'] ?? 'doctor_dashboard.php';
  header("Location: $back");
  exit;
}

// If we have an ID (best case)
if (isset($_POST['id']) && ctype_digit((string)$_POST['id'])) {
    $id = (int)$_POST['id'];

    // Fetch the appointment
    $stmt = $conn->prepare("SELECT Name, Email, Phone, Date, Time, doctor_id, Message FROM appointments WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $ap = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($ap) {
        // Insert into completed_appointments
        $ins = $conn->prepare("INSERT INTO completed_appointments (Name, Email, Phone, Date, Time, doctor_id, Message) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("sssssis", $ap['Name'], $ap['Email'], $ap['Phone'], $ap['Date'], $ap['Time'], $ap['doctor_id'], $ap['Message']);
        $ins->execute();
        $ins->close();

        // Delete from original appointments table
        $del = $conn->prepare("DELETE FROM appointments WHERE id = ? LIMIT 1");
        $del->bind_param("i", $id);
        $del->execute();
        $del->close();
    }
}

// Redirect back to the dashboard
$conn->close();
go_back();
?>
