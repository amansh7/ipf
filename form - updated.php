<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once "./vendor/autoload.php";
// require "./db_config.php";
$config = require 'config.php';

// date_default_timezone_set('Asia/Kolkata');
// $created_at = date('Y-m-d H:i:s');
$baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/';

// Function to validate input
function validateInput($data) {
    $errors = [];
    $requiredFields = [
        'full_name' => 'Full Name is required.',
        'mobile' => 'Mobile number is required.',
        'email' => 'Please enter a valid email address.',
        'country_of_origin' => 'Country of Origin is required.',
        'company' => 'Company is required.',
        'job_title' => 'Job Title is required.',
        'country' => 'Country is required.',
        'state' => 'State is required.',
        'city' => 'City is required.',
        'address' => 'Address is required.',
        'postcode' => 'Postcode is required.',
        'nationality' => 'Nationality is required.',
        'industry' => 'Industry is required.',
        'stage' => 'Stage is required.',
        // 'travel_mode' => 'Travel Mode is required.',
        'job_function' => 'Job Function is required.',
        // 'is_interested' => 'Interested in speaking at future events is required.',
        // 'role' => 'Role is required.',
        'no_of_emp' => 'Number of Employees must be a positive integer.',
    ];

    $maxLength = [
        'full_name' => 100,
        'mobile' => 12,
        'email' => 100,
        'country_of_origin' => 70,
        'company' => 100,
        'job_title' => 100,
        'country' => 100,
        'state' => 100,
        'city' => 100,
        'address' => 100,
        'postcode' => 10,
        'nationality' => 100,
        'industry' => 100,
        'stage' => 100,
        'job_function' => 100,
        'no_of_emp' => 20,
    ];

    foreach ($requiredFields as $field => $errorMsg) {
        $value = trim($data[$field] ?? '');
        if (empty($value) || ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL))) {
            $errors[$field] = $errorMsg;
        }
        if (!empty($value) && isset($maxLength[$field]) && strlen($value) > $maxLength[$field]) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " Max {$maxLength[$field]} characters allowed.";
        }
        if ($field === 'mobile' && !preg_match('/^\d{1,12}$/', $value)) {
            $errors[$field] = "Mobile number must be up to 12 digits.";
        }
    }

    return $errors;
}

function createEmailBody($data) {
    $items = array_map(function($key, $value) {
        $formattedKey = ucfirst(str_replace('_', ' ', $key));
        return "<li>{$formattedKey}: {$value}</li>";
    }, array_keys($data), $data);
    
    if (isset($data['country_code']) && isset($data['mobile'])) {
        $mobile = "{$data['country_code']} {$data['mobile']}";
        $items = array_filter($items, function($item) {
            return !strpos($item, 'Country code:') && !strpos($item, 'Mobile:');
        });
        foreach ($items as $index => $item) {
            if (strpos($item, 'Email:') !== false) {
                array_splice($items, $index - 1, 0, "<li>Mobile: {$mobile}</li>");
                break;
            }
        }
    }
    return '<html>
                <body>
                    <p>Hi,<br/><br/>
                    Here are the registration details. Please check them out:</p>
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

        /* $stmt = $db->prepare("INSERT INTO registration (
            email, 
            full_name, 
            job_title, 
            company, 
            address, 
            country, 
            city, 
            state, 
            postcode, 
            mobile, 
            nationality, 
            travel_mode, 
            industry, 
            job_function, 
            role, 
            no_of_emp, 
            country_of_origin, 
            stage,
            is_interested,
            description,
            country_code,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }

        $stmt->bind_param("ssssssssssssssssssssss",
            $_POST['email'],
            $_POST['full_name'],
            $_POST['job_title'],
            $_POST['company'],
            $_POST['address'],
            $_POST['country'],
            $_POST['city'],
            $_POST['state'],
            $_POST['postcode'],
            $_POST['mobile'],
            $_POST['nationality'],
            $_POST['travel_mode'],
            $_POST['industry'],
            $_POST['job_function'],
            $_POST['role'],
            $_POST['no_of_emp'],
            $_POST['country_of_origin'],
            $_POST['stage'],
            $_POST['is_interested'],
            $_POST['description'],
            $_POST['country_code'],
            $created_at
        );

        if (!$stmt->execute()) {
            throw new Exception("Error during registration: " . $stmt->error);
        }

        $stmt->close(); */

        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = $config['HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['USERNAME'];
        $mail->Password = $config['PASSWORD'];
        $mail->Port = $config['PORT'];

        // Email 1 for user
        $userTemplate = file_get_contents('emailer.html');
        $userEmailBody = str_replace('{full_name}', $_POST['full_name'], $userTemplate);
        // $userEmailBody = preg_replace('/src="images/', 'src="' . $baseUrl . 'images/"', $userEmailBody);

        $mail->setFrom('registration@ipfstartuphub.com', 'IPF Startup Hub');
        $mail->addAddress($_POST['email']);
        $mail->Subject = 'IPF registration';
        $mail->isHTML(true);
        $mail->Body = $userEmailBody;
        if(!$mail->send()) { throw new Exception('User Mail could not be sent. Mailer Error: ' . $mail->ErrorInfo); }
        
        // Email 2 for IPF
        $mail->clearAddresses();
        $mail->setFrom('registration@ipfstartuphub.com', 'IPF Startup Hub');
        $mail->addAddress('registration@ipfstartuphub.com');
        $mail->Subject = 'New Registration from ' . $_POST['full_name'];
        $mail->isHTML(true);
        $mail->Body = createEmailBody($_POST);
        if (!$mail->send()) { throw new Exception('Message could not be sent. Mailer Error: ' . $mail->ErrorInfo); }

        echo json_encode(['status' => 'success', 'message' => 'Your details have been submitted successfully, and please check your email.']);
    } catch (Exception $e) {
        // throw $e;
        echo json_encode(['status' => 'error', 'message' => json_decode($e->getMessage(), true)]);
    }

    // $db->close();
}
?>
