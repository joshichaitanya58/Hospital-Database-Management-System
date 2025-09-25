<?php
$conn = new mysqli("localhost", "root", "", "hospital_data");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch doctors for the dropdown
$doctors_result = $conn->query("SELECT id, Name FROM doctors_account ORDER BY Name");

// Create appointments table if it doesn't exist (with doctor_id)
$createTableSQL = "
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL,
    Phone VARCHAR(20) NOT NULL,
    Date DATE NOT NULL,
    Time TIME NOT NULL,
    doctor_id INT NOT NULL,
    Message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createTableSQL)) {
    die("Error creating appointments table: " . $conn->error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $phone    = $_POST['phone'];
    $date     = $_POST['date'];
    $time     = $_POST['time'];
    $doctor_id = $_POST['doctor_id'];
    $message  = $_POST['message'];

    $sql = "INSERT INTO appointments (Name, Email, Phone, Date, Time, doctor_id, Message)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssis", $name, $email, $phone, $date, $time, $doctor_id, $message);

    if ($stmt->execute()) {
        echo "<script>alert('Appointment booked successfully!'); window.location.href = 'index.php';</script>";
        $stmt->close();
        $conn->close();
        exit();
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="appoinment-container">
        <h2>Book Appointment</h2>
        <form class="appointmentForm" action="#" method="POST">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <label for="phone">Phone:</label>
            <input type="tel" id="phone" name="phone" required>
            <label for="date">Date:</label>
            <input type="date" id="date" name="date" required>
            <label for="time">Time:</label>
            <input type="time" id="time" name="time" required>
            <label for="doctor">Doctor:</label>
            <select id="doctor" name="doctor_id" required>
                <option value="">Select a Doctor</option>
                <?php if ($doctors_result) {
                    while($doctor = $doctors_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($doctor['id']) ?>"><?= htmlspecialchars($doctor['Name']) ?></option>
                <?php endwhile;
                } ?>
            </select>
            <label for="message">Message:</label>
            <textarea id="message" name="message"></textarea>
            <button type="submit">Book Appointment</button>
        </form>
    </div>

</body>

</html>
<?php
$conn->close();
?>