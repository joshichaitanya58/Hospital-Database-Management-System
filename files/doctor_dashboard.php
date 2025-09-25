<?php
// doctor_dashboard.php
session_start();
ob_start();
// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctorsLogin.php");
    exit();
}

$doctorId = $_SESSION['doctor_id'];
$doctorName = $_SESSION['doctor_name'] ?? 'Doctor';

// DB connection
$conn = new mysqli("localhost", "root", "", "hospital_data");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// --- Schema Migration: Add payment_method to bills if it doesn't exist ---
$check_column_sql = "SHOW COLUMNS FROM `bills` LIKE 'payment_method'";
$result = $conn->query($check_column_sql);
if ($result && $result->num_rows == 0) {
    // Column does not exist, so add it
    $conn->query("ALTER TABLE `bills` ADD `payment_method` VARCHAR(50) NULL DEFAULT NULL AFTER `paid_at`");
}
// --- End Schema Migration ---

// Create bills table if it doesn't exist
$conn->query("
  CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL DEFAULT NULL,
    payment_method VARCHAR(50) NULL DEFAULT NULL,
    FOREIGN KEY (patient_id) REFERENCES patients_account(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors_account(id) ON DELETE CASCADE
  )");

// Ensure completed_appointments table exists
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

// Handle medical record update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Create new medical record
    if (isset($_POST['create_record'])) {
        $patient_id = $_POST['patient_id'];
        $record_date = $_POST['record_date'];
        $diagnosis = $_POST['diagnosis'];
        $treatment = $_POST['treatment'];
        $prescription = $_POST['prescription'];

        $sql = "INSERT INTO medical_records (patient_id, doctor_id, record_date, diagnosis, treatment, prescription) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissss", $patient_id, $doctorId, $record_date, $diagnosis, $treatment, $prescription);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']); exit();
    }
    // Generate new bill
    if (isset($_POST['generate_bill'])) {
        $patient_id = $_POST['patient_id'];
        $description = $_POST['description'];
        $amount = $_POST['amount'];

        $sql = "INSERT INTO bills (patient_id, doctor_id, description, amount) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisd", $patient_id, $doctorId, $description, $amount);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']); exit();
    }
    // Update medical record
    if (isset($_POST['update_record'])) {
        $record_id = $_POST['record_id'];
        $diagnosis = $_POST['diagnosis'];
        $treatment = $_POST['treatment'];
        $prescription = $_POST['prescription'];
        
        $sql = "UPDATE medical_records SET diagnosis=?, treatment=?, prescription=? WHERE id=? AND doctor_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $diagnosis, $treatment, $prescription, $record_id, $doctorId);
        $stmt->execute();
        $stmt->close();        
        header("Location: " . $_SERVER['PHP_SELF']); exit();
    }
    // Delete medical record
    elseif (isset($_POST['delete_record'])) {
        $record_id = $_POST['record_id'];
        
        $sql = "DELETE FROM medical_records WHERE id=? AND doctor_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $record_id, $doctorId);
        $stmt->execute();
        $stmt->close();        
        header("Location: " . $_SERVER['PHP_SELF']); exit();
    }
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .highlight-row { background-color: #fff8c6 !important; } 
  </style>
</head>
<body class="bg-light">

<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Welcome, Dr. <?= htmlspecialchars($doctorName) ?></h3>
    <a href="doctorsLogin.php" class="btn btn-danger">Logout</a>
  </div>

  <!-- ================= Create New Records / Bills ================= -->
  <div class="row">
    <!-- Create Medical Record -->
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-journal-plus"></i> Create New Medical Record</h5>
          <form method="POST">
            <div class="mb-2">
              <label class="form-label">Patient:</label>
              <select name="patient_id" class="form-select" required>
                <option value="">Select Patient</option>
                <?php
                  $patients = $conn->query("SELECT id, Name FROM patients_account ORDER BY Name");
                  while($p = $patients->fetch_assoc()) {
                    echo "<option value='{$p['id']}'>".htmlspecialchars($p['Name'])."</option>";
                  }
                ?>
              </select>
            </div>
            <div class="mb-2"><label>Record Date:</label><input type="date" name="record_date" class="form-control" required></div>
            <div class="mb-2"><label>Diagnosis:</label><textarea name="diagnosis" class="form-control" rows="2" required></textarea></div>
            <div class="mb-2"><label>Treatment:</label><textarea name="treatment" class="form-control" rows="2"></textarea></div>
            <div class="mb-2"><label>Prescription:</label><textarea name="prescription" class="form-control" rows="2"></textarea></div>
            <button type="submit" name="create_record" class="btn btn-primary w-100">Create Record</button>
          </form>
        </div>
      </div>
    </div>
    <!-- Generate Bill -->
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-receipt-cutoff"></i> Generate Bill</h5>
          <form method="POST">
            <div class="mb-2">
              <label class="form-label">Patient:</label>
              <select name="patient_id" class="form-select" required>
                <option value="">Select Patient</option>
                <?php
                  $patients->data_seek(0); // Reset pointer
                  while($p = $patients->fetch_assoc()) {
                    echo "<option value='{$p['id']}'>".htmlspecialchars($p['Name'])."</option>";
                  }
                ?>
              </select>
            </div>
            <div class="mb-2"><label>Description:</label><textarea name="description" class="form-control" rows="2" required placeholder="e.g., Consultation Fee"></textarea></div>
            <div class="mb-2"><label>Amount (₹):</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
            <button type="submit" name="generate_bill" class="btn btn-success w-100 mt-4">Generate Bill</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= Patient Medical Records ================= -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title"><i class="bi bi-file-medical"></i> Patient Medical Records</h5>
      <p>View medical records for your patients only.</p>
      
      <?php
      // Get medical records only for this doctor's patients
      $sql = "SELECT mr.*, p.Name AS patient_name, p.Email, p.Mobile_Number 
              FROM medical_records mr
              JOIN patients_account p ON mr.patient_id = p.id 
              WHERE mr.doctor_id = ?
              ORDER BY mr.record_date DESC";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $doctorId);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead class="table-dark">
              <tr>
                <th>Patient Name</th>
                <th>Date</th>
                <th>Diagnosis</th>
                <th>Treatment</th>
                <th>Prescription</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php while ($rec = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($rec['patient_name'] ?? '') ?></td>
                <td><?= isset($rec['record_date']) ? htmlspecialchars($rec['record_date']) : '' ?></td>
                <td><?= htmlspecialchars($rec['diagnosis'] ?? '') ?></td>
                <td><?= htmlspecialchars($rec['treatment'] ?? '') ?></td>
                <td><?= htmlspecialchars($rec['prescription'] ?? '') ?></td>
                <td>
                  <button class="btn btn-sm btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#recordForm<?= $rec['id'] ?>">
                    Edit
                  </button>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="record_id" value="<?= $rec['id'] ?>">
                    <button type="submit" name="delete_record" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">Delete</button>
                  </form>
                  
                  <!-- Medical Record Update Form -->
                  <div class="collapse mt-2" id="recordForm<?= $rec['id'] ?>">
                    <form method="POST">
                      <input type="hidden" name="record_id" value="<?= $rec['id'] ?>">
                      <div class="mb-2">
                        <label class="form-label">Diagnosis:</label>
                        <textarea name="diagnosis" class="form-control" rows="2"><?= htmlspecialchars($rec['diagnosis'] ?? '') ?></textarea>
                      </div>
                      <div class="mb-2">
                        <label class="form-label">Treatment:</label>
                        <textarea name="treatment" class="form-control" rows="2"><?= htmlspecialchars($rec['treatment'] ?? '') ?></textarea>
                      </div>
                      <div class="mb-2">
                        <label class="form-label">Prescription:</label>
                        <textarea name="prescription" class="form-control" rows="2"><?= htmlspecialchars($rec['prescription'] ?? '') ?></textarea>
                      </div>
                      <button type="submit" name="update_record" class="btn btn-primary btn-sm">Update Record</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-center text-muted">No medical records found for your patients.</p>
      <?php endif; ?>
      <?php $stmt->close(); ?>
    </div>
  </div>

  <!-- ================= Generated Bills ================= -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title"><i class="bi bi-cash-stack"></i> Generated Bills</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="table-dark">
            <tr>
              <th>Patient Name</th><th>Date</th><th>Description</th><th>Amount</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sql = "SELECT b.*, p.Name as patient_name FROM bills b JOIN patients_account p ON b.patient_id = p.id WHERE b.doctor_id = ? ORDER BY b.generated_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $doctorId);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res && $res->num_rows > 0):
              while ($row = $res->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['patient_name']) ?></td>
                  <td><?= htmlspecialchars(date('d M Y', strtotime($row['generated_at']))) ?></td>
                  <td><?= htmlspecialchars($row['description']) ?></td>
                  <td>₹<?= htmlspecialchars(number_format($row['amount'], 2)) ?></td>
                  <td><span class="badge bg-<?= $row['status'] == 'paid' ? 'success' : 'warning' ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
                  <td>
                    <?php if ($row['status'] == 'paid'): ?>
                      <a href="receipt.php?bill_id=<?= $row['id'] ?>" class="btn btn-sm btn-info" target="_blank">View Receipt</a>
                    <?php endif; ?>
                  </td>                </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="5" class="text-center text-muted">No bills generated yet.</td></tr>
            <?php endif; $stmt->close(); ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ================= Shared Patient Files ================= -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title"><i class="bi bi-folder-symlink"></i> Shared Patient Files</h5>
      <p>Files uploaded by patients and shared with you.</p>
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="table-dark">
            <tr>
              <th>Patient Name</th>
              <th>File Name</th>
              <th>Uploaded At</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sql_files = "SELECT r.file_name, r.file_path, r.uploaded_at, p.Name as patient_name 
                          FROM records r 
                          JOIN patients_account p ON r.patient_id = p.id 
                          WHERE r.doctor_id = ? 
                          ORDER BY r.uploaded_at DESC";
            $stmt_files = $conn->prepare($sql_files);
            $stmt_files->bind_param("i", $doctorId);
            $stmt_files->execute();
            $files_result = $stmt_files->get_result();

            if ($files_result && $files_result->num_rows > 0):
              while ($file_row = $files_result->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($file_row['patient_name']) ?></td>
                  <td><?= htmlspecialchars($file_row['file_name']) ?></td>
                  <td><?= htmlspecialchars(date('d M Y, h:i A', strtotime($file_row['uploaded_at']))) ?></td>
                  <td>
                    <a href="<?= htmlspecialchars($file_row['file_path']) ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="bi bi-eye"></i> View</a>
                    <a href="<?= htmlspecialchars($file_row['file_path']) ?>" class="btn btn-sm btn-success" download><i class="bi bi-download"></i> Download</a>
                  </td>
                </tr>
              <?php endwhile;
            else: ?>
              <tr><td colspan="4" class="text-center text-muted">No files have been shared with you yet.</td></tr>
            <?php endif;
            $stmt_files->close(); ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ================= Upcoming Appointments ================= -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title"><i class="bi bi-calendar-check"></i> Upcoming Appointments</h5>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead class="table-dark">
            <tr>
              <th>Patient Name</th><th>Email</th><th>Phone</th>
              <th>Date & Time</th><th>Message</th><th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Only show appointments for this doctor using doctor_id
            $stmt = $conn->prepare("SELECT a.*, d.Name as doctor_name FROM appointments a JOIN doctors_account d ON a.doctor_id = d.id WHERE a.doctor_id = ? ORDER BY a.Date, a.Time");
            $stmt->bind_param("i", $doctorId);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res && $res->num_rows > 0):
              while ($row = $res->fetch_assoc()): ?>
                <tr class="highlight-row">
                  <td><?= htmlspecialchars($row['Name'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['Email'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['Phone'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['Date'] . ' ' . $row['Time']) ?></td>
                  <td><?= htmlspecialchars($row['Message'] ?? '') ?></td>
                  <td>
                    <form action="complete.php" method="post" class="d-inline">
                      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                      <button type="submit" class="btn btn-success btn-sm">Complete</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile;
            else: ?>
              <tr><td colspan="6" class="text-center text-muted">No upcoming appointments found.</td></tr>
            <?php endif; ?>
            <?php $stmt->close(); ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ================= Completed Appointments ================= -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title"><i class="bi bi-check-circle"></i> Completed Appointments</h5>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead class="table-dark">
            <tr>
              <th>Patient Name</th><th>Email</th><th>Phone</th>
              <th>Appt. Date & Time</th><th>Message</th><th>Completed At</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Only show completed appointments for this doctor using doctor_id
            $stmt = $conn->prepare("SELECT * FROM completed_appointments WHERE doctor_id = ? ORDER BY completed_at DESC");
            $stmt->bind_param("i", $doctorId);
            $stmt->execute();
            $cres = $stmt->get_result();
            
            if ($cres && $cres->num_rows > 0):
              while ($row = $cres->fetch_assoc()): ?>
                <tr class="table-secondary">
                  <td><?= htmlspecialchars($row['Name'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['Email'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['Phone'] ?? '') ?></td>
                  <td><?= htmlspecialchars(($row['Date'] ?? '') . ' ' . ($row['Time'] ?? '')) ?></td>
                  <td><?= htmlspecialchars($row['Message'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['completed_at'] ?? '-') ?></td>
                </tr>
              <?php endwhile;
            else: ?>
              <tr><td colspan="6" class="text-center text-muted">No completed appointments yet.</td></tr>
            <?php endif; ?>
            <?php $stmt->close(); ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php $conn->close(); ?>
</body>
</html>