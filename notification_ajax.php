<?php
/**
 * Notification AJAX Handler
 * This file handles AJAX requests for notifications, such as marking them as read
 */

require_once 'db_connect.php';
require_once 'auth_function.php';

// Ensure user is logged in
if (!isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Handle the mark as read action
if (isset($_POST['action']) && $_POST['action'] === 'mark_as_read') {
    $notification_ids_json = isset($_POST['notification_ids']) ? $_POST['notification_ids'] : '[]';
    
    // Decode the JSON string to get the array
    $notification_ids = json_decode($notification_ids_json, true);
    
    // Validate input
    if (empty($notification_ids) || !is_array($notification_ids)) {
        echo json_encode(['success' => false, 'message' => 'No notifications specified']);
        exit;
    }
    
    // Sanitize notification IDs
    $sanitized_ids = array_map('intval', $notification_ids);
    $id_string = implode(',', $sanitized_ids);
    
    try {
        // Determine the correct user conditions based on user type
        $user_condition = '';
        if ($_SESSION['user_type'] === 'Admin' && isset($_SESSION['admin_id'])) {
            $user_condition = "AND recipient_id = " . intval($_SESSION['admin_id']) . " AND recipient_role = 'Admin'";
        } elseif ($_SESSION['user_type'] === 'Employee' && isset($_SESSION['employee_id'])) {
            $user_condition = "AND recipient_id = " . intval($_SESSION['employee_id']) . " AND recipient_role = 'Employee'";
        }
        
        // Update notification status to 'Read'
        $query = "UPDATE elms_notifications 
                  SET notification_status = 'Read' 
                  WHERE notification_id IN ($id_string) $user_condition";
        
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute();
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Notifications marked as read']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
    exit;
}

// Handle invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request']); 