<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

include('header.php');
?>

<h1 class="mt-4">Leave Management Reports</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Reports</li>
</ol>

<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> Available Reports</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Leave Usage by Department Report -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon-circle bg-primary text-white me-3">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <h5 class="mb-0">Department Leave Usage</h5>
                                </div>
                                <p class="card-text">View leave usage statistics grouped by department.</p>
                                <a href="report_department_leave.php" class="btn btn-outline-primary">Generate Report</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Leave Usage by Employee Report -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon-circle bg-success text-white me-3">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <h5 class="mb-0">Employee Leave Report</h5>
                                </div>
                                <p class="card-text">Generate detailed leave reports for individual employees.</p>
                                <a href="report_employee_leave.php" class="btn btn-outline-success">Generate Report</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Leave Type Usage Report -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon-circle bg-info text-white me-3">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <h5 class="mb-0">Leave Type Analysis</h5>
                                </div>
                                <p class="card-text">Analyze which types of leave are most commonly used.</p>
                                <a href="report_leave_type.php" class="btn btn-outline-info">Generate Report</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monthly/Yearly Report -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon-circle bg-warning text-white me-3">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <h5 class="mb-0">Time-based Analysis</h5>
                                </div>
                                <p class="card-text">View leave patterns by month, quarter, or year.</p>
                                <a href="report_time_based.php" class="btn btn-outline-warning">Generate Report</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Leave Balance Report -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon-circle bg-danger text-white me-3">
                                        <i class="fas fa-balance-scale"></i>
                                    </div>
                                    <h5 class="mb-0">Leave Balance Summary</h5>
                                </div>
                                <p class="card-text">Overview of all employees' remaining leave balances.</p>
                                <a href="report_leave_balance.php" class="btn btn-outline-danger">Generate Report</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Report Generator -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon-circle bg-secondary text-white me-3">
                                        <i class="fas fa-cogs"></i>
                                    </div>
                                    <h5 class="mb-0">Custom Report</h5>
                                </div>
                                <p class="card-text">Create custom reports with filters and parameters of your choice.</p>
                                <a href="report_custom.php" class="btn btn-outline-secondary">Generate Report</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-circle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 1.2rem;
}

.card {
    transition: transform 0.3s;
}

.card:hover {
    transform: translateY(-5px);
}
</style>

<?php include('footer.php'); ?> 