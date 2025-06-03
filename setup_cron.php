#!/bin/bash
# This script should set up a CRON job to run cron.php every 5 minutes.
# You need to implement the CRON setup logic here.
$cronFile = realpath(__DIR__ . '/cron.php');
if (!$cronFile) {
    exit("cron.php file not found.");
}

// Define the cron job command (adjust PHP path if needed)
$phpPath = trim(shell_exec('which php'));
if (!$phpPath) {
    exit("PHP executable not found in PATH.");
}

$cronJob = "*/5 * * * * $phpPath $cronFile > /dev/null 2>&1";

// Get existing crontab
exec('crontab -l 2>&1', $output, $returnVar);

if ($returnVar !== 0) {
    // No crontab exists yet
    $currentCron = [];
} else {
    $currentCron = $output;
}

// Check if the cron job already exists
foreach ($currentCron as $line) {
    if (strpos($line, $cronFile) !== false) {
        echo "Cron job already installed.\n";
        exit;
    }
}

// Add new cron job
$currentCron[] = $cronJob;
$tempFile = tempnam(sys_get_temp_dir(), 'cron');

file_put_contents($tempFile, implode("\n", $currentCron) . "\n");

// Install new crontab from the temp file
exec("crontab $tempFile", $output2, $return2);
unlink($tempFile);

if ($return2 === 0) {
    echo "Cron job successfully installed to run every 5 minutes.\n";
} else {
    echo "Failed to install cron job.\n";
}
