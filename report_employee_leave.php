<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

// Get all employees for the filter
$employeeStmt = $pdo->query("
    SELECT e.employee_id, CONCAT(e.employee_first_name, ' ', e.employee_last_name) as employee_name 
    FROM elms_employee e 
    WHERE e.employee_status = 'Active'
    ORDER BY e.employee_first_name
");
$employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all leave types for the filter
$leaveTypeStmt = $pdo->query("SELECT leave_type_id, leave_type_name FROM elms_leave_type ORDER BY leave_type_name");
$leaveTypes = $leaveTypeStmt->fetchAll(PDO::FETCH_ASSOC);

// Default date range (current year)
$currentYear = date('Y');
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : "$currentYear-01-01";
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$selectedEmployeeId = isset($_GET['employee_id']) ? $_GET['employee_id'] : 'all';
$selectedLeaveTypeId = isset($_GET['leave_type_id']) ? $_GET['leave_type_id'] : 'all';
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build the query based on filters
$params = [];
$whereConditions = ["l.leave_start_date >= :start_date", "l.leave_end_date <= :end_date"];
$params[':start_date'] = $startDate;
$params[':end_date'] = $endDate;

if ($selectedEmployeeId !== 'all') {
    $whereConditions[] = "l.employee_id = :employee_id";
    $params[':employee_id'] = $selectedEmployeeId;
}

if ($selectedLeaveTypeId !== 'all') {
    $whereConditions[] = "l.leave_type = :leave_type_id";
    $params[':leave_type_id'] = $selectedLeaveTypeId;
}

if ($selectedStatus !== 'all') {
    $whereConditions[] = "l.leave_status = :status";
    $params[':status'] = $selectedStatus;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Query to get employee leave data
$query = "
    SELECT 
        l.leave_id,
        CONCAT(e.employee_first_name, ' ', e.employee_last_name) as employee_name,
        e.employee_unique_code,
        d.department_name,
        lt.leave_type_name,
        l.leave_status,
        l.leave_start_date,
        l.leave_end_date,
        DATEDIFF(l.leave_end_date, l.leave_start_date) + 1 as days_count,
        l.leave_reason,
        l.admin_remark
    FROM 
        elms_leave l
    JOIN 
        elms_employee e ON l.employee_id = e.employee_id
    JOIN 
        elms_department d ON e.employee_department = d.department_id
    JOIN 
        elms_leave_type lt ON l.leave_type = lt.leave_type_id
    $whereClause
    ORDER BY 
        l.leave_start_date DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$leaveData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$totalLeaves = count($leaveData);
$totalDays = 0;
$approvedCount = 0;
$pendingCount = 0;
$rejectedCount = 0;
$leaveTypeCount = [];

foreach ($leaveData as $leave) {
    $totalDays += $leave['days_count'];
    
    if ($leave['leave_status'] == 'Approve') {
        $approvedCount++;
    } elseif ($leave['leave_status'] == 'Pending') {
        $pendingCount++;
    } elseif ($leave['leave_status'] == 'Reject') {
        $rejectedCount++;
    }
    
    // Count by leave type
    if (!isset($leaveTypeCount[$leave['leave_type_name']])) {
        $leaveTypeCount[$leave['leave_type_name']] = 0;
    }
    $leaveTypeCount[$leave['leave_type_name']]++;
}

include('header.php');
?>

<h1 class="mt-4">Employee Leave Report</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
    <li class="breadcrumb-item active">Employee Leave Report</li>
</ol>

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Report Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="report_employee_leave.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="employee_id" class="form-label">Employee</label>
                        <select class="form-select" id="employee_id" name="employee_id">
                            <option value="all" <?php echo $selectedEmployeeId === 'all' ? 'selected' : ''; ?>>All Employees</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['employee_id']; ?>" <?php echo $selectedEmployeeId == $employee['employee_id'] ? 'selected' : ''; ?>><?php echo $employee['employee_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="leave_type_id" class="form-label">Leave Type</label>
                        <select class="form-select" id="leave_type_id" name="leave_type_id">
                            <option value="all" <?php echo $selectedLeaveTypeId === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <?php foreach ($leaveTypes as $type): ?>
                                <option value="<?php echo $type['leave_type_id']; ?>" <?php echo $selectedLeaveTypeId == $type['leave_type_id'] ? 'selected' : ''; ?>><?php echo $type['leave_type_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo $selectedStatus === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Approve" <?php echo $selectedStatus === 'Approve' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Pending" <?php echo $selectedStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Reject" <?php echo $selectedStatus === 'Reject' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="report_employee_leave.php" class="btn btn-secondary ms-2">Reset</a>
                        <button type="button" id="exportPdfBtn" class="btn btn-danger ms-2">Export to PDF</button>
                        <button type="button" id="exportExcelBtn" class="btn btn-success ms-2">Export to Excel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Leave Requests</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalLeaves; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Days</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalDays; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Approved Leaves</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $approvedCount; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Leaves</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pendingCount; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" id="reportContent">
    <?php if (!empty($leaveData)): ?>
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i> Leave Applications</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="leaveTable">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Leave Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaveData as $leave): ?>
                                <tr>
                                    <td><?php echo $leave['employee_name']; ?> (<?php echo $leave['employee_unique_code']; ?>)</td>
                                    <td><?php echo $leave['department_name']; ?></td>
                                    <td><?php echo $leave['leave_type_name']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($leave['leave_start_date'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($leave['leave_end_date'])); ?></td>
                                    <td><?php echo $leave['days_count']; ?></td>
                                    <td>
                                        <?php if ($leave['leave_status'] == 'Approve'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php elseif ($leave['leave_status'] == 'Pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view_leave_details.php?leave_id=<?php echo $leave['leave_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Leave Type Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="leaveTypeChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Monthly Leave Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="col-lg-12 mb-4">
        <div class="alert alert-info">
            No leave applications found with the selected filters.
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df;
}
.border-left-success {
    border-left: 4px solid #1cc88a;
}
.border-left-info {
    border-left: 4px solid #36b9cc;
}
.border-left-warning {
    border-left: 4px solid #f6c23e;
}
@media print {
    .no-print {
        display: none !important;
    }
    table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    th, td {
        border: 1px solid #ddd !important;
        padding: 8px !important;
    }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($leaveData)): ?>
    // Prepare data for charts
    const leaveTypeLabels = <?php echo json_encode(array_keys($leaveTypeCount)); ?>;
    const leaveTypeData = <?php echo json_encode(array_values($leaveTypeCount)); ?>;
    
    // Monthly data processing
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const monthlyData = Array(12).fill(0);
    
    <?php 
    // Generate JS code for monthly data
    echo "const leavesByMonth = {\n";
    foreach ($leaveData as $leave) {
        echo "    '" . date('n', strtotime($leave['leave_start_date'])) . "': ('" . date('n', strtotime($leave['leave_start_date'])) . "' in leavesByMonth ? leavesByMonth['" . date('n', strtotime($leave['leave_start_date'])) . "'] + 1 : 1),\n";
    }
    echo "};\n";
    ?>
    
    for (let i = 1; i <= 12; i++) {
        monthlyData[i-1] = leavesByMonth[i] || 0;
    }
    
    // Leave Type Distribution Chart
    const leaveTypeCtx = document.getElementById('leaveTypeChart').getContext('2d');
    const leaveTypeChart = new Chart(leaveTypeCtx, {
        type: 'pie',
        data: {
            labels: leaveTypeLabels,
            datasets: [{
                data: leaveTypeData,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
    
    // Monthly Leave Distribution Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyChart = new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Leave Applications',
                data: monthlyData,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
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
            }
        }
    });
    <?php endif; ?>
    
    // Export to PDF
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const reportTitle = 'Employee Leave Report';
        const dateRange = `${document.getElementById('start_date').value} to ${document.getElementById('end_date').value}`;
        const reportContent = document.getElementById('reportContent');
        
        const opt = {
            margin: 10,
            filename: 'employee_leave_report.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };
        
        const headerHtml = `
            <div style="text-align: center; margin-bottom: 20px;">
                <h2>${reportTitle}</h2>
                <p>Date Range: ${dateRange}</p>
            </div>
        `;
        
        const element = document.createElement('div');
        element.innerHTML = headerHtml;
        element.appendChild(reportContent.cloneNode(true));
        
        html2pdf().set(opt).from(element).save();
    });
    
    // Export to Excel
    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('leaveTable');
        if (table) {
            const ws = XLSX.utils.table_to_sheet(table);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Employee Leave Report');
            XLSX.writeFile(wb, 'employee_leave_report.xlsx');
        } else {
            alert('No data to export');
        }
    });
});
</script>

<?php include('footer.php'); ?> 