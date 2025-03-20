<?php
session_start();

function log_error($message) {
    $log_file = __DIR__ . '/error_log.txt';
    $current_time = date('Y-m-d H:i:s');
    $formatted_message = "[{$current_time}] {$message}\n";
    file_put_contents($log_file, $formatted_message, FILE_APPEND);
}
?>