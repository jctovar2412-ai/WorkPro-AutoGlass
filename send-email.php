<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$subject = isset($input['_subject']) ? $input['_subject'] : 'New Form Submission - WorkPro AutoGlass';
$to = 'contact@workproautoglass.com';
$from = 'joseluis@workproautoglass.com';

$body = "<h2>$subject</h2><table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; font-family: Arial, sans-serif;'>";
foreach ($input as $key => $value) {
    if (strpos($key, '_') !== 0) {
        $cleanKey = str_replace('_', ' ', $key);
        $body .= "<tr><td style='background: #f4f4f4; font-weight: bold;'>$cleanKey</td><td>$value</td></tr>";
    }
}
$body .= "</table>";

$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: WorkPro AutoGlass <$from>\r\n";
$headers .= "Reply-To: $from\r\n";

if (mail($to, $subject, $body, $headers)) {
    echo json_encode(['status' => 'success', 'message' => 'Email sent successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send email']);
}
