<?php
/**
 * Security Configuration
 * Set SECURE_MODE to toggle between vulnerable and secure versions
 */

// Get SECURE_MODE from environment, default to false (vulnerable mode)
define('SECURE_MODE', getenv('SECURE_MODE') === 'true' || getenv('SECURE_MODE') === '1');

// Helper function to conditionally escape output (prevent XSS)
function secure_output($text) {
    if (SECURE_MODE) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    return $text;
}

// Helper function for secure SQL query building
function prepare_statement($conn, $query, $params = []) {
    if (!SECURE_MODE) {
        // Vulnerable mode: perform string interpolation
        foreach ($params as $key => $value) {
            $query = str_replace('$' . $key, "'" . $value . "'", $query);
        }
        return $query;
    }
    // Secure mode: return prepared statement
    return $conn->prepare($query);
}

// Debug mode to show which version is running
if (getenv('DEBUG') === 'true') {
    error_log('Bank running in ' . (SECURE_MODE ? 'SECURE' : 'VULNERABLE') . ' mode');
}
?>
