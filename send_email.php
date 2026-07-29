<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* LOAD PHPMailer FILES */
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';
require_once '../PHPMailer/src/Exception.php';

function sendAlertEmail($toEmail, $temperature, $humidity){
    global $conn, $worker_id; // make sure these are available

    $mail = new PHPMailer(true);

    try{
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        /* YOUR GMAIL */
        $mail->Username = 'nandhiniravi878@gmail.com';

        /* YOUR APP PASSWORD */
        $mail->Password = 'qmgfmxlblbshxxdj';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('nandhiniravi878@gmail.com','HenCare Alert');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);

        $mail->Subject = "HenCare Temperature Alert";

        $mail->Body = "
        <h2>HenCare Alert</h2>
        <p>Temperature exceeded the safe limit in the poultry shed.</p>
        <p><b>Temperature:</b> ".$temperature." °C</p>
        <p><b>Humidity:</b> ".$humidity." %</p>
        <p>Please check the shed immediately.</p>
        ";

        $mail->send();
        echo "Email Sent Successfully";
        $status = "Sent"; // email sent successfully
    }
    catch(Exception $e){
        echo "Email Failed: {$mail->ErrorInfo}";
        $status = "Failed"; // email failed
    }

    // ------------------- Save Alert in Database -------------------
    if(isset($conn) && $conn && isset($worker_id)){
        $stmt = $conn->prepare("INSERT INTO alerts(worker_id,message,status) VALUES(?,?,?)");
        if($stmt){
            
            $alertMessage = "Temperature " . $temperature . "°C exceeded ideal limit!";
            $stmt->bind_param("iss",$worker_id,$alertMessage,$status);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>
