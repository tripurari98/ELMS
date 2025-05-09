<?php
require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

// Get date range parameters
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$currentYear = date('Y');
$startYear = $currentYear - 4; // For year dropdown, show 5 years

// Generate appropriate SQL based on selected period
switch ($period) {
    case 'weekly':
        $groupBy = "YEARWEEK(l.leave_start_date)";
        $dateFormat = "CONCAT('Week ', WEEK(l.leave_start_date), ' (', DATE_FORMAT(l.leave_start_date, '%Y'), ')')";
        $where = "YEAR(l.leave_start_date) = :year";
        break;
    case 'quarterly':
        $groupBy = "YEAR(l.leave_start_date), QUARTER(l.leave_start_date)";
        $dateFormat = "CONCAT('Q', QUARTER(l.leave_start_date), ' ', YEAR(l.leave_start_date))";
        $where = "YEAR(l.leave_start_date) = :year";
        break;
    case 'yearly':
        $groupBy = "YEAR(l.leave_start_date)";
        $dateFormat = "YEAR(l.leave_start_date)";
        $where = "YEAR(l.leave_start_date) >= :start_year AND YEAR(l.leave_start_date) <= :end_year";
        break;
    default: // monthly
        $groupBy = "YEAR(l.leave_start_date), MONTH(l.leave_start_date)";
        $dateFormat = "DATE_FORMAT(l.leave_start_date, '%b %Y')";
        $where = "YEAR(l.leave_start_date) = :year";
        break;
}

// Prepare and execute main query
$query = "
    SELECT 
        $dateFormat as period_label,
        COUNT(l.leave_id) as total_applications,
        SUM(DATEDIFF(l.leave_end_date, l.leave_start_date) + 1) as total_days,
        COUNT(CASE WHEN l.leave_status = 'Approve' THEN 1 END) as approved,
        COUNT(CASE WHEN l.leave_status = 'Pending' THEN 1 END) as pending,
        COUNT(CASE WHEN l.leave_status = 'Reject' THEN 1 END) as rejected,
        MIN(l.leave_start_date) as period_start,
        MAX(l.leave_start_date) as period_end
    FROM 
        elms_leave l
    WHERE 
        $where
    GROUP BY 
        $groupBy
    ORDER BY 
        MIN(l.leave_start_date)
";

$stmt = $pdo->prepare($query);

if ($period == 'yearly') {
    $endYear = $currentYear;
    $stmt->bindParam(':start_year', $startYear, PDO::PARAM_INT);
    $stmt->bindParam(':end_year', $endYear, PDO::PARAM_INT);
} else {
    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
}

$stmt->execute();
$timeData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get leave type breakdown for each period
$leaveTypeQuery = "
    SELECT 
        $dateFormat as period_label,
        lt.leave_type_name,
        COUNT(l.leave_id) as count
    FROM 
        elms_leave l
    JOIN 
        elms_leave_type lt ON l.leave_type = lt.leave_type_id
    WHERE 
        $where
    GROUP BY 
        $groupBy, lt.leave_type_id
    ORDER BY 
        MIN(l.leave_start_date), lt.leave_type_name
";

$typeStmt = $pdo->prepare($leaveTypeQuery);

if ($period == 'yearly') {
    $typeStmt->bindParam(':start_year', $startYear, PDO::PARAM_INT);
    $typeStmt->bindParam(':end_year', $endYear, PDO::PARAM_INT);
} else {
    $typeStmt->bindParam(':year', $year, PDO::PARAM_INT);
}

$typeStmt->execute();
$leaveTypeData = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

// Get department breakdown for each period
$deptQuery = "
    SELECT 
        $dateFormat as period_label,
        d.department_name,
        COUNT(l.leave_id) as count
    FROM 
        elms_leave l
    JOIN 
        elms_employee e ON l.employee_id = e.employee_id
    JOIN 
        elms_department d ON e.employee_department = d.department_id
    WHERE 
        $where
    GROUP BY 
        $groupBy, d.department_id
    ORDER BY 
        MIN(l.leave_start_date), d.department_name
";

$deptStmt = $pdo->prepare($deptQuery);

if ($period == 'yearly') {
    $deptStmt->bindParam(':start_year', $startYear, PDO::PARAM_INT);
    $deptStmt->bindParam(':end_year', $endYear, PDO::PARAM_INT);
} else {
    $deptStmt->bindParam(':year', $year, PDO::PARAM_INT);
}

$deptStmt->execute();
$departmentData = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// Process data for charts
$periods = array_column($timeData, 'period_label');
$applications = array_column($timeData, 'total_applications');
$totalDays = array_column($timeData, 'total_days');
$approved = array_column($timeData, 'approved');
$pending = array_column($timeData, 'pending');
$rejected = array_column($timeData, 'rejected');

// Group leave type data by period
$leaveTypesByPeriod = [];
foreach ($leaveTypeData as $data) {
    if (!isset($leaveTypesByPeriod[$data['period_label']])) {
        $leaveTypesByPeriod[$data['period_label']] = [];
    }
    $leaveTypesByPeriod[$data['period_label']][$data['leave_type_name']] = $data['count'];
}

// Group department data by period
$deptsByPeriod = [];
foreach ($departmentData as $data) {
    if (!isset($deptsByPeriod[$data['period_label']])) {
        $deptsByPeriod[$data['period_label']] = [];
    }
    $deptsByPeriod[$data['period_label']][$data['department_name']] = $data['count'];
}

include('header.php');
?>

<h1 class="mt-4">Time-based Leave Analysis</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
    <li class="breadcrumb-item active">Time-based Analysis</li>
</ol>

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Report Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="report_time_based.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="period" class="form-label">Time Period</label>
                        <select class="form-select" id="period" name="period">
                            <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            <option value="weekly" <?php echo $period === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="quarterly" <?php echo $period === 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                            <option value="yearly" <?php echo $period === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="yearSelector" <?php echo $period === 'yearly' ? 'style="display:none;"' : ''; ?>>
                        <label for="year" class="form-label">Year</label>
                        <select class="form-select" id="year" name="year">
                            <?php for ($i = $currentYear; $i >= $startYear; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo $year == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="report_time_based.php" class="btn btn-secondary ms-2">Reset</a>
                    </div>
                    <div class="col-md-2 d-flex align-items-end justify-content-end">
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
                <h5 class="mb-0"><i class="fas fa-table me-2"></i> <?php echo ucfirst($period); ?> Leave Summary</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="timeDataTable">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Total Applications</th>
                                <th>Total Days</th>
                                <th>Approved</th>
                                <th>Pending</th>
                                <th>Rejected</th>
                                <th>Approval Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($timeData)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No data available for the selected time period</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($timeData as $data): ?>
                                    <?php 
                                    $approvalRate = $data['total_applications'] > 0 ? round(($data['approved'] / $data['total_applications']) * 100) : 0;
                                    $rateClass = $approvalRate > 80 ? 'success' : ($approvalRate > 50 ? 'warning' : 'danger');
                                    ?>
                                    <tr>
                                        <td><?php echo $data['period_label']; ?></td>
                                        <td><?php echo $data['total_applications']; ?></td>
                                        <td><?php echo $data['total_days']; ?></td>
                                        <td><?php echo $data['approved']; ?></td>
                                        <td><?php echo $data['pending']; ?></td>
                                        <td><?php echo $data['rejected']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <div class="progress-bar bg-<?php echo $rateClass; ?>" role="progressbar" style="width: <?php echo $approvalRate; ?>%;" aria-valuenow="<?php echo $approvalRate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <span><?php echo $approvalRate; ?>%</span>
                                            </div>
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

    <?php if (!empty($timeData)): ?>
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> Leave Applications Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="applicationsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Leave Status by Period</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i> Leave Types Over Time</h5>
            </div>
            <div class="card-body">
                <canvas id="leaveTypeChart" style="height: 300px;"></canvas>
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
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle year selector based on period selection
    document.getElementById('period').addEventListener('change', function() {
        const yearSelector = document.getElementById('yearSelector');
        if (this.value === 'yearly') {
            yearSelector.style.display = 'none';
        } else {
            yearSelector.style.display = 'block';
        }
    });
    
    <?php if (!empty($timeData)): ?>
    // Applications Trend Chart
    const applicationsCtx = document.getElementById('applicationsChart').getContext('2d');
    const applicationsChart = new Chart(applicationsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($periods); ?>,
            datasets: [
                {
                    label: 'Applications',
                    data: <?php echo json_encode($applications); ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.1,
                    fill: true
                },
                {
                    label: 'Days',
                    data: <?php echo json_encode($totalDays); ?>,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    fill: true,
                    hidden: true
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Leave Applications Over Time'
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
    
    // Leave Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($periods); ?>,
            datasets: [
                {
                    label: 'Approved',
                    data: <?php echo json_encode($approved); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Pending',
                    data: <?php echo json_encode($pending); ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.7)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Rejected',
                    data: <?php echo json_encode($rejected); ?>,
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
    
    // Process leave type data for chart
    const allLeaveTypes = new Set();
    <?php foreach ($leaveTypeData as $data): ?>
        allLeaveTypes.add('<?php echo $data['leave_type_name']; ?>');
    <?php endforeach; ?>
    
    const leaveTypesArray = Array.from(allLeaveTypes);
    const leaveTypeDatasets = [];
    const colors = [
        'rgba(54, 162, 235, 0.7)',
        'rgba(255, 99, 132, 0.7)',
        'rgba(255, 206, 86, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(255, 159, 64, 0.7)',
        'rgba(201, 203, 207, 0.7)'
    ];
    
    leaveTypesArray.forEach((leaveType, index) => {
        const data = <?php echo json_encode($periods); ?>.map(period => {
            const periodData = <?php echo json_encode($leaveTypesByPeriod); ?>[period] || {};
            return periodData[leaveType] || 0;
        });
        
        leaveTypeDatasets.push({
            label: leaveType,
            data: data,
            backgroundColor: colors[index % colors.length],
            borderColor: colors[index % colors.length].replace('0.7', '1'),
            borderWidth: 1
        });
    });
    
    // Leave Type Chart
    const leaveTypeCtx = document.getElementById('leaveTypeChart').getContext('2d');
    const leaveTypeChart = new Chart(leaveTypeCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($periods); ?>,
            datasets: leaveTypeDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Leave Types Over Time'
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
    <?php endif; ?>
    
    // Export to PDF
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const reportTitle = '<?php echo ucfirst($period); ?> Leave Analysis Report';
        const selectedYear = <?php echo $year; ?>;
        const reportContent = document.getElementById('reportContent');
        
        const opt = {
            margin: 10,
            filename: '<?php echo $period; ?>_leave_report.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };
        
        const headerHtml = `
            <div style="text-align: center; margin-bottom: 20px;">
                <h2>${reportTitle}</h2>
                <p>Year: ${selectedYear}</p>
            </div>
        `;
        
        const element = document.createElement('div');
        element.innerHTML = headerHtml;
        element.appendChild(reportContent.cloneNode(true));
        
        html2pdf().set(opt).from(element).save();
    });
    
    // Export to Excel
    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('timeDataTable');
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, '<?php echo ucfirst($period); ?> Leave Report');
        XLSX.writeFile(wb, '<?php echo $period; ?>_leave_report.xlsx');
    });
});
</script>

<?php include('footer.php'); ?> 