<?php

/**
 * Generate a 6-digit numeric verification code.
 */
function generateVerificationCode(): string {
    // TODO: Implement this function
    return str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
}

/**
 * Send a verification code to an email.
 */
function sendVerificationEmail(string $email, string $code): bool {
    // TODO: Implement this function
    $subject = 'Your Verification Code';
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: no-reply@example.com' . "\r\n";

    $body = "<p>Your verification code is: <strong>$code</strong></p>";

    return mail($email, $subject, $body, $headers);
}

/**
 * Register an email by storing it in a file.
 */
function registerEmail(string $email): bool {
  $file = __DIR__ . '/registered_emails.txt';
    // TODO: Implement this function
    // Normalize email
    $email = trim(strtolower($email));


    // Check if already registered
    if (file_exists($file)) {
       $file = __DIR__ . '/registered_emails.txt';
    $email = trim(strtolower($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Read existing emails safely
    $emails = [];
    if (file_exists($file)) {
        $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $emails = array_map('trim', $emails);
        $emails = array_map('strtolower', $emails);
    }

    // If email already registered, return true (idempotent)
    if (in_array($email, $emails, true)) {
        return true;
    }

    // Append email safely
    $fp = fopen($file, 'a');
    if (!$fp) {
        return false;
    }
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, $email . PHP_EOL);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }
    fclose($fp);
    return false;


    
}

/**
 * Unsubscribe an email by removing it from the list.
 */
function unsubscribeEmail(string $email): bool {
    $file = __DIR__ . '/registered_emails.txt';
    // TODO: Implement this function
    $email = trim(strtolower($email));

    if (!file_exists($file)) {
        return false; // File doesn't exist, nothing to remove
    }
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $emails = array_map('trim', $emails);
    $emails = array_map('strtolower', $emails);

    $filtered = array_filter($emails, fn($e) => $e !== $email);
    
    // If the email was not found, return false
    if (count($emails) === count($updatedEmails)) {
        return false;
    }

    // Write back updated list
    
    $fp = fopen($file, 'w');
    if (!$fp) {
        return false;
    }
    if (flock($fp, LOCK_EX)) {
        foreach ($filtered as $line) {
            fwrite($fp, $line . PHP_EOL);
        }
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }
    fclose($fp);
    return false;

}

/**
 * Fetch GitHub timeline.
 */
function fetchGitHubTimeline() {
    // TODO: Implement this function
     $url = 'https://www.github.com/timeline';

    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-cURL'); // GitHub requires User-Agent

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

     if ($httpCode === 200 && $response !== false) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
    }

    return null;
}

/**
 * Format GitHub timeline data. Returns a valid HTML sting.
 */
function formatGitHubData(array $data): string {
    // TODO: Implement this function
      // Start HTML email structure
    $html = '<h2>GitHub Timeline Updates</h2>';
    $html .= '<table border="1">';
    $html .= '<tr><th>Event</th><th>User</th></tr>';

    foreach ($data as $event) {
        // Sanitize input
        $type = isset($event['type']) ? htmlspecialchars($event['type']) : 'N/A';
        $user = isset($event['user']) ? htmlspecialchars($event['user']) : 'Unknown';

        $html .= "<tr><td>{$type}</td><td>{$user}</td></tr>";
    }

    $html .= '</table>';

    // Placeholder unsubscribe link to be replaced dynamically before sending email
    $html .= '<p><a href="unsubscribe_url" id="unsubscribe-button">Unsubscribe</a></p>';

    return $html;
}

/**
 * Send the formatted GitHub updates to registered emails.
 */
function sendGitHubUpdatesToSubscribers(): void {
  $file = __DIR__ . '/registered_emails.txt';

    if (!file_exists($file) || !is_readable($file)) {
        return;
    }

    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($emails)) {
        return;
    }

    $timelineData = fetchGitHubTimeline();

    // fetchGitHubTimeline() should return an array of events, 
    // if it returns raw HTML or null, handle accordingly
    if (empty($timelineData) || !is_array($timelineData)) {
        return;
    }

    // Get the base HTML email body with placeholder unsubscribe link
    $baseHtml = formatGitHubData($timelineData);

    foreach ($emails as $email) {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        // Build unsubscribe URL with url-encoded email
        $unsubscribeUrl = 'https://yourdomain.com/src/unsubscribe.php?email=' . urlencode($email);

        // Replace placeholder 'unsubscribe_url' in the HTML with real URL
        $emailBody = str_replace('unsubscribe_url', $unsubscribeUrl, $baseHtml);

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: no-reply@example.com\r\n";

        $subject = "Latest GitHub Updates";

        mail($email, $subject, $emailBody, $headers);
    }
}
