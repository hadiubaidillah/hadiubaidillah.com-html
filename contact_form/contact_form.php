<?php
// Suppress HTML error output, return JSON only
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require __DIR__ . '/vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/config.php';

// Configure
$sendTo = 'hadiubaidillahx@gmail.com';
$subject = 'New message from contact form';
$fields = array('name' => 'Name', 'email' => 'Email', 'message' => 'Message');
$okMessage = 'Contact form successfully submitted. Thank you, I will get back to you soon!';
$errorMessage = 'There was an error while submitting the form. Please try again later';

// Response function
function sendResponse($type, $message) {
    echo json_encode(['type' => $type, 'message' => $message]);
    exit;
}

// Check reCAPTCHA
if (!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
    sendResponse('danger', 'Please click on the reCAPTCHA box.');
}

// Verify reCAPTCHA
$secret = $config['recaptcha_secret'];
$c = curl_init('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$_POST['g-recaptcha-response']);
curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
$verifyResponse = curl_exec($c);
curl_close($c);

$responseData = json_decode($verifyResponse);
if (!$responseData || !$responseData->success) {
    sendResponse('danger', 'Robot verification failed, please try again.');
}

// Send email
try {
    // Build email content
    $emailText = "You have new message from Contact Form<br><br>";
    foreach ($_POST as $key => $value) {
        if (isset($fields[$key])) {
            $emailText .= "<strong>$fields[$key]:</strong> " . htmlspecialchars($value) . "<br>";
        }
    }

    // Get sender info
    $senderEmail = isset($_POST['email']) ? $_POST['email'] : '';
    $senderName = isset($_POST['name']) ? $_POST['name'] : 'Website Visitor';

    // Create PHPMailer instance
    $mail = new PHPMailer(true);

    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = $config['smtp']['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp']['username'];
    $mail->Password   = $config['smtp']['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $config['smtp']['port'];

    // Recipients
    $mail->setFrom($config['smtp']['from_email'], $config['smtp']['from_name']);
    $mail->addAddress($sendTo);

    // Reply to sender's email
    if (!empty($senderEmail)) {
        $mail->addReplyTo($senderEmail, $senderName);
    }

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $emailText;
    $mail->AltBody = strip_tags(str_replace('<br>', "\n", $emailText));

    $mail->send();
    sendResponse('success', $okMessage);

} catch (Exception $e) {
    sendResponse('danger', $errorMessage);
}
