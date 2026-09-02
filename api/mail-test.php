<?php
// api/mail-test.php
//
// TEMPORARY DEBUGGING TOOL — sends one test email and prints the full
// SMTP conversation log so you can see exactly which step failed.
//
// DELETE THIS FILE once you're done debugging. It exposes SMTP handshake
// details (though never your actual password — see smtp_mailer.php's
// redaction) and there's no reason to leave a mail-testing endpoint
// sitting on a live site indefinitely.
//
// Usage: /api/mail-test.php?token=YOUR_TOKEN&to=you@example.com
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Point these paths directly to the files inside your uploaded PHPMailer folder
require_once __DIR__ .'/../PHPMailer/src/Exception.php';
require_once __DIR__ .'/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ .'/../PHPMailer/src/SMTP.php';

require_once __DIR__ . '/../includes/mailer.php';

const DEBUG_TOKEN = 'S@k@ry@';

header('Content-Type: text/plain');

if (!hash_equals(DEBUG_TOKEN, $_GET['token'] ?? '')) {
    http_response_code(401);
    echo "Unauthorized.\n";
    exit;
}

$to = $_GET['to'] ?? '';
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "Add ?to=youremail@example.com to the URL to send a test message there.\n";
    exit;
}

echo "Attempting to send a test email to: $to\n";
//echo "Using SMTP_HOST=" . SMTP_HOST . "  SMTP_PORT=" . SMTP_PORT . "  SMTP_ENCRYPTION=" . SMTP_ENCRYPTION . "\n";
echo str_repeat('-', 60) . "\n\n";

$t = $_GET['t'] ?? 'php_mail'; 
if ($t == 'smtp'){
[$ok, $log] = smtp_send_email(
    $to,
    'BookMe SMTP test',
    "If you're reading this, SMTP delivery is working.\n\nSent at " . date('r'),
    SMTP_USERNAME !== '' ? SMTP_USERNAME : 'test@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    'BookMe Test'
);

echo "RESULT: " . ($ok ? "SUCCESS — check the inbox at $to (and spam folder)" : "FAILED") . "\n\n";
echo $log . "\n";
}
else{
/*
$ret = send_via_php_mail(
  $to,
  'BookMe SMTP test',
  "If you're reading this, SMTP delivery is working.\n\nSent at " . date('r'),
  default_from_address()
);



$mail = new PHPMailer(true);

try {
	$mail->SMTPDebug = 3;
    // --- Server Settings ---
	$mail->isMail();
    //$mail->isSMTP();                                      // Set mailer to use SMTP
    //$mail->Host       = 'free.mboxhosting.com';        // Specify Runhosting SMTP server
    //$mail->Host       = 'localhost';        // Specify Runhosting SMTP server

    
    // 💡 THIS ENABLES SMTP AUTHENTICATION
    //$mail->SMTPAuth   = true;                             
    
    //$mail->Username   = 'no-reply@bookappointment.me';            // Your full email address
    //$mail->Password   = 'T12tbEsa*tb';              // Your email account password
//    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Enable TLS encryption
//    $mail->Port       = 587;                              // TCP port to connect to

//    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;   // Enable TLS encryption
//    $mail->Port       = 465;                              // TCP port to connect to
//$mail->SMTPSecure = '';                 // Remove ENCRYPTION_SMTPS / SSL
//$mail->SMTPAutoTLS = false;             // Disable automatic TLS upgrades
//$mail->Port       = 25;                 // Switch to internal port 25


    // --- Recipients ---
    $mail->setFrom('no-reply@bookappointment.me', '');
    $mail->addAddress($to);           // Add a recipient

    // --- Content ---
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Test Email with SMTP Auth';
    $mail->Body    = 'This email was sent using authenticated SMTP on Runhosting!';

    $mail->send();
    echo 'Message has been sent successfully.';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
*/
$subject = "Hi!";
$body = "TEST";
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type:text/html;charset=UTF-8\r\n";
$headers .= "From: You <no-reply@bookappointment.me>\r\n";

if(mail($to,$subject,$body,$headers)) {
echo "MAIL - OK";
} else {
echo "MAIL FAILED";
}

var_dump(['return php_mail' => $ret]);
}