<?php
require_once 'functions.php';
// Define file for storing verification codes
$codeFile = __DIR__ . '/unsubscribe_codes.json';

$step = 1;
$message = '';

// Step 1: User submits their email to unsubscribe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unsubscribe_email'])) {
    $email = trim(strtolower($_POST['unsubscribe_email']));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
    } else {
        // Generate code and store it
        $code = generateVerificationCode();

        $codes = file_exists($codeFile) ? json_decode(file_get_contents($codeFile), true) : [];
        $codes[$email] = $code;
        file_put_contents($codeFile, json_encode($codes, JSON_PRETTY_PRINT));

        // Send email with code
        $subject = 'Your Verification Code';
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: no-reply@example.com\r\n";
        $body = "<p>Your verification code is: <strong>$code</strong></p>";

        if (mail($email, $subject, $body, $headers)) {
            $step = 2;
            $message = "A verification code has been sent to your email.";
        } else {
            $message = "Failed to send verification email.";
        }
    }
}

// Step 2: User submits verification code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unsubscribe_verification_code'])) {
    $email = trim(strtolower($_POST['email_hidden'] ?? ''));
    $inputCode = $_POST['unsubscribe_verification_code'] ?? '';

    $codes = file_exists($codeFile) ? json_decode(file_get_contents($codeFile), true) : [];

    if (isset($codes[$email]) && $codes[$email] === $inputCode) {
        if (unsubscribeEmail($email)) {
            unset($codes[$email]);
            file_put_contents($codeFile, json_encode($codes, JSON_PRETTY_PRINT));

            $message = "Successfully unsubscribed.";
            $step = 3;
        } else {
            $message = "Failed to unsubscribe. You may not be registered.";
            $step = 2;
        }
    } else {
        $message = "Invalid verification code.";
        $step = 2;
    }
}

// TODO: Implement the form and logic for email unsubscription.
?>
<!DOCTYPE html>
<html>
<head>
    <title>Unsubscribe</title>
</head>
<body>
    <h1>Unsubscribe from GitHub Updates</h1>
    <p style="color:red;"><?= htmlspecialchars($message) ?></p>

    <?php if ($step === 1): ?>
        <form method="POST">
            <label>Enter your email to unsubscribe:</label><br>
            <input type="email" name="unsubscribe_email" required>
            <button id="submit-unsubscribe">Unsubscribe</button>
        </form>

    <?php elseif ($step === 2): ?>
        <form method="POST">
            <input type="hidden" name="email_hidden" value="<?= htmlspecialchars($email) ?>">
            <label>Enter the 6-digit code sent to your email:</label><br>
            <input type="text" name="unsubscribe_verification_code">
            <button id="verify-unsubscribe">Verify</button>
        </form>

    <?php else: ?>
        <p>You have been unsubscribed. Thank you.</p>
    <?php endif; ?>
</body>
</html>
