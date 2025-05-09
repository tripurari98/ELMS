<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

// Fetch departments
$stmt = $pdo->query("SELECT department_id, department_name FROM elms_department");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$errors = [];
$data = [];
$message = '';

// Validate and sanitize form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Required fields
    $requiredFields = [
        'first_name', 'last_name', 'email', 'password', 'gender',
        'birthdate', 'department', 'mobile', 'status'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace("_", " ", $field)) . " is required.";
        } else {
            $data[$field] = sanitizeInput($_POST[$field]);
        }
    }

    // Additional validations
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!empty($data['mobile']) && !preg_match('/^[0-9]{10}$/', $data['mobile'])) {
        $errors[] = "Invalid mobile number. Must be 10 digits.";
    }

    // If no errors, insert data into the database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO elms_employee 
                (employee_unique_code, employee_first_name, employee_last_name, employee_email, employee_password, 
                employee_gender, employee_birthdate, employee_department, employee_address, employee_city, 
                employee_country, employee_mobile_number, employee_status)
                VALUES 
                (:unique_code, :first_name, :last_name, :email, :password, :gender, :birthdate, :department, 
                :address, :city, :country, :mobile, :status)
            ");

            $uniqueCode = uniqid('EMP-'); // Generate a unique employee code

            $stmt->execute([
                ':unique_code' 		=> $uniqueCode,
                ':first_name' 		=> $data['first_name'],
                ':last_name' 		=> $data['last_name'],
                ':email' 			=> $data['email'],
                ':password' 		=> password_hash($data['password'], PASSWORD_BCRYPT), // Hash password
                ':gender' 			=> $data['gender'],
                ':birthdate' 		=> $data['birthdate'],
                ':department' 		=> $data['department'],
                ':address' 			=> $_POST['address'] ?? null,
                ':city' 			=> $_POST['city'] ?? null,
                ':country' 			=> $_POST['country'] ?? null,
                ':mobile' 			=> $data['mobile'],
                ':status' 			=> $data['status'],
            ]);

            $employee_id = $pdo->lastInsertId();

            $leaveTypes = $pdo->query("SELECT leave_type_id, days_allowed FROM elms_leave_type WHERE leave_type_status = 'Active'")->fetchAll(PDO::FETCH_ASSOC);
			foreach ($leaveTypes as $type) {
			    $stmt = $pdo->prepare("INSERT INTO elms_leave_balance (employee_id, leave_type_id, leave_balance) VALUES (:employee_id, :leave_type_id, :balance)");
			    $stmt->execute([
			        ':employee_id' 		=> $employee_id,
			        ':leave_type_id' 	=> $type['leave_type_id'],
			        ':balance' 			=> $type['days_allowed']
			    ]);
			}

            // Success message
           	$message = "Employee added successfully!";
        } catch (PDOException $e) {
            $errors[] = "Error adding employee: " . $e->getMessage();
        }
    }
}


include('header.php');

?>

<h1 class="mt-4">Add Employee</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="employee.php">Employee Management</a></li>
    <li class="breadcrumb-item active">Add New Employee</li>
</ol>

<div class="row">
    <div class="col-md-6">
        <?php
        if($message !== ''){
        	echo getMsg('success', $message);
        }
        if(!empty($errors)){
        	echo getMsg('danger', '<ul class="list-unstyle"><li>'.implode("</li><li>", $errors).'</li></ul>');
        }
        ?>
        <div class="card">
            <div class="card-header">Add Employee</div>
            <div class="card-body">
            <form method="post" action="add_employee.php">
                <div class="row mb-3">
	                <div class="col-md-6">
	                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
	                    <input type="text" class="form-control" id="first_name" name="first_name" required>
	                </div>
	                <div class="col-md-6">
	                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
	                    <input type="text" class="form-control" id="last_name" name="last_name" required>
	                </div>
	            </div>
	            <div class="mb-3">
	                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
	                <input type="email" class="form-control" id="email" name="email" required>
	            </div>
	            <div class="mb-3">
	                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
	                <input type="password" class="form-control" id="password" name="password" required>
	            </div>
	            <div class="row mb-3">
	                <div class="col-md-6">
	                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
	                    <select class="form-select" id="gender" name="gender" required>
	                        <option value="">Select Gender</option>
	                        <option value="Male">Male</option>
	                        <option value="Female">Female</option>
	                        <option value="Other">Other</option>
	                    </select>
	                </div>
	                <div class="col-md-6">
	                    <label for="birthdate" class="form-label">Birthdate <span class="text-danger">*</span></label>
	                    <input type="date" class="form-control" id="birthdate" name="birthdate" required>
	                </div>
	            </div>
	            <div class="mb-3">
	                <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
	                <select class="form-select" id="department" name="department" required>
	                    <option value="">Select Department</option>
	                    <?php foreach ($departments as $department): ?>
	                        <option value="<?= htmlspecialchars($department['department_id']) ?>">
	                            <?= htmlspecialchars($department['department_name']) ?>
	                        </option>
	                    <?php endforeach; ?>
	                </select>
	            </div>
	            <div class="mb-3">
	                <label for="address" class="form-label">Address</label>
	                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
	            </div>
	            <div class="row mb-3">
	                <div class="col-md-6">
	                    <label for="city" class="form-label">City</label>
	                    <input type="text" class="form-control" id="city" name="city">
	                </div>
	                <div class="col-md-6">
	                    <label for="country" class="form-label">Country</label>
	                    <input type="text" class="form-control" id="country" name="country">
	                </div>
	            </div>
	            <div class="mb-3">
	                <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
	                <input type="text" class="form-control" id="mobile" name="mobile" required>
	            </div>
	            <div class="mb-3">
                	<label for="status" class="form-label">Status</label>
	                <select class="form-select" id="status" name="status" required>
	                    <option value="Active">Active</option>
	                    <option value="Inactive">Inactive</option>
	                </select>
            	</div>
	            <div class="text-center">
	                <button type="submit" class="btn btn-primary">Add Employee</button>
	            </div>
            </form>
        </div>
    </div>
</div>


<?php
include('footer.php');
?>