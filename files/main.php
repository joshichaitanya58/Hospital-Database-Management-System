<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "hospital_data");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Schema Migration: Add payment_method to bills if it doesn't exist ---
$check_column_sql = "SHOW COLUMNS FROM `bills` LIKE 'payment_method'";
$result = $conn->query($check_column_sql);
if ($result && $result->num_rows == 0) {
    // Column does not exist, so add it
    $alter_table_sql = "ALTER TABLE `bills` ADD `payment_method` VARCHAR(50) NULL DEFAULT NULL AFTER `paid_at`";
    $conn->query($alter_table_sql); // This will fix the table structure.
}
// --- Schema Migration: Add doctor_id to records if it doesn't exist ---
$check_column_sql_records = "SHOW COLUMNS FROM `records` LIKE 'doctor_id'";
$result_records = $conn->query($check_column_sql_records);
if ($result_records && $result_records->num_rows == 0) {
    // Column does not exist, so add it
    $alter_table_sql_records = "ALTER TABLE `records` ADD `doctor_id` INT NOT NULL AFTER `patient_id`";
    $conn->query($alter_table_sql_records);
}
// --- End Schema Migration ---

// Check if patient is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header("Location: patientsLogin.php");
    exit();
}

$patient_id = $_SESSION['user_id'];
$patient_name = $_SESSION['user_name'] ?? 'Patient';
$payment_message = '';

// Handle Bill Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_bill'])) {
    $bill_id_to_pay = $_POST['bill_id'];
    $payment_method = $_POST['payment_method'] ?? 'Unknown';
    
    // Update bill status to 'paid' only if it's currently 'unpaid'
    $pay_stmt = $conn->prepare("UPDATE bills SET status = 'paid', paid_at = CURRENT_TIMESTAMP, payment_method = ? WHERE id = ? AND patient_id = ? AND status = 'unpaid'");
    $pay_stmt->bind_param("sii", $payment_method, $bill_id_to_pay, $patient_id);
    if ($pay_stmt->execute()) {
        if ($pay_stmt->affected_rows > 0) {
            $payment_message = '<div class="alert alert-success mt-3">Bill paid successfully!</div>';
        }
    } else {
        $payment_message = '<div class="alert alert-danger mt-3">Error processing payment.</div>';
    }
    $pay_stmt->close();
}

// Create medical_records table if it doesn't exist
$createTableSQL = "
CREATE TABLE IF NOT EXISTS medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    diagnosis TEXT,
    treatment TEXT,
    prescription TEXT,
    record_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createTableSQL)) {
    die("Error creating medical_records table: " . $conn->error);
}

// Create records table if it doesn't exist (for file uploads)
$createRecordsTableSQL = "
CREATE TABLE IF NOT EXISTS records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createRecordsTableSQL)) {
    die("Error creating records table: " . $conn->error);
}

// --- File Upload (Patient only) ---
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['medical_record'])) {
    $doctor_id_for_upload = $_POST['doctor_id'] ?? null;

    if (empty($doctor_id_for_upload)) {
        $upload_message = '<div class="alert alert-danger">Please select a doctor to share the file with.</div>';
    } else {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $file_name = preg_replace("/[^a-zA-Z0-9-_\.]/", "", basename($_FILES["medical_record"]["name"]));
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["medical_record"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO records (patient_id, doctor_id, file_name, file_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $patient_id, $doctor_id_for_upload, $file_name, $target_file);
            $upload_message = $stmt->execute()
                ? '<div class="alert alert-success">File uploaded and shared successfully.</div>'
                : '<div class="alert alert-danger">Database error while saving file record.</div>';
            $stmt->close();
        } else {
            $upload_message = '<div class="alert alert-danger">Error uploading file.</div>';
        }
    }
}

// Fetch doctors for the upload form
$doctors_result = $conn->query("SELECT id, Name FROM doctors_account ORDER BY Name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Health Records</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-4">
  <!-- Patient Upload Section -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title"><i class="bi bi-upload"></i> Upload Medical Records</h5>
      <p>Hello, <strong><?php echo htmlspecialchars($patient_name); ?></strong></p>
      <?php if ($upload_message) echo $upload_message; ?>
      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="doctor_select" class="form-label">Share with Doctor:</label>
          <select class="form-select" name="doctor_id" id="doctor_select" required>
              <option value="" selected disabled>-- Select a Doctor --</option>
              <?php
              if ($doctors_result && $doctors_result->num_rows > 0) {
                  $doctors_result->data_seek(0); 
                  while($doctor = $doctors_result->fetch_assoc()) {
                      echo "<option value='" . htmlspecialchars($doctor['id']) . "'>" . htmlspecialchars($doctor['Name']) . "</option>";
                  }
              }
              ?>
          </select>
        </div>
        <div class="mb-3">
          <input class="form-control" type="file" name="medical_record" required>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Upload</button>
      </form>
    </div>
  </div>

  <!-- Uploaded Files Section -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title"><i class="bi bi-files"></i> Uploaded Files</h5>
      <?php
      $sql = "SELECT * FROM records WHERE patient_id = ? ORDER BY uploaded_at DESC";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $patient_id);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead class="table-dark">
              <tr>
                <th>File Name</th>
                <th>Uploaded At</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                <td><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($row['uploaded_at']))); ?></td>
                <td>
                  <?php if (!empty($row['file_path'])): ?>
                    <a href="<?= htmlspecialchars($row['file_path']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                      <i class="bi bi-eye"></i> View
                    </a>
                    <a href="<?= htmlspecialchars($row['file_path']) ?>" class="btn btn-sm btn-success" download>
                      <i class="bi bi-download"></i> Download
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted">No files uploaded yet.</p>
      <?php endif; ?>
      <?php $stmt->close(); ?>
    </div>
  </div>

  <!-- Billing Section -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title"><i class="bi bi-receipt"></i> My Bills</h5>
      <?php if ($payment_message) echo $payment_message; ?>
      <?php
      $sql = "SELECT b.*, d.Name as doctor_name 
              FROM bills b 
              JOIN doctors_account d ON b.doctor_id = d.id 
              WHERE b.patient_id = ? 
              ORDER BY b.generated_at DESC";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $patient_id);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead class="table-dark">
              <tr>
                <th>Date</th>
                <th>Doctor</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars(date('d M Y', strtotime($row['generated_at']))) ?></td>
                <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td>₹<?= htmlspecialchars(number_format($row['amount'], 2)) ?></td>
                <td><span class="badge bg-<?= $row['status'] == 'paid' ? 'success' : 'warning' ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
                <td>
                  <?php if ($row['status'] == 'unpaid'): ?>
                    <button type="button" class="btn btn-sm btn-success pay-now-btn" data-bs-toggle="modal" data-bs-target="#paymentModal" data-bill-id="<?= $row['id'] ?>" data-bill-amount="<?= htmlspecialchars(number_format($row['amount'], 2)) ?>">
                      Pay Now
                    </button>
                  <?php else: ?>
                    Paid on <?= date('d M Y', strtotime($row['paid_at'])) ?>
                    <a href="receipt.php?bill_id=<?= $row['id'] ?>" class="btn btn-sm btn-info ms-2" target="_blank">View Receipt</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: echo '<p class="text-muted">No bills found.</p>'; endif; $stmt->close(); ?>
    </div>
  </div>

  <!-- Medical History Section -->
  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="card-title"><i class="bi bi-file-medical"></i> Medical History</h5>
      <?php
      // Check if doctors_account table exists
      $checkDoctorsTable = $conn->query("SHOW TABLES LIKE 'doctors_account'");
      if ($checkDoctorsTable->num_rows == 0) {
          echo "<p class='text-muted'>No medical records found. Doctors table not available.</p>";
      } else {
          $sql = "SELECT mr.*, d.Name as doctor_name 
                  FROM medical_records mr
                  JOIN doctors_account d ON mr.doctor_id = d.id 
                  WHERE mr.patient_id = ? 
                  ORDER BY mr.record_date DESC, mr.created_at DESC";
          $stmt = $conn->prepare($sql);
          
          if ($stmt) {
              $stmt->bind_param("i", $patient_id);
              $stmt->execute();
              $result = $stmt->get_result();

              if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                      <tr>
                        <th>Date</th>
                        <th>Doctor</th>
                        <th>Diagnosis</th>
                        <th>Treatment</th>
                        <th>Prescription</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($row['record_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['diagnosis']); ?></td>
                        <td><?php echo htmlspecialchars($row['treatment']); ?></td>
                        <td><?php echo htmlspecialchars($row['prescription']); ?></td>
                      </tr>
                    <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-muted">No medical records found.</p>
              <?php endif;
              
              $stmt->close();
          } else {
              echo "<p class='text-muted'>Error preparing medical records query.</p>";
          }
      }
      ?>
    </div>
  </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentModalLabel">Complete Your Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>You are paying: <strong id="paymentAmount"></strong></p>
        
        <!-- Payment Options Tabs -->
        <ul class="nav nav-tabs" id="paymentTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="upi-tab" data-bs-toggle="tab" data-bs-target="#upi" type="button" role="tab" aria-controls="upi" aria-selected="true">UPI / QR Code</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="card-tab" data-bs-toggle="tab" data-bs-target="#card" type="button" role="tab" aria-controls="card" aria-selected="false">Credit/Debit Card</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="netbanking-tab" data-bs-toggle="tab" data-bs-target="#netbanking" type="button" role="tab" aria-controls="netbanking" aria-selected="false">Net Banking</button>
          </li>
        </ul>
        
        <div class="tab-content" id="paymentTabsContent">
          <!-- UPI Tab -->
          <div class="tab-pane fade show active p-3 text-center" id="upi" role="tabpanel" aria-labelledby="upi-tab">
            <h6>Scan the QR Code to Pay</h6>
            <img src="qr_placeholder.png" alt="QR Code for UPI Payment" class="img-fluid my-3" style="max-width: 200px;">
            <p>After completing the payment using your UPI app, click the button below.</p>
          </div>
          <!-- Card Tab -->
          <div class="tab-pane fade p-3" id="card" role="tabpanel" aria-labelledby="card-tab">
            <form id="card-payment-form">
              <div class="mb-3">
                <label for="cardNumber" class="form-label">Card Number</label>
                <input type="text" class="form-control" id="cardNumber" placeholder="XXXX XXXX XXXX XXXX" required>
              </div>
              <div class="mb-3">
                <label for="cardName" class="form-label">Name on Card</label>
                <input type="text" class="form-control" id="cardName" placeholder="John Doe" required>
              </div>
              <div class="row">
                <div class="col-md-7">
                  <div class="mb-3">
                    <label for="cardExpiry" class="form-label">Expiry Date</label>
                    <input type="text" class="form-control" id="cardExpiry" placeholder="MM/YY" required>
                  </div>
                </div>
                <div class="col-md-5">
                  <div class="mb-3">
                    <label for="cardCvv" class="form-label">CVV</label>
                    <input type="text" class="form-control" id="cardCvv" placeholder="XXX" required>
                  </div>
                </div>
              </div>
              <button type="button" id="pay-by-card-btn" class="btn btn-primary w-100 mt-3">Pay Now</button>
              <p class="small text-muted mt-2">For this demo, no actual payment will be processed.</p>
            </form>
          </div>
          <!-- Net Banking Tab -->
          <div class="tab-pane fade p-3" id="netbanking" role="tabpanel" aria-labelledby="netbanking-tab">
            <form id="netbanking-payment-form">
                <div class="mb-3">
                    <label for="bankSelect" class="form-label">Select Your Bank</label>
                    <select class="form-select" id="bankSelect" required>
                        <option value="" selected disabled>Choose...</option>
                        <option value="1">State Bank of India</option>
                        <option value="2">HDFC Bank</option>
                        <option value="3">ICICI Bank</option>
                        <option value="4">Axis Bank</option>
                        <option value="5">Kotak Mahindra Bank</option>
                        <option value="6">Punjab National Bank</option>
                    </select>
                </div>
                <button type="button" id="pay-by-netbanking-btn" class="btn btn-primary w-100 mt-3">Proceed to Pay</button>
                <p class="small text-muted mt-2">You would be redirected to your bank's portal. For this demo, this will just confirm the payment.</p>
            </form>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST" id="payment-confirmation-form" class="d-inline">
            <input type="hidden" name="bill_id" id="modal_bill_id">
            <input type="hidden" name="payment_method" id="modal_payment_method" value="UPI/QR Code">
            <button type="submit" name="pay_bill" class="btn btn-primary">I have completed the payment</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var paymentModal = document.getElementById('paymentModal');
    paymentModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var billId = button.getAttribute('data-bill-id');
        var billAmount = button.getAttribute('data-bill-amount');
        document.getElementById('modal_bill_id').value = billId;
        document.getElementById('paymentAmount').innerHTML = '₹' + billAmount;
        // Reset to default tab and payment method when modal opens
        var defaultTab = new bootstrap.Tab(document.getElementById('upi-tab'));
        defaultTab.show();
        document.getElementById('modal_payment_method').value = 'UPI/QR Code';
    });

    // Update hidden payment method field on tab change
    document.querySelectorAll('#paymentTabs button[data-bs-toggle="tab"]').forEach(function (tabEl) {
        tabEl.addEventListener('shown.bs.tab', function (event) {
            let method = 'UPI/QR Code'; // Default
            if (event.target.id === 'card-tab') {
                method = 'Credit/Debit Card';
            } else if (event.target.id === 'netbanking-tab') {
                method = 'Net Banking';
            }
            document.getElementById('modal_payment_method').value = method;
        });
    });

    // Handle Card Payment Button click
    document.getElementById('pay-by-card-btn').addEventListener('click', function() {
        const cardForm = document.getElementById('card-payment-form');
        if (cardForm.checkValidity()) {
            document.getElementById('payment-confirmation-form').submit();
        } else {
            cardForm.reportValidity();
        }
    });

    // Handle Net Banking Payment Button click
    document.getElementById('pay-by-netbanking-btn').addEventListener('click', function() {
        const netbankingForm = document.getElementById('netbanking-payment-form');
        if (netbankingForm.checkValidity()) {
            document.getElementById('payment-confirmation-form').submit();
        } else {
            netbankingForm.reportValidity();
        }
    });
});
</script>
</body>
</html>