<?php
// trade.php - Execute Arbitrage Trade via POST Parameters from Index/All-Trades
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
$user = null;

if ($is_logged_in && $pdo) {
    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
}

// Capture incoming POST parameters (with fallback defaults if accessed directly)
$symbol = $_POST['symbol'] ?? $_GET['symbol'] ?? 'BTC';
$coin_name = $_POST['coin_name'] ?? $_GET['coin_name'] ?? 'Bitcoin';
$buy_exchange = $_POST['buy_exchange'] ?? $_GET['buy_exchange'] ?? 'Binance (Spot)';
$sell_exchange = $_POST['sell_exchange'] ?? $_GET['sell_exchange'] ?? 'Hyperliquid (Perp)';
$buy_price = isset($_POST['buy_price']) ? (float)$_POST['buy_price'] : 64200.00;
$sell_price = isset($_POST['sell_price']) ? (float)$_POST['sell_price'] : 67153.20;
$profit_percentage = isset($_POST['profit_percentage']) ? (float)$_POST['profit_percentage'] : 4.60;
$estimated_time = $_POST['estimated_time'] ?? $_GET['estimated_time'] ?? '15 - 30 mins';

// If profit_percentage wasn't provided or is 0, compute it from buy/sell prices
if ($profit_percentage <= 0 && $buy_price > 0) {
    $profit_percentage = (($sell_price - $buy_price) / $buy_price) * 100;
}

$error_message = "";

// Handle Trade Execution Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_trade'])) {
    if (!$is_logged_in) {
        $error_message = "You must be logged in to execute a trade.";
    } else {
        $account_type = $_POST['account_type'] ?? 'real';
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);

        if (!$amount || $amount <= 0) {
            $error_message = "Please enter a valid investment amount.";
        } else {
            $current_balance = ($account_type === 'demo') ? (float)($user['demo_balance'] ?? 0) : (float)($user['real_balance'] ?? 0);

            if ($amount > $current_balance) {
                $error_message = "Insufficient balance in your " . ucfirst($account_type) . " Account.";
            } else {
                $potential_profit = $amount * ($profit_percentage / 100);
                $expires_at = date('Y-m-d H:i:s', strtotime('+20 minutes'));

                try {
                    $pdo->beginTransaction();

                    // Deduct balance from user account
                    if ($account_type === 'demo') {
                        $updateStmt = $pdo->prepare("UPDATE users SET demo_balance = demo_balance - ? WHERE id = ?");
                    } else {
                        $updateStmt = $pdo->prepare("UPDATE users SET real_balance = real_balance - ? WHERE id = ?");
                    }
                    $updateStmt->execute([$amount, $user_id]);

                    // Record trade in user_trades
                    $insertStmt = $pdo->prepare("
                        INSERT INTO user_trades (user_id, account_type, amount, profit_percentage, potential_profit, status, expires_at)
                        VALUES (?, ?, ?, ?, ?, 'pending', ?)
                    ");
                    $insertStmt->execute([
                        $user_id,
                        $account_type,
                        $amount,
                        $profit_percentage,
                        $potential_profit,
                        $expires_at
                    ]);

                    $trade_id = $pdo->lastInsertId();
                    $pdo->commit();

                    header("Location: trade-results.php?trade_id=" . $trade_id);
                    exit();

                } catch (Exception $e) {
                    if ($pdo && $pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error_message = "Trade Execution Failed: " . $e->getMessage();
                }
            }
        }
    }
}

// Support contacts
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
    <title>Execute Arbitrage Trade | <?= e($coin_name) ?> (<?= e($symbol) ?>)</title>
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
            width: min(900px, calc(100% - 32px));
            margin: 40px auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .back-link:hover { color: var(--text-main); }

        .trade-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 28px;
        }

        .deal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 24px;
        }

        .deal-title h2 { font-size: 24px; font-weight: 700; }
        .deal-title span { color: var(--text-muted); font-size: 14px; }

        .profit-badge {
            background: rgba(14, 203, 129, 0.15);
            color: var(--green);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 22px;
            font-weight: 800;
        }

        .route-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            background: #1e2329;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .route-item p { font-size: 13px; color: var(--text-muted); }
        .route-item h3 { font-size: 18px; margin-top: 4px; }
        .route-item span { font-size: 14px; color: var(--green); font-weight: 600; }

        .alert-box {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-danger { background: rgba(246, 70, 93, 0.2); border: 1px solid var(--red); color: #ff8091; }
        .alert-warning { background: rgba(255, 193, 7, 0.15); border: 1px solid #ffc107; color: #ffe066; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; color: var(--text-muted); }

        .form-control, .form-select {
            width: 100%;
            background: #1e2329;
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 15px;
            outline: none;
        }
        .form-control:focus, .form-select:focus { border-color: var(--accent-blue); }

        .balance-info {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-top: 6px;
            color: var(--text-muted);
        }

        .summary-card {
            background: #1e2329;
            border-radius: 8px;
            padding: 16px;
            margin-top: 24px;
            margin-bottom: 24px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .summary-row:last-child { margin-bottom: 0; }
        .summary-row.total {
            font-weight: bold;
            font-size: 16px;
            color: var(--green);
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
        }

        .btn-execute {
            width: 100%;
            background: var(--green);
            color: #000;
            border: none;
            padding: 14px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-execute:hover { opacity: 0.9; }

        .btn-login-required {
            display: block;
            text-align: center;
            background: var(--accent-blue);
            color: #fff;
            padding: 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 700;
        }

        .floating-support { position: fixed; bottom: 20px; right: 20px; z-index: 99; display: flex; flex-direction: column; gap: 10px; }
        .support-btn { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.4); transition: transform 0.2s; }
        .support-btn:hover { transform: scale(1.1); }
        .sup-telegram { background: #0088cc; } .sup-whatsapp { background: #25d366; } .sup-email { background: var(--accent-blue); }
    </style>
</head>
<body>

    <?php if (file_exists('inc/navbar.php')) include 'inc/navbar.php'; ?>

    <main class="container">
        <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Market Opportunities</a>

        <div class="trade-box">
            <div class="deal-header">
                <div class="deal-title">
                    <h2><?= e($coin_name) ?> Arbitrage Trade</h2>
                    <span>Pair: <?= e($symbol) ?> / USDT</span>
                </div>
                <div class="profit-badge">+<?= number_format($profit_percentage, 2) ?>% Yield</div>
            </div>

            <!-- Route Summary -->
            <div class="route-grid">
                <div class="route-item">
                    <p><i class="fa-solid fa-cart-shopping"></i> Buy Exchange (Lower Price)</p>
                    <h3><?= e($buy_exchange) ?></h3>
                    <span>$<?= number_format($buy_price, 4) ?></span>
                </div>
                <div class="route-item">
                    <p><i class="fa-solid fa-tags"></i> Sell Exchange (Higher Price)</p>
                    <h3><?= e($sell_exchange) ?></h3>
                    <span style="color: var(--green);">$<?= number_format($sell_price, 4) ?></span>
                </div>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert-box alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($error_message) ?></div>
            <?php endif; ?>

            <?php if (!$is_logged_in): ?>
                <div class="alert-box alert-warning">
                    <i class="fa-solid fa-lock"></i> You must be logged in to execute trades. Please log in or register to continue.
                </div>
                <a href="login.php" class="btn-login-required">Log In to Trade</a>
            <?php else: ?>

                <form method="POST" action="">
                    <!-- Hidden inputs preserving exact trade parameters passed via POST -->
                    <input type="hidden" name="symbol" value="<?= e($symbol) ?>">
                    <input type="hidden" name="coin_name" value="<?= e($coin_name) ?>">
                    <input type="hidden" name="buy_exchange" value="<?= e($buy_exchange) ?>">
                    <input type="hidden" name="sell_exchange" value="<?= e($sell_exchange) ?>">
                    <input type="hidden" name="buy_price" value="<?= e($buy_price) ?>">
                    <input type="hidden" name="sell_price" value="<?= e($sell_price) ?>">
                    <input type="hidden" name="profit_percentage" value="<?= e($profit_percentage) ?>">
                    <input type="hidden" name="estimated_time" value="<?= e($estimated_time) ?>">

                    <!-- Account Type Selector -->
                    <div class="form-group">
                        <label for="account_type">Select Trading Account</label>
                        <select name="account_type" id="account_type" class="form-select" onchange="updateBalanceDisplay()">
                            <option value="real">Real Balance Account ($<?= number_format($user['real_balance'] ?? 0, 2) ?> USDT)</option>
                            <option value="demo">Demo Practice Account ($<?= number_format($user['demo_balance'] ?? 0, 2) ?> USDT)</option>
                        </select>
                        <div class="balance-info">
                            <span>Available Balance:</span>
                            <strong id="balanceDisplay">$<?= number_format($user['real_balance'] ?? 0, 2) ?> USDT</strong>
                        </div>
                    </div>

                    <!-- Investment Amount -->
                    <div class="form-group">
                        <label for="amount">Investment Amount (USDT)</label>
                        <input type="number" step="any" name="amount" id="amount" class="form-control" placeholder="e.g. 500" required oninput="calculateProfit()">
                    </div>

                    <!-- Trade Calculation Summary -->
                    <div class="summary-card">
                        <div class="summary-row">
                            <span>Estimated Duration:</span>
                            <strong><i class="fa-regular fa-clock"></i> <?= e($estimated_time) ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>Target Arbitrage Spread:</span>
                            <strong>+<?= number_format($profit_percentage, 2) ?>%</strong>
                        </div>
                        <div class="summary-row">
                            <span>Calculated Profit:</span>
                            <strong id="profitDisplay" style="color: var(--green);">$0.00 USDT</strong>
                        </div>
                        <div class="summary-row total">
                            <span>Total Estimated Return:</span>
                            <span id="totalDisplay">$0.00 USDT</span>
                        </div>
                    </div>

                    <button type="submit" name="execute_trade" class="btn-execute">
                        <i class="fa-solid fa-bolt"></i> Execute Arbitrage Trade Now
                    </button>
                </form>

            <?php endif; ?>
        </div>
    </main>

    <div class="floating-support">
        <a href="https://t.me/<?= e(ltrim($support['telegram'], '@')) ?>" target="_blank" class="support-btn sup-telegram" title="Telegram Support"><i class="fa-brands fa-telegram"></i></a>
        <a href="https://wa.me/<?= e($whatsapp_number) ?>?text=Hello%20ArbitragePro%20Support" target="_blank" class="support-btn sup-whatsapp" title="WhatsApp Support"><i class="fa-brands fa-whatsapp"></i></a>
        <a href="mailto:<?= e($support['email']) ?>" class="support-btn sup-email" title="Email Support"><i class="fa-solid fa-envelope"></i></a>
    </div>

    <script>
        const realBalance = <?= (float)($user['real_balance'] ?? 0) ?>;
        const demoBalance = <?= (float)($user['demo_balance'] ?? 0) ?>;
        const profitMargin = <?= (float)$profit_percentage ?>;

        function updateBalanceDisplay() {
            const type = document.getElementById('account_type').value;
            const display = document.getElementById('balanceDisplay');
            if (type === 'demo') {
                display.innerText = '$' + demoBalance.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' USDT';
            } else {
                display.innerText = '$' + realBalance.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' USDT';
            }
        }

        function calculateProfit() {
            const amountInput = parseFloat(document.getElementById('amount').value);
            const profitDisplay = document.getElementById('profitDisplay');
            const totalDisplay = document.getElementById('totalDisplay');

            if (!isNaN(amountInput) && amountInput > 0) {
                const profit = amountInput * (profitMargin / 100);
                const total = amountInput + profit;

                profitDisplay.innerText = '+$' + profit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' USDT';
                totalDisplay.innerText = '$' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' USDT';
            } else {
                profitDisplay.innerText = '$0.00 USDT';
                totalDisplay.innerText = '$0.00 USDT';
            }
        }
    </script>
</body>
</html>
