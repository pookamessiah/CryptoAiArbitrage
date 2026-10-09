<?php
// trade-results.php - Trade Status, Live Progress, and Outcome Page
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

// Ensure user authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$trade_id = isset($_GET['trade_id']) ? (int)$_GET['trade_id'] : 0;

$single_trade = null;
$user_trades = [];

if ($trade_id > 0) {
    // Fetch specific trade detail
    $stmt = $pdo->prepare("
        SELECT ut.*, ad.symbol, ad.coin_name, ad.buy_exchange, ad.sell_exchange, ad.buy_price, ad.sell_price, ad.estimated_time
        FROM user_trades ut
        INNER JOIN arbitrage_deals ad ON ut.deal_id = ad.id
        WHERE ut.id = ? AND ut.user_id = ?
    ");
    $stmt->execute([$trade_id, $user_id]);
    $single_trade = $stmt->fetch();
}

// Fetch all trades for current user
$stmtAll = $pdo->prepare("
    SELECT ut.*, ad.symbol, ad.coin_name, ad.buy_exchange, ad.sell_exchange
    FROM user_trades ut
    INNER JOIN arbitrage_deals ad ON ut.deal_id = ad.id
    WHERE ut.user_id = ?
    ORDER BY ut.id DESC
");
$stmtAll->execute([$user_id]);
$user_trades = $stmtAll->fetchAll();

// Support details
$support = ['phone' => '+1 (800) 123-4567', 'telegram' => '@ArbitrageSupport', 'email' => 'support@yourdomain.com'];
$stmtSup = $pdo->query("SELECT * FROM support_info LIMIT 1");
if ($dbSup = $stmtSup->fetch()) {
    $support = $dbSup;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade Results & Analytics | ArbitragePro</title>
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
            width: min(1100px, calc(100% - 32px));
            margin: 32px auto;
        }

        .page-title {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Detailed Card Styles */
        .detail-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 28px;
            margin-bottom: 32px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }

        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 24px;
        }

        .badge-status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pending { background: rgba(255, 193, 7, 0.15); color: var(--warning); border: 1px solid var(--warning); }
        .status-in_progress { background: rgba(43, 108, 176, 0.2); color: #63b3ed; border: 1px solid #63b3ed; }
        .status-completed { background: rgba(14, 203, 129, 0.15); color: var(--green); border: 1px solid var(--green); }
        .status-cancelled { background: rgba(246, 70, 93, 0.15); color: var(--red); border: 1px solid var(--red); }

        /* Multi-step Visual Progress Tracker */
        .progress-tracker {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 30px 0 40px;
        }

        .progress-tracker::before {
            content: '';
            position: absolute;
            top: 18px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--border-color);
            z-index: 1;
        }

        .step-item {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }

        .step-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #1e2329;
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .step-item.active .step-icon {
            background: var(--green);
            border-color: var(--green);
            color: #000;
        }

        .step-item.current .step-icon {
            background: var(--accent-blue);
            border-color: #63b3ed;
            color: #fff;
            box-shadow: 0 0 12px rgba(99, 179, 237, 0.6);
        }

        .step-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .step-item.active .step-label { color: var(--text-main); }

        /* Key Metric Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            background: #1e2329;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .metric-box label { display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 4px; }
        .metric-box span { font-size: 18px; font-weight: 700; }

        /* Admin Note Box */
        .admin-note-box {
            background: rgba(43, 108, 176, 0.1);
            border-left: 4px solid var(--accent-blue);
            padding: 16px;
            border-radius: 0 8px 8px 0;
            margin-top: 20px;
        }

        .admin-note-box h5 { color: #63b3ed; font-size: 14px; margin-bottom: 6px; }
        .admin-note-box p { font-size: 14px; color: var(--text-main); }

        /* History Table */
        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            overflow-x: auto;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .history-table th {
            padding: 12px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
        }

        .history-table td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .history-table tr:last-child td { border-bottom: none; }
        .history-table tr:hover { background: var(--bg-hover); }

        .btn-view {
            color: var(--accent-blue);
            text-decoration: none;
            font-weight: 600;
        }

        .btn-view:hover { text-decoration: underline; }

        /* Support Floating Widget */
        .floating-support { position: fixed; bottom: 20px; right: 20px; z-index: 99; display: flex; flex-direction: column; gap: 10px; }
        .support-btn { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.4); }
        .sup-telegram { background: #0088cc; } .sup-phone { background: #25d366; } .sup-email { background: var(--accent-blue); }

        @media (max-width: 768px) {
            .metrics-grid { grid-template-columns: repeat(2, 1fr); }
            .progress-tracker { flex-direction: column; gap: 16px; }
            .progress-tracker::before { display: none; }
            .step-item { display: flex; align-items: center; gap: 12px; text-align: left; }
            .step-icon { margin: 0; }
        }
    </style>
</head>
<body>

    <?php include 'inc/navbar.php'; ?>

    <main class="container">
        
        <?php if ($single_trade): ?>
            <!-- Single Selected Trade Detailed Status Card -->
            <div class="page-title">
                <i class="fa-solid fa-square-poll-vertical" style="color: var(--green);"></i> 
                Trade Details #TRD-<?= (int)$single_trade['id'] ?>
            </div>

            <div class="detail-card">
                <div class="status-header">
                    <div>
                        <h3 style="font-size: 22px; font-weight: 700;">
                            <?= e($single_trade['coin_name']) ?> (<?= e($single_trade['symbol']) ?>/USDT)
                        </h3>
                        <span style="font-size: 13px; color: var(--text-muted);">
                            Executed via <?= e(strtoupper($single_trade['account_type'])) ?> Account • Started: <?= e(date('M d, Y - H:i', strtotime($single_trade['started_at']))) ?>
                        </span>
                    </div>

                    <span class="badge-status status-<?= e($single_trade['status']) ?>">
                        <?= e(str_replace('_', ' ', $single_trade['status'])) ?>
                    </span>
                </div>

                <!-- Multi-step Visual Progress Tracker -->
                <div class="progress-tracker">
                    <?php
                    $status = $single_trade['status'];
                    $s1 = ($status == 'pending' || $status == 'in_progress' || $status == 'completed') ? 'active' : '';
                    $s2 = ($status == 'in_progress' || $status == 'completed') ? 'active' : (($status == 'pending') ? 'current' : '');
                    $s3 = ($status == 'in_progress') ? 'current' : (($status == 'completed') ? 'active' : '');
                    $s4 = ($status == 'completed') ? 'active' : '';
                    ?>
                    <div class="step-item <?= $s1 ?>">
                        <div class="step-icon"><i class="fa-solid fa-check"></i></div>
                        <div class="step-label">Signal Selected</div>
                    </div>
                    <div class="step-item <?= $s2 ?>">
                        <div class="step-icon"><i class="fa-solid fa-building-columns"></i></div>
                        <div class="step-label">Capital Allocated</div>
                    </div>
                    <div class="step-item <?= $s3 ?>">
                        <div class="step-icon"><i class="fa-solid fa-arrows-rotate fa-spin"></i></div>
                        <div class="step-label">Cross-Exchange Execution</div>
                    </div>
                    <div class="step-item <?= $s4 ?>">
                        <div class="step-icon"><i class="fa-solid fa-flag-checkered"></i></div>
                        <div class="step-label">Profits Settled</div>
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div class="metrics-grid">
                    <div class="metric-box">
                        <label>Investment Amount</label>
                        <span>$<?= number_format($single_trade['amount'], 2) ?></span>
                    </div>
                    <div class="metric-box">
                        <label>Target Spread</label>
                        <span style="color: var(--green);">+<?= e($single_trade['profit_percentage']) ?>%</span>
                    </div>
                    <div class="metric-box">
                        <label>Expected Profit</label>
                        <span style="color: var(--green);">+$<?= number_format($single_trade['potential_profit'], 2) ?></span>
                    </div>
                    <div class="metric-box">
                        <label>Realized Net Return</label>
                        <?php if ($single_trade['status'] === 'completed'): ?>
                            <span style="color: var(--green);">$<?= number_format($single_trade['amount'] + $single_trade['realized_profit'], 2) ?></span>
                        <?php else: ?>
                            <span style="color: var(--warning);">Pending...</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="font-size: 14px; color: var(--text-muted); display: flex; gap: 20px;">
                    <span><strong>Buy Exchange:</strong> <?= e($single_trade['buy_exchange']) ?> ($<?= number_format($single_trade['buy_price'], 2) ?>)</span>
                    <span><strong>Sell Exchange:</strong> <?= e($single_trade['sell_exchange']) ?> ($<?= number_format($single_trade['sell_price'], 2) ?>)</span>
                </div>

                <!-- Admin Remarks/Comments -->
                <?php if (!empty($single_trade['admin_comment'])): ?>
                    <div class="admin-note-box">
                        <h5><i class="fa-solid fa-comment-dots"></i> Operations Manager Note</h5>
                        <p><?= e($single_trade['admin_comment']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Trade History Table -->
        <div class="page-title">
            <i class="fa-solid fa-clock-rotate-left" style="color: var(--accent-blue);"></i> Trade History & Results
        </div>

        <div class="table-card">
            <?php if (!empty($user_trades)): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Trade ID</th>
                            <th>Pair</th>
                            <th>Account</th>
                            <th>Amount</th>
                            <th>Spread</th>
                            <th>Target Profit</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_trades as $t): ?>
                            <tr>
                                <td><strong>#TRD-<?= (int)$t['id'] ?></strong></td>
                                <td><?= e($t['symbol']) ?>/USDT</td>
                                <td><span style="text-transform: uppercase; font-size: 12px; color: var(--text-muted);"><?= e($t['account_type']) ?></span></td>
                                <td>$<?= number_format($t['amount'], 2) ?></td>
                                <td style="color: var(--green);">+<?= e($t['profit_percentage']) ?>%</td>
                                <td style="color: var(--green);">+$<?= number_format($t['potential_profit'], 2) ?></td>
                                <td>
                                    <span class="badge-status status-<?= e($t['status']) ?>" style="font-size: 11px; padding: 4px 10px;">
                                        <?= e(str_replace('_', ' ', $t['status'])) ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-muted); font-size: 13px;"><?= e(date('M d, H:i', strtotime($t['started_at']))) ?></td>
                                <td><a href="trade-results.php?trade_id=<?= (int)$t['id'] ?>" class="btn-view">Details <i class="fa-solid fa-angle-right"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <i class="fa-solid fa-chart-line" style="font-size: 38px; margin-bottom: 12px;"></i>
                    <p>No trade executions recorded yet. Visit the <a href="index.php" style="color: var(--green);">home page</a> to launch your first arbitrage trade.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- Floating Support Bar -->
    <div class="floating-support">
        <a href="https://t.me/<?= e(ltrim($support['telegram'], '@')) ?>" target="_blank" class="support-btn sup-telegram" title="Telegram Support">
            <i class="fa-brands fa-telegram"></i>
        </a>
        <a href="tel:<?= e($support['phone']) ?>" class="support-btn sup-phone" title="Phone Support">
            <i class="fa-solid fa-phone"></i>
        </a>
        <a href="mailto:<?= e($support['email']) ?>" class="support-btn sup-email" title="Email Support">
            <i class="fa-solid fa-envelope"></i>
        </a>
    </div>

</body>
</html>
