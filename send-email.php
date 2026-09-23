<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input && !empty($_POST)) {
    $input = $_POST;
}

$subject = isset($input['_subject']) ? $input['_subject'] : 'New Web Request - WorkPro AutoGlass';
$to = 'contact@workproautoglass.com';
$from = 'joseluis@workproautoglass.com';

$body = "<div style='font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #D4AF37; border-radius: 10px; background: #0D0D0D; color: #FFFFFF;'>";
$body .= "<h2 style='color: #D4AF37; border-bottom: 2px solid #D4AF37; padding-bottom: 10px;'>$subject</h2>";
$body .= "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%; color: #333; background: #FFF;'>";

if (is_array($input)) {
    foreach ($input as $key => $value) {
        if (strpos($key, '_') !== 0) {
            $cleanKey = str_replace('_', ' ', $key);
            $body .= "<tr><td style='background: #1A1A1A; color: #D4AF37; font-weight: bold; width: 40%;'>$cleanKey</td><td style='color: #333;'>$value</td></tr>";
        }
    }
}

$body .= "</table>";
$body .= "<p style='color: #9CA3A8; font-size: 12px; margin-top: 20px; text-align: center;'>WorkPro AutoGlass • Official Web Form Notification</p>";
$body .= "</div>";

$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: WorkPro AutoGlass <$from>\r\n";
$headers .= "Reply-To: $from\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Send to contact@workproautoglass.com and copy to joseluis@workproautoglass.com
$recipients = "$to, $from";

if (@mail($recipients, $subject, $body, $headers)) {
    echo json_encode(['status' => 'success', 'message' => 'Email sent successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Mail function failed']);
}
