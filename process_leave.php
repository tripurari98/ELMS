<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

if (!isset($_GET['id'])) {
    header("Location: leave_list.php");
    exit;
}

$leave_id = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT l.*, e.employee_first_name, e.employee_last_name, lt.leave_type_name
    FROM elms_leave l
    JOIN elms_employee e ON l.employee_id = e.employee_id
    JOIN elms_leave_type lt ON l.leave_type = lt.leave_type_id
    WHERE l.leave_id = :leave_id
");
$stmt->execute([':leave_id' => $leave_id]);

$leave = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$leave) {
    header("Location: leave_list.php");
    exit;
}

if ($leave['leave_status'] == 'Pending') {
    // Mark leave as "Admin Read"
    $pdo->prepare("UPDATE elms_leave SET leave_status = 'Admin Read' WHERE leave_id = :leave_id")
        ->execute([':leave_id' => $leave_id]);
}

if (isset($_POST['process_leave'])) {
    $error = [];
    $leave_status = $_POST['leave_status'];
    $admin_remark = $_POST['admin_remark'];
    $admin_remark_date = date('Y-m-d H:i:s');

    // Validation
    if (empty($leave_status)) {
        $error[] = "Leave status is required.";
    }
    if (empty($admin_remark)) {
        $error[] = "Admin comment is required.";
    }

    // If no errors, update leave data
    if (empty($error)) {
        $stmt = $pdo->prepare("
            UPDATE elms_leave 
            SET leave_status = :leave_status, 
                leave_admin_remark = :admin_remark, 
                leave_admin_remark_date = :admin_remark_date
            WHERE leave_id = :leave_id
        ");

        $stmt->execute([
            ':leave_status' => $leave_status,
            ':admin_remark' => $admin_remark,
            ':admin_remark_date' => $admin_remark_date,
            ':leave_id' => $leave_id
        ]);

        if ($stmt->rowCount()) {
            $_SESSION['success'] = "Leave request processed successfully!";
            header("Location: leave_list.php");
            exit;
        } else {
            $error[] = "Failed to process leave request. Please try again.";
        }
    }
}

include('header.php');

?>

<h1 class="mt-4">Process Leave Application</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="employee_dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="leave_list.php">Applied Leave List</a></li>
    <li class="breadcrumb-item active">Process Leave Application</li>
</ol>

<div class="row">
    <div class="col-md-6">
        <?php
        if(!empty($errors)){
        	echo getMsg('danger', '<ul class="list-unstyle"><li>'.implode("</li><li>", $errors).'</li></ul>');
        }
        ?>
        <div class="card">
            <div class="card-header">Leave Details</div>
            <div class="card-body">
            	<h2>Leave Details</h2>
                <p><strong>Employee Name:</strong> <?= htmlspecialchars($leaveDetails['employee_name']); ?></p>
                <p><strong>Leave Type:</strong> <?= htmlspecialchars($leaveDetails['leave_type_name']); ?></p>
                <p><strong>Start Date:</strong> <?= htmlspecialchars($leaveDetails['leave_start_date']); ?></p>
                <p><strong>End Date:</strong> <?= htmlspecialchars($leaveDetails['leave_end_date']); ?></p>
                <p><strong>Description:</strong> <?= $leaveDetails['leave_description']; ?></p>
                <p><strong>Status:</strong> <?= $badge[$leaveDetails['leave_status']] ?? '<span class="badge bg-secondary">Unknown</span>'; ?></p>
                <hr>

	            <form action="process_leave.php?id=<?php echo $leave['leave_id']; ?>" method="POST">
	                <div class="mb-3">
	                    <label for="leave_status" class="form-label">Leave Status</label>
	                    <select class="form-select" id="leave_status" name="leave_status" required>
	                        <option value="">Select Status</option>
	                        <option value="Approve" <?= ($leave['leave_status'] == 'Approve') ? 'selected' : ''; ?>>Approve</option>
	                        <option value="Reject" <?= ($leave['leave_status'] == 'Reject') ? 'selected' : ''; ?>>Reject</option>
	                    </select>
	                </div>
	                <div class="mb-3">
	                    <label for="admin_remark" class="form-label">Admin Comment</label>
	                    <textarea class="form-control" id="admin_remark" name="admin_remark" rows="4"><?= htmlspecialchars($leave['leave_admin_remark']); ?></textarea>
	                </div>
	                <button type="submit" class="btn btn-primary" name="process_leave">Submit</button>
	            </form>
            </div>
        </div>
    </div>
</div>

<?php
include('footer.php');
?>

<script>
    CKEDITOR.replace('admin_remark');
</script>