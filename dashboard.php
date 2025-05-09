<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

// Statistics queries
$employeeSql = "SELECT COUNT(*) FROM elms_employee WHERE employee_status = 'Active'";
$departmentSql = "SELECT COUNT(*) FROM elms_department";
$leaveTypeSql = "SELECT COUNT(*) FROM elms_leave_type";
$leaveSql = "SELECT COUNT(*) FROM elms_leave";
$approveLeaveSql = "SELECT COUNT(*) FROM elms_leave WHERE leave_status = 'Approve'";
$pendingLeaveSql = "SELECT COUNT(*) FROM elms_leave WHERE leave_status = 'Pending'";
$rejectLeaveSql = "SELECT COUNT(*) FROM elms_leave WHERE leave_status = 'Reject'";

// Get leave distribution by types
$leaveDistributionSql = "SELECT lt.leave_type_name, COUNT(l.leave_id) as count 
                         FROM elms_leave l 
                         JOIN elms_leave_type lt ON l.leave_type = lt.leave_type_id 
                         GROUP BY l.leave_type";

// Recent pending leave applications
$recentPendingLeavesSql = "SELECT l.leave_id, e.employee_first_name, e.employee_last_name, 
                          lt.leave_type_name, l.leave_start_date, l.leave_end_date, 
                          l.leave_apply_date, l.leave_status 
                          FROM elms_leave l 
                          JOIN elms_employee e ON l.employee_id = e.employee_id 
                          JOIN elms_leave_type lt ON l.leave_type = lt.leave_type_id 
                          WHERE l.leave_status = 'Pending' 
                          ORDER BY l.leave_apply_date DESC 
                          LIMIT 5";

// Execute queries
$stmt = $pdo->prepare($employeeSql);
$stmt->execute();
$total_employee = $stmt->fetchColumn();

$stmt = $pdo->prepare($departmentSql);
$stmt->execute();
$total_department = $stmt->fetchColumn();

$stmt = $pdo->prepare($leaveTypeSql);
$stmt->execute();
$total_leave_type = $stmt->fetchColumn();

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

// Get leave distribution data
$stmt = $pdo->prepare($leaveDistributionSql);
$stmt->execute();
$leave_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
$leave_types = [];
$leave_counts = [];
foreach ($leave_distribution as $item) {
    $leave_types[] = $item['leave_type_name'];
    $leave_counts[] = $item['count'];
}

// Recent pending leaves
$stmt = $pdo->prepare($recentPendingLeavesSql);
$stmt->execute();
$pending_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        background: linear-gradient(135deg, #f1c40f, #e67e22);
    }
    
    .card-gradient-3 {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
    }
    
    .card-gradient-4 {
        background: linear-gradient(135deg, #1abc9c, #16a085);
    }
    
    .card-gradient-5 {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
    }
    
    .card-gradient-6 {
        background: linear-gradient(135deg, #9b59b6, #8e44ad);
    }
    
    .card-gradient-7 {
        background: linear-gradient(135deg, #34495e, #2c3e50);
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
    
    .status-badge {
        padding: 5px 10px;
        border-radius: 30px;
        font-weight: 500;
        font-size: 0.8rem;
    }
</style>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="my-4 dashboard-title">
            <i class="fas fa-tachometer-alt me-2"></i> Admin Dashboard
        </h2>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </div>
    
    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card dashboard-card card-gradient-1 text-white h-100">
                <div class="card-body text-center">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?php echo $total_employee; ?></div>
                    <div class="stat-label">Employees</div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="employee.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card dashboard-card card-gradient-2 text-white h-100">
                <div class="card-body text-center">
                    <div class="stat-icon"><i class="fas fa-building"></i></div>
                    <div class="stat-value"><?php echo $total_department; ?></div>
                    <div class="stat-label">Departments</div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="department.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card dashboard-card card-gradient-3 text-white h-100">
                <div class="card-body text-center">
                    <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div class="stat-value"><?php echo $total_leave_type; ?></div>
                    <div class="stat-label">Leave Types</div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="leave_type.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card dashboard-card card-gradient-4 text-white h-100">
                <div class="card-body text-center">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-value"><?php echo $total_leaves; ?></div>
                    <div class="stat-label">Total Leaves</div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="leave_list.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card dashboard-card card-gradient-5 text-white h-100">
                <div class="card-body text-center">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value"><?php echo $total_approve_leaves; ?></div>
                    <div class="stat-label">Approved Leaves</div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="leave_list.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card dashboard-card card-gradient-6 text-white h-100">
                <div class="card-body text-center">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-value"><?php echo $total_pending_leaves; ?></div>
                    <div class="stat-label">Pending Leaves</div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="leave_list.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card dashboard-card card-gradient-7 text-white h-100">
                <div class="card-body text-center">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-value"><?php echo $total_reject_leaves; ?></div>
                    <div class="stat-label">Rejected Leaves</div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="leave_list.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts & Recent Pending Leaves -->
    <div class="row dashboard-charts">
        <div class="col-xl-6 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Leave Distribution by Type</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="leaveDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Leave Status Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="leaveStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card dashboard-card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i> Recent Pending Leave Applications</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($pending_leaves)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover recent-leaves-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Date Range</th>
                                    <th>Applied On</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_leaves as $leave): ?>
                                <tr>
                                    <td><?php echo $leave['leave_id']; ?></td>
                                    <td><?php echo $leave['employee_first_name'] . ' ' . $leave['employee_last_name']; ?></td>
                                    <td><?php echo $leave['leave_type_name']; ?></td>
                                    <td><?php echo date("M d, Y", strtotime($leave['leave_start_date'])); ?> - <?php echo date("M d, Y", strtotime($leave['leave_end_date'])); ?></td>
                                    <td><?php echo date("M d, Y", strtotime($leave['leave_apply_date'])); ?></td>
                                    <td><span class="status-badge bg-warning text-white">Pending</span></td>
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
                        <i class="fas fa-info-circle me-2"></i> No pending leave applications at the moment.
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-end mt-3">
                        <a href="leave_list.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-1"></i> View All Leaves
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>

    <!-- <?php if ($_SESSION['user_type'] === 'Admin'): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 rounded shadow">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>System Utilities</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        These utilities help maintain the system and fix known issues. Use with caution.
                    </div>
                    
                    <div class="list-group">
                        <a href="fix_rejected_leaves.php" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><i class="fas fa-sync me-2"></i>Fix Rejected Leave Balances</h5>
                            </div>
                            <p class="mb-1">Restore leave balances that were incorrectly deducted for rejected leave applications.</p>
                            <small class="text-muted">Use this utility to correct historical data - only needs to be run once.</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div> -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include('footer.php'); ?>

<script>
// Chart for Leave Distribution by Type
const leaveDistributionCtx = document.getElementById('leaveDistributionChart').getContext('2d');
const leaveDistributionChart = new Chart(leaveDistributionCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($leave_types); ?>,
        datasets: [{
            data: <?php echo json_encode($leave_counts); ?>,
            backgroundColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(40, 167, 69, 0.8)'
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
                text: 'Leave Distribution by Type'
            }
        }
    }
});

// Chart for Leave Status Distribution
const leaveStatusCtx = document.getElementById('leaveStatusChart').getContext('2d');
const leaveStatusChart = new Chart(leaveStatusCtx, {
    type: 'bar',
    data: {
        labels: ['Approved', 'Pending', 'Rejected'],
        datasets: [{
            label: 'Number of Leaves',
            data: [
                <?php echo $total_approve_leaves; ?>,
                <?php echo $total_pending_leaves; ?>,
                <?php echo $total_reject_leaves; ?>
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(220, 53, 69, 0.8)'
            ],
            borderColor: [
                'rgba(40, 167, 69, 1)',
                'rgba(255, 193, 7, 1)',
                'rgba(220, 53, 69, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Leave Distribution by Status'
            }
        }
    }
});
</script>