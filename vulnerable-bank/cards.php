<?php
require_once 'config.php';
require_once 'functions.php';
redirect_if_not_logged_in();

$user = get_user_data($conn, $_SESSION['user_id']);
$message = '';

if (isset($_POST['create_card'])) {
    $type = $_POST['card_type'];
    $currency = $_POST['currency'];
    $card_num = "4" . rand(100000000000000, 999999999999999);
    $expiry = date('m/y', strtotime('+3 years'));
    $cvv = rand(100, 999);
    
    if (SECURE_MODE) {
        // SECURE: Using prepared statement
        $stmt = $conn->prepare("INSERT INTO virtual_cards (user_id, card_number, expiry, cvv, type, currency) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiis", $user['id'], $card_num, $expiry, $cvv, $type, $currency);
        $stmt->execute();
    } else {
        // VULNERABLE: SQL injection in $type and $currency
        $conn->query("INSERT INTO virtual_cards (user_id, card_number, expiry, cvv, type, currency) VALUES ({$user['id']}, '$card_num', '$expiry', '$cvv', '$type', '$currency')");
    }
    $message = "Virtual card created successfully!";
}

if (isset($_POST['top_up'])) {
    $card_id = (int)$_POST['card_id'];
    $amount = (float)$_POST['amount'];
    
    if ($user['balance'] >= $amount) {
        if (SECURE_MODE) {
            // SECURE: Using prepared statements
            $stmt1 = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt1->bind_param("di", $amount, $user['id']);
            $stmt1->execute();
            
            $stmt2 = $conn->prepare("UPDATE virtual_cards SET balance = balance + ? WHERE id = ?");
            $stmt2->bind_param("di", $amount, $card_id);
            $stmt2->execute();
        } else {
            // VULNERABLE: SQL injection possible
            $conn->query("UPDATE users SET balance = balance - $amount WHERE id = {$user['id']}");
            $conn->query("UPDATE virtual_cards SET balance = balance + $amount WHERE id = $card_id");
        }
        log_transaction($conn, $user['id'], 'debit', "Top-up virtual card", $amount);
        $message = "Top-up successful!";
        $user = get_user_data($conn, $user['id']);
    } else {
        $message = "Insufficient balance!";
    }
}

if (SECURE_MODE) {
    // SECURE: Using prepared statement
    $stmt = $conn->prepare("SELECT * FROM virtual_cards WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $cards = $stmt->get_result();
} else {
    // VULNERABLE: SQL injection possible
    $cards = $conn->query("SELECT * FROM virtual_cards WHERE user_id = {$user['id']}");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Cards - V-Bank</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/card.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</head>
<body class="light">
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <header class="top-bar">
                <div>
                    <h1>Virtual Cards</h1>
                    <p class="page-subtitle">Manage your digital spending everywhere</p>
                </div>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <!-- Create Card Form -->
                <div class="card">
                    <h3>Create New Card</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Card Type</label>
                            <select name="card_type" class="search-input full-width">
                                <option value="standard">Standard (Free)</option>
                                <option value="premium">Premium ($10)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Currency</label>
                            <select name="currency" class="search-input full-width">
                                <option value="USD">USD</option>
                                <option value="BTC">BTC</option>
                                <option value="ETH">ETH</option>
                            </select>
                        </div>
                        <button type="submit" name="create_card" class="btn btn-primary full-width mt-1 custom-btn">
                            <i class="fas fa-plus"></i> Create Card
                        </button>
                    </form>
                </div>

                <!-- Balance Display -->
                <div class="card">
                    <h3>Your Available Balance</h3>
                    <div class="balance-text">$<?php echo number_format($user['balance'], 2); ?></div>
                    <p style="color: var(--text-muted); font-size: 0.8rem;">Funds available for card top-up</p>
                </div>
            </div>

            <h3 class="section-title">My Virtual Cards</h3>
            <div class="virtual-cards-container">
                <?php if ($cards && $cards->num_rows > 0): ?>
                    <?php while ($c = $cards->fetch_assoc()): ?>
                        <div class="card card-item">
                            <!-- Visual Credit Card -->
                            <div class="card-visual <?php echo $c['type']; ?>">
                                <div class="card-visual-header">
                                    <span class="card-brand">V-BANK</span>
                                    <span class="card-type-label"><?php echo strtoupper($c['type']); ?></span>
                                </div>
                                <div class="card-number"><?php echo wordwrap($c['card_number'], 4, ' ', true); ?></div>
                                <div class="card-visual-footer">
                                    <div class="v-label">EXP: <?php echo $c['expiry']; ?></div>
                                    <div class="v-label">CVV: ***</div>
                                    <div class="v-currency"><?php echo $c['currency']; ?></div>
                                </div>
                            </div>

                            <!-- Card Info & Top up -->
                            <div class="card-balance-info">
                                <span>Current Balance:</span>
                                <strong><?php echo number_format($c['balance'], 2); ?> <?php echo $c['currency']; ?></strong>
                            </div>

                            <form method="POST" class="top-up-form">
                                <input type="hidden" name="card_id" value="<?php echo $c['id']; ?>">
                                <div class="input-group">
                                    <input type="number" name="amount" class="search-input" placeholder="0.00" step="0.01" required>
                                    <button type="submit" name="top_up" class="btn btn-primary">Top-up</button>
                                </div>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card full-width text-center" style="grid-column: 1/-1;">
                        <p style="color: var(--text-muted);">You don't have any virtual cards yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>