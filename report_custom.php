<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

// Get all departments for filter
$deptStmt = $pdo->query("SELECT department_id, department_name FROM elms_department ORDER BY department_name");
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all leave types for filter
$leaveTypeStmt = $pdo->query("SELECT leave_type_id, leave_type_name FROM elms_leave_type ORDER BY leave_type_name");
$leaveTypes = $leaveTypeStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all employees for filter
$employeeStmt = $pdo->query("
    SELECT e.employee_id, CONCAT(e.employee_first_name, ' ', e.employee_last_name) as employee_name 
    FROM elms_employee e 
    WHERE e.employee_status = 'Active'
    ORDER BY e.employee_first_name
");
$employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
$reportData = [];
$reportGenerated = false;
$columnNames = [];
$selectedColumns = [];

if (isset($_GET['generate_report'])) {
    $reportGenerated = true;
    
    // Get filter values
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    $selectedDeptId = isset($_GET['department_id']) ? $_GET['department_id'] : 'all';
    $selectedLeaveTypeId = isset($_GET['leave_type_id']) ? $_GET['leave_type_id'] : 'all';
    $selectedEmployeeId = isset($_GET['employee_id']) ? $_GET['employee_id'] : 'all';
    $selectedStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
    $groupBy = isset($_GET['group_by']) ? $_GET['group_by'] : 'none';
    
    // Get selected columns
    $selectedColumns = isset($_GET['columns']) ? $_GET['columns'] : ['employee_name', 'department_name', 'leave_type_name', 'leave_start_date', 'leave_end_date', 'leave_status'];
    
    // Build column definitions
    $columnDefs = [
        'employee_name' => "CONCAT(e.employee_first_name, ' ', e.employee_last_name) as employee_name",
        'employee_id' => "e.employee_unique_code as employee_id",
        'department_name' => "d.department_name",
        'leave_type_name' => "lt.leave_type_name",
        'leave_days' => "DATEDIFF(l.leave_end_date, l.leave_start_date) + 1 as leave_days",
        'leave_start_date' => "DATE_FORMAT(l.leave_start_date, '%d %b %Y') as leave_start_date",
        'leave_end_date' => "DATE_FORMAT(l.leave_end_date, '%d %b %Y') as leave_end_date",
        'leave_application_date' => "DATE_FORMAT(l.leave_application_date, '%d %b %Y') as leave_application_date",
        'leave_status' => "l.leave_status",
        'leave_reason' => "l.leave_reason",
        'admin_remark' => "l.admin_remark"
    ];
    
    // Prepare column list for query
    $selectColumns = [];
    foreach ($selectedColumns as $col) {
        if (isset($columnDefs[$col])) {
            $selectColumns[] = $columnDefs[$col];
            $columnNames[$col] = str_replace('_', ' ', ucfirst($col));
        }
    }
    
    // Build query
    $select = implode(", ", $selectColumns);
    
    // Add group by and aggregates if necessary
    $groupByClause = '';
    if ($groupBy !== 'none') {
        switch ($groupBy) {
            case 'department':
                $groupByClause = "GROUP BY d.department_id";
                break;
            case 'leave_type':
                $groupByClause = "GROUP BY lt.leave_type_id";
                break;
            case 'status':
                $groupByClause = "GROUP BY l.leave_status";
                break;
            case 'month':
                $select .= ", DATE_FORMAT(l.leave_start_date, '%b %Y') as month";
                $groupByClause = "GROUP BY DATE_FORMAT(l.leave_start_date, '%Y%m')";
                $columnNames['month'] = 'Month';
                break;
        }
    }
    
    // Build where clause
    $whereConditions = [];
    $params = [];
    
    if (!empty($startDate)) {
        $whereConditions[] = "l.leave_start_date >= :start_date";
        $params[':start_date'] = $startDate;
    }
    
    if (!empty($endDate)) {
        $whereConditions[] = "l.leave_end_date <= :end_date";
        $params[':end_date'] = $endDate;
    }
    
    if ($selectedDeptId !== 'all') {
        $whereConditions[] = "e.employee_department = :department_id";
        $params[':department_id'] = $selectedDeptId;
    }
    
    if ($selectedLeaveTypeId !== 'all') {
        $whereConditions[] = "l.leave_type = :leave_type_id";
        $params[':leave_type_id'] = $selectedLeaveTypeId;
    }
    
    if ($selectedEmployeeId !== 'all') {
        $whereConditions[] = "l.employee_id = :employee_id";
        $params[':employee_id'] = $selectedEmployeeId;
    }
    
    if ($selectedStatus !== 'all') {
        $whereConditions[] = "l.leave_status = :status";
        $params[':status'] = $selectedStatus;
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Build and execute the final query
    $query = "
        SELECT 
            $select
        FROM 
            elms_leave l
        JOIN 
            elms_employee e ON l.employee_id = e.employee_id
        JOIN 
            elms_department d ON e.employee_department = d.department_id
        JOIN 
            elms_leave_type lt ON l.leave_type = lt.leave_type_id
        $whereClause
        $groupByClause
        ORDER BY 
            l.leave_start_date DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include('header.php');
?>

<h1 class="mt-4">Custom Report Generator</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
    <li class="breadcrumb-item active">Custom Report</li>
</ol>

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i> Report Configuration</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="report_custom.php" class="row g-3">
                    <div class="col-md-12 mb-3">
                        <h5>Filters</h5>
                        <hr>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="all">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department_id']; ?>" <?php echo (isset($_GET['department_id']) && $_GET['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>><?php echo $dept['department_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="leave_type_id" class="form-label">Leave Type</label>
                        <select class="form-select" id="leave_type_id" name="leave_type_id">
                            <option value="all">All Types</option>
                            <?php foreach ($leaveTypes as $type): ?>
                                <option value="<?php echo $type['leave_type_id']; ?>" <?php echo (isset($_GET['leave_type_id']) && $_GET['leave_type_id'] == $type['leave_type_id']) ? 'selected' : ''; ?>><?php echo $type['leave_type_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="employee_id" class="form-label">Employee</label>
                        <select class="form-select" id="employee_id" name="employee_id">
                            <option value="all">All Employees</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['employee_id']; ?>" <?php echo (isset($_GET['employee_id']) && $_GET['employee_id'] == $employee['employee_id']) ? 'selected' : ''; ?>><?php echo $employee['employee_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo (!isset($_GET['status']) || $_GET['status'] === 'all') ? 'selected' : ''; ?>>All Status</option>
                            <option value="Approve" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Approve') ? 'selected' : ''; ?>>Approved</option>
                            <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Reject" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Reject') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="group_by" class="form-label">Group By</label>
                        <select class="form-select" id="group_by" name="group_by">
                            <option value="none" <?php echo (!isset($_GET['group_by']) || $_GET['group_by'] === 'none') ? 'selected' : ''; ?>>No Grouping</option>
                            <option value="department" <?php echo (isset($_GET['group_by']) && $_GET['group_by'] === 'department') ? 'selected' : ''; ?>>Department</option>
                            <option value="leave_type" <?php echo (isset($_GET['group_by']) && $_GET['group_by'] === 'leave_type') ? 'selected' : ''; ?>>Leave Type</option>
                            <option value="status" <?php echo (isset($_GET['group_by']) && $_GET['group_by'] === 'status') ? 'selected' : ''; ?>>Status</option>
                            <option value="month" <?php echo (isset($_GET['group_by']) && $_GET['group_by'] === 'month') ? 'selected' : ''; ?>>Month</option>
                        </select>
                    </div>
                    
                    <div class="col-md-12 mb-3 mt-4">
                        <h5>Columns to Include</h5>
                        <hr>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="employee_name" id="col_employee_name" <?php echo (!isset($_GET['columns']) || in_array('employee_name', $_GET['columns'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="col_employee_name">Employee Name</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="employee_id" id="col_employee_id" <?php echo (isset($_GET['columns']) && in_array('employee_id', $_GET['columns'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="col_employee_id">Employee ID</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="department_name" id="col_department_name" <?php echo (!isset($_GET['columns']) || in_array('department_name', $_GET['columns'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="col_department_name">Department</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="leave_type_name" id="col_leave_type_name" <?php echo (!isset($_GET['columns']) || in_array('leave_type_name', $_GET['columns'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="col_leave_type_name">Leave Type</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="leave_days" id="col_leave_days" <?php echo (isset($_GET['columns']) && in_array('leave_days', $_GET['columns'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="col_leave_days">Leave Days</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="leave_start_date" id="col_leave_start_date" <?php echo (!isset($_GET['columns']) || in_array('leave_start_date', $_GET['columns'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="col_leave_start_date">Start Date</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="leave_end_date" id="col_leave_end_date" <?php echo (!isset($_GET['columns']) || in_array('leave_end_date', $_GET['columns'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="col_leave_end_date">End Date</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="leave_application_date" id="col_leave_application_date" <?php echo (isset($_GET['columns']) && in_array('leave_application_date', $_GET['columns'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="col_leave_application_date">Application Date</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="leave_status" id="col_leave_status" <?php echo (!isset($_GET['columns']) || in_array('leave_status', $_GET['columns'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="col_leave_status">Status</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="leave_reason" id="col_leave_reason" <?php echo (isset($_GET['columns']) && in_array('leave_reason', $_GET['columns'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="col_leave_reason">Reason</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="admin_remark" id="col_admin_remark" <?php echo (isset($_GET['columns']) && in_array('admin_remark', $_GET['columns'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="col_admin_remark">Admin Remark</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12 text-end mt-4">
                        <input type="hidden" name="generate_report" value="1">
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                        <a href="report_custom.php" class="btn btn-secondary ms-2">Reset</a>
                        <?php if ($reportGenerated && !empty($reportData)): ?>
                            <button type="button" id="exportPdfBtn" class="btn btn-danger ms-2">Export to PDF</button>
                            <button type="button" id="exportExcelBtn" class="btn btn-success ms-2">Export to Excel</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($reportGenerated): ?>
<div class="row" id="reportContent">
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i> Custom Report Results</h5>
            </div>
            <div class="card-body">
                <?php if (empty($reportData)): ?>
                    <div class="alert alert-info">
                        No data found with the selected filters.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="customReportTable">
                            <thead>
                                <tr>
                                    <?php foreach ($selectedColumns as $col): ?>
                                        <?php if (isset($columnNames[$col])): ?>
                                            <th><?php echo $columnNames[$col]; ?></th>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php if (isset($_GET['group_by']) && $_GET['group_by'] === 'month'): ?>
                                        <th>Month</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $row): ?>
                                    <tr>
                                        <?php foreach ($selectedColumns as $col): ?>
                                            <?php if (isset($row[$col])): ?>
                                                <td>
                                                    <?php 
                                                    if ($col === 'leave_status') {
                                                        $statusClass = '';
                                                        if ($row[$col] === 'Approve') {
                                                            $statusClass = 'bg-success';
                                                        } elseif ($row[$col] === 'Pending') {
                                                            $statusClass = 'bg-warning';
                                                        } else {
                                                            $statusClass = 'bg-danger';
                                                        }
                                                        echo "<span class='badge $statusClass'>{$row[$col]}</span>";
                                                    } else {
                                                        echo $row[$col];
                                                    }
                                                    ?>
                                                </td>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if (isset($_GET['group_by']) && $_GET['group_by'] === 'month' && isset($row['month'])): ?>
                                            <td><?php echo $row['month']; ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($reportGenerated && !empty($reportData)): ?>
    // Export to PDF
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const reportTitle = 'Custom Leave Report';
        const reportContent = document.getElementById('reportContent');
        
        const opt = {
            margin: 10,
            filename: 'custom_leave_report.pdf',
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
        const table = document.getElementById('customReportTable');
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Custom Report');
        XLSX.writeFile(wb, 'custom_leave_report.xlsx');
    });
    <?php endif; ?>
});
</script>

<?php include('footer.php'); ?> 