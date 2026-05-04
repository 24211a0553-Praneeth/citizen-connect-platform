<?php
/**
 * NotificationHelper - A unified service for sending Email and SMS notifications.
 * Support for PHPMailer and common SMS Gateways.
 */
class NotificationHelper {
    
    private static $log_file = __DIR__ . '/notifications_log.txt';

    /**
     * Send an email notification.
     * Integrates with PHPMailer or standard mail()
     */
    public static function sendEmail($to, $subject, $message) {
        if (empty($to)) return false;

        // --- PRODUCTION SMTP CONFIGURATION (Example using PHPMailer) ---
        /*
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'YOUR_GMAIL@gmail.com'; 
            $mail->Password   = 'YOUR_APP_PASSWORD'; 
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('no-reply@citizenconnect.gov', 'Citizen Connect Admin');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
            return false;
        }
        */

        // --- MOCK MODE: Log to file for verification ---
        $log_entry = "[" . date('Y-m-d H:i:s') . "] EMAIL to $to | Subject: $subject | Body: " . strip_tags($message) . PHP_EOL;
        file_put_contents(self::$log_file, $log_entry, FILE_APPEND);

        return true;
    }

    /**
     * Send an SMS notification.
     * Integrates with Gateways like Twilio, Vonage, or Fast2SMS.
     */
    public static function sendSMS($to, $message) {
        if (empty($to)) return false;

        // --- PRODUCTION SMS GATEWAY (Example: Twilio) ---
        /*
        $sid    = "YOUR_TWILIO_SID";
        $token  = "YOUR_TWILIO_TOKEN";
        $from   = "+1234567890"; // Your Twilio Number
        $url    = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
        
        $data = [
            'From' => $from,
            'To'   => $to,
            'Body' => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("SMS failed: " . $err);
            return false;
        }
        return true;
        */

        // --- MOCK MODE: Log to file for verification ---
        $log_entry = "[" . date('Y-m-d H:i:s') . "] SMS to $to | Message: $message" . PHP_EOL;
        file_put_contents(self::$log_file, $log_entry, FILE_APPEND);

        return true;
    }

    /**
     * Broadcast a notification to both channels if available.
     * Also records the notification in the system database for UI display.
     */
    public static function broadcast($email, $phone, $subject, $message, $smsMessage = null, $conn = null, $target_user = null, $dept = 'all') {
        $email_sent = false;
        $sms_sent = false;

        // Fallback for smsMessage
        if ($smsMessage === null) $smsMessage = strip_tags($message);

        // 1. Send Email
        if (!empty($email)) {
            $email_sent = self::sendEmail($email, $subject, $message);
        }

        // 2. Send SMS
        if (!empty($phone)) {
            $sms_sent = self::sendSMS($phone, $smsMessage);
        }

        // 3. PERSIST TO DATABASE for Dashboard display
        if (!$conn || !($conn instanceof mysqli)) {
            $conn = new mysqli("localhost", "root", "", "citizen_connect");
        }

        if ($conn && !$conn->connect_error) {
            $stmt = $conn->prepare("INSERT INTO notifications (title, message, department, target_user, sent_by) VALUES (?, ?, ?, ?, 'system')");
            $clean_msg = strip_tags($message);
            $stmt->bind_param("ssss", $subject, $clean_msg, $dept, $target_user);
            $stmt->execute();
            $stmt->close();
        }

        return ['email' => $email_sent, 'sms' => $sms_sent];
    }
}
?>
