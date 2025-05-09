
<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminOrUserLogin();

$message = $_SESSION['message'] ?? null;

unset($_SESSION['message']);

include('header.php');
?>

<h1 class="mt-4">Leave Management</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="employee_dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Leave Management</li>
</ol>

<!-- Success Message -->
<?php if ($message): ?>
<?php echo getMsg('success', $message); ?>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col col-md-6"><b>Leave List</b></div>
            <div class="col col-md-6">
                <?php
                if(isset($_SESSION['employee_id'])){
                ?>
                <a href="apply_leave.php" class="btn btn-success btn-sm float-end">Apply</a>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
    <div class="card-body">
        <table id="leaveTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee Name</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Applied On</th>
                    <th>Process On</th>
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
    $('#leaveTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'leave_ajax.php', // PHP script to fetch data
            type: 'POST',
        },
        columns: [
            { data: 'leave_id' },
            { data: 'employee_name' },
            { data: 'leave_type_name' },
            { data: 'leave_start_date' },
            { data: 'leave_end_date' },
            {
                data : null,
                render: function(data, type, row){
                    if(row.leave_status === 'Pending'){
                        return `<span class="badge bg-primary">Pending</span>`;
                    }
                    if(row.leave_status === 'Admin Read'){
                        return `<span class="badge bg-info">Admin Read</span>`;
                    }
                    if(row.leave_status === 'Approve'){
                        return `<span class="badge bg-success">Approve</span>`;
                    }
                    if(row.leave_status === 'Reject'){
                        return `<span class="badge bg-danger">Reject</span>`;
                    }
                }
            },
            { data: 'leave_apply_date' },
            {
                data: null,
                render: function (data, type, row) {
                    if(row.leave_admin_remark_date !== null){
                        return row.leave_admin_remark_date;
                    } else {
                        return 'NA';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    return `<a href="view_leave_details.php?id=${row.leave_id}" class="btn btn-info btn-sm">View Details</a>`;
                },
                orderable: false,
                searchable: false
            }
        ]
    });
});
</script>