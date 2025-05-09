<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

$message = $_SESSION['message'] ?? null;
$error = isset($_GET['error']) ? $_GET['error'] : null;
$success = isset($_GET['success']) ? $_GET['success'] : null;

// Clear the message from the session
unset($_SESSION['message']);

include('header.php');
?>

<h1 class="mt-4">Employee Management</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Employee Management</li>
</ol>
<!-- Success Message -->
<?php if ($message): ?>
<?php echo getMsg('success', $message); ?>
<?php endif; ?>

<!-- Success Message from URL parameter -->
<?php if ($success): ?>
<?php echo getMsg('success', $success); ?>
<?php endif; ?>

<!-- Error Message -->
<?php if ($error): ?>
<?php echo getMsg('danger', $error); ?>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col col-md-6"><b>Employee List</b></div>
            <div class="col col-md-6">
                <a href="add_employee.php" class="btn btn-success btn-sm float-end">Add</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <table id="employeeTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<?php
include('footer.php');
?>

<script>
$(document).ready(function() {
    $('#employeeTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "employee_ajax.php",
            "type": "POST"
        },
        "columns": [
            { "data": "employee_id" },
            { "data": "employee_first_name" },
            { "data": "employee_last_name" },
            { "data": "employee_email" },
            { "data": "department_name" },
            { 
                "data" : null,
                "render" : function(data, type, row){
                    if(row.employee_status === 'Active'){
                        return `<span class="badge bg-success">Active</span>`;
                    } else {
                        return `<span class="badge bg-danger">Inactive</span>`;
                    }
                } 
            },
            {
                "data" : null,
                "render" : function(data, type, row){
                    return `
                    <div class="text-center">
                        <a href="edit_employee.php?id=${row.employee_id}" class="btn btn-warning btn-sm">Edit</a>&nbsp;
                        <a href="javascript:void(0);" onclick="confirmDelete(${row.employee_id})" class="btn btn-danger btn-sm">Delete</a>
                    </div>
                    `;
                }
            }
        ]
    });
});

function confirmDelete(employeeId) {
    if (confirm("Are you sure you want to delete this employee? This action cannot be undone and will delete all associated leave records.")) {
        window.location.href = `delete_employee.php?id=${employeeId}`;
    }
}
</script>