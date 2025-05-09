<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkEmployeeLogin();

// Initialize variables
$errors = [];
$data = [];
$message = '';

// Validate and sanitize form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Required fields
    $requiredFields = [
        'employee_id', 'first_name', 'last_name', 'email', 'gender',
        'birthdate', 'mobile'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = ucfirst(str_replace("_", " ", $field)) . " is required.";
        } else {
            $data[$field] = sanitizeInput($_POST[$field]);
        }
    }

    // Additional validations
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    if (!empty($data['mobile']) && !preg_match('/^[0-9]{10}$/', $data['mobile'])) {
        $errors['mobile'] = "Invalid mobile number. Must be 10 digits.";
    }

    // If no errors, update the database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE elms_employee 
                SET 
                    employee_first_name = :first_name,
                    employee_last_name = :last_name,
                    employee_email = :email,
                    employee_gender = :gender,
                    employee_birthdate = :birthdate, 
                    employee_address = :address,
                    employee_city = :city,
                    employee_country = :country,
                    employee_mobile_number = :mobile 
                WHERE 
                    employee_id = :employee_id
            ");

            $stmt->execute([
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':email' => $data['email'],
                ':gender' => $data['gender'],
                ':birthdate' => $data['birthdate'], 
                ':address' => $_POST['address'] ?? null,
                ':city' => $_POST['city'] ?? null,
                ':country' => $_POST['country'] ?? null,
                ':mobile' => $data['mobile'],
                ':employee_id' => $data['employee_id'],
            ]);

            // Success message
            $message = "Employee updated successfully!";
        } catch (PDOException $e) {
            $errors[] = "Error updating employee: " . $e->getMessage();
        }
    }
}

// Fetch employee data
if (isset($_SESSION['employee_id']) && is_numeric($_SESSION['employee_id'])) {
    $employeeId = $_SESSION['employee_id'];

    // Get employee data
    $stmt = $pdo->prepare("SELECT * FROM elms_employee WHERE employee_id = :id");
    $stmt->execute([':id' => $employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        die("Employee not found!");
    }

    // Fetch departments
    $deptStmt = $pdo->query("SELECT department_id, department_name FROM elms_department");
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    die("Invalid employee ID.");
}

include('header.php');

?>

<h1 class="mt-4">Profile</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="employee_dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Profile</li>
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
            <div class="card-header">Profile</div>
            <div class="card-body">
            <form method="post" action="employee_profile.php?id=<?= htmlspecialchars($employee['employee_id']) ?>">
                <div class="row mb-3">
	                <div class="col-md-6">
	                    <label for="first_name" class="form-label">First Name<span class="text-danger">*</span></label>
	                    <input type="text" class="form-control" id="first_name" name="first_name" 
	                           value="<?= htmlspecialchars($employee['employee_first_name']) ?>" required>
	                </div>
	                <div class="col-md-6">
	                    <label for="last_name" class="form-label">Last Name<span class="text-danger">*</span></label>
	                    <input type="text" class="form-control" id="last_name" name="last_name" 
	                           value="<?= htmlspecialchars($employee['employee_last_name']) ?>" required>
	                </div>
	            </div>
	            <div class="mb-3">
	                <label for="email" class="form-label">Email<span class="text-danger">*</span></label>
	                <input type="email" class="form-control" id="email" name="email" 
	                       value="<?= htmlspecialchars($employee['employee_email']) ?>" required>
	            </div>
	            <div class="row mb-3">
	                <div class="col-md-6">
	                    <label for="gender" class="form-label">Gender<span class="text-danger">*</span></label>
	                    <select class="form-select" id="gender" name="gender" required>
	                        <option value="Male" <?= $employee['employee_gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
	                        <option value="Female" <?= $employee['employee_gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
	                        <option value="Other" <?= $employee['employee_gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
	                    </select>
	                </div>
	                <div class="col-md-6">
	                    <label for="birthdate" class="form-label">Birthdate<span class="text-danger">*</span></label>
	                    <input type="date" class="form-control" id="birthdate" name="birthdate" 
	                           value="<?= htmlspecialchars($employee['employee_birthdate']) ?>" required>
	                </div>
	            </div>
	            <div class="mb-3">
	                <label for="department" class="form-label">Department<span class="text-danger">*</span></label>
	                <select class="form-select" id="department" name="department" disabled required>
	                    <?php foreach ($departments as $department): ?>
	                        <option value="<?= htmlspecialchars($department['department_id']) ?>" 
	                                <?= $employee['employee_department'] == $department['department_id'] ? 'selected' : '' ?>>
	                            <?= htmlspecialchars($department['department_name']) ?>
	                        </option>
	                    <?php endforeach; ?>
	                </select>
	            </div>
	            <div class="mb-3">
	                <label for="address" class="form-label">Address</label>
	                <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($employee['employee_address']) ?></textarea>
	            </div>
	            <div class="row mb-3">
	                <div class="col-md-6">
	                    <label for="city" class="form-label">City</label>
	                    <input type="text" class="form-control" id="city" name="city" 
	                           value="<?= htmlspecialchars($employee['employee_city']) ?>">
	                </div>
	                <div class="col-md-6">
	                    <label for="country" class="form-label">Country</label>
	                    <input type="text" class="form-control" id="country" name="country" 
	                           value="<?= htmlspecialchars($employee['employee_country']) ?>">
	                </div>
	            </div>
	            <div class="mb-3">
	                <label for="mobile" class="form-label">Mobile Number<span class="text-danger">*</span></label>
	                <input type="text" class="form-control" id="mobile" name="mobile" 
	                       value="<?= htmlspecialchars($employee['employee_mobile_number']) ?>" required>
	            </div>
	            <div class="text-center">
	            	<input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee['employee_id']) ?>">
	                <button type="submit" class="btn btn-primary">Update</button>
	            </div>
            </form>
        </div>
    </div>
</div>


<?php
include('footer.php');
?>