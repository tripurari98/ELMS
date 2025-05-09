<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

$columns = [
    'leave_id',
    'employee_name',
    'leave_type_name',
    'leave_start_date',
    'leave_end_date',
    'leave_status',    
    'leave_apply_date',
    'leave_admin_remark_date',
];

// Pagination and search parameters
$limit = $_POST['length'];
$start = $_POST['start'];
$order = $columns[$_POST['order'][0]['column']];
$dir = $_POST['order'][0]['dir'];
$searchValue = $_POST['search']['value'];

$filterQuery = ' (CONCAT(elms_employee.employee_first_name, " ", elms_employee.employee_last_name) LIKE "%'.$searchValue.'%" OR elms_leave_type.leave_type_name LIKE "%'.$searchValue.'%" OR elms_leave.leave_status LIKE "%'.$searchValue.'%") ';

// Get total records
if(isset($_SESSION['employee_id'])){
    $totalRecordsStmt = $pdo->query("SELECT COUNT(*) FROM elms_leave WHERE employee_id = '".$_SESSION['employee_id']."'");
    $totalFilteredRecordsStmt = $pdo->query("SELECT COUNT(*) FROM elms_leave INNER JOIN elms_employee ON elms_leave.employee_id = elms_employee.employee_id INNER JOIN elms_leave_type ON elms_leave.leave_type = elms_leave_type.leave_type_id WHERE elms_leave.employee_id = '".$_SESSION['employee_id']."' AND " . $filterQuery . "");
} else {
    $totalRecordsStmt = $pdo->query("SELECT COUNT(*) FROM elms_leave");
    $totalFilteredRecordsStmt = $pdo->query("SELECT COUNT(*) FROM elms_leave INNER JOIN elms_employee ON elms_leave.employee_id = elms_employee.employee_id INNER JOIN elms_leave_type ON elms_leave.leave_type = elms_leave_type.leave_type_id WHERE " . $filterQuery . "");
}
$totalRecords = $totalRecordsStmt->fetchColumn();
$totalFilteredRecords = $totalFilteredRecordsStmt->fetchColumn();

$query = "
    SELECT 
        elms_leave.leave_id,
        CONCAT(elms_employee.employee_first_name, ' ', elms_employee.employee_last_name) AS employee_name,
        elms_leave_type.leave_type_name,
        elms_leave.leave_start_date,
        elms_leave.leave_end_date,
        elms_leave.leave_status,
        elms_leave.leave_admin_remark_date,
        elms_leave.leave_apply_date
    FROM elms_leave
    INNER JOIN elms_employee ON elms_leave.employee_id = elms_employee.employee_id
    INNER JOIN elms_leave_type ON elms_leave.leave_type = elms_leave_type.leave_type_id
";

// Filter for employee or admin
if (isset($_SESSION['employee_id'])) {
    $query .= " WHERE elms_leave.employee_id = :employee_id AND " . $filterQuery . "";
} elseif ($searchValue) {
    $query .= " WHERE " . $filterQuery . "";
}

// Sorting
//$orderColumn = $columns[$_POST['order'][0]['column']];
//$orderDirection = $_POST['order'][0]['dir'];
//$query .= " ORDER BY $orderColumn $orderDirection LIMIT $start, $limit";

$query .= " ORDER BY elms_leave.leave_id DESC LIMIT $start, $limit";

// Prepare data query
$stmt = $pdo->prepare($query);

if (isset($_SESSION['employee_id'])) {
    $stmt->bindParam(':employee_id', $_SESSION['employee_id'], PDO::PARAM_INT);
}

$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$response = [
    'draw'              => intval($_POST['draw']),
    'recordsTotal'      => $totalRecords,
    'recordsFiltered'   => $totalFilteredRecords,
    'data'              => $data
];

echo json_encode($response);

?>