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
$password = 'JoseLuis2026**';
$host = 'smtp.hostinger.com';

// Build HTML email content
$body = "<div style='font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #D4AF37; border-radius: 10px; background: #0D0D0D; color: #FFFFFF;'>";
$body .= "<h2 style='color: #D4AF37; border-bottom: 2px solid #D4AF37; padding-bottom: 10px;'>" . htmlspecialchars($subject) . "</h2>";
$body .= "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%; color: #333; background: #FFF;'>";

if (is_array($input)) {
    foreach ($input as $key => $value) {
        if (strpos($key, '_') !== 0) {
            $cleanKey = htmlspecialchars(str_replace('_', ' ', $key));
            $cleanVal = htmlspecialchars(is_array($value) ? implode(', ', $value) : $value);
            $body .= "<tr><td style='background: #1A1A1A; color: #D4AF37; font-weight: bold; width: 40%;'>$cleanKey</td><td style='color: #333;'>$cleanVal</td></tr>";
        }
    }
}

$body .= "</table>";
$body .= "<p style='color: #9CA3A8; font-size: 12px; margin-top: 20px; text-align: center;'>WorkPro AutoGlass • Official Web Form Notification</p>";
$body .= "</div>";

function sendSmtpEmail($host, $port, $user, $pass, $from, $recipients, $subject, $bodyHtml) {
    $timeout = 15;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $protocol = ($port == 465) ? 'ssl://' : '';
    $socket = @stream_socket_client($protocol . $host . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

    if (!$socket) {
        return ['success' => false, 'error' => "Socket connect failed on port $port: $errstr ($errno)"];
    }

    $read = function($socket) {
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        return $response;
    };

    $send = function($socket, $cmd) use ($read) {
        fputs($socket, $cmd . "\r\n");
        return $read($socket);
    };

    $res = $read($socket); // banner
    if (substr($res, 0, 3) != '220') {
        fclose($socket);
        return ['success' => false, 'error' => "SMTP Banner error: $res"];
    }

    $res = $send($socket, "EHLO " . gethostname());
    if ($port == 587) {
        $res = $send($socket, "STARTTLS");
        if (substr($res, 0, 3) == '220') {
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            $res = $send($socket, "EHLO " . gethostname());
        }
    }

    $res = $send($socket, "AUTH LOGIN");
    if (substr($res, 0, 3) != '334') {
        fclose($socket);
        return ['success' => false, 'error' => "AUTH LOGIN failed: $res"];
    }

    $res = $send($socket, base64_encode($user));
    if (substr($res, 0, 3) != '334') {
        fclose($socket);
        return ['success' => false, 'error' => "Username failed: $res"];
    }

    $res = $send($socket, base64_encode($pass));
    if (substr($res, 0, 3) != '235') {
        fclose($socket);
        return ['success' => false, 'error' => "Password auth failed: $res"];
    }

    $res = $send($socket, "MAIL FROM: <$user>");
    if (substr($res, 0, 3) != '250') {
        fclose($socket);
        return ['success' => false, 'error' => "MAIL FROM failed: $res"];
    }

    foreach ($recipients as $rcpt) {
        $res = $send($socket, "RCPT TO: <$rcpt>");
        if (substr($res, 0, 3) != '250' && substr($res, 0, 3) != '251') {
            fclose($socket);
            return ['success' => false, 'error' => "RCPT TO <$rcpt> failed: $res"];
        }
    }

    $res = $send($socket, "DATA");
    if (substr($res, 0, 3) != '354') {
        fclose($socket);
        return ['success' => false, 'error' => "DATA command failed: $res"];
    }

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: WorkPro AutoGlass <$user>\r\n";
    $headers .= "To: " . implode(', ', $recipients) . "\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "X-Mailer: WorkPro AutoGlass Native SMTP\r\n";

    $message = $headers . "\r\n" . $bodyHtml . "\r\n.";

    $res = $send($socket, $message);
    if (substr($res, 0, 3) != '250') {
        fclose($socket);
        return ['success' => false, 'error' => "Sending data failed: $res"];
    }

    $send($socket, "QUIT");
    fclose($socket);

    return ['success' => true];
}

$recipients = ['contact@workproautoglass.com', 'joseluis@workproautoglass.com'];

// Try SMTP on port 465 (SSL)
$smtpResult = sendSmtpEmail($host, 465, $from, $password, $from, $recipients, $subject, $body);

if (!$smtpResult['success']) {
    // Fallback to port 587 (TLS)
    $smtpResult = sendSmtpEmail($host, 587, $from, $password, $from, $recipients, $subject, $body);
}

if ($smtpResult['success']) {
    echo json_encode(['status' => 'success', 'message' => 'Email sent successfully via Hostinger SMTP']);
} else {
    // Final fallback to PHP mail()
    $toHeader = implode(', ', $recipients);
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: WorkPro AutoGlass <$from>\r\nReply-To: $from\r\n";
    if (@mail($toHeader, $subject, $body, $headers)) {
        echo json_encode(['status' => 'success', 'message' => 'Email sent successfully via PHP mail()']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'SMTP and mail() failed: ' . $smtpResult['error']]);
    }
}
