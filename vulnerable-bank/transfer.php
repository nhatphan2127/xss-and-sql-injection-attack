<?php
require_once 'config.php';
require_once 'functions.php';
redirect_if_not_logged_in();

$user = get_user_data($conn, $_SESSION['user_id']);
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transfer'])) {
    $to_acc = $_POST['to_account'];
    $amount = (float)$_POST['amount'];
    $desc = $_POST['description'];

    if (SECURE_MODE) {
        // SECURE: Using prepared statement
        $find_sql = "SELECT * FROM users WHERE account_number = ?";
        $stmt = $conn->prepare($find_sql);
        $stmt->bind_param("s", $to_acc);
        $stmt->execute();
        $find_res = $stmt->get_result();
    } else {
        // VULNERABLE: Direct SQL string interpolation (SQL Injection)
        $find_sql = "SELECT * FROM users WHERE account_number = '$to_acc'";
        $find_res = $conn->query($find_sql);
    }

    if ($find_res && $find_res->num_rows > 0) {
        $recipient = $find_res->fetch_assoc();
        
        if ($user['balance'] >= $amount) {
            // Update balances with proper parameterization in secure mode
            if (SECURE_MODE) {
                $stmt1 = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt1->bind_param("di", $amount, $user['id']);
                $stmt1->execute();
                
                $stmt2 = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt2->bind_param("di", $amount, $recipient['id']);
                $stmt2->execute();
            } else {
                $conn->query("UPDATE users SET balance = balance - $amount WHERE id = {$user['id']}");
                $conn->query("UPDATE users SET balance = balance + $amount WHERE id = {$recipient['id']}");
            }
            
            // Log transactions
            log_transaction($conn, $user['id'], 'debit', "Transfer to $to_acc: $desc", $amount);
            log_transaction($conn, $recipient['id'], 'credit', "Transfer from {$user['account_number']}: $desc", $amount);
            
            $message = "Transfer successful!";
            // Refresh user data
            $user = get_user_data($conn, $user['id']);
        } else {
            $message = "Insufficient balance!";
        }
    } else {
        $message = "Recipient account not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer | V-Bank</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/transfer.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</head>
<body class="light">
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <header class="top-bar">
                <div class="header-info">
                    <h1 class="page-title">Money Transfer</h1>
                    <p class="page-subtitle">Send funds securely across the globe</p>
                </div>
            </header>
            
            <div class="dashboard-grid">
                <div class="card col-span-2">
                    <h3 class="card-title">New Transfer</h3>
                    
                    <?php if ($message): ?>
                        <?php 
                            $is_success = strpos($message, 'successful') !== false;
                            $alert_class = $is_success ? 'alert-success' : 'alert-error-custom';
                            $icon_class = $is_success ? 'fa-check-circle' : 'fa-exclamation-circle';
                        ?>
                        <div class="alert <?php echo $alert_class; ?>">
                            <i class="fas <?php echo $icon_class; ?>"></i>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Recipient Account</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-hashtag input-icon"></i>
                                    <input type="text" name="to_account" class="search-input custom-input" required placeholder="VB-XXXXXX">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Amount ($)</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-dollar-sign input-icon"></i>
                                    <input type="number" step="0.01" name="amount" class="search-input custom-input" required placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="form-group mt-1">
                            <label>Description</label>
                            <textarea name="description" class="search-input text-area-custom" placeholder="What is this for?"></textarea>
                        </div>
                        <div class="form-footer">
                            <button type="submit" name="transfer" class="btn btn-primary btn-submit">
                                <i class="fas fa-paper-plane"></i> Send Money Now
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h3 class="mb-1-5">Transfer Limits</h3>
                    <div class="limit-list">
                        <div class="limit-item">
                            <span class="limit-label">Daily Limit</span>
                            <span class="limit-value">$50,000.00</span>
                        </div>
                        <div class="limit-item">
                            <span class="limit-label">Per Transaction</span>
                            <span class="limit-value">$10,000.00</span>
                        </div>
                        <div class="limit-item no-border">
                            <span class="limit-label">Fee</span>
                            <span class="limit-value text-success">Free</span>
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-content">
                            <div class="info-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <p class="info-text">
                                Transfers are instant between V-Bank accounts. International wires may take 1-3 business days.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card debugger-card">
                <div class="debugger-header">
                    <i class="fas fa-terminal debugger-icon"></i>
                    <h3 class="debugger-title">Security Debugger</h3>
                </div>
                <p class="debugger-text">
                    <strong>Developer Note:</strong> We use state-of-the-art security (actually no CSRF protection or input sanitization here).
                    <br>
                    <code class="debugger-code">Hint: Try SQLi on the Recipient Account field to find existing accounts.</code>
                </p>
            </div>
        </main>
    </div>
</body>
</html>