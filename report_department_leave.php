<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

// Get all departments
$deptStmt = $pdo->query("SELECT department_id, department_name FROM elms_department ORDER BY department_name");
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all leave types for the filter
$leaveTypeStmt = $pdo->query("SELECT leave_type_id, leave_type_name FROM elms_leave_type ORDER BY leave_type_name");
$leaveTypes = $leaveTypeStmt->fetchAll(PDO::FETCH_ASSOC);

// Default date range (current year)
$currentYear = date('Y');
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : "$currentYear-01-01";
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$selectedDeptId = isset($_GET['department_id']) ? $_GET['department_id'] : 'all';
$selectedLeaveTypeId = isset($_GET['leave_type_id']) ? $_GET['leave_type_id'] : 'all';
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build the query based on filters
$params = [];
$whereConditions = ["l.leave_start_date >= :start_date", "l.leave_end_date <= :end_date"];
$params[':start_date'] = $startDate;
$params[':end_date'] = $endDate;

if ($selectedDeptId !== 'all') {
    $whereConditions[] = "e.employee_department = :department_id";
    $params[':department_id'] = $selectedDeptId;
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

// Query to get department-wise leave data
$query = "
    SELECT 
        d.department_name,
        COUNT(l.leave_id) as total_leaves,
        SUM(DATEDIFF(l.leave_end_date, l.leave_start_date) + 1) as total_days,
        COUNT(CASE WHEN l.leave_status = 'Approve' THEN 1 END) as approved,
        COUNT(CASE WHEN l.leave_status = 'Pending' THEN 1 END) as pending,
        COUNT(CASE WHEN l.leave_status = 'Reject' THEN 1 END) as rejected,
        COUNT(DISTINCT e.employee_id) as employees_on_leave
    FROM 
        elms_leave l
    JOIN 
        elms_employee e ON l.employee_id = e.employee_id
    JOIN 
        elms_department d ON e.employee_department = d.department_id
    JOIN 
        elms_leave_type lt ON l.leave_type = lt.leave_type_id
    $whereClause
    GROUP BY 
        d.department_id
    ORDER BY 
        d.department_name
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$departmentData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get overall totals
$totalQuery = "
    SELECT 
        COUNT(l.leave_id) as total_leaves,
        SUM(DATEDIFF(l.leave_end_date, l.leave_start_date) + 1) as total_days,
        COUNT(CASE WHEN l.leave_status = 'Approve' THEN 1 END) as approved,
        COUNT(CASE WHEN l.leave_status = 'Pending' THEN 1 END) as pending,
        COUNT(CASE WHEN l.leave_status = 'Reject' THEN 1 END) as rejected,
        COUNT(DISTINCT e.employee_id) as employees_on_leave
    FROM 
        elms_leave l
    JOIN 
        elms_employee e ON l.employee_id = e.employee_id
    JOIN 
        elms_department d ON e.employee_department = d.department_id
    JOIN 
        elms_leave_type lt ON l.leave_type = lt.leave_type_id
    $whereClause
";

$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->execute($params);
$totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

// Get leave type breakdown for chart
$leaveTypeBreakdownQuery = "
    SELECT 
        lt.leave_type_name,
        COUNT(l.leave_id) as count,
        d.department_name
    FROM 
        elms_leave l
    JOIN 
        elms_employee e ON l.employee_id = e.employee_id
    JOIN 
        elms_department d ON e.employee_department = d.department_id
    JOIN 
        elms_leave_type lt ON l.leave_type = lt.leave_type_id
    $whereClause
    GROUP BY 
        d.department_id, lt.leave_type_id
    ORDER BY 
        d.department_name, lt.leave_type_name
";

$leaveTypeBreakdownStmt = $pdo->prepare($leaveTypeBreakdownQuery);
$leaveTypeBreakdownStmt->execute($params);
$leaveTypeBreakdown = $leaveTypeBreakdownStmt->fetchAll(PDO::FETCH_ASSOC);

// Process data for charts
$departmentNames = [];
$approvedData = [];
$pendingData = [];
$rejectedData = [];

foreach ($departmentData as $dept) {
    $departmentNames[] = $dept['department_name'];
    $approvedData[] = $dept['approved'];
    $pendingData[] = $dept['pending'];
    $rejectedData[] = $dept['rejected'];
}

// Prepare data for leave type breakdown chart
$departmentLeaveTypeData = [];
foreach ($leaveTypeBreakdown as $breakdown) {
    if (!isset($departmentLeaveTypeData[$breakdown['department_name']])) {
        $departmentLeaveTypeData[$breakdown['department_name']] = [];
    }
    $departmentLeaveTypeData[$breakdown['department_name']][$breakdown['leave_type_name']] = $breakdown['count'];
}

include('header.php');
?>

<h1 class="mt-4">Department Leave Usage Report</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
    <li class="breadcrumb-item active">Department Leave Usage</li>
</ol>

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Report Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="report_department_leave.php" class="row g-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="all" <?php echo $selectedDeptId === 'all' ? 'selected' : ''; ?>>All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department_id']; ?>" <?php echo $selectedDeptId == $dept['department_id'] ? 'selected' : ''; ?>><?php echo $dept['department_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
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
                        <a href="report_department_leave.php" class="btn btn-secondary ms-2">Reset</a>
                        <button type="button" id="exportPdfBtn" class="btn btn-danger ms-2">Export to PDF</button>
                        <button type="button" id="exportExcelBtn" class="btn btn-success ms-2">Export to Excel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row" id="reportContent">
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i> Department Leave Summary</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="departmentTable">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Total Leave Applications</th>
                                <th>Total Leave Days</th>
                                <th>Approved</th>
                                <th>Pending</th>
                                <th>Rejected</th>
                                <th>Employees on Leave</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($departmentData)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No data available for the selected filters</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($departmentData as $dept): ?>
                                    <tr>
                                        <td><?php echo $dept['department_name']; ?></td>
                                        <td><?php echo $dept['total_leaves']; ?></td>
                                        <td><?php echo $dept['total_days']; ?></td>
                                        <td><?php echo $dept['approved']; ?></td>
                                        <td><?php echo $dept['pending']; ?></td>
                                        <td><?php echo $dept['rejected']; ?></td>
                                        <td><?php echo $dept['employees_on_leave']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-primary fw-bold">
                                    <td>All Departments</td>
                                    <td><?php echo $totals['total_leaves']; ?></td>
                                    <td><?php echo $totals['total_days']; ?></td>
                                    <td><?php echo $totals['approved']; ?></td>
                                    <td><?php echo $totals['pending']; ?></td>
                                    <td><?php echo $totals['rejected']; ?></td>
                                    <td><?php echo $totals['employees_on_leave']; ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($departmentData)): ?>
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Leave Status by Department</h5>
            </div>
            <div class="card-body">
                <canvas id="leaveStatusChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-pie-chart me-2"></i> Total Leave Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="leaveDaysChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        margin-bottom: 20px !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
        padding: 10px !important;
    }
    
    table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    
    th, td {
        border: 1px solid #ddd !important;
        padding: 8px !important;
    }
    
    .table-primary {
        background-color: #cfe2ff !important;
    }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($departmentData)): ?>
    // Leave Status by Department Chart
    const leaveStatusCtx = document.getElementById('leaveStatusChart').getContext('2d');
    const leaveStatusChart = new Chart(leaveStatusCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($departmentNames); ?>,
            datasets: [
                {
                    label: 'Approved',
                    data: <?php echo json_encode($approvedData); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Pending',
                    data: <?php echo json_encode($pendingData); ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.7)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Rejected',
                    data: <?php echo json_encode($rejectedData); ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Leave Days by Department Chart
    const leaveDaysCtx = document.getElementById('leaveDaysChart').getContext('2d');
    const leaveDaysChart = new Chart(leaveDaysCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($departmentNames); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($departmentData, 'total_days')); ?>,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(220, 53, 69, 0.7)',
                    'rgba(0, 123, 255, 0.7)'
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
                    text: 'Total Leave Days by Department'
                }
            }
        }
    });
    <?php endif; ?>
    
    // Export to PDF
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const reportTitle = 'Department Leave Usage Report';
        const dateRange = `${document.getElementById('start_date').value} to ${document.getElementById('end_date').value}`;
        const reportContent = document.getElementById('reportContent');
        
        const opt = {
            margin: 10,
            filename: 'department_leave_report.pdf',
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
        const table = document.getElementById('departmentTable');
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Department Leave Report');
        XLSX.writeFile(wb, 'department_leave_report.xlsx');
    });
});
</script>

<?php include('footer.php'); ?> 