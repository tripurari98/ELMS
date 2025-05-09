<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

// Get all departments for filter
$deptStmt = $pdo->query("SELECT department_id, department_name FROM elms_department ORDER BY department_name");
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all leave types
$leaveTypeStmt = $pdo->query("SELECT leave_type_id, leave_type_name FROM elms_leave_type ORDER BY leave_type_name");
$leaveTypes = $leaveTypeStmt->fetchAll(PDO::FETCH_ASSOC);

// Apply filters
$selectedDeptId = isset($_GET['department_id']) ? $_GET['department_id'] : 'all';
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build the query based on filters
$whereConditions = ["e.employee_status = 'Active'"]; // Only show active employees by default
$params = [];

if ($selectedDeptId !== 'all') {
    $whereConditions[] = "e.employee_department = :department_id";
    $params[':department_id'] = $selectedDeptId;
}

if ($selectedStatus !== 'all') {
    $whereConditions[0] = "e.employee_status = :status"; // Replace the default condition
    $params[':status'] = $selectedStatus;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Fetch employees with their department info
$query = "
    SELECT 
        e.employee_id,
        e.employee_unique_code,
        e.employee_first_name,
        e.employee_last_name,
        e.employee_email,
        e.employee_status,
        d.department_name
    FROM 
        elms_employee e
    LEFT JOIN 
        elms_department d ON e.employee_department = d.department_id
    $whereClause
    ORDER BY 
        e.employee_first_name, e.employee_last_name
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch leave balances for all employees
$balancesQuery = "
    SELECT 
        lb.employee_id,
        lt.leave_type_name,
        lb.leave_balance,
        lt.days_allowed
    FROM 
        elms_leave_balance lb
    JOIN 
        elms_leave_type lt ON lb.leave_type_id = lt.leave_type_id
    ORDER BY 
        lb.employee_id, lt.leave_type_name
";

$balancesStmt = $pdo->query($balancesQuery);
$allBalances = $balancesStmt->fetchAll(PDO::FETCH_ASSOC);

// Group balances by employee
$employeeBalances = [];
foreach ($allBalances as $balance) {
    $employeeId = $balance['employee_id'];
    if (!isset($employeeBalances[$employeeId])) {
        $employeeBalances[$employeeId] = [];
    }
    $employeeBalances[$employeeId][] = $balance;
}

// Calculate statistics for summary cards
$totalEmployees = count($employees);
$totalLeaveTypes = count($leaveTypes);

// Calculate employees with low balances (less than 30% of allowed days)
$lowBalanceEmployees = 0;
$lowBalanceCount = 0;
$totalBalancePercentage = 0;
$balanceCount = 0;

foreach ($allBalances as $balance) {
    $percentage = ($balance['leave_balance'] / $balance['days_allowed']) * 100;
    $totalBalancePercentage += $percentage;
    $balanceCount++;
    
    if ($percentage < 30) {
        $lowBalanceCount++;
        // Count unique employees with low balance
        if (!isset($lowBalanceEmployeeIds[$balance['employee_id']])) {
            $lowBalanceEmployeeIds[$balance['employee_id']] = true;
            $lowBalanceEmployees++;
        }
    }
}

$averageBalancePercentage = $balanceCount > 0 ? round($totalBalancePercentage / $balanceCount) : 0;

include('header.php');
?>

<h1 class="mt-4">Leave Balance Report</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
    <li class="breadcrumb-item active">Leave Balance Report</li>
</ol>

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Report Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="report_leave_balance.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="all" <?php echo $selectedDeptId === 'all' ? 'selected' : ''; ?>>All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department_id']; ?>" <?php echo $selectedDeptId == $dept['department_id'] ? 'selected' : ''; ?>><?php echo $dept['department_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Employee Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo $selectedStatus === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Active" <?php echo $selectedStatus === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $selectedStatus === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="report_leave_balance.php" class="btn btn-secondary ms-2">Reset</a>
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
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Employees</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalEmployees; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Average Leave Balance</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $averageBalancePercentage; ?>%</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low Balance Employees</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $lowBalanceEmployees; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Leave Types</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalLeaveTypes; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" id="reportContent">
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i> Employee Leave Balances</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="balanceTable">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Status</th>
                                <?php foreach ($leaveTypes as $type): ?>
                                    <th><?php echo $type['leave_type_name']; ?></th>
                                <?php endforeach; ?>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="<?php echo 5 + count($leaveTypes); ?>" class="text-center">No employees found with the selected filters</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td><?php echo $employee['employee_unique_code']; ?></td>
                                        <td><?php echo $employee['employee_first_name'] . ' ' . $employee['employee_last_name']; ?></td>
                                        <td><?php echo $employee['department_name'] ?? 'Not Assigned'; ?></td>
                                        <td>
                                            <?php if ($employee['employee_status'] == 'Active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <?php foreach ($leaveTypes as $type): ?>
                                            <td>
                                                <?php
                                                $balanceFound = false;
                                                if (isset($employeeBalances[$employee['employee_id']])) {
                                                    foreach ($employeeBalances[$employee['employee_id']] as $balance) {
                                                        if ($balance['leave_type_name'] == $type['leave_type_name']) {
                                                            $percentage = ($balance['leave_balance'] / $balance['days_allowed']) * 100;
                                                            $colorClass = $percentage > 50 ? 'success' : ($percentage > 30 ? 'warning' : 'danger');
                                                            
                                                            echo "<div class='d-flex align-items-center'>";
                                                            echo "<div class='flex-grow-1 me-2'>";
                                                            echo "<div class='progress' style='height: 10px;'>";
                                                            echo "<div class='progress-bar bg-$colorClass' role='progressbar' style='width: $percentage%;' aria-valuenow='$percentage' aria-valuemin='0' aria-valuemax='100'></div>";
                                                            echo "</div>";
                                                            echo "</div>";
                                                            echo "<span class='small'>" . $balance['leave_balance'] . "/" . $balance['days_allowed'] . "</span>";
                                                            echo "</div>";
                                                            
                                                            $balanceFound = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                                
                                                if (!$balanceFound) {
                                                    echo "<span class='text-muted'>N/A</span>";
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                        
                                        <td>
                                            <a href="report_employee_leave.php?employee_id=<?php echo $employee['employee_id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df;
}
.border-left-success {
    border-left: 4px solid #1cc88a;
}
.border-left-warning {
    border-left: 4px solid #f6c23e;
}
.border-left-info {
    border-left: 4px solid #36b9cc;
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export to PDF
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const reportTitle = 'Leave Balance Report';
        const reportContent = document.getElementById('reportContent');
        
        const opt = {
            margin: 10,
            filename: 'leave_balance_report.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };
        
        const headerHtml = `
            <div style="text-align: center; margin-bottom: 20px;">
                <h2>${reportTitle}</h2>
                <p>Generated on: ${new Date().toLocaleDateString()}</p>
            </div>
        `;
        
        const element = document.createElement('div');
        element.innerHTML = headerHtml;
        element.appendChild(reportContent.cloneNode(true));
        
        html2pdf().set(opt).from(element).save();
    });
    
    // Export to Excel
    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('balanceTable');
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Leave Balance Report');
        XLSX.writeFile(wb, 'leave_balance_report.xlsx');
    });
});
</script>

<?php include('footer.php'); ?> 