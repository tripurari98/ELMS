<?php

require_once 'db_connect.php';

$columns = [
    0 => 'leave_type_id',
    1 => 'leave_type_name',
    2 => 'leave_type_status',
    3 => 'added_on',
    4 => 'updated_on',
];

$limit = $_POST['length'];
$start = $_POST['start'];
$order = $columns[$_POST['order'][0]['column']];
$dir = $_POST['order'][0]['dir'];

$searchValue = $_POST['search']['value'];

// Get total records
$totalRecordsStmt = $pdo->query("SELECT COUNT(*) FROM elms_leave_type");
$totalRecords = $totalRecordsStmt->fetchColumn();

// Get total filtered records
$filterQuery = "SELECT COUNT(*) FROM elms_leave_type WHERE 1=1";
if (!empty($searchValue)) {
    $filterQuery .= " AND (leave_type_name LIKE '%$searchValue%' OR leave_type_status LIKE '%$searchValue%')";
}
$totalFilteredRecordsStmt = $pdo->query($filterQuery);
$totalFilteredRecords = $totalFilteredRecordsStmt->fetchColumn();

// Fetch data
$dataQuery = "SELECT * FROM elms_leave_type WHERE 1=1";
if (!empty($searchValue)) {
    $dataQuery .= " AND (leave_type_name LIKE '%$searchValue%' OR leave_type_status LIKE '%$searchValue%')";
}
$dataQuery .= " ORDER BY $order $dir LIMIT $start, $limit";
$dataStmt = $pdo->query($dataQuery);
$data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

$response = [
    "draw"              => intval($_POST['draw']),
    "recordsTotal"      => intval($totalRecords),
    "recordsFiltered"   => intval($totalFilteredRecords),
    "data"              => $data
];

echo json_encode($response);

?>