<?php
// inc/navbar.php - Navigation Header Bar with Hamburger Menu & Language Selector
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure language definitions are available
if (!isset($supported_languages)) {
    require_once __DIR__ . '/../language.php';
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

        <!-- Hamburger Menu Button (Mobile) -->
        <button class="hamburger-btn" id="hamburgerToggle" aria-label="Toggle Navigation">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="nav-menu-wrapper" id="navMenuWrapper">
            <div class="nav-links">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-house"></i> Home</a>
                <a href="all-trades.php" class="nav-item"><i class="fa-solid fa-list-check"></i> All Trades</a>
                <a href="trade-results.php" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i> My Trades</a>
            </div>

            <div class="nav-actions-right">
                <!-- Language Selector Dropdown -->
                <div class="lang-dropdown">
                    <button class="lang-btn" aria-label="Select Language">
                        <i class="fa-solid fa-globe"></i> <span><?= strtoupper($current_lang) ?></span>
                    </button>
                    <div class="lang-menu">
                        <div class="lang-header">Select Language</div>
                        <div class="lang-list">
                            <?php foreach ($supported_languages as $code => $lang): ?>
                                <a href="?set_lang=<?= $code ?>" class="<?= $current_lang === $code ? 'active-lang' : '' ?>">
                                    <?= $lang['native'] ?> <span style="color: var(--text-muted); font-size: 11px;">(<?= $lang['name'] ?>)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
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
                                <i class="fa-solid fa-circle-user"></i> <?= htmlspecialchars(explode(' ', $nav_user['fullname'])[0]) ?>
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
        </div>
    </div>
</nav>

<script>
// Mobile Hamburger Toggle Script
document.getElementById('hamburgerToggle').addEventListener('click', function() {
    document.getElementById('navMenuWrapper').classList.toggle('active');
});
</script>

<style>
:root {
    --bg-dark: #0b0e11;
    --bg-card: #181a20;
    --bg-hover: #2b313a;
    --text-main: #eaecef;
    --text-muted: #848e9c;
    --green: #0ecb81;
    --red: #f6465d;
    --border-color: #2b313a;
}

.navbar {
    background: var(--bg-card);
    border-bottom: 1px solid var(--border-color);
    position: sticky;
    top: 0;
    z-index: 1000;
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
    color: var(--green);
    font-size: 24px;
}

.nav-menu-wrapper {
    display: flex;
    align-items: center;
    gap: 24px;
    flex: 1;
    justify-content: flex-end;
}

.nav-links {
    display: flex;
    gap: 24px;
}

.nav-item {
    color: var(--text-muted);
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: color 0.2s;
}

.nav-item:hover { color: var(--text-main); }

.nav-actions-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* Hamburger button hidden by default on desktop */
.hamburger-btn {
    display: none;
    background: transparent;
    border: none;
    color: var(--text-main);
    font-size: 22px;
    cursor: pointer;
}

/* Language Selector Styles */
.lang-dropdown {
    position: relative;
    display: inline-block;
}

.lang-btn {
    background: #1e2329;
    border: 1px solid var(--border-color);
    color: var(--text-main);
    padding: 8px 12px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
}

.lang-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    margin-top: 8px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    width: 200px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.5);
    z-index: 1010;
    overflow: hidden;
}

.lang-dropdown:hover .lang-menu { display: block; }

.lang-header {
    padding: 10px 14px;
    font-size: 11px;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border-color);
    font-weight: 700;
    text-transform: uppercase;
}

.lang-list {
    max-height: 250px;
    overflow-y: auto;
}

.lang-list::-webkit-scrollbar { width: 6px; }
.lang-list::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 3px; }

.lang-menu a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    color: var(--text-main);
    text-decoration: none;
    font-size: 13px;
}

.lang-menu a:hover, .lang-menu a.active-lang {
    background: var(--bg-hover);
    color: var(--green);
}

.nav-auth {
    display: flex;
    align-items: center;
    gap: 16px;
}

.balance-badge {
    background: #1e2329;
    border: 1px solid var(--border-color);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bal-divider { color: var(--border-color); }

.user-dropdown {
    position: relative;
    display: inline-block;
}

.user-btn {
    background: #1e2329;
    border: 1px solid var(--border-color);
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
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    min-width: 180px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    z-index: 1010;
    overflow: hidden;
}

.user-dropdown:hover .dropdown-menu { display: block; }

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    color: var(--text-main);
    text-decoration: none;
    font-size: 13px;
}

.dropdown-menu a:hover { background: var(--bg-hover); }

.btn-nav-login {
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    padding: 8px 16px;
    font-size: 14px;
}

.btn-nav-register {
    background: var(--green);
    color: #000;
    text-decoration: none;
    font-weight: 700;
    padding: 8px 18px;
    border-radius: 6px;
    font-size: 14px;
}

/* Responsive Mobile Styles */
@media (max-width: 992px) {
    .hamburger-btn { display: block; }
    
    .nav-menu-wrapper {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: var(--bg-card);
        flex-direction: column;
        align-items: flex-start;
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        gap: 20px;
    }

    .nav-menu-wrapper.active { display: flex; }

    .nav-links {
        flex-direction: column;
        width: 100%;
        gap: 14px;
    }

    .nav-actions-right {
        flex-direction: column;
        width: 100%;
        align-items: flex-start;
        gap: 14px;
    }

    .balance-badge { width: 100%; justify-content: space-between; }
    .user-dropdown { width: 100%; }
    .user-btn { width: 100%; justify-content: space-between; }
    .dropdown-menu { position: static; width: 100%; box-shadow: none; display: block; margin-top: 8px; }
    .lang-dropdown { width: 100%; }
    .lang-btn { width: 100%; justify-content: space-between; }
    .lang-menu { position: static; width: 100%; box-shadow: none; margin-top: 8px; }
}
</style>
