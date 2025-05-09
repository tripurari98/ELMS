<?php 

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminOrUserLogin();

$notification_id = '';

if (!isset($_GET['id'])) {
    header("Location: leave_list.php");
    exit();
}

if(isset($_GET['notification_id'])){
    $notification_id = $_GET['notification_id'];
}

$leave_id = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT 
        elms_leave.*,
        CONCAT(elms_employee.employee_first_name, ' ', elms_employee.employee_last_name) AS employee_name,
        elms_leave_type.leave_type_name
    FROM elms_leave
    INNER JOIN elms_employee ON elms_leave.employee_id = elms_employee.employee_id
    INNER JOIN elms_leave_type ON elms_leave.leave_type = elms_leave_type.leave_type_id
    WHERE elms_leave.leave_id = :leave_id
");

$stmt->execute([':leave_id' => $leave_id]);
$leaveDetails = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$leaveDetails) {
    echo "Leave details not found!";
    exit();
}

if(isset($_SESSION['admin_id'])){
    if ($leaveDetails['leave_status'] == 'Pending') {
        // Mark leave as "Admin Read"
        $pdo->prepare("UPDATE elms_leave SET leave_status = 'Admin Read' WHERE leave_id = :leave_id")
            ->execute([':leave_id' => $leave_id]);
        $leaveDetails['leave_status'] = 'Admin Read';

        if($notification_id !== ''){
            $mark_as_read = $pdo->prepare("
                UPDATE elms_notifications 
                SET notification_status = 'Read' 
                WHERE notification_id = :notification_id
            ");
            $mark_as_read->execute([':notification_id' => $notification_id]);
        }
    }
}

if(isset($_SESSION['employee_id'])){
    if($notification_id !== ''){
        $mark_as_read = $pdo->prepare("
            UPDATE elms_notifications 
            SET notification_status = 'Read' 
            WHERE notification_id = :notification_id
        ");
        $mark_as_read->execute([':notification_id' => $notification_id]);
    }
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
        // Start transaction to ensure database consistency
        $pdo->beginTransaction();
        
        try {
            // Update leave status
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

            // Create and send notification to employee
            $employee_notification_message = "Your leave request has been $leave_status.";

            $insert_employee_notification = $pdo->prepare("
                INSERT INTO elms_notifications (recipient_id, recipient_role, notification_message, leave_id) 
                VALUES (:recipient_id, 'Employee', :notification_message, :leave_id)
            ");

            $insert_employee_notification->execute([
                ':recipient_id'         =>  $leaveDetails['employee_id'],
                ':notification_message' =>  $employee_notification_message,
                ':leave_id'             =>  $leave_id
            ]);

            // Calculate leave days
            $start = new DateTime($leaveDetails['leave_start_date']);
            $end = new DateTime($leaveDetails['leave_end_date']);
            $interval = $start->diff($end);
            $requested_days = $interval->days + 1;  // Adding 1 to include both start and end days

            // Only update leave balance if status is 'Approve'
            if ($leave_status === 'Approve') {
                $update_balance = $pdo->prepare("
                    UPDATE elms_leave_balance 
                    SET leave_balance = leave_balance - :days 
                    WHERE employee_id = :employee_id 
                    AND leave_type_id = :leave_type_id
                ");
                
                $update_balance->execute([
                    ':days'             => $requested_days,
                    ':employee_id'      => $leaveDetails['employee_id'],
                    ':leave_type_id'    => $leaveDetails['leave_type']
                ]);
                
                if ($update_balance->rowCount() === 0) {
                    // If no rows affected, could be an issue with the leave balance
                    throw new Exception("Failed to update leave balance. The employee may not have sufficient balance.");
                }
            }
            
            // Commit the transaction if everything succeeds
            $pdo->commit();
            
            $_SESSION['success'] = "Leave request processed successfully!";
            header("Location: leave_list.php");
            exit;
        } catch (Exception $e) {
            // Roll back the transaction if anything fails
            $pdo->rollBack();
            $error[] = "Error: " . $e->getMessage();
        }
    }
}

$badge = [
    'Pending'       => '<span class="badge bg-primary">Pending</span>',
    'Admin Read'    => '<span class="badge bg-info">Admin Read</span>',
    'Approve'       => '<span class="badge bg-success">Approve</span>',
    'Reject'        => '<span class="badge bg-danger">Reject</span>'
];

include('header.php');

?>

<h1 class="mt-4">Leave Details</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="employee_dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="leave_list.php">Applied Leave List</a></li>
    <li class="breadcrumb-item active">Leave Details</li>
</ol>

<div class="row">
    <div class="col-md-6">
        <?php
        if(!empty($error)){
        	echo getMsg('danger', '<ul class="list-unstyle"><li>'.implode("</li><li>", $error).'</li></ul>');
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
                <?php 
                if(isset($_SESSION['employee_id'])){
                ?>
                <p><strong>Admin Remark Date:</strong> <?= (!is_null($leaveDetails['leave_admin_remark_date'])) ? $leaveDetails['leave_admin_remark_date'] : 'NA'; ?></p>
                <p><strong>Admin Remark:</strong> <?= (!is_null($leaveDetails['leave_admin_remark'])) ? $leaveDetails['leave_admin_remark'] : 'NA'; ?></p>
                <?php
                }
                if(isset($_SESSION['admin_id'])){
                ?>
                <form action="view_leave_details.php?id=<?php echo $leaveDetails['leave_id']; ?>" method="POST">
                    <div class="mb-3">
                        <label for="leave_status" class="form-label">Leave Status</label>
                        <select class="form-select" id="leave_status" name="leave_status" required>
                            <option value="">Select Status</option>
                            <option value="Approve" <?= ($leaveDetails['leave_status'] == 'Approve') ? 'selected' : ''; ?>>Approve</option>
                            <option value="Reject" <?= ($leaveDetails['leave_status'] == 'Reject') ? 'selected' : ''; ?>>Reject</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="admin_remark" class="form-label">Admin Comment</label>
                        <textarea class="form-control" id="admin_remark" name="admin_remark" rows="4"><?= htmlspecialchars($leaveDetails['leave_admin_remark']); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" name="process_leave">Submit</button>
                </form>
                <!-- Include CKEditor -->
                <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
                <script>
                    // Initialize CKEditor
                    CKEDITOR.replace('admin_remark', {
                        height: 200,
                    });
                </script>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
<?php
include('footer.php');
?>