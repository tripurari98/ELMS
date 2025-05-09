<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

$message = $_SESSION['message'] ?? null;
$error = isset($_GET['error']) ? $_GET['error'] : null;
$success = isset($_GET['success']) ? $_GET['success'] : null;

unset($_SESSION['message']);

include('header.php');
?>

<h1 class="mt-4">Leave Type Management</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Leave Type Management</li>
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
            <div class="col col-md-6"><b>Leave Type List</b></div>
            <div class="col col-md-6">
                <a href="sync_leave_balances.php" class="btn btn-info btn-sm float-end me-2">
                    <i class="fas fa-sync-alt"></i> Sync Leave Balances
                </a>
                <a href="add_leave_type.php" class="btn btn-success btn-sm float-end me-2">Add</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <table id="leaveTypeTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Leave Type</th>
                    <th>Allow Leave Day</th>
                    <th>Status</th>
                    <th>Added On</th>
                    <th>Updated On</th>
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
    $('#leaveTypeTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "leave_type_ajax.php",
            "type": "POST"
        },
        "columns": [
            { "data": "leave_type_id" },
            { "data": "leave_type_name" },
            { 
                "data" : null,
                "render" : function(data, type, row){
                    return row.days_allowed + ' days';
                } 
            },
            { 
                "data" : null,
                "render" : function(data, type, row){
                    if(row.leave_type_status === 'Active'){
                        return `<span class="badge bg-success">Active</span>`;
                    } else {
                        return `<span class="badge bg-danger">Inactive</span>`;
                    }
                } 
            },
            { "data": "added_on" },
            { "data": "updated_on" },
            {
                "data" : null,
                "render" : function(data, type, row){
                    return `
                    <div class="text-center">
                        <a href="edit_leave_type.php?id=${row.leave_type_id}" class="btn btn-warning btn-sm">Edit</a>&nbsp;
                        <a href="javascript:void(0);" onclick="confirmDelete(${row.leave_type_id})" class="btn btn-danger btn-sm">Delete</a>
                    </div>
                    `;
                }
            }
        ]
    });
});

function confirmDelete(leaveTypeId) {
    if (confirm("Are you sure you want to delete this leave type? This action cannot be undone.")) {
        window.location.href = `delete_leave_type.php?id=${leaveTypeId}`;
    }
}
</script>