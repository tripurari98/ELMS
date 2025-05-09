<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $department_name = trim($_POST['department_name']);
    $department_status = trim($_POST['department_status']);
    $message = '';

    // Validate inputs
    if (empty($department_name)) {
        $message = 'Department name is required.';
    } else {
        // Check if Department already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM elms_department WHERE department_name = :department_name");
        $stmt->execute(['department_name' => $department_name]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $message = 'Department with this name already exists.';
        } else {
            // Insert into database
            try {
                $stmt = $pdo->prepare("INSERT INTO elms_department (department_name, department_status) VALUES (:department_name, :department_status)");
                $stmt->execute([
                    'department_name' => $department_name,
                    'department_status' => $department_status
                ]);
                header('location:department.php');
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

include('header.php');
?>

<h1 class="mt-4">Add Department</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="category.php">Department Management</a></li>
    <li class="breadcrumb-item active">Add Department</li>
</ol>

<div class="row">
    <div class="col-md-4">
        <?php
        if(isset($message) && $message !== ''){
            echo '
            <div class="alert alert-danger">
            '.$message.'
            </div>
            ';
        }
        ?>
        <div class="card">
            <div class="card-header">Add Department</div>
            <div class="card-body">
            <form method="post" action="add_department.php">
                <div class="mb-3">
                    <label for="department_name">Department Name</label>
                    <input type="text" id="department_name" name="department_name" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="department_status">Department Status</label>
                    <select id="department_status" name="department_status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <input type="submit" value="Add Department" class="btn btn-primary">
            </form>
        </div>
    </div>
</div>

<?php
include('footer.php');
?>