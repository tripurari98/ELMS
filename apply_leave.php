<?php 

require_once 'db_connect.php';
require_once 'auth_function.php';

checkEmployeeLogin();

$employee_id = $_SESSION['employee_id']; // Get the logged-in employee ID

$errors = [];

// Check if employee is active and department is active before showing form
$status_check = $pdo->prepare("
    SELECT 
        e.employee_status,
        d.department_status,
        d.department_name
    FROM 
        elms_employee e
    LEFT JOIN 
        elms_department d ON e.employee_department = d.department_id
    WHERE 
        e.employee_id = :employee_id
");
$status_check->execute([':employee_id' => $employee_id]);
$status_data = $status_check->fetch(PDO::FETCH_ASSOC);

// Early validation messages for inactive statuses
if ($status_data['employee_status'] === 'Inactive') {
    $errors[] = "Your account is currently inactive. You cannot apply for leave.";
}

if ($status_data['department_status'] === 'Inactive') {
    $errors[] = "Your department ({$status_data['department_name']}) is currently inactive. You cannot apply for leave.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize inputs
    $leave_type = isset($_POST['leave_type']) ? sanitizeInput($_POST['leave_type']) : '';
    $leave_start_date = isset($_POST['leave_start_date']) ? sanitizeInput($_POST['leave_start_date']) : '';
    $leave_end_date = isset($_POST['leave_end_date']) ? sanitizeInput($_POST['leave_end_date']) : '';
    $leave_description = isset($_POST['leave_description']) ? trim($_POST['leave_description']) : '';
    $leaveBalance = intval(0);
    $requested_days = intval(0);

    // Check if the selected leave type is active
    if (!empty($leave_type)) {
        $leave_type_check = $pdo->prepare("SELECT leave_type_status, leave_type_name FROM elms_leave_type WHERE leave_type_id = :leave_type_id");
        $leave_type_check->execute([':leave_type_id' => $leave_type]);
        $leave_type_data = $leave_type_check->fetch(PDO::FETCH_ASSOC);
        
        if ($leave_type_data && $leave_type_data['leave_type_status'] === 'Inactive') {
            $errors[] = "The selected leave type ({$leave_type_data['leave_type_name']}) is inactive and cannot be used.";
        }
    }

    //check leave balance
    $stmt = $pdo->prepare("SELECT leave_balance FROM elms_leave_balance WHERE employee_id = :employee_id AND leave_type_id = :leave_type_id");
    $stmt->execute([
        ':employee_id'      => $employee_id,
        ':leave_type_id'    => $leave_type
    ]);

    $leaveBalance = $stmt->fetchColumn();

    $start = new DateTime($leave_start_date);
    $end = new DateTime($leave_end_date);

    // Calculate the difference
    $interval = $start->diff($end);

    $requested_days = $interval->days;

    $requested_days = $requested_days + intval(1);

    if ($leaveBalance < $requested_days) {
        $errors[] = "Insufficient leave balance.";
    }

    // Validation logic
    if (empty($leave_type)) {
        $errors[] = "Leave type is required.";
    }
    if (empty($leave_start_date)) {
        $errors[] = "Start date is required.";
    }
    if (empty($leave_end_date)) {
        $errors[] = "End date is required.";
    }
    if ($leave_start_date > $leave_end_date) {
        $errors[] = "End date cannot be earlier than the start date.";
    }
    if (empty($leave_description)) {
        $errors[] = "Reason for leave is required.";
    }

    // Insert into the database if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO elms_leave (
                    employee_id, leave_type, leave_start_date, 
                    leave_end_date, leave_description, leave_status, leave_apply_date
                ) VALUES (
                    :employee_id, :leave_type, :leave_start_date, 
                    :leave_end_date, :leave_description, 'Pending', NOW()
                )
            ");

            $stmt->execute([
                ':employee_id' 			=> $employee_id,
                ':leave_type' 			=> $leave_type,
                ':leave_start_date' 	=> $leave_start_date,
                ':leave_end_date' 		=> $leave_end_date,
                ':leave_description' 	=> $leave_description
            ]);

            $leave_id = $pdo->lastInsertId();

            // Get admin user IDs
            $get_admins = $pdo->query("SELECT admin_id FROM elms_admin");
            $admins = $get_admins->fetchAll(PDO::FETCH_ASSOC);

            // Insert notifications for each admin
            $notification_message = "New leave request from Employee ID {$_SESSION['employee_id']}";
            foreach ($admins as $admin) {
                $insert_notification = $pdo->prepare("
                    INSERT INTO elms_notifications (recipient_id, recipient_role, notification_message, leave_id) 
                    VALUES (:recipient_id, 'Admin', :notification_message, :leave_id)
                ");
                $insert_notification->execute([
                    ':recipient_id'         =>  $admin['admin_id'],
                    ':notification_message' =>  $notification_message,
                    ':leave_id'             =>  $leave_id
                ]);
            }

            $_SESSION['message'] = "Leave application submitted successfully!";
            header("Location: leave_list.php");
            exit();
        } catch (PDOException $e) {
            $errors[] =  "Error: " . $e->getMessage();
        }
    }
}

include('header.php');

?>

<h1 class="mt-4">Apply for Leave</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="employee_dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Apply for Leave</li>
</ol>

<div class="row">
    <div class="col-md-6">
        <?php
        if(!empty($errors)){
        	echo getMsg('danger', '<ul class="list-unstyle"><li>'.implode("</li><li>", $errors).'</li></ul>');
        }
        if(!empty($message)){
        	echo getMsg('success', $message);
        }
        ?>
        <div class="card">
            <div class="card-header">Apply for Leave</div>
            <div class="card-body">
                <?php if (empty($errors) || (!in_array("Your account is currently inactive. You cannot apply for leave.", $errors) && !in_array("Your department ({$status_data['department_name']}) is currently inactive. You cannot apply for leave.", $errors))): ?>
            	<form id="leaveForm" method="POST" action="apply_leave.php" class="mt-4">
		            <div class="mb-3">
		                <label for="leave_type" class="form-label">Leave Type</label>
		                <select id="leave_type" name="leave_type" class="form-select" required>
		                    <option value="">Select Leave Type</option>
		                    <?php
		                    // Fetch leave types from the database
		                    $stmt = $pdo->query("SELECT leave_type_id, leave_type_name FROM elms_leave_type WHERE leave_type_status = 'Active' ORDER BY leave_type_name ASC");
		                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		                        echo "<option value='{$row['leave_type_id']}'>{$row['leave_type_name']}</option>";
		                    }
		                    ?>
		                </select>
		            </div>
		            <div class="mb-3">
		                <label for="leave_start_date" class="form-label">Start Date</label>
		                <input type="date" id="leave_start_date" name="leave_start_date" class="form-control" required>
		            </div>
		            <div class="mb-3">
		                <label for="leave_end_date" class="form-label">End Date</label>
		                <input type="date" id="leave_end_date" name="leave_end_date" class="form-control" required>
		            </div>
		            <div class="mb-3">
		                <label for="leave_description" class="form-label">Reason for Leave</label>
		                <textarea id="leave_description" name="leave_description" class="form-control"></textarea>
		            </div>
		            <button type="submit" class="btn btn-primary">Apply</button>
		        </form>
                <?php else: ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Cannot Apply for Leave</h5>
                    <p>You cannot apply for leave due to the following reasons:</p>
                    <ul>
                        <?php foreach($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p>Please contact your administrator for assistance.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- Include CKEditor -->
<script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
<script>
    // Initialize CKEditor
    CKEDITOR.replace('leave_description', {
        height: 200,
    });
</script>
<?php
include('footer.php');
?>