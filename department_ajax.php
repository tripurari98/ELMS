<?php

require_once 'db_connect.php';

$columns = [
    0 => 'department_id',
    1 => 'department_name',
    2 => 'department_status',
    3 => 'added_on',
    4 => 'updated_on',
];

$limit = $_GET['length'];
$start = $_GET['start'];
$order = $columns[$_GET['order'][0]['column']];
$dir = $_GET['order'][0]['dir'];

$searchValue = $_GET['search']['value'];

// Get total records
$totalRecordsStmt = $pdo->query("SELECT COUNT(*) FROM elms_department");
$totalRecords = $totalRecordsStmt->fetchColumn();

// Get total filtered records
$filterQuery = "SELECT COUNT(*) FROM elms_department WHERE 1=1";
if (!empty($searchValue)) {
    $filterQuery .= " AND (department_name LIKE '%$searchValue%' OR department_status LIKE '%$searchValue%')";
}
$totalFilteredRecordsStmt = $pdo->query($filterQuery);
$totalFilteredRecords = $totalFilteredRecordsStmt->fetchColumn();

// Fetch data
$dataQuery = "SELECT * FROM elms_department WHERE 1=1";
if (!empty($searchValue)) {
    $dataQuery .= " AND (department_name LIKE '%$searchValue%' OR department_status LIKE '%$searchValue%')";
}
$dataQuery .= " ORDER BY $order $dir LIMIT $start, $limit";
$dataStmt = $pdo->query($dataQuery);
$data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

$response = [
    "draw"              => intval($_GET['draw']),
    "recordsTotal"      => intval($totalRecords),
    "recordsFiltered"   => intval($totalFilteredRecords),
    "data"              => $data
];

echo json_encode($response);

?>