<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkEmployeeLogin();

// Basic stats queries
$leaveSql = "SELECT COUNT(*) FROM elms_leave WHERE employee_id = '".$_SESSION['employee_id']."'";
$approveLeaveSql = "SELECT COUNT(*) FROM elms_leave WHERE employee_id = '".$_SESSION['employee_id']."' AND leave_status = 'Approve'";
$pendingLeaveSql = "SELECT COUNT(*) FROM elms_leave WHERE employee_id = '".$_SESSION['employee_id']."' AND leave_status = 'Pending'";
$rejectLeaveSql = "SELECT COUNT(*) FROM elms_leave WHERE employee_id = '".$_SESSION['employee_id']."' AND leave_status = 'Reject'";

// Recent leave applications
$recentLeavesSql = "SELECT l.leave_id, lt.leave_type_name, l.leave_start_date, l.leave_end_date, 
                  l.leave_status, l.leave_apply_date 
                  FROM elms_leave l 
                  JOIN elms_leave_type lt ON l.leave_type = lt.leave_type_id 
                  WHERE l.employee_id = :employee_id 
                  ORDER BY l.leave_apply_date DESC 
                  LIMIT 5";

// Get leave types to show in chart
$leaveTypesSql = "SELECT lt.leave_type_name, COUNT(l.leave_id) as count 
                FROM elms_leave l 
                JOIN elms_leave_type lt ON l.leave_type = lt.leave_type_id 
                WHERE l.employee_id = :employee_id 
                GROUP BY l.leave_type";

// Get leave status for chart
$leaveStatusSql = "SELECT leave_status, COUNT(*) as count 
                  FROM elms_leave 
                  WHERE employee_id = :employee_id 
                  GROUP BY leave_status";

// Execute the queries
$stmt = $pdo->prepare($leaveSql);
$stmt->execute();
$total_leaves = $stmt->fetchColumn();

$stmt = $pdo->prepare($approveLeaveSql);
$stmt->execute();
$total_approve_leaves = $stmt->fetchColumn();

$stmt = $pdo->prepare($pendingLeaveSql);
$stmt->execute();
$total_pending_leaves = $stmt->fetchColumn();

$stmt = $pdo->prepare($rejectLeaveSql);
$stmt->execute();
$total_reject_leaves = $stmt->fetchColumn();

// Get leave balances including inactive types
$stmt = $pdo->prepare("SELECT 
                      lt.leave_type_name, 
                      lb.leave_balance, 
                      lt.days_allowed,
                      lt.leave_type_status
                      FROM elms_leave_balance lb 
                      INNER JOIN elms_leave_type lt ON lb.leave_type_id = lt.leave_type_id 
                      WHERE lb.employee_id = :employee_id");
$stmt->execute([':employee_id' => $_SESSION['employee_id']]);
$balances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get employee info
$stmt = $pdo->prepare("SELECT e.*, d.department_name 
                      FROM elms_employee e 
                      LEFT JOIN elms_department d ON e.employee_department = d.department_id 
                      WHERE e.employee_id = :employee_id");
$stmt->execute([':employee_id' => $_SESSION['employee_id']]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent leaves
$stmt = $pdo->prepare($recentLeavesSql);
$stmt->execute([':employee_id' => $_SESSION['employee_id']]);
$recent_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get leave types for chart
$stmt = $pdo->prepare($leaveTypesSql);
$stmt->execute([':employee_id' => $_SESSION['employee_id']]);
$leave_types_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$leave_types = [];
$leave_type_counts = [];
foreach ($leave_types_data as $type) {
    $leave_types[] = $type['leave_type_name'];
    $leave_type_counts[] = $type['count'];
}

// Get leave status for chart
$stmt = $pdo->prepare($leaveStatusSql);
$stmt->execute([':employee_id' => $_SESSION['employee_id']]);
$leave_status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$status_labels = [];
$status_counts = [];
$status_colors = [];
foreach ($leave_status_data as $status) {
    $status_labels[] = $status['leave_status'];
    $status_counts[] = $status['count'];
    
    // Set colors based on status
    if ($status['leave_status'] == 'Approve') {
        $status_colors[] = 'rgba(40, 167, 69, 0.8)';
    } elseif ($status['leave_status'] == 'Pending') {
        $status_colors[] = 'rgba(255, 193, 7, 0.8)';
    } elseif ($status['leave_status'] == 'Reject') {
        $status_colors[] = 'rgba(220, 53, 69, 0.8)';
    } else {
        $status_colors[] = 'rgba(108, 117, 125, 0.8)';
    }
}

include('header.php');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Dashboard Styles */
    .dashboard-card {
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s, box-shadow 0.3s;
        overflow: hidden;
        border: none;
        height: 100%;
    }
    
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
    
    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .card-gradient-1 {
        background: linear-gradient(135deg, #3498db, #2c3e50);
    }
    
    .card-gradient-2 {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
    }
    
    .card-gradient-3 {
        background: linear-gradient(135deg, #9b59b6, #8e44ad);
    }
    
    .card-gradient-4 {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
    }
    
    .dashboard-charts {
        margin-top: 2rem;
    }
    
    .chart-container {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }
    
    .dashboard-title {
        font-weight: 700;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    
    .recent-leaves-table th, .recent-leaves-table td {
        padding: 12px 15px;
        vertical-align: middle;
    }
    
    .progress {
        height: 10px;
        border-radius: 5px;
    }
    
    .leave-balance-card {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
    }
    
    .leave-balance-card:hover {
        transform: translateY(-5px);
    }
    
    .leave-balance-icon {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    
    .leave-balance-value {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .balance-indicator {
        font-size: 0.9rem;
        margin-top: 5px;
    }
    
    .status-badge {
        padding: 5px 10px;
        border-radius: 30px;
        font-weight: 500;
        font-size: 0.8rem;
    }
    
    .welcome-card {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="my-4 dashboard-title">
            <i class="fas fa-tachometer-alt me-2"></i> Employee Dashboard
        </h2>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </div>
    
    <!-- Welcome Card -->
    <div class="welcome-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3>Welcome, <?php echo $employee['employee_first_name'] . ' ' . $employee['employee_last_name']; ?>!</h3>
                <p>Department: <?php echo $employee['department_name'] ?? 'Not Assigned'; ?></p>
                <p>Employee ID: <?php echo $employee['employee_unique_code']; ?></p>
                <a href="employee_profile.php" class="btn btn-light btn-sm mt-2">
                    <i class="fas fa-user me-1"></i> View Profile
                </a>
                <a href="apply_leave.php" class="btn btn-warning btn-sm mt-2 ms-2">
                    <i class="fas fa-plus-circle me-1"></i> Apply for Leave
                </a>
            </div>
            <div class="col-md-4 text-end d-none d-md-block">
                <i class="fas fa-user-circle" style="font-size: 6rem; opacity: 0.5;"></i>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card dashboard-card card-gradient-1 text-white">
                <div class="card-body text-center">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-value"><?php echo $total_leaves; ?></div>
                    <div class="stat-label">Total Leaves</div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card dashboard-card card-gradient-2 text-white">
                <div class="card-body text-center">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value"><?php echo $total_approve_leaves; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card dashboard-card card-gradient-3 text-white">
                <div class="card-body text-center">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-value"><?php echo $total_pending_leaves; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card dashboard-card card-gradient-4 text-white">
                <div class="card-body text-center">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-value"><?php echo $total_reject_leaves; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Leave Balances -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card dashboard-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i> Available Leave Balances</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($balances as $balance): ?>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card leave-balance-card <?php echo ($balance['leave_type_status'] === 'Inactive') ? 'border-danger' : ''; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0"><?php echo $balance['leave_type_name']; ?></h5>
                                        <span class="leave-balance-icon">
                                            <?php if ($balance['leave_type_status'] === 'Inactive'): ?>
                                                <i class="fas fa-ban text-danger"></i>
                                            <?php else: ?>
                                                <i class="fas fa-hourglass-half text-primary"></i>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($balance['leave_type_status'] === 'Inactive'): ?>
                                        <div class="alert alert-danger py-2 mb-2">
                                            <i class="fas fa-exclamation-circle me-2"></i> This leave type is currently inactive
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="leave-balance-value">
                                        <?php echo $balance['leave_balance']; ?> <small>/ <?php echo $balance['days_allowed']; ?> days</small>
                                    </div>
                                    
                                    <div class="progress mt-3">
                                        <?php 
                                        $percentage = ($balance['leave_balance'] / $balance['days_allowed']) * 100;
                                        $color = $percentage > 50 ? 'success' : ($percentage > 25 ? 'warning' : 'danger');
                                        ?>
                                        <div class="progress-bar bg-<?php echo $color; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    
                                    <div class="balance-indicator d-flex justify-content-between align-items-center mt-2">
                                        <span class="text-<?php echo $color; ?>"><?php echo $percentage; ?>% available</span>
                                        <?php if ($balance['leave_type_status'] === 'Inactive'): ?>
                                            <span class="badge bg-danger">Cannot Apply</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($balances)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> No leave balances available. Please contact your administrator.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="row dashboard-charts">
        <div class="col-xl-6 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Leave Types Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <?php if (!empty($leave_types)): ?>
                        <canvas id="leaveTypesChart"></canvas>
                        <?php else: ?>
                        <div class="alert alert-info text-center py-5">
                            <i class="fas fa-info-circle fs-1 mb-3"></i>
                            <p>No leave data available to generate chart.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Leave Status Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <?php if (!empty($status_labels)): ?>
                        <canvas id="leaveStatusChart"></canvas>
                        <?php else: ?>
                        <div class="alert alert-info text-center py-5">
                            <i class="fas fa-info-circle fs-1 mb-3"></i>
                            <p>No leave data available to generate chart.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Leaves -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card dashboard-card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> Recent Leave Applications</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_leaves)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover recent-leaves-table">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th>Period</th>
                                    <th>Applied On</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_leaves as $leave): ?>
                                <tr>
                                    <td><?php echo $leave['leave_type_name']; ?></td>
                                    <td><?php echo date("M d, Y", strtotime($leave['leave_start_date'])); ?> - <?php echo date("M d, Y", strtotime($leave['leave_end_date'])); ?></td>
                                    <td><?php echo date("M d, Y", strtotime($leave['leave_apply_date'])); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($leave['leave_status']) {
                                            case 'Approve':
                                                $status_class = 'bg-success';
                                                break;
                                            case 'Pending':
                                                $status_class = 'bg-warning';
                                                break;
                                            case 'Reject':
                                                $status_class = 'bg-danger';
                                                break;
                                            default:
                                                $status_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?> text-white">
                                            <?php echo $leave['leave_status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_leave_details.php?id=<?php echo $leave['leave_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> You haven't applied for any leaves yet.
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-end mt-3">
                        <a href="apply_leave.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i> Apply for Leave
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include('footer.php'); ?>

<script>
<?php if (!empty($leave_types)): ?>
// Chart for Leave Types Distribution
const leaveTypesCtx = document.getElementById('leaveTypesChart').getContext('2d');
const leaveTypesChart = new Chart(leaveTypesCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($leave_types); ?>,
        datasets: [{
            data: <?php echo json_encode($leave_type_counts); ?>,
            backgroundColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            },
            title: {
                display: true,
                text: 'Leave Types Distribution'
            }
        }
    }
});
<?php endif; ?>

<?php if (!empty($status_labels)): ?>
// Chart for Leave Status Distribution
const leaveStatusCtx = document.getElementById('leaveStatusChart').getContext('2d');
const leaveStatusChart = new Chart(leaveStatusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($status_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($status_counts); ?>,
            backgroundColor: <?php echo json_encode($status_colors); ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            },
            title: {
                display: true,
                text: 'Leave Status Distribution'
            }
        }
    }
});
<?php endif; ?>
</script>

