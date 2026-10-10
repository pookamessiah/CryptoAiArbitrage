<?php
// trade-results.php - Trade Status & Auto-Completion Handler
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

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;

if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

$trade_id = isset($_GET['trade_id']) ? (int)$_GET['trade_id'] : 0;
$trade = null;

if ($pdo && $trade_id > 0) {
    try {
        // Fetch trade record ensuring it belongs to the logged-in user
        $stmtTrade = $pdo->prepare("SELECT * FROM user_trades WHERE id = ? AND user_id = ?");
        $stmtTrade->execute([$trade_id, $user_id]);
        $trade = $stmtTrade->fetch(PDO::FETCH_ASSOC);

        // Check if trade is pending and has reached its expiration time
        if ($trade && ($trade['status'] === 'pending' || $trade['status'] === 'active')) {
            $now = time();
            $expires_at = strtotime($trade['expires_at'] ?? 'now');

            // If expiration time has passed, auto-complete the trade
            if ($now >= $expires_at) {
                $pdo->beginTransaction();

                // 1. Update trade status to completed/won
                $updateTrade = $pdo->prepare("UPDATE user_trades SET status = 'completed' WHERE id = ?");
                $updateTrade->execute([$trade_id]);

                // 2. Calculate return (Principal + Profit)
                $total_return = (float)$trade['amount'] + (float)$trade['potential_profit'];

                // 3. Credit user balance
                if ($trade['account_type'] === 'demo') {
                    $updateBal = $pdo->prepare("UPDATE users SET demo_balance = demo_balance + ? WHERE id = ?");
                } else {
                    $updateBal = $pdo->prepare("UPDATE users SET real_balance = real_balance + ? WHERE id = ?");
                }
                $updateBal->execute([$total_return, $user_id]);

                $pdo->commit();

                // Refresh trade record data after update
                $stmtTrade->execute([$trade_id, $user_id]);
                $trade = $stmtTrade->fetch(PDO::FETCH_ASSOC);
            }
        }
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

// Fetch user data for balance display
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Support info
$support = ['phone' => '+18001234567', 'telegram' => '@ArbitrageSupport', 'email' => 'support@yourdomain.com'];
if ($pdo) {
    try {
        $stmtSup = $pdo->query("SELECT * FROM support_info LIMIT 1");
        if ($dbSup = $stmtSup->fetch(PDO::FETCH_ASSOC)) {
            $support = $dbSup;
        }
    } catch (Exception $e) {}
}
$whatsapp_number = preg_replace('/[^0-9]/', '', $support['phone']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade Execution Results | ArbitragePro</title>
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
            width: min(800px, calc(100% - 32px));
            margin: 40px auto;
        }

        .result-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 32px;
            text-align: center;
        }

        .status-icon {
            font-size: 54px;
            margin-bottom: 16px;
        }
        .status-icon.pending { color: var(--warning); }
        .status-icon.completed, .status-icon.won { color: var(--green); }
        .status-icon.lost { color: var(--red); }

        .status-title {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .status-subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 28px;
        }

        .details-grid {
            background: #1e2329;
            border-radius: 8px;
            padding: 20px;
            text-align: left;
            margin-bottom: 28px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .detail-row:last-child { margin-bottom: 0; }
        .detail-row span { color: var(--text-muted); }
        .detail-row strong { color: #fff; }

        .timer-box {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid var(--warning);
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 28px;
            font-size: 15px;
            color: #ffe066;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
        }

        .btn-main {
            background: var(--green);
            color: #000;
            font-weight: 700;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn-main:hover { opacity: 0.9; }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-secondary:hover { background: var(--bg-hover); }

        .floating-support { position: fixed; bottom: 20px; right: 20px; z-index: 99; display: flex; flex-direction: column; gap: 10px; }
        .support-btn { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.4); }
        .sup-telegram { background: #0088cc; } .sup-whatsapp { background: #25d366; } .sup-email { background: var(--accent-blue); }
    </style>
</head>
<body>

    <?php if (file_exists('inc/navbar.php')) include 'inc/navbar.php'; ?>

    <main class="container">
        <div class="result-card">
            <?php if (!$trade): ?>
                <div class="status-icon lost"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <h1 class="status-title">Trade Not Found</h1>
                <p class="status-subtitle">The requested trade record does not exist or you do not have permission to view it.</p>
                <a href="index.php" class="btn-main">Return to Dashboard</a>
            <?php else: ?>
                <?php 
                    $is_completed = ($trade['status'] === 'completed' || $trade['status'] === 'won');
                    $is_lost = ($trade['status'] === 'lost');
                    $status_class = $is_completed ? 'completed' : ($is_lost ? 'lost' : 'pending');
                ?>

                <div class="status-icon <?= $status_class ?>">
                    <?php if ($is_completed): ?>
                        <i class="fa-solid fa-circle-check"></i>
                    <?php elseif ($is_lost): ?>
                        <i class="fa-solid fa-circle-xmark"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-spinner fa-spin"></i>
                    <?php endif; ?>
                </div>

                <h1 class="status-title">
                    <?php if ($is_completed): ?>
                        Trade Successfully Completed
                    <?php elseif ($is_lost): ?>
                        Trade Unsuccessful
                    <?php else: ?>
                        Arbitrage Trade in Progress
                    <?php endif; ?>
                </h1>
                
                <p class="status-subtitle">
                    <?php if ($is_completed): ?>
                        Arbitrage spread captured successfully. Principal and profits have been credited to your <?= ucfirst($trade['account_type']) ?> balance.
                    <?php elseif ($is_lost): ?>
                        Trade execution encountered network congestion or slippage limit.
                    <?php else: ?>
                        Our automated routing system is executing your cross-exchange arbitrage trade.
                    <?php endif; ?>
                </p>

                <?php if (!$is_completed && !$is_lost): ?>
                    <div class="timer-box" id="timerBox">
                        <i class="fa-regular fa-clock"></i> Estimated Completion Time: <span id="countdown">Calculating...</span>
                    </div>
                <?php endif; ?>

                <div class="details-grid">
                    <div class="detail-row">
                        <span>Trade ID:</span>
                        <strong>#<?= (int)$trade['id'] ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Account Type:</span>
                        <strong><?= ucfirst($trade['account_type']) ?> Account</strong>
                    </div>
                    <div class="detail-row">
                        <span>Invested Amount:</span>
                        <strong>$<?= number_format((float)$trade['amount'], 2) ?> USDT</strong>
                    </div>
                    <div class="detail-row">
                        <span>Profit Margin Spread:</span>
                        <strong style="color: var(--green);">+<?= number_format((float)$trade['profit_percentage'], 2) ?>%</strong>
                    </div>
                    <div class="detail-row">
                        <span>Potential / Realized Profit:</span>
                        <strong style="color: var(--green);">+$<?= number_format((float)$trade['potential_profit'], 2) ?> USDT</strong>
                    </div>
                    <div class="detail-row">
                        <span>Total Return:</span>
                        <strong>$<?= number_format((float)$trade['amount'] + (float)$trade['potential_profit'], 2) ?> USDT</strong>
                    </div>
                    <div class="detail-row">
                        <span>Status:</span>
                        <strong style="text-transform: uppercase; color: <?= $is_completed ? 'var(--green)' : ($is_lost ? 'var(--red)' : 'var(--warning)') ?>;">
                            <?= e($trade['status']) ?>
                        </strong>
                    </div>
                    <div class="detail-row">
                        <span>Expires At:</span>
                        <strong><?= e($trade['expires_at']) ?></strong>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="index.php" class="btn-main"><i class="fa-solid fa-bolt"></i> Explore More Trades</a>
                    <a href="dashboard.php" class="btn-secondary"><i class="fa-solid fa-wallet"></i> View Account Balance</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div class="floating-support">
        <a href="https://t.me/<?= e(ltrim($support['telegram'], '@')) ?>" target="_blank" class="support-btn sup-telegram" title="Telegram Support"><i class="fa-brands fa-telegram"></i></a>
        <a href="https://wa.me/<?= e($whatsapp_number) ?>?text=Hello%20Support%20regarding%20Trade%20ID%20<?= $trade_id ?>" target="_blank" class="support-btn sup-whatsapp" title="WhatsApp Support"><i class="fa-brands fa-whatsapp"></i></a>
        <a href="mailto:<?= e($support['email']) ?>" class="support-btn sup-email" title="Email Support"><i class="fa-solid fa-envelope"></i></a>
    </div>

    <?php if ($trade && !$is_completed && !$is_lost): ?>
    <script>
        // Countdown timer for pending trades
        const expiresAt = new Date("<?= $trade['expires_at'] ?>").getTime();

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = expiresAt - now;

            if (distance < 0) {
                document.getElementById("countdown").innerText = "Completing trade...";
                setTimeout(() => { location.reload(); }, 1500); // Auto-reload to trigger PHP completion handler
                return;
            }

            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("countdown").innerText = minutes + "m " + seconds + "s remaining";
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();
    </script>
    <?php endif; ?>
</body>
</html>
