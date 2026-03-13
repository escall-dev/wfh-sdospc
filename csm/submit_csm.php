<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;

require_once __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Capture form data
    $sex = $_POST['sex'] ?? '';
    $age = $_POST['age'] ?? '';
    $customer_type = $_POST['customer_type'] ?? '';
    $offices = $_POST['office_name'] ?? '';
    $sub_offices = $_POST['sub_office_name'] ?? '';
    $services = $_POST['service_name'] ?? '';
    $cc1 = $_POST['aware'] ?? '';
    $cc2 = $_POST['seen'] ?? '';
    $cc3 = $_POST['used'] ?? '';
    $sqd1 = $_POST['SQD1'] ?? '';
    $sqd2 = $_POST['SQD2'] ?? '';
    $sqd3 = $_POST['SQD3'] ?? '';
    $sqd4 = $_POST['SQD4'] ?? '';
    $sqd5 = $_POST['SQD5'] ?? '';
    $sqd6 = $_POST['SQD6'] ?? '';
    $sqd7 = $_POST['SQD7'] ?? '';
    $sqd8 = $_POST['SQD8'] ?? '';
    $remarks = $_POST['suggestion'] ?? '';
    $email = !empty($_POST['email']) ? $_POST['email'] : null;
    $full_name = $_POST['full_name'] ?? '';
    $school_office = $_POST['school_office'] ?? '';
    $controlNumber = $_POST['control_number'] ?? '';
	
// Generate Unique Control Number
function generateControlNumber($conn) {
    do {
        // Generate a 14-digit random number
        $controlNumber = random_int(10000000000000, 99999999999999);

        $check = $conn->prepare("SELECT control_number FROM csm_submission WHERE control_number = ?");
        $check->bind_param("s", $controlNumber);
        $check->execute();
        $check->store_result();

    } while ($check->num_rows > 0);

    return $controlNumber;
}

$controlNumber = generateControlNumber($conn);

    // Insert feedback data into database
    $stmt = $conn->prepare("
        INSERT INTO csm_submission 
        (control_number, full_name, school_office, sex, age, customer_type, offices, sub_offices, services, 
        cc1, cc2, cc3, sqd1, sqd2, sqd3, sqd4, sqd5, sqd6, sqd7, sqd8, remarks)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param(
        "ssssisssssssiiiiiiiis",
        $controlNumber, $full_name, $school_office, $sex, $age, $customer_type, $offices, $sub_offices, $services,
        $cc1, $cc2, $cc3, $sqd1, $sqd2, $sqd3, $sqd4, $sqd5, $sqd6, $sqd7, $sqd8, $remarks
    );

    if (!$stmt->execute()) {
        die("Database error: " . $stmt->error);
    }

    // Close DB early
    $stmt->close();
    $conn->close();

// ========== EMAIL SENDING ==========

// Generate Certificate of Appearance
$templatePath = __DIR__ . "/CSM_Appearance_template.docx";
$generatedFile = __DIR__ . "/generated_docx/generated_certificate_" . $controlNumber . ".docx";

$folder = __DIR__ . "/generated_docx";

if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

try {
    $template = new TemplateProcessor($templatePath);

    // Determine final office output
    if (!empty($sub_offices)) {
        $final_office = $sub_offices . " (" . $offices . ")";
    } else {
        $final_office = $offices;
    }

    // Insert template values
    $template->setValue('control_number', $controlNumber);
    $template->setValue('full_name', $full_name);
    $template->setValue('school_office', $school_office);
    $template->setValue('services', $services);

    // Insert final office logic
    $template->setValue('final_office', $final_office);

    // Date fields
    $template->setValue('month', date('F'));
    $template->setValue('day', date('j'));
    $template->setValue('year', date('Y'));

    // Save output
    $template->saveAs($generatedFile);

    if (!file_exists($generatedFile)) {
        error_log("Failed to generate DOCX: $generatedFile");
    }

} catch (Exception $e) {
    error_log("Certificate generation failed: " . $e->getMessage());
}

// Send Email ONLY if email is provided
if ($email) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ict.sanpedrocity@deped.gov.ph';
        $mail->Password   = 'lrzp kiph pndi ybrp';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('ict.sanpedrocity@deped.gov.ph', 'SDO San Pedro City');
        $mail->addAddress($email);

        // ===== Attach the Certificate of Appearance =====
            if (file_exists($pdfFile)) {
                $mail->addAttachment("/generated_docx/generated_certificate_" . $controlNumber . ".docx");
            }

        // Email contents
        $mail->isHTML(true);
        $mail->Subject = 'Copy of Your Submitted CSM Feedback';
        $mail->Body = "
            <html>
			<head>
			<style> 
			body { font-family: Arial, sans-serif; color: #333; }
			h2 { color: #004aad; } 
			table { border-collapse: collapse; width: 100%; margin-top: 10px; } 
			th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } 
			th { background-color: #004aad; color: white; } 
			tr:nth-child(even) { background-color: #f2f2f2; } 
			</style> 
			</head>
			<body>
            <h2>Thank you for your feedback!</h2>
                    <p>Here is a copy of your submitted CSM responses:</p>

                    <h3>Personal & Transaction Info</h3>
                    <table>
                        <tr><th>Field</th><th>Your Response</th></tr>
                        <tr><td>Age</td><td>$age</td></tr>
                        <tr><td>Sex</td><td>$sex</td></tr>
                        <tr><td>Customer Type</td><td>$customer_type</td></tr>
                        <tr><td>Office</td><td>$offices</td></tr>
                        <tr><td>Sub-office</td><td>$sub_offices</td></tr>
                        <tr><td>Service</td><td>$services</td></tr>
                        <tr><td>Aware of Charter</td><td>$cc1</td></tr>
                        <tr><td>Seen Charter</td><td>$cc2</td></tr>
                        <tr><td>Used Charter</td><td>$cc3</td></tr>
                    </table>

                    <h3>Service Quality Dimension (SQD)</h3>
                    <table>
                        <tr><th>Question</th><th>Your Response</th></tr>
                        <tr><td>SQD1</td><td>$sqd1</td></tr>
                        <tr><td>SQD2</td><td>$sqd2</td></tr>
                        <tr><td>SQD3</td><td>$sqd3</td></tr>
                        <tr><td>SQD4</td><td>$sqd4</td></tr>
                        <tr><td>SQD5</td><td>$sqd5</td></tr>
                        <tr><td>SQD6</td><td>$sqd6</td></tr>
                        <tr><td>SQD7</td><td>$sqd7</td></tr>
                        <tr><td>SQD8</td><td>$sqd8</td></tr>
                    </table>

                    <h3>Remarks / Suggestions</h3>
                    <p>$remarks</p>
                    <p>Thank you for helping us improve our services!</p>
            </body></html>
        ";

        $mail->send();

    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}
    // ========== DEFAULT RESPONSE ==========
    header("Location: csm.php?submitted=1");
	exit;
} else {
    echo "Invalid request.";
}
?>
