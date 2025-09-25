<?php
// contact_process.php
$conn = new mysqli("localhost", "root", "", "hospital_data");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create contact_messages table if it doesn't exist
$createTableSQL = "
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createTableSQL)) {
    die("Error creating table: " . $conn->error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    $sql = "INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $email, $message);

    if ($stmt->execute()) {
        echo "<script>alert('Message sent successfully!'); window.location.href = 'index.php';</script>";
    } else {
        echo "<script>alert('Error sending message.'); window.history.back();</script>";
    }

    $stmt->close();
}

$conn->close();
?>