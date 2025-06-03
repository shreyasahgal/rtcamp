<?php
require_once 'functions.php';

// TODO: Implement the form and logic for email registration and verification
$message = '';
$email = '';
$verification_step = false; // Show verification code input only after email submission

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Email submission: send verification code
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $code = generateVerificationCode(); // 6-digit numeric code
            saveVerificationCode($email, $code); // Store code linked with email (in file or session)

            // Send verification email
            $subject = "Your Verification Code";
            $body = "<p>Your verification code is: <strong>$code</strong></p>";
            $headers = "From: no-reply@example.com\r\n";
            $headers .= "Content-type: text/html\r\n";

            if (mail($email, $subject, $body, $headers)) {
                $message = "Verification code sent to $email. Please enter the code below.";
                $verification_step = true;
            } else {
                $message = "Failed to send verification email. Please try again.";
            }
        } else {
            $message = "Please enter a valid email address.";
        }
    }

    // 2. Verification code submission: verify and register email
    if (isset($_POST['verification_code']) && isset($_POST['email_for_verification'])) {
        $email = trim($_POST['email_for_verification']);
        $code = trim($_POST['verification_code']);

        if (verifyCodeForEmail($email, $code)) {
            if (!isEmailRegistered($email)) {
                registerEmail($email);
            }
            $message = "Email $email successfully verified and registered.";
            $verification_step = false;
        } else {
            $message = "Invalid verification code. Please try again.";
            $verification_step = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Email Registration & Verification</title>
</head>
<body>
    <h1>Email Verification & Registration</h1>

    <?php if ($message): ?>
        <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
    <?php endif; ?>

    <!-- Email input form -->
    <form method="POST" action="">
        <label for="email">Enter your email:</label><br>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
        <button type="submit" id="submit-email">Submit</button>
    </form>

    <!-- Verification code input form (always visible, disabled if not needed) -->
    <form method="POST" action="">
        <label for="verification_code">Enter verification code:</label><br>
        <input type="text" name="verification_code" maxlength="6" 
            <?php echo $verification_step ? '' : 'disabled'; ?> required>
        <input type="hidden" name="email_for_verification" value="<?php echo htmlspecialchars($email); ?>">
        <button type="submit" id="submit-verification" <?php echo $verification_step ? '' : 'disabled'; ?>>Verify</button>
    </form>
</body>
</html>
