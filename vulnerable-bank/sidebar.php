<div class="mobile-header">
    <div class="sidebar-brand" style="margin-bottom: 0;">
        <i class="fas fa-university"></i> <span>V-BANK</span>
    </div>
    <button class="btn" onclick="toggleSidebar()" style="background: var(--primary-glow); color: var(--primary);">
        <i class="fas fa-bars"></i>
    </button>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-university"></i> <span>V-BANK</span>
    </div>
    <div class="sidebar-nav">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-fw fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>
        <a href="transfer.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transfer.php' ? 'active' : ''; ?>">
            <i class="fas fa-fw fa-exchange-alt"></i> <span>Transfer</span>
        </a>
        <a href="loans.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'loans.php' ? 'active' : ''; ?>">
            <i class="fas fa-fw fa-hand-holding-usd"></i> <span>Loans</span>
        </a>
        <a href="cards.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'cards.php' ? 'active' : ''; ?>">
            <i class="fas fa-fw fa-credit-card"></i> <span>Cards</span>
        </a>
        <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-fw fa-user"></i> <span>Profile</span>
        </a>
        <a href="logout.php">
            <i class="fas fa-fw fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
    
    <div style="position: absolute; bottom: 20px; left: 25px; right: 25px;" class="sidebar-footer">
        <div style="background: var(--primary-glow); padding: 15px; border-radius: 1rem; font-size: 0.75rem; text-align: center; border: 1px solid var(--border-color);">
            <p style="margin: 0; font-weight: 700; color: var(--primary);">Platinum Member</p>
            <div style="width: 100%; height: 6px; background: var(--border-color); border-radius: 3px; margin-top: 10px; overflow: hidden;">
                <div style="width: 75%; height: 100%; background: var(--primary);"></div>
            </div>
        </div>
    </div>
</nav>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
</script>
