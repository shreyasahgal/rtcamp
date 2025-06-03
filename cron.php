<?php
require_once 'functions.php'; 
// This script should send GitHub updates to registered emails every 5 minutes.
// You need to implement this functionality.
// GitHub Timeline URL
$url = 'https://www.github.com/timeline';

// Fetch GitHub timeline content with proper User-Agent header
$options = [
    "http" => [
        "header" => "User-Agent: PHP"
    ]
];
$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);

if ($response === FALSE) {
    exit("Failed to fetch GitHub timeline.");
}

// NOTE: For demo, we use a simple static table.
// You can extend this to parse $response properly to build HTML table.
$htmlContent = '
<h2>GitHub Timeline Updates</h2>
<table border="1">
  <tr><th>Event</th><th>User</th></tr>
  <tr><td>Push</td><td>testuser</td></tr>
</table>';

// Get all registered emails from registered_emails.txt
$emails = getRegisteredEmails(); // returns array of emails

foreach ($emails as $email) {
    $unsubscribeUrl = generateUnsubscribeLink($email);

    $emailBody = $htmlContent . '
    <p><a href="' . htmlspecialchars($unsubscribeUrl) . '" id="unsubscribe-button">Unsubscribe</a></p>';

    $subject = 'Latest GitHub Updates';

    $headers = "From: no-reply@example.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    mail($email, $subject, $emailBody, $headers);
}
