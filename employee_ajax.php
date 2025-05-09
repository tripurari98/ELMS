<?php

//employee_ajax.php

require_once 'db_connect.php';

try {

	require_once 'db_connect.php';

    $draw = $_POST['draw'];
    $start = $_POST['start'];
    $length = $_POST['length'];
    $searchValue = $_POST['search']['value'];

    // Total records
    $totalRecordsStmt = $pdo->query("SELECT COUNT(*) FROM elms_employee");
    $totalRecords = $totalRecordsStmt->fetchColumn();

    // Total filtered records
    $query = "SELECT COUNT(*) FROM elms_employee e 
              LEFT JOIN elms_department d ON e.employee_department = d.department_id 
              WHERE employee_first_name LIKE :search 
              OR employee_last_name LIKE :search 
              OR employee_email LIKE :search";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':search' => "%$searchValue%"]);
    $totalFiltered = $stmt->fetchColumn();

    // Fetch data
    $query = "SELECT e.*, d.department_name FROM elms_employee e 
              LEFT JOIN elms_department d ON e.employee_department = d.department_id 
              WHERE employee_first_name LIKE :search 
              OR employee_last_name LIKE :search 
              OR employee_email LIKE :search 
              ORDER BY employee_id ASC LIMIT :start, :length";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':search', "%$searchValue%", PDO::PARAM_STR);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare JSON response
    $response = [
        "draw" 				=> intval($draw),
        "recordsTotal" 		=> $totalRecords,
        "recordsFiltered" 	=> $totalFiltered,
        "data" 				=> $employees
    ];

    echo json_encode($response);
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}

?>