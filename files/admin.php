<?php
// admin.php - All-in-one Admin Panel
session_start();

// ==================================================================
// CONFIGURATION
// ==================================================================

// Hardcoded admin credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'password123');

// Whitelist of tables that can be managed
$allowed_tables = [
    'patients_account', 'doctors_account', 'appointments',
    'completed_appointments', 'medical_records', 'bills',
    'records', 'contact_messages'
];

// ==================================================================
// DATABASE CONNECTION
// ==================================================================
$conn = new mysqli("localhost", "root", "", "hospital_data");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// ==================================================================
// ACTION ROUTING & PROCESSING (No HTML output here)
// ==================================================================

$action = $_GET['action'] ?? $_POST['action'] ?? 'default';

// --- LOGOUT ACTION ---
if ($action === 'logout') {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
    header('Location: admin.php');
    exit();
}

// --- LOGIN ACTION (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $_SESSION['admin_message'] = "Invalid username or password.";
        header('Location: admin.php');
        exit;
    }
}

// --- SECURITY GATE: All actions below require login ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // If not logged in, the only thing to do is show the login form.
    // This will be handled in the HTML VIEW section.
} else {
    // --- DELETE ACTION (POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
        $table = $_POST['table'] ?? '';
        $id = $_POST['id'] ?? '';
        if (!empty($id) && in_array($table, $allowed_tables)) {
            $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "Record #$id deleted successfully from '$table'.";
            } else {
                $_SESSION['admin_message'] = "Error deleting record: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['admin_message'] = "Invalid delete request.";
        }
        header('Location: admin.php');
        exit;
    }

    // --- UPDATE ACTION (POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
        $table = $_POST['table'] ?? '';
        $id = $_POST['id'] ?? '';

        if (empty($id) || !in_array($table, $allowed_tables)) {
            $_SESSION['admin_message'] = "Error: Invalid request.";
        } else {
            $fields = [];
            $params = [];
            $types = '';

            foreach ($_POST as $key => $value) {
                if ($key === 'action' || $key === 'table' || $key === 'id') continue;

                if ($key === 'Password' && !empty($value)) {
                    $fields[] = "`Password` = ?";
                    $params[] = password_hash($value, PASSWORD_DEFAULT);
                    $types .= 's';
                } elseif ($key !== 'Password') {
                    $fields[] = "`$key` = ?";
                    $params[] = ($value === '') ? null : $value;
                    $types .= 's';
                }
            }

            if (!empty($fields)) {
                $sql = "UPDATE `$table` SET " . implode(', ', $fields) . " WHERE id = ?";
                $params[] = $id;
                $types .= 'i';

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    $_SESSION['admin_message'] = "Record #$id updated successfully in '$table'.";
                } else {
                    $_SESSION['admin_message'] = "Error updating record: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['admin_message'] = "No fields were changed.";
            }
        }
        header('Location: admin.php');
        exit;
    }

    // --- DOWNLOAD CSV ACTION ---
    if ($action === 'download_csv') {
        $table_name = $_GET['table'] ?? '';

        if (empty($table_name) || !in_array($table_name, $allowed_tables)) {
            die("Invalid or not-allowed table specified.");
        }

        $result = $conn->query("SELECT * FROM " . $conn->real_escape_string($table_name));
        if (!$result) {
            die("Could not retrieve data from table: " . $conn->error);
        }

        $filename = $table_name . "_" . date('Y-m-d') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');

        if ($result->num_rows > 0) {
            $first_row = $result->fetch_assoc();
            fputcsv($output, array_keys($first_row));
            $result->data_seek(0); // Rewind to write all rows
        }

        while ($row = $result->fetch_assoc()) {
            // Mask password field for security in CSV
            if (isset($row['Password'])) {
                $row['Password'] = '********';
            }
            fputcsv($output, $row);
        }

        fclose($output);
        $conn->close();
        exit();
    }
}

// ==================================================================
// HTML VIEW ROUTING
// ==================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { background-color: #ffffff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 40px; width: 100%; max-width: 400px; text-align: center; margin: 10vh auto; }
        .login-container h2 { color:#333; margin-bottom:30px; font-weight:600; }
        .login-container form input { width:100%; padding:12px; margin-bottom:15px; border:1px solid #ddd; border-radius:5px; font-size:16px; box-sizing: border-box; }
        .login-container button { background:#4a90e2; color:white; border:none; padding:12px 0; width:100%; border-radius:5px; font-size:16px; cursor:pointer; }
        .login-container a { color: #4a90e2; text-decoration: none; }
        .table-wrapper { max-height: 400px; overflow-y: auto; }
        .edit-form-container { max-width: 800px; }
    </style>
</head>
<body>

<?php
// If not logged in, show the login form and stop.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true):
    $message = $_SESSION['admin_message'] ?? '';
    unset($_SESSION['admin_message']);
?>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if(!empty($message)) echo "<p style='text-align:center;color:red;'>$message</p>"; ?>
        <form method="POST" action="admin.php">
            <input type="hidden" name="action" value="login">
            <input type="text" name="username" placeholder="Username" required value="admin">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p style="margin-top: 20px;"><a href="index.php">Back to Home</a></p>
    </div>

<?php
// If logged in, proceed to show the correct view.
else:
    // --- EDIT FORM VIEW ---
    if ($action === 'edit'):
        $table = $_GET['table'] ?? '';
        $id = $_GET['id'] ?? '';

        if (empty($table) || empty($id) || !in_array($table, $allowed_tables) || !ctype_digit((string)$id)) {
            die("Invalid request. Please go back and try again.");
        }

        $stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$record) die("Record not found.");

        $columns_info = [];
        $columns_result = $conn->query("SHOW COLUMNS FROM `$table`");
        while ($col = $columns_result->fetch_assoc()) $columns_info[$col['Field']] = $col;
?>
    <div class="container my-5 edit-form-container">
        <h2 class="mb-4">Edit Record #<?= htmlspecialchars($id) ?> in <kbd><?= htmlspecialchars($table) ?></kbd></h2>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="admin.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

                    <?php foreach ($record as $key => $value): ?>
                        <div class="mb-3">
                            <label for="<?= htmlspecialchars($key) ?>" class="form-label fw-bold"><?= htmlspecialchars($key) ?></label>
                            <?php
                            $col_info = $columns_info[$key] ?? [];
                            $is_readonly = ($key === 'id' || strpos($col_info['Extra'] ?? '', 'auto_increment') !== false);
                            
                            if ($key === 'Password'): ?>
                                <input type="password" class="form-control" id="<?= htmlspecialchars($key) ?>" name="<?= htmlspecialchars($key) ?>" placeholder="Enter new password to change">
                                <small class="form-text text-muted">Leave blank to keep the current password.</small>
                            <?php elseif (strpos($col_info['Type'], 'text') !== false): ?>
                                <textarea class="form-control" name="<?= htmlspecialchars($key) ?>" rows="4" <?= $is_readonly ? 'readonly' : '' ?>><?= htmlspecialchars($value ?? '') ?></textarea>
                            <?php else: 
                                $input_type = 'text';
                                if (strpos($col_info['Type'], 'date') !== false) $input_type = 'date';
                                elseif (strpos($col_info['Type'], 'time') !== false) $input_type = 'time';
                                elseif (strpos($col_info['Type'], 'int') !== false || strpos($col_info['Type'], 'decimal') !== false) $input_type = 'number';
                            ?>
                                <input type="<?= $input_type ?>" class="form-control" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value ?? '') ?>" <?= $is_readonly ? 'readonly' : '' ?> <?= $input_type === 'number' && strpos($col_info['Type'], 'decimal') !== false ? 'step="0.01"' : '' ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="admin.php" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php
    // --- DEFAULT VIEW: DATA VIEWER ---
    else:
        function fetch_data($conn, $table_name) {
            $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
            if ($check_table->num_rows == 0) return [];
            $result = $conn->query("SELECT * FROM " . $conn->real_escape_string($table_name));
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }
        $message = $_SESSION['admin_message'] ?? '';
        unset($_SESSION['admin_message']);
    ?>
    <div class="container-fluid my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Admin Dashboard</h1>
            <div>
                <a href="index.php" class="btn btn-secondary">Back to Home</a>
                <a href="admin.php?action=logout" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php foreach ($allowed_tables as $table): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-capitalize"><i class="bi bi-table"></i> <?= str_replace('_', ' ', $table) ?></h5>
                <a href="admin.php?action=download_csv&table=<?= htmlspecialchars($table) ?>" class="btn btn-sm btn-success"><i class="bi bi-download"></i> CSV</a>
            </div>
            <div class="card-body">
                <?php
                $data = fetch_data($conn, $table);
                if (!empty($data)):
                    $headers = array_keys($data[0]);
                ?>
                <div class="table-responsive table-wrapper">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <?php foreach ($headers as $header): ?><th><?= htmlspecialchars($header) ?></th><?php endforeach; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                            <tr>
                                <?php foreach ($row as $key => $cell): ?>
                                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($cell ?? '') ?>">
                                        <?= htmlspecialchars(($key === 'Password') ? '********' : $cell) ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-nowrap">
                                    <a href="admin.php?action=edit&table=<?= htmlspecialchars($table) ?>&id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <form action="admin.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">Table '<?= htmlspecialchars($table) ?>' is empty or does not exist.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>