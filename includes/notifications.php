<?php
// Mock Notifications Module
function sendServiceCompletionNotification($serviceId, $db) {
    // Look up customer details
    $res = oraQuery($db, 
        "SELECT c.full_name, c.phone, c.email, v.number_plate 
         FROM service_records sr
         JOIN vehicles v ON sr.vehicle_id = v.vehicle_id
         JOIN customers c ON v.customer_id = c.customer_id
         WHERE sr.service_id = :sid", 
        ['sid' => $serviceId]
    );

    if (count($res) === 0) return false;
    $cust = $res[0];

    // Mock sending SMS/Email
    $logMsg = date('Y-m-d H:i:s') . " - NOTIFICATION SENT: [SMS to {$cust['phone']}] Dear {$cust['full_name']}, the service for your vehicle ({$cust['number_plate']}) is now Completed. Please proceed for billing.\n";
    
    // In a real app we would call an API like Twilio or SendGrid here
    // For this mock, we append it to a local log file
    file_put_contents(__DIR__ . '/../notifications.log', $logMsg, FILE_APPEND);
    return true;
}
?>
