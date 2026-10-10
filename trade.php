<?php
// trade.php - Trade Execution Page with Live Binance API Prices & 15-Min Auto-Refresh
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

// Check Authentication
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$user = null;

if ($is_logged_in) {
    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
}

// Retrieve Selected Deal ID from URL
$deal_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$deal = null;

if ($pdo) {
    try {
        $stmtDeal = $pdo->prepare("SELECT * FROM arbitrage_deals WHERE id = ?");
        $stmtDeal->execute([$deal_id]);
        $deal = $stmtDeal->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Fallback dataset if database deal ID doesn't exist yet
if (!$deal) {
    $fallback_deals = [
        1 => ['id' => 1, 'symbol' => 'BTC', 'coin_name' => 'Bitcoin', 'buy_exchange' => 'Binance (Spot)', 'sell_exchange' => 'Hyperliquid (Perp)', 'estimated_time' => '15 - 30 mins'],
        2 => ['id' => 2, 'symbol' => 'ETH', 'coin_name' => 'Ethereum', 'buy_exchange' => 'Uniswap v3 (DEX)', 'sell_exchange' => 'Bybit (Futures)', 'estimated_time' => '20 - 40 mins'],
        3 => ['id' => 3, 'symbol' => 'SOL', 'coin_name' => 'Solana', 'buy_exchange' => 'Raydium (DEX)', 'sell_exchange' => 'OKX (Perp)', 'estimated_time' => '10 - 25 mins'],
        4 => ['id' => 4, 'symbol' => 'XRP', 'coin_name' => 'Ripple', 'buy_exchange' => 'Gate.io (Spot)', 'sell_exchange' => 'Bitget (Futures)', 'estimated_time' => '15 - 30 mins'],
        5 => ['id' => 5, 'symbol' => 'SUI', 'coin_name' => 'Sui Network', 'buy_exchange' => 'Cetus (DEX)', 'sell_exchange' => 'Binance (Perp)', 'estimated_time' => '10 - 20 mins']
    ];
    $deal = $fallback_deals[$deal_id] ?? $fallback_deals[1];
}

$error_message = "";

// Handle Trade Execution Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_trade'])) {
    if (!$is_logged_in) {
        $error_message = "You must be logged in to execute a trade.";
    } else {
        $account_type = $_POST['account_type'] ?? 'real';
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
        $profit_percentage = filter_var($_POST['profit_percentage'], FILTER_VALIDATE_FLOAT);

        if (!$amount || $amount <= 0) {
            $error_message = "Please enter a valid investment amount.";
        } else {
            $current_balance = ($account_type === 'demo') ? (float)$user['demo_balance'] : (float)$user['real_balance'];

            if ($amount > $current_balance) {
                $error_message = "Insufficient balance in your " . ucfirst($account_type) . " Account.";
            } else {
                $potential_profit = $amount * ($profit_percentage / 100);
                $expires_at = date('Y-m-d H:i:s', strtotime('+20 minutes'));

                try {
                    $pdo->beginTransaction();

                    if ($account_type === 'demo') {
                        $updateStmt = $pdo->prepare("UPDATE users SET demo_balance = demo_balance - ? WHERE id = ?");
                    } else {
                        $updateStmt = $pdo->prepare("UPDATE users SET real_balance = real_balance - ? WHERE id = ?");
                    }
                    $updateStmt->execute([$amount, $user_id]);

                    $insertStmt = $pdo->prepare("
                        INSERT INTO user_trades (user_id, deal_id, account_type, amount, profit_percentage, potential_profit, status, expires_at)
                        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
                    ");
                    $insertStmt->execute([
                        $user_id,
                        $deal['id'],
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
                    $pdo->rollBack();
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
    <title>Execute Arbitrage Trade | <?= e($deal['coin_name']) ?> (<?= e($deal['symbol']) ?>)</title>
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
        .support-btn { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.4); }
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
                    <h2><?= e($deal['coin_name']) ?> Arbitrage Trade</h2>
                    <span>Pair: <?= e($deal['symbol']) ?> / USDT</span>
                </div>
                <div class="profit-badge" id="profitBadgeHeading">+0.00% Yield</div>
            </div>

            <!-- Route Summary -->
            <div class="route-grid">
                <div class="route-item">
                    <p><i class="fa-solid fa-cart-shopping"></i> Buy Exchange (<?= e($deal['buy_exchange'] ?? 'Binance (Spot)') ?>)</p>
                    <h3><?= e($deal['buy_exchange'] ?? 'Binance (Spot)') ?></h3>
                    <span id="buyExPrice">$0.00</span>
                </div>
                <div class="route-item">
                    <p><i class="fa-solid fa-tags"></i> Sell Exchange (<?= e($deal['sell_exchange'] ?? 'Hyperliquid (Perp)') ?>)</p>
                    <h3><?= e($deal['sell_exchange'] ?? 'Hyperliquid (Perp)') ?></h3>
                    <span id="sellExPrice" style="color: var(--green);">$0.00</span>
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
                    <input type="hidden" name="profit_percentage" id="inputProfitPct" value="0">

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
                            <strong><i class="fa-regular fa-clock"></i> <?= e($deal['estimated_time'] ?? '15 - 30 mins') ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>Target Arbitrage Spread:</span>
                            <strong id="summarySpread">0.00%</strong>
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
        const targetSymbol = "<?= e($deal['symbol'] ?? 'BTC') ?>";

        let activeProfitMargin = 4.60;

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
                const profit = amountInput * (activeProfitMargin / 100);
                const total = amountInput + profit;

                profitDisplay.innerText = '+$' + profit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' USDT';
                totalDisplay.innerText = '$' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' USDT';
            } else {
                profitDisplay.innerText = '$0.00 USDT';
                totalDisplay.innerText = '$0.00 USDT';
            }
        }

        async function fetchLiveTradePricing() {
            try {
                const response = await fetch('https://api.binance.com/api/v3/ticker/price');
                const data = await response.json();
                const prices = {};
                data.forEach(item => { prices[item.symbol] = parseFloat(item.price); });

                const basePrice = prices[targetSymbol + 'USDT'] || 64200.00;
                const buyP = basePrice;
                const sellP = basePrice * 1.046; // Matches index.php live spread calculation
                
                activeProfitMargin = ((sellP - buyP) / buyP) * 100;

                document.getElementById('buyExPrice').innerText = '$' + buyP.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4});
                document.getElementById('sellExPrice').innerText = '$' + sellP.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4});
                document.getElementById('profitBadgeHeading').innerText = '+' + activeProfitMargin.toFixed(2) + '% Yield';
                document.getElementById('summarySpread').innerText = activeProfitMargin.toFixed(2) + '%';
                document.getElementById('inputProfitPct').value = activeProfitMargin.toFixed(2);

                calculateProfit();
            } catch (err) {
                console.error("Error fetching live price for trade:", err);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchLiveTradePricing();
            setInterval(fetchLiveTradePricing, 15 * 60 * 1000); // Auto-refresh every 15 mins
        });
    </script>
</body>
</html>
