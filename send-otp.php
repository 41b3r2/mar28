<?php
// send-otp.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'connection.php';

header('Content-Type: application/json');

// Error logging function
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'otp_errors.log');
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $stmt = $conn->prepare("SELECT * FROM employee WHERE email = ?");
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Email not found');
    }

    $otp = sprintf("%06d", mt_rand(1, 999999));
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $stmt = $conn->prepare("UPDATE employee SET otp = ?, otp_expiry = ? WHERE email = ?");
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    $stmt->bind_param("sss", $otp, $otp_expiry, $email);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update OTP: ' . $stmt->error);
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';  
    $mail->SMTPAuth   = true;
    $mail->Username   = 'enanojra@gmail.com';  
    $mail->Password   = 'ovqgmiilcxbdoonj';     
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->setFrom('enanojra@gmail.com', 'PCL Fleet Ledger');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset OTP';
    $mail->Body    = "Your OTP for password reset is: <b>$otp</b>. This OTP will expire in 15 minutes.";
    $mail->send();

    echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);

} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>