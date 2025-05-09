<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

$leave_type_id = $_GET['id'] ?? '';
$leave_type_name = '';
$leave_type_status = 'Active';
$days_allowed = '';
$message = '';

// Fetch the current leave type data
if (!empty($leave_type_id)) {
    $stmt = $pdo->prepare("SELECT * FROM elms_leave_type WHERE leave_type_id = :leave_type_id");
    $stmt->execute(['leave_type_id' => $leave_type_id]);
    $leave_type = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($leave_type) {
        $leave_type_name = $leave_type['leave_type_name'];
        $leave_type_status = $leave_type['leave_type_status'];
        $days_allowed = $leave_type['days_allowed'];
    } else {
        $message = 'Leave Type Data not found.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_type_name = trim($_POST['leave_type_name']);
    $leave_type_status = trim($_POST['leave_type_status']);
    $days_allowed = trim($_POST['days_allowed']);
    $leave_type_id = $_POST['leave_type_id'];
    // Validate inputs
    if (empty($leave_type_name)) {
        $message = 'Leave Type name is required.';
    } else {
        // Check if leave type name is already exists or not
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM elms_leave_type WHERE leave_type_name = :leave_type_name AND leave_type_id != :leave_type_id");
        $stmt->execute([
            'leave_type_name'   => $leave_type_name,
            'leave_type_id'     => $leave_type_id
        ]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $message = 'Leave Type with this name already exists.';
        } else {
            // Update the database
            try {
                $stmt = $pdo->prepare("UPDATE elms_leave_type SET leave_type_name = :leave_type_name, leave_type_status = :leave_type_status, days_allowed = :days_allowed WHERE leave_type_id = :leave_type_id");
                $stmt->execute([
                    'leave_type_name'   => $leave_type_name,
                    'leave_type_status' => $leave_type_status,
                    'leave_type_id'     => $leave_type_id,
                    'days_allowed'      => $days_allowed
                ]);
                header('location:leave_type.php');
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

include('header.php');
?>

<h1 class="mt-4">Edit Leave Type</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="leave_type.php">Leave Type Management</a></li>
    <li class="breadcrumb-item active">Edit Leave Type</li>
</ol>

<div class="row">
    <div class="col-md-4">
        <?php
        if(isset($error) && $error !== ''){
            echo getMsg('alert', $error);
        }
        ?>
        <div class="card">
            <div class="card-header">Edit Leave Type</div>
            <div class="card-body">
            <form method="post" action="edit_leave_type.php?id=<?php echo htmlspecialchars($leave_type_id); ?>">
                <div class="mb-3">
                    <label for="leave_type_name">Leave Type Name</label>
                    <input type="text" id="leave_type_name" name="leave_type_name" class="form-control" value="<?php echo htmlspecialchars($leave_type_name); ?>">
                </div>
                <div class="mb-3">
                    <label for="days_allowed">Allowed Leave Day Per Year</label>
                    <input type="number" id="days_allowed" name="days_allowed" class="form-control" required value="<?php echo htmlspecialchars($days_allowed); ?>" />
                </div>
                <div class="mb-3">
                    <label for="leave_type_status">Leave Type Status</label>
                    <select id="leave_type_status" name="leave_type_status" class="form-select">
                        <option value="Active" <?php if ($leave_type_status == 'Active') echo 'selected'; ?>>Active</option>
                        <option value="Inactive" <?php if ($leave_type_status == 'Inactive') echo 'selected'; ?>>Inactive</option>
                    </select>
                </div>
                <input type="hidden" name="leave_type_id" value="<?php echo htmlspecialchars($leave_type_id); ?>">
                <input type="submit" value="Update Leave Type" class="btn btn-primary">
            </form>
        </div>
    </div>
</div>

<?php
include('footer.php');
?>