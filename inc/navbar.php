<?php
// inc/navbar.php - Navigation Header Bar with Auto-Translation Script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_lang = $_SESSION['lang'] ?? ($_COOKIE['lang_pref'] ?? 'en');

$nav_user = null;
if (isset($_SESSION['user_id']) && isset($pdo)) {
    $stmtNav = $pdo->prepare("SELECT fullname, email, real_balance, demo_balance FROM users WHERE id = ?");
    $stmtNav->execute([$_SESSION['user_id']]);
    $nav_user = $stmtNav->fetch();
}
?>
<nav class="navbar">
    <div class="nav-container">
        <!-- Left Side: Hamburger & Logo -->
        <div class="nav-left">
            <button class="hamburger-btn" id="hamburgerToggle" aria-label="Toggle Navigation">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a href="index.php" class="brand-logo">
                <i class="fa-solid fa-chart-line logo-icon"></i>
                <span>Arbitrage<strong style="color: var(--green);">Pro</strong></span>
            </a>
        </div>

        <!-- Center: Navigation Links -->
        <div class="nav-links-wrapper" id="navMenuWrapper">
            <div class="nav-links">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-house"></i> Home</a>
                <a href="all-trades.php" class="nav-item"><i class="fa-solid fa-list-check"></i> All Trades</a>
                <a href="trade-results.php" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i> My Trades</a>
            </div>
        </div>

        <!-- Right Side: Translator & Auth -->
        <div class="nav-actions-right">
            <!-- Language Selector Button -->
            <a href="language.php" class="lang-btn" title="Select Language">
                <i class="fa-solid fa-globe"></i> <span><?= strtoupper($current_lang) ?></span>
            </a>

            <!-- Authentication / User Profile -->
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
                            <i class="fa-solid fa-circle-user"></i> <span><?= htmlspecialchars(explode(' ', $nav_user['fullname'])[0]) ?></span>
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
</nav>

<!-- Hidden Google Translate Element required for automated translation -->
<div id="google_translate_element" style="display:none;"></div>

<script type="text/javascript">
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'en',
        includedLanguages: 'en,es,fr,de,pt,it,ru,zh-CN,ja,ko,ar,hi,tr,nl,pl,vi,id,tl,uk,el,sv,ro,th,bn,fa,ur,he,cs,hu,da,fi,no,ms,sw,ta,te,mr,gu,pa,ml',
        autoDisplay: false
    }, 'google_translate_element');
}
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<script>
// Mobile Hamburger Toggle Script
document.getElementById('hamburgerToggle').addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('navMenuWrapper').classList.toggle('active');
});

document.addEventListener('click', function() {
    const menu = document.getElementById('navMenuWrapper');
    if (menu.classList.contains('active')) {
        menu.classList.remove('active');
    }
});

document.getElementById('navMenuWrapper').addEventListener('click', function(e) {
    e.stopPropagation();
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

/* Hide Google Translate Banner / Top Frame if it appears */
.goog-te-banner-frame { display: none !important; }
body { top: 0px !important; }

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

.nav-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.hamburger-btn {
    display: none;
    background: transparent;
    border: none;
    color: var(--text-main);
    font-size: 20px;
    cursor: pointer;
    padding: 8px;
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

.nav-links-wrapper {
    display: flex;
    align-items: center;
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
    padding: 6px 0;
}

.nav-item:hover { color: var(--text-main); }

.nav-actions-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.lang-btn {
    background: #1e2329;
    border: 1px solid var(--border-color);
    color: var(--text-main);
    padding: 8px 12px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: border-color 0.2s;
}
.lang-btn:hover { border-color: var(--green); color: var(--green); }

.nav-auth {
    display: flex;
    align-items: center;
    gap: 12px;
}

.balance-badge {
    background: #1e2329;
    border: 1px solid var(--border-color);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
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
    padding: 8px 12px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
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

.user-dropdown:hover .dropdown-menu, .user-dropdown:focus-within .dropdown-menu { display: block; }

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
    padding: 8px 14px;
    font-size: 13px;
}

.btn-nav-register {
    background: var(--green);
    color: #000;
    text-decoration: none;
    font-weight: 700;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
}

/* Responsive Mobile Styles */
@media (max-width: 992px) {
    .hamburger-btn { display: flex; align-items: center; justify-content: center; }
    
    .nav-links-wrapper {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: var(--bg-card);
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-color);
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    }

    .nav-links-wrapper.active { display: block; }

    .nav-links {
        flex-direction: column;
        gap: 12px;
    }

    .nav-actions-right {
        gap: 8px;
    }

    .balance-badge { display: none; }
}
    /* Hide content briefly until Google Translate initializes to prevent flashing */
body {
    opacity: 0;
    animation: fadeIn 0.3s ease-in-out forwards;
}
@keyframes fadeIn {
    to { opacity: 1; }
}
</style>
