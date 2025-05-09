<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

// Get all leave types
$leaveTypeStmt = $pdo->query("SELECT leave_type_id, leave_type_name FROM elms_leave_type ORDER BY leave_type_name");
$leaveTypes = $leaveTypeStmt->fetchAll(PDO::FETCH_ASSOC);

// Default date range (current year)
$currentYear = date('Y');
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : "$currentYear-01-01";
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$selectedLeaveTypeId = isset($_GET['leave_type_id']) ? $_GET['leave_type_id'] : 'all';

// Build the query based on filters
$params = [];
$whereConditions = ["l.leave_start_date >= :start_date", "l.leave_end_date <= :end_date"];
$params[':start_date'] = $startDate;
$params[':end_date'] = $endDate;

if ($selectedLeaveTypeId !== 'all') {
    $whereConditions[] = "l.leave_type = :leave_type_id";
    $params[':leave_type_id'] = $selectedLeaveTypeId;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Query to get leave type usage data
$query = "
    SELECT 
        lt.leave_type_id,
        lt.leave_type_name,
        lt.days_allowed,
        COUNT(l.leave_id) as total_applications,
        SUM(DATEDIFF(l.leave_end_date, l.leave_start_date) + 1) as total_days_taken,
        COUNT(CASE WHEN l.leave_status = 'Approve' THEN 1 END) as approved,
        COUNT(CASE WHEN l.leave_status = 'Pending' THEN 1 END) as pending,
        COUNT(CASE WHEN l.leave_status = 'Reject' THEN 1 END) as rejected,
        COUNT(DISTINCT l.employee_id) as unique_employees
    FROM 
        elms_leave_type lt
    LEFT JOIN 
        elms_leave l ON lt.leave_type_id = l.leave_type
        AND l.leave_start_date >= :start_date 
        AND l.leave_end_date <= :end_date
    GROUP BY 
        lt.leave_type_id
    ORDER BY 
        total_applications DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$leaveTypeData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate monthly trends for selected leave type or all types
$monthlyQuery = "
    SELECT 
        MONTH(l.leave_start_date) as month,
        lt.leave_type_name,
        COUNT(l.leave_id) as count
    FROM 
        elms_leave l
    JOIN 
        elms_leave_type lt ON l.leave_type = lt.leave_type_id
    $whereClause
    GROUP BY 
        MONTH(l.leave_start_date), lt.leave_type_id
    ORDER BY 
        MONTH(l.leave_start_date), lt.leave_type_name
";

$monthlyStmt = $pdo->prepare($monthlyQuery);
$monthlyStmt->execute($params);
$monthlyData = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

// Get department-wise breakdown
$deptQuery = "
    SELECT 
        d.department_name,
        lt.leave_type_name,
        COUNT(l.leave_id) as count
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
        d.department_name, count DESC
";

$deptStmt = $pdo->prepare($deptQuery);
$deptStmt->execute($params);
$departmentData = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

include('header.php');
?>

<h1 class="mt-4">Leave Type Analysis Report</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
    <li class="breadcrumb-item active">Leave Type Analysis</li>
</ol>

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Report Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="report_leave_type.php" class="row g-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="leave_type_id" class="form-label">Leave Type</label>
                        <select class="form-select" id="leave_type_id" name="leave_type_id">
                            <option value="all" <?php echo $selectedLeaveTypeId === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <?php foreach ($leaveTypes as $type): ?>
                                <option value="<?php echo $type['leave_type_id']; ?>" <?php echo $selectedLeaveTypeId == $type['leave_type_id'] ? 'selected' : ''; ?>><?php echo $type['leave_type_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="report_leave_type.php" class="btn btn-secondary ms-2">Reset</a>
                    </div>
                    <div class="col-md-12 text-end mt-3">
                        <button type="button" id="exportPdfBtn" class="btn btn-danger">Export to PDF</button>
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
                <h5 class="mb-0"><i class="fas fa-table me-2"></i> Leave Type Summary</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="leaveTypeTable">
                        <thead>
                            <tr>
                                <th>Leave Type</th>
                                <th>Allocated Days</th>
                                <th>Applications</th>
                                <th>Total Days Taken</th>
                                <th>Approved</th>
                                <th>Pending</th>
                                <th>Rejected</th>
                                <th>Employees Using</th>
                                <th>Utilization Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaveTypeData as $type): ?>
                                <?php 
                                // Calculate utilization rate
                                $utilization = ($type['total_days_taken'] / ($type['days_allowed'] * $type['unique_employees'])) * 100;
                                $utilization = is_nan($utilization) || is_infinite($utilization) ? 0 : $utilization;
                                $utilization = min(round($utilization), 100); // Cap at 100%
                                
                                // Determine color based on utilization
                                $colorClass = $utilization < 30 ? 'success' : ($utilization < 70 ? 'warning' : 'danger');
                                ?>
                                <tr>
                                    <td><?php echo $type['leave_type_name']; ?></td>
                                    <td><?php echo $type['days_allowed']; ?></td>
                                    <td><?php echo $type['total_applications']; ?></td>
                                    <td><?php echo $type['total_days_taken']; ?></td>
                                    <td><?php echo $type['approved']; ?></td>
                                    <td><?php echo $type['pending']; ?></td>
                                    <td><?php echo $type['rejected']; ?></td>
                                    <td><?php echo $type['unique_employees']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                <div class="progress-bar bg-<?php echo $colorClass; ?>" role="progressbar" style="width: <?php echo $utilization; ?>%;" aria-valuenow="<?php echo $utilization; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span><?php echo $utilization; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Leave Type Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="leaveTypeChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Monthly Trends</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-building me-2"></i> Department-wise Usage</h5>
            </div>
            <div class="card-body">
                <canvas id="departmentChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
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
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for Leave Type Distribution chart
    const leaveTypes = <?php echo json_encode(array_column($leaveTypeData, 'leave_type_name')); ?>;
    const applications = <?php echo json_encode(array_column($leaveTypeData, 'total_applications')); ?>;
    const daysTaken = <?php echo json_encode(array_column($leaveTypeData, 'total_days_taken')); ?>;
    
    // Leave Type Distribution Chart
    const leaveTypeCtx = document.getElementById('leaveTypeChart').getContext('2d');
    const leaveTypeChart = new Chart(leaveTypeCtx, {
        type: 'pie',
        data: {
            labels: leaveTypes,
            datasets: [{
                data: applications,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(201, 203, 207, 0.7)'
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
                    text: 'Leave Applications by Type'
                }
            }
        }
    });
    
    // Process monthly data
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    // Group monthly data by leave type
    const monthlyLeaveData = {};
    <?php foreach ($monthlyData as $data): ?>
        if (!monthlyLeaveData['<?php echo $data['leave_type_name']; ?>']) {
            monthlyLeaveData['<?php echo $data['leave_type_name']; ?>'] = Array(12).fill(0);
        }
        monthlyLeaveData['<?php echo $data['leave_type_name']; ?>'][<?php echo $data['month']-1; ?>] = <?php echo $data['count']; ?>;
    <?php endforeach; ?>
    
    // Create datasets for monthly chart
    const monthlyDatasets = [];
    const colors = [
        'rgba(54, 162, 235, 0.7)',
        'rgba(255, 99, 132, 0.7)',
        'rgba(255, 206, 86, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(255, 159, 64, 0.7)',
        'rgba(201, 203, 207, 0.7)'
    ];
    
    let colorIndex = 0;
    for (const [leaveType, data] of Object.entries(monthlyLeaveData)) {
        monthlyDatasets.push({
            label: leaveType,
            data: data,
            backgroundColor: colors[colorIndex % colors.length],
            borderColor: colors[colorIndex % colors.length].replace('0.7', '1'),
            borderWidth: 1
        });
        colorIndex++;
    }
    
    // Monthly Trend Chart
    const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    const monthlyTrendChart = new Chart(monthlyTrendCtx, {
        type: 'line',
        data: {
            labels: months,
            datasets: monthlyDatasets
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Monthly Leave Applications'
                }
            },
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
    
    // Process department data
    const departmentLabels = [];
    const departmentDatasets = [];
    
    // Group department data by leave type
    const deptLeaveData = {};
    <?php foreach ($departmentData as $data): ?>
        if (!departmentLabels.includes('<?php echo $data['department_name']; ?>')) {
            departmentLabels.push('<?php echo $data['department_name']; ?>');
        }
        
        if (!deptLeaveData['<?php echo $data['leave_type_name']; ?>']) {
            deptLeaveData['<?php echo $data['leave_type_name']; ?>'] = {};
        }
        
        deptLeaveData['<?php echo $data['leave_type_name']; ?>']['<?php echo $data['department_name']; ?>'] = <?php echo $data['count']; ?>;
    <?php endforeach; ?>
    
    // Create datasets for department chart
    colorIndex = 0;
    for (const [leaveType, deptData] of Object.entries(deptLeaveData)) {
        const dataArray = [];
        
        for (const dept of departmentLabels) {
            dataArray.push(deptData[dept] || 0);
        }
        
        departmentDatasets.push({
            label: leaveType,
            data: dataArray,
            backgroundColor: colors[colorIndex % colors.length],
            borderColor: colors[colorIndex % colors.length].replace('0.7', '1'),
            borderWidth: 1
        });
        
        colorIndex++;
    }
    
    // Department Chart
    const departmentCtx = document.getElementById('departmentChart').getContext('2d');
    const departmentChart = new Chart(departmentCtx, {
        type: 'bar',
        data: {
            labels: departmentLabels,
            datasets: departmentDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Leave Usage by Department'
                }
            },
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
    
    // Export to PDF
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const reportTitle = 'Leave Type Analysis Report';
        const dateRange = `${document.getElementById('start_date').value} to ${document.getElementById('end_date').value}`;
        const reportContent = document.getElementById('reportContent');
        
        const opt = {
            margin: 10,
            filename: 'leave_type_analysis_report.pdf',
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
        const table = document.getElementById('leaveTypeTable');
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Leave Type Analysis');
        XLSX.writeFile(wb, 'leave_type_analysis_report.xlsx');
    });
});
</script>

<?php include('footer.php'); ?> 