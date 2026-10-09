<?php
// inc/navbar.php - Navigation Header Bar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nav_user = null;
if (isset($_SESSION['user_id']) && isset($pdo)) {
    $stmtNav = $pdo->prepare("SELECT fullname, email, real_balance, demo_balance FROM users WHERE id = ?");
    $stmtNav->execute([$_SESSION['user_id']]);
    $nav_user = $stmtNav->fetch();
}
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="brand-logo">
            <i class="fa-solid fa-chart-line logo-icon"></i>
            <span>Arbitrage<strong style="color: var(--green);">Pro</strong></span>
        </a>

        <div class="nav-links">
            <a href="index.php" class="nav-item"><i class="fa-solid fa-house"></i> Home</a>
            <a href="all-trades.php" class="nav-item"><i class="fa-solid fa-list-check"></i> All Trades</a>
            <a href="trade-results.php" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i> My Trades</a>
        </div>

        <div class="nav-auth">
            <?php if ($nav_user): ?>
                <div class="balance-badge">
                    <span class="bal-label">Real:</span>
                    <strong class="bal-val" style="color: var(--green);">$<?= number_format($nav_user['real_balance'], 2) ?></strong>
                    <span class="bal-divider">|</span>
                    <span class="bal-label">Demo:</span>
                    <strong class="bal-val" style="color: #ffc107;">$<?= number_format($nav_user['demo_balance'], 2) ?></strong>
                </div>

                <div class="user-dropdown">
                    <button class="user-btn">
                        <i class="fa-solid fa-circle-user"></i> <?= e(explode(' ', $nav_user['fullname'])[0]) ?>
                    </button>
                    <div class="dropdown-menu">
                        <a href="deposit.php"><i class="fa-solid fa-wallet"></i> Deposit Funds</a>
                        <a href="withdraw.php"><i class="fa-solid fa-money-bill-transfer"></i> Withdraw</a>
                        <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Account Dashboard</a>
                        <hr style="border-color: var(--border-color); margin: 6px 0;">
                        <a href="logout.php" style="color: var(--red);"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn-nav-login">Log In</a>
                <a href="register.php" class="btn-nav-register">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
.navbar {
    background: #181a20;
    border-bottom: 1px solid #2b313a;
    position: sticky;
    top: 0;
    z-index: 100;
    padding: 12px 0;
}

.nav-container {
    width: min(1280px, calc(100% - 32px));
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.brand-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    text-decoration: none;
}

.logo-icon {
    color: #0ecb81;
    font-size: 24px;
}

.nav-links {
    display: flex;
    gap: 24px;
}

.nav-item {
    color: #848e9c;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: color 0.2s;
}

.nav-item:hover { color: #eaecef; }

.nav-auth {
    display: flex;
    align-items: center;
    gap: 16px;
}

.balance-badge {
    background: #1e2329;
    border: 1px solid #2b313a;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bal-divider { color: #2b313a; }

.user-dropdown {
    position: relative;
    display: inline-block;
}

.user-btn {
    background: #1e2329;
    border: 1px solid #2b313a;
    color: #fff;
    padding: 8px 14px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    margin-top: 8px;
    background: #181a20;
    border: 1px solid #2b313a;
    border-radius: 8px;
    min-width: 180px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    z-index: 101;
    overflow: hidden;
}

.user-dropdown:hover .dropdown-menu { display: block; }

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    color: #eaecef;
    text-decoration: none;
    font-size: 13px;
}

.dropdown-menu a:hover { background: #2b313a; }

.btn-nav-login {
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    padding: 8px 16px;
    font-size: 14px;
}

.btn-nav-register {
    background: #0ecb81;
    color: #000;
    text-decoration: none;
    font-weight: 700;
    padding: 8px 18px;
    border-radius: 6px;
    font-size: 14px;
}

@media (max-width: 768px) {
    .nav-links { display: none; }
    .balance-badge { font-size: 11px; padding: 4px 10px; }
}
</style>
