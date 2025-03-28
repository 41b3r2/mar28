<?php
// delete_trip.php
ini_set('display_errors', 0); // Disable displaying errors directly in the response
ini_set('log_errors', 1); // Enable logging errors
ini_set('error_log', 'error.log'); // Specify error log file

require_once 'connection.php'; // Include database connection

// Make sure we output clean JSON with no whitespace before/after
ob_start();

$response = array('success' => false, 'message' => '');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cs_id'])) {
        $cs_id = filter_var($_POST['cs_id'], FILTER_SANITIZE_NUMBER_INT);
        
        if (empty($cs_id)) {
            throw new Exception("Invalid trip ID");
        }

        // First check if the record exists
        $check_sql = "SELECT cs_id FROM customerservice WHERE cs_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $cs_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            throw new Exception("Trip not found");
        }
        $check_stmt->close();
        
        // Delete the record
        $sql = "DELETE FROM customerservice WHERE cs_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $cs_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Trip deleted successfully';
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
        
        $stmt->close();
    } else {
        throw new Exception("Invalid request");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in delete_trip.php: " . $e->getMessage()); // Log the error
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