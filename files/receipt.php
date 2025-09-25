<?php
// receipt.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['doctor_id'])) {
    // Not a patient or a doctor, or not logged in at all
    header("Location: patientsLogin.php"); // Redirect to a generic login or home
    exit();
}

// Check for bill_id
if (!isset($_GET['bill_id']) || !ctype_digit((string)$_GET['bill_id'])) {
    die("Invalid Bill ID.");
}

$bill_id = (int)$_GET['bill_id'];
$user_type = $_SESSION['user_type'] ?? '';

// Database connection
$conn = new mysqli("localhost", "root", "", "hospital_data");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch bill, patient, and doctor details
$sql = "SELECT b.*, 
               p.Name AS patient_name, p.Email AS patient_email, p.Mobile_Number AS patient_phone,
               d.Name AS doctor_name, d.Specialization AS doctor_specialization
        FROM bills b
        JOIN patients_account p ON b.patient_id = p.id
        JOIN doctors_account d ON b.doctor_id = d.id
        WHERE b.id = ? AND b.status = 'paid'
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$result = $stmt->get_result();
$bill = $result->fetch_assoc();
$stmt->close();

if (!$bill) {
    die("Receipt not found or bill is not paid.");
}

// Security Check: Ensure the logged-in user is associated with this bill
if ($user_type === 'patient' && $_SESSION['user_id'] != $bill['patient_id']) {
    die("Access Denied. You are not authorized to view this receipt.");
}
if ($user_type === 'doctor' && $_SESSION['doctor_id'] != $bill['doctor_id']) {
    die("Access Denied. You are not authorized to view this receipt.");
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - Bill #<?= htmlspecialchars($bill['id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .receipt-container { max-width: 800px; margin: 50px auto; border: 1px solid #dee2e6; padding: 30px; background-color: #fff; box-shadow: 0 0 15px rgba(0,0,0,0.05); }
        .receipt-header { text-align: center; margin-bottom: 40px; }
        .receipt-header h1 { color: #205b80; }
        .receipt-footer { text-align: center; margin-top: 40px; font-size: 0.9em; color: #6c757d; }
        @media print {
            body { background-color: #fff; }
            .receipt-container { margin: 0; border: none; box-shadow: none; max-width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="receipt-header">
        <h1>CareRecords</h1>
        <h4>Payment Receipt</h4>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <strong>Bill ID:</strong> #<?= htmlspecialchars($bill['id']) ?><br>
            <strong>Generated On:</strong> <?= htmlspecialchars(date('d M Y', strtotime($bill['generated_at']))) ?><br>
            <strong>Paid On:</strong> <?= htmlspecialchars(date('d M Y, h:i A', strtotime($bill['paid_at']))) ?><br>
            <strong>Payment Method:</strong> <?= htmlspecialchars($bill['payment_method'] ?? 'N/A') ?>
        </div>
        <div class="col-6 text-end">
            <span class="badge bg-success fs-6">PAID</span>
        </div>
    </div>

    <hr>

    <div class="row my-4">
        <div class="col-md-6">
            <h5>Billed To:</h5>
            <address>
                <strong><?= htmlspecialchars($bill['patient_name']) ?></strong><br>
                Email: <?= htmlspecialchars($bill['patient_email']) ?><br>
                Phone: <?= htmlspecialchars($bill['patient_phone']) ?>
            </address>
        </div>
        <div class="col-md-6 text-md-end">
            <h5>Consulting Doctor:</h5>
            <address>
                <strong>Dr. <?= htmlspecialchars($bill['doctor_name']) ?></strong><br>
                <?= htmlspecialchars($bill['doctor_specialization']) ?><br>
            </address>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($bill['description']) ?></td>
                    <td class="text-end">₹<?= htmlspecialchars(number_format($bill['amount'], 2)) ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td class="text-end">Total</td>
                    <td class="text-end">₹<?= htmlspecialchars(number_format($bill['amount'], 2)) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print Receipt</button>
    </div>

    <div class="receipt-footer">
        <p>Thank you for your payment. We wish you good health.</p>
        <p>&copy; <?= date('Y') ?> CareRecords. All rights reserved.</p>
    </div>
</div>

</body>
</html>