<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once "./vendor/autoload.php";
$config = require 'config.php';

// Function to validate input
function validateInput($data) {
    $errors = [];
    $requiredFields = [
        'full_name' => 'Full Name is required.',
        'email' => 'Please enter a valid email address.',
        'phone' => 'Phone Number is required.',
        'subject' => 'Subject is required.',
    ];

    $maxLength = [
        'full_name' => 100,
        'email' => 100,
        'phone' => 12,
        'subject' => 100,
    ];

    foreach ($requiredFields as $field => $errorMsg) {
        $value = trim($data[$field] ?? '');
        if (empty($value) || ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL))) {
            $errors[$field] = $errorMsg;
        }
        if (!empty($value) && isset($maxLength[$field]) && strlen($value) > $maxLength[$field]) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " Max {$maxLength[$field]} characters allowed.";
        }
        if ($field === 'phone' && !preg_match('/^\d{1,12}$/', $value)) {
            $errors[$field] = "Phone number must be up to 12 digits.";
        }
    }

    return $errors;
}

function createEmailBody($data) {
    $items = array_map(function($key, $value) {
        $formattedKey = ucfirst(str_replace('_', ' ', $key));
        return "<li>{$formattedKey}: {$value}</li>";
    }, array_keys($data), $data);
    
    return '<html>
                <body>
                    <p>Hi,<br/><br/>
                    Here are the details. Please check them out:</p>
                    <ul>' . implode('', $items) . '</ul>
                </body>
                <p>Regards,</p>
    </html>';
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $errors = validateInput($_POST);
        if (!empty($errors)) {
            throw new Exception(json_encode($errors));
        }

        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = $config['HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['USERNAME'];
        $mail->Password = $config['PASSWORD'];
        $mail->Port = $config['PORT'];

        $mail->setFrom('registration@ipfstartuphub.com', 'IPF Startup Hub');
        $mail->addAddress('registration@ipfstartuphub.com');
        $mail->Subject = 'New Contact us from ' . $_POST['full_name'];
        $mail->isHTML(true);
        $mail->Body = createEmailBody($_POST);
        if (!$mail->send()) { throw new Exception('Message could not be sent. Mailer Error: ' . $mail->ErrorInfo); }

        echo json_encode(['status' => 'success', 'message' => 'Your details have been submitted successfully.']);
    } catch (Exception $e) {
        // throw $e;
        echo json_encode(['status' => 'error', 'message' => json_decode($e->getMessage(), true)]);
    }

}
?>
