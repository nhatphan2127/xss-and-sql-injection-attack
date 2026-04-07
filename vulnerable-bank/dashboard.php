<?php
require_once 'functions.php';
redirect_if_not_logged_in();

$user = get_user_data($conn, $_SESSION['user_id']);
$transactions = get_transactions($conn, $user['id']);

// Reflected XSS: Search functionality that reflects the search query unsafely
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Loan summary
$loan_sql = "SELECT SUM(amount) as total FROM loans WHERE user_id = {$user['id']} AND status = 'approved'";
$loan_res = $conn->query($loan_sql);
$loan_data = $loan_res->fetch_assoc();
$total_loan = $loan_data['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | V-Bank</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</head>
<body class="light">
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <header class="top-bar">
                <div>
                    <h1 style="font-size: 2rem;">Overview</h1>
                    <p style="color: var(--text-muted); font-weight: 500;">Welcome back, <span style="color: var(--primary); font-weight: 700;"><?php echo $user['username']; ?></span></p>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="position: relative;">
                        <form method="GET">
                            <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                            <input type="text" name="search" class="search-input" placeholder="Search data..." style="padding-left: 2.5rem; width: 250px;" value="<?php echo $search_query; ?>">
                        </form>
                    </div>
                    <button class="btn btn-primary" onclick="toggleDarkMode()" style="width: 3rem; height: 3rem; padding: 0; justify-content: center; border-radius: 1rem;">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div style="display: flex; align-items: center; gap: 0.75rem; background: var(--card-bg); padding: 0.4rem 0.8rem; border-radius: 1rem; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                        <img src="<?php echo strpos($user['avatar'], 'uploads/') === 0 ? $user['avatar'] : 'uploads/' . $user['avatar']; ?>" 
                             style="width: 2.2rem; height: 2.2rem; border-radius: 0.75rem; object-fit: cover;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-weight: 700; font-size: 0.875rem; line-height: 1.2;"><?php echo $user['username']; ?></span>
                            <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600;">Platinum</span>
                        </div>
                    </div>
                </div>
            </header>

            <?php if ($search_query): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <!-- VULNERABLE: Reflected XSS -->
                    Search results for: <span style="font-weight: 800;"><?php echo $search_query; ?></span>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <div class="card" style="position: relative; overflow: hidden; background: linear-gradient(135deg, var(--primary), var(--primary-light)); border: none; color: white;">
                    <div style="position: absolute; right: -20px; top: -20px; font-size: 8rem; opacity: 0.1; transform: rotate(15deg);">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3 style="color: rgba(255,255,255,0.8);">Available Balance</h3>
                    <div class="balance-text" style="color: white; font-size: 2.5rem;">$<?php echo number_format($user['balance'], 2); ?></div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; font-size: 0.875rem; background: rgba(0,0,0,0.1); padding: 0.75rem; border-radius: 0.75rem; backdrop-filter: blur(4px);">
                        <span style="opacity: 0.9;">Acc: <span id="accNum" style="font-weight: 700;"><?php echo $user['account_number']; ?></span></span>
                        <button onclick="copyAcc()" class="btn" style="padding: 0.25rem 0.5rem; background: transparent; color: white;"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
                
                <div class="card">
                    <h3><i class="fas fa-hand-holding-usd" style="margin-right: 0.5rem; color: var(--danger);"></i> Total Debt</h3>
                    <div class="balance-text" style="color: var(--danger);">$<?php echo number_format($total_loan, 2); ?></div>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                        <a href="loans.php" style="color: var(--danger); text-decoration: none; font-size: 0.875rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                            Manage Loans <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="card">
                    <h3><i class="fas fa-bolt" style="margin-right: 0.5rem; color: var(--warning);"></i> Quick Actions</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.5rem;">
                        <a href="transfer.php" class="btn btn-primary" style="font-size: 0.75rem; justify-content: center; background: var(--primary-glow); color: var(--primary); border: 1px solid var(--primary);">
                            <i class="fas fa-paper-plane"></i> Send
                        </a>
                        <a href="cards.php" class="btn btn-primary" style="font-size: 0.75rem; justify-content: center; background: rgba(14, 165, 233, 0.1); color: var(--info); border: 1px solid var(--info);">
                            <i class="fas fa-credit-card"></i> Cards
                        </a>
                        <a href="bills.php" class="btn btn-primary" style="font-size: 0.75rem; justify-content: center; background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid var(--success);">
                            <i class="fas fa-file-invoice-dollar"></i> Bills
                        </a>
                        <a href="profile.php" class="btn btn-primary" style="font-size: 0.75rem; justify-content: center; background: rgba(100, 116, 139, 0.1); color: var(--secondary); border: 1px solid var(--secondary);">
                            <i class="fas fa-user-cog"></i> Setup
                        </a>
                    </div>
                </div>
            </div>

            <div class="card" style="padding: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid var(--border-color);">
                    <h2 style="font-size: 1.25rem; font-weight: 800;">Recent Transactions</h2>
                    <a href="bills.php" class="btn" style="color: var(--primary); font-weight: 700; background: var(--primary-glow); padding: 0.5rem 1rem;">View All</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($t = $transactions->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: <?php echo $t['type'] == 'credit' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)'; ?>; display: flex; align-items: center; justify-content: center; color: <?php echo $t['type'] == 'credit' ? 'var(--success)' : 'var(--danger)'; ?>;">
                                            <i class="fas fa-<?php echo $t['type'] == 'credit' ? 'arrow-down' : 'arrow-up'; ?>"></i>
                                        </div>
                                        <span style="font-weight: 700;"><?php echo $t['description']; ?></span>
                                    </div>
                                </td>
                                <td><span style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Finance</span></td>
                                <td style="color: var(--text-muted);"><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                                <td>
                                    <span style="font-weight: 800; color: <?php echo $t['type'] == 'credit' ? 'var(--success)' : 'var(--danger)'; ?>">
                                        <?php echo $t['type'] == 'credit' ? '+' : '-'; ?> $<?php echo number_format($t['amount'], 2); ?>
                                    </span>
                                </td>
                                <td><span class="status-pill status-pill-success">Completed</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Chat Widget - DOM XSS Vulnerable -->
    <div id="chat-widget">
        <div class="chat-button" onclick="toggleChat()">
            <i class="fas fa-comment-dots fa-lg"></i>
        </div>
        <div class="chat-window" id="chatWindow">
            <div class="chat-header">
                <span>V-Bank AI Assistant</span>
                <i class="fas fa-times" onclick="toggleChat()" style="cursor: pointer;"></i>
            </div>
            <div class="chat-messages" id="chatMessages">
                <div style="background: var(--bg-main); padding: 1rem; border-radius: 1rem; border-bottom-left-radius: 0; margin-bottom: 1rem; font-size: 0.875rem; border: 1px solid var(--border-color);">
                    Hello! I'm your AI banking assistant. How can I help you today?
                </div>
            </div>
            <div class="chat-input-area">
                <input type="text" id="chatInput" class="search-input" placeholder="Type your message..." onkeypress="if(event.key==='Enter') sendMessage()">
                <button onclick="sendMessage()" class="btn btn-primary" style="width: 3rem; height: 3rem; padding: 0; justify-content: center; border-radius: 0.75rem;">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <div id="toast" style="visibility: hidden; position: fixed; bottom: 3rem; left: 50%; transform: translateX(-50%); background: #1e293b; color: white; padding: 1rem 2rem; border-radius: 1rem; z-index: 1000; font-weight: 700; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        Copied to clipboard!
    </div>

    <script>
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
    }

    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }

    function toggleChat() {
        const win = document.getElementById('chatWindow');
        win.style.display = win.style.display === 'flex' ? 'none' : 'flex';
    }

    function sendMessage() {
        const input = document.getElementById('chatInput');
        const msg = input.value;
        if (!msg) return;

        const container = document.getElementById('chatMessages');
        
        // Add user message
        const userDiv = document.createElement('div');
        userDiv.style = "background: var(--primary); color: white; padding: 1rem; border-radius: 1rem; border-bottom-right-radius: 0; margin-bottom: 1rem; align-self: flex-end; margin-left: 2rem; font-size: 0.875rem; font-weight: 600;";
        userDiv.textContent = msg;
        container.appendChild(userDiv);

        // System response
        const botDiv = document.createElement('div');
        botDiv.style = "background: var(--bg-main); padding: 1rem; border-radius: 1rem; border-bottom-left-radius: 0; margin-bottom: 1rem; margin-right: 2rem; font-size: 0.875rem; border: 1px solid var(--border-color);";
        
        // VULNERABLE: DOM XSS via innerHTML
        botDiv.innerHTML = "Processing: <strong>" + msg + "</strong>";
        
        container.appendChild(botDiv);
        input.value = '';
        container.scrollTop = container.scrollHeight;
    }

    function copyAcc() {
        const acc = document.getElementById('accNum').innerText;
        navigator.clipboard.writeText(acc);
        const toast = document.getElementById('toast');
        toast.style.visibility = 'visible';
        setTimeout(() => toast.style.visibility = 'hidden', 2000);
    }

    // DOM XSS from URL Hash
    window.addEventListener('load', () => {
        if (window.location.hash) {
            const welcomeMsg = decodeURIComponent(window.location.hash.substring(1));
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success';
            // VULNERABLE: DOM XSS
            alertDiv.innerHTML = "<i class='fas fa-gift'></i> <span><strong>Promo Unlocked:</strong> " + welcomeMsg + "</span>";
            document.querySelector('.main-content').prepend(alertDiv);
        }
    });
    </script>
</body>
</html>
