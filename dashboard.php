<?php
// dashboard.php - User Account Overview Dashboard with Active Trade Filtering & Robust Analytics
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';

$pdo = null;
try {
    $db = new Database();
    $pdo = $db->connect();
} catch (Exception $e) {
    die($e->getMessage());
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Handle Mark Message as Read action
if (isset($_GET['mark_read'])) {
    $msg_id = (int)$_GET['mark_read'];
    $stmtRead = $pdo->prepare("UPDATE user_messages SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmtRead->execute([$msg_id, $user_id]);
    header("Location: dashboard.php");
    exit();
}

// Fetch User Info
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch Admin Direct Messages / Notifications
$stmtMsgs = $pdo->prepare("SELECT * FROM user_messages WHERE user_id = ? ORDER BY id DESC");
$stmtMsgs->execute([$user_id]);
$messages = $stmtMsgs->fetchAll(PDO::FETCH_ASSOC);

// Calculate User Stats
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_trades,
        SUM(CASE WHEN status IN ('pending', 'in_progress', 'active') THEN 1 ELSE 0 END) as active_trades,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_trades,
        SUM(CASE WHEN status = 'completed' THEN COALESCE(realized_profit, potential_profit, 0) ELSE 0 END) as total_profit
    FROM user_trades 
    WHERE user_id = ?
");
$stmtStats->execute([$user_id]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

// Handle Filtering (All Trades vs Active Trades)
$filter = $_GET['filter'] ?? 'all';
$tradeQuery = "
    SELECT ut.*, 
           COALESCE(ad.symbol, ut.symbol, 'BTC') as symbol, 
           COALESCE(ad.coin_name, ut.coin_name, 'Bitcoin') as coin_name
    FROM user_trades ut
    LEFT JOIN arbitrage_deals ad ON ut.deal_id = ad.id
    WHERE ut.user_id = ?
";

if ($filter === 'active') {
    $tradeQuery .= " AND ut.status IN ('pending', 'in_progress', 'active')";
}
$tradeQuery .= " ORDER BY ut.id DESC";

$stmtTrades = $pdo->prepare($tradeQuery);
$stmtTrades->execute([$user_id]);
$trades = $stmtTrades->fetchAll(PDO::FETCH_ASSOC);

// Support details
$support = ['phone' => '+1 (800) 123-4567', 'telegram' => '@ArbitrageSupport', 'email' => 'support@yourdomain.com'];
try {
    $stmtSup = $pdo->query("SELECT * FROM support_info LIMIT 1");
    if ($dbSup = $stmtSup->fetch(PDO::FETCH_ASSOC)) {
        $support = $dbSup;
    }
} catch (Exception $e) {}
$whatsapp_number = preg_replace('/[^0-9]/', '', $support['phone']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Dashboard | ArbitragePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0b0e11;
            --bg-card: #181a20;
            --bg-hover: #2b313a;
            --text-main: #eaecef;
            --text-muted: #848e9c;
            --green: #0ecb81;
            --red: #f6465d;
            --warning: #ffc107;
            --accent-blue: #2b6cb0;
            --border-color: #2b313a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Segoe UI', Roboto, -apple-system, sans-serif;
            line-height: 1.5;
            padding-bottom: 60px;
        }

        .container {
            width: min(1200px, calc(100% - 32px));
            margin: 32px auto;
        }

        .welcome-bar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .welcome-bar h2 { font-size: 22px; font-weight: 700; }
        .welcome-bar p { color: var(--text-muted); font-size: 14px; }

        .btn-group { display: flex; gap: 12px; }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
        }

        .btn-deposit { background: var(--green); color: #000; }
        .btn-withdraw { background: #1e2329; border: 1px solid var(--border-color); color: #fff; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }

        /* Admin Notifications Box */
        .msg-box {
            background: rgba(43, 108, 176, 0.15);
            border: 1px solid var(--accent-blue);
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .msg-box.unread { border-left: 4px solid var(--warning); background: rgba(255, 193, 7, 0.08); border-color: rgba(255, 193, 7, 0.3); }
        .msg-content p { font-size: 14px; color: #fff; }
        .msg-content span { font-size: 11px; color: var(--text-muted); }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-decoration: none;
            color: inherit;
            display: block;
            transition: border-color 0.2s, transform 0.2s;
        }
        .stat-card.clickable:hover {
            border-color: var(--accent-blue);
            transform: translateY(-2px);
        }

        .stat-title {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 800;
        }

        /* Recent Activity Table */
        .section-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            overflow-x: auto;
            margin-bottom: 32px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-header h3 { font-size: 18px; font-weight: 700; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }

        .data-table th {
            padding: 12px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
        }

        .data-table td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pending { background: rgba(255, 193, 7, 0.15); color: #ffc107; }
        .status-in_progress, .status-active { background: rgba(43, 108, 176, 0.2); color: #63b3ed; }
        .status-completed { background: rgba(14, 203, 129, 0.15); color: var(--green); }
        .status-cancelled, .status-lost { background: rgba(246, 70, 93, 0.15); color: var(--red); }

        .filter-badge {
            background: var(--bg-hover);
            color: #fff;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            margin-left: 10px;
        }
        .filter-badge.active { background: var(--accent-blue); }

        /* Floating Support Bar */
        .floating-support { position: fixed; bottom: 20px; right: 20px; z-index: 99; display: flex; flex-direction: column; gap: 10px; }
        .support-btn { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.4); }
        .sup-telegram { background: #0088cc; } .sup-phone { background: #25d366; } .sup-email { background: var(--accent-blue); }
    </style>
</head>
<body>

    <?php if (file_exists('inc/navbar.php')) include 'inc/navbar.php'; ?>

    <main class="container">
        <!-- Welcome Banner -->
        <div class="welcome-bar">
            <div>
                <h2>Welcome back, <?= e($user['fullname']) ?> 👋</h2>
                <p>Email: <?= e($user['email']) ?> • Account Status: <span style="color: var(--green); text-transform: capitalize; font-weight: 600;"><?= e($user['status']) ?></span></p>
            </div>
            <div class="btn-group">
                <a href="deposit.php" class="btn-action btn-deposit"><i class="fa-solid fa-wallet"></i> Deposit</a>
                <a href="withdraw.php" class="btn-action btn-withdraw"><i class="fa-solid fa-money-bill-transfer"></i> Withdraw</a>
            </div>
        </div>

        <!-- Admin Direct Messages / Notifications Section -->
        <?php if (!empty($messages)): ?>
        <div class="section-box">
            <div class="section-header" style="margin-bottom: 16px;">
                <h3><i class="fa-solid fa-bell" style="color: var(--warning);"></i> Admin Notifications & Messages</h3>
            </div>
            <div>
                <?php foreach ($messages as $m): ?>
                    <div class="msg-box <?= $m['is_read'] == 0 ? 'unread' : '' ?>">
                        <div class="msg-content">
                            <p><i class="fa-solid fa-bullhorn" style="color: var(--accent-blue); margin-right: 6px;"></i> <?= e($m['message']) ?></p>
                            <span>Received on: <?= e($m['created_at']) ?></span>
                        </div>
                        <?php if ($m['is_read'] == 0): ?>
                            <a href="dashboard.php?mark_read=<?= $m['id'] ?>" class="btn-action btn-deposit btn-sm" style="background: var(--accent-blue); color: #fff;">Mark Read</a>
                        <?php else: ?>
                            <span style="font-size: 12px; color: var(--green);"><i class="fa-solid fa-check"></i> Read</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Key Metrics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title"><i class="fa-solid fa-vault"></i> Real Balance</div>
                <div class="stat-value" style="color: var(--green);">$<?= number_format((float)$user['real_balance'], 2) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-title"><i class="fa-solid fa-flask"></i> Demo Balance</div>
                <div class="stat-value" style="color: #ffc107;">$<?= number_format((float)$user['demo_balance'], 2) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-title"><i class="fa-solid fa-chart-line"></i> Total Realized Profit</div>
                <div class="stat-value" style="color: var(--green);">+$<?= number_format((float)($stats['total_profit'] ?? 0), 2) ?></div>
            </div>

            <!-- Clickable Active Trades Card -->
            <a href="dashboard.php?filter=active" class="stat-card clickable">
                <div class="stat-title"><i class="fa-solid fa-arrows-spin"></i> Active Trades (Click to view)</div>
                <div class="stat-value" style="color: #63b3ed;"><?= (int)($stats['active_trades'] ?? 0) ?></div>
            </a>
        </div>

        <!-- Transactions & Trade History -->
        <div class="section-box">
            <div class="section-header">
                <div>
                    <h3>
                        <?= $filter === 'active' ? 'Active Ongoing Trades' : 'All Transactions & Trade History' ?>
                    </h3>
                </div>
                <div>
                    <a href="dashboard.php" class="filter-badge <?= $filter === 'all' ? 'active' : '' ?>">All Trades</a>
                    <a href="dashboard.php?filter=active" class="filter-badge <?= $filter === 'active' ? 'active' : '' ?>">Active Only</a>
                </div>
            </div>

            <?php if (!empty($trades)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asset Pair</th>
                            <th>Account</th>
                            <th>Amount</th>
                            <th>Target / Realized Profit</th>
                            <th>Status</th>
                            <th>Date / Expiry</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trades as $t): ?>
                            <tr>
                                <td><strong>#TRD-<?= (int)$t['id'] ?></strong></td>
                                <td><?= e($t['symbol']) ?>/USDT</td>
                                <td><span style="text-transform: uppercase; font-size: 12px; color: var(--text-muted);"><?= e($t['account_type']) ?></span></td>
                                <td>$<?= number_format((float)$t['amount'], 2) ?></td>
                                <td style="color: var(--green);">+$<?= number_format((float)($t['realized_profit'] ?: $t['potential_profit']), 2) ?></td>
                                <td>
                                    <span class="badge-status status-<?= e($t['status']) ?>">
                                        <?= e(str_replace('_', ' ', $t['status'])) ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-muted); font-size: 13px;">
                                    <?= e($t['expires_at'] ?? $t['created_at']) ?>
                                </td>
                                <td>
                                    <a href="trade-results.php?trade_id=<?= (int)$t['id'] ?>" class="btn-action btn-deposit btn-sm" style="background: var(--accent-blue); color: #fff;">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 30px;">
                    <?= $filter === 'active' ? 'No active ongoing trades found right now.' : 'No trade transactions executed yet.' ?> 
                    <a href="index.php" style="color: var(--green);">Browse available markets</a> to begin.
                </p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Floating Support -->
    <div class="floating-support">
        <a href="https://t.me/<?= e(ltrim($support['telegram'], '@')) ?>" target="_blank" class="support-btn sup-telegram" title="Telegram Support">
            <i class="fa-brands fa-telegram"></i>
        </a>
        <a href="https://wa.me/<?= e($whatsapp_number) ?>?text=Hello%20Support" target="_blank" class="support-btn sup-phone" title="WhatsApp Support">
            <i class="fa-brands fa-whatsapp"></i>
        </a>
        <a href="mailto:<?= e($support['email']) ?>" class="support-btn sup-email" title="Email Support">
            <i class="fa-solid fa-envelope"></i>
        </a>
    </div>

</body>
</html>
