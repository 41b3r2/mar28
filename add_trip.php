<?php
// add_trip.php - Implementing auto-generated topsheet numbers in TS-00001 format
ini_set('display_errors', 0); // Disable displaying errors directly in the response
ini_set('log_errors', 1); // Enable logging errors
ini_set('error_log', 'error.log'); // Specify error log file in current directory

require_once 'connection.php'; // Ensure this path is correct

// Make sure we output clean JSON with no whitespace before/after
ob_start();

$response = array('success' => false, 'message' => '');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get the latest topsheet number from the database
        $query = "SELECT topsheet FROM customerservice WHERE topsheet LIKE 'TS-%' ORDER BY CAST(SUBSTRING(topsheet, 4) AS UNSIGNED) DESC LIMIT 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            // If records exist, get the last topsheet and increment
            $row = $result->fetch_assoc();
            $lastTopsheet = $row['topsheet'];
            $lastNumber = intval(substr($lastTopsheet, 3)); // Extract the number part
            $nextNumber = $lastNumber + 1;
        } else {
            // If no records exist, start from 1
            $nextNumber = 1;
        }
        
        // Format with leading zeros to create TS-00001 format
        $topsheet = 'TS-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        
        // Sanitize form inputs
        $waybill = isset($_POST['waybill']) ? filter_var($_POST['waybill'], FILTER_SANITIZE_NUMBER_INT) : null;
        $date = isset($_POST['date']) ? filter_var($_POST['date'], FILTER_SANITIZE_STRING) : null;
        $status = isset($_POST['status']) ? filter_var($_POST['status'], FILTER_SANITIZE_STRING) : null;
        $delivery_type = isset($_POST['delivery_type']) ? filter_var($_POST['delivery_type'], FILTER_SANITIZE_STRING) : null;
        $amount = isset($_POST['amount']) ? filter_var($_POST['amount'], FILTER_SANITIZE_STRING) : null;
        $source = isset($_POST['source']) ? filter_var($_POST['source'], FILTER_SANITIZE_STRING) : null;
        $pickup = isset($_POST['pickup']) ? filter_var($_POST['pickup'], FILTER_SANITIZE_STRING) : null;
        $dropoff = isset($_POST['dropoff']) ? filter_var($_POST['dropoff'], FILTER_SANITIZE_STRING) : null;
        $rate = isset($_POST['rate']) ? filter_var($_POST['rate'], FILTER_SANITIZE_STRING) : null;
        $call_time = isset($_POST['call_time']) ? filter_var($_POST['call_time'], FILTER_SANITIZE_STRING) : null;

        // Validate required fields
        if (empty($waybill) || empty($date) || empty($status)) {
            throw new Exception("Required fields cannot be empty");
        }

        // Set the default situation to "Pending"
        $situation = "Pending";

        // Prepare and execute the SQL query
        $sql = "INSERT INTO customerservice (topsheet, waybill, date, status, delivery_type, amount, source, pickup, dropoff, rate, call_time, situation) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssssssss", $topsheet, $waybill, $date, $status, $delivery_type, $amount, $source, $pickup, $dropoff, $rate, $call_time, $situation);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Trip added successfully';
            $response['topsheet'] = $topsheet; // Return the generated topsheet number
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        throw new Exception("Invalid request method");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in add_trip.php: " . $e->getMessage()); // Log the error
}

$conn->close();

// Clear any previous output
ob_end_clean();

// Ensure proper headers are sent
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Output the JSON with proper flags for safety
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
exit;
?>