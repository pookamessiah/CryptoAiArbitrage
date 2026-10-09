<?php
// deposit.php - Funds Deposit Request Page
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

// Ensure Authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// System Deposit Wallet Addresses (Admin can update these in the database later)
$wallet_addresses = [
    'USDT (TRC20)' => 'T9xZ3mL7qP1vK8wN4R5yT0uY6eS2a1b3c4',
    'BTC (Bitcoin)' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
    'ETH (ERC20)' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F'
];

$error_message = '';
$success_message = '';

// Process Deposit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_deposit'])) {
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
    $payment_method = trim($_POST['payment_method'] ?? '');
    $transaction_hash = trim($_POST['transaction_hash'] ?? '');

    if (!$amount || $amount <= 0) {
        $error_message = "Please enter a valid deposit amount.";
    } elseif (empty($payment_method)) {
        $error_message = "Please select a payment method.";
    } elseif (empty($transaction_hash)) {
        $error_message = "Please enter the Transaction Hash / TXID as proof of transfer.";
    } else {
        $stmtInsert = $pdo->prepare("
            INSERT INTO deposits (user_id, amount, payment_method, transaction_hash, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        if ($stmtInsert->execute([$user_id, $amount, $payment_method, $transaction_hash])) {
            $success_message = "Deposit request of $" . number_format($amount, 2) . " submitted successfully! The admin team will verify and credit your balance shortly.";
        } else {
            $error_message = "Failed to submit deposit request. Please try again.";
        }
    }
}

// Fetch Deposit History for User
$stmtHistory = $pdo->prepare("SELECT * FROM deposits WHERE user_id = ? ORDER BY id DESC");
$stmtHistory->execute([$user_id]);
$deposit_history = $stmtHistory->fetchAll();

// Support Contacts
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
    <title>Deposit Funds | ArbitragePro</title>
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
            width: min(1000px, calc(100% - 32px));
            margin: 32px auto;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h2 { font-size: 26px; font-weight: 800; }
        .page-header p { color: var(--text-muted); font-size: 14px; }

        .grid-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .card-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .wallet-item {
            background: #1e2329;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 14px;
        }

        .wallet-item span { font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px; }
        .wallet-address {
            font-family: monospace;
            font-size: 13px;
            word-break: break-all;
            color: var(--green);
            background: #0b0e11;
            padding: 8px 10px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-copy {
            background: var(--border-color);
            color: #fff;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .alert-danger { background: rgba(246, 70, 93, 0.15); border: 1px solid var(--red); color: #ff8091; }
        .alert-success { background: rgba(14, 203, 129, 0.15); border: 1px solid var(--green); color: #80ffd2; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 6px; }
        .form-control, .form-select {
            width: 100%;
            background: #1e2329;
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }

        .btn-submit {
            width: 100%;
            background: var(--green);
            color: #000;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
        }

        /* History Table */
        .history-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            overflow-x: auto;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }

        .history-table th { padding: 12px; color: var(--text-muted); border-bottom: 1px solid var(--border-color); }
        .history-table td { padding: 14px 12px; border-bottom: 1px solid var(--border-color); }

        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pending { background: rgba(255, 193, 7, 0.15); color: var(--warning); }
        .status-approved { background: rgba(14, 203, 129, 0.15); color: var(--green); }
        .status-rejected { background: rgba(246, 70, 93, 0.15); color: var(--red); }

        /* Floating Support Bar */
        .floating-support { position: fixed; bottom: 20px; right: 20px; z-index: 99; display: flex; flex-direction: column; gap: 10px; }
        .support-btn { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.4); }
        .sup-telegram { background: #0088cc; } .sup-phone { background: #25d366; } .sup-email { background: var(--accent-blue); }

        @media (max-width: 768px) {
            .grid-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'inc/navbar.php'; ?>

    <main class="container">
        <div class="page-header">
            <h2>Deposit Account Balance</h2>
            <p>Transfer funds to the payment addresses below and submit your transaction hash for admin verification.</p>
        </div>

        <div class="grid-layout">
            <!-- Left: Wallet Addresses -->
            <div class="card-box">
                <div class="card-title">
                    <i class="fa-solid fa-qrcode" style="color: var(--accent-blue);"></i> Official Deposit Addresses
                </div>

                <?php foreach ($wallet_addresses as $method => $addr): ?>
                    <div class="wallet-item">
                        <span><?= e($method) ?> Network</span>
                        <div class="wallet-address">
                            <span id="addr_<?= md5($method) ?>"><?= e($addr) ?></span>
                            <button class="btn-copy" onclick="copyAddress('addr_<?= md5($method) ?>')"><i class="fa-regular fa-copy"></i> Copy</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Right: Deposit Form -->
            <div class="card-box">
                <div class="card-title">
                    <i class="fa-solid fa-paper-plane" style="color: var(--green);"></i> Confirm Your Payment
                </div>

                <?php if (!empty($error_message)): ?>
                    <div class="alert-box alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error_message) ?></div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert-box alert-success"><i class="fa-solid fa-circle-check"></i> <?= e($success_message) ?></div>
                <?php endif; ?>

                <form method="POST" action="deposit.php">
                    <div class="form-group">
                        <label for="payment_method">Selected Payment Method</label>
                        <select name="payment_method" id="payment_method" class="form-select" required>
                            <option value="USDT (TRC20)">USDT (TRC20)</option>
                            <option value="BTC (Bitcoin)">BTC (Bitcoin)</option>
                            <option value="ETH (ERC20)">ETH (ERC20)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="amount">Transferred Amount (USDT / USD Equivalent)</label>
                        <input type="number" step="any" name="amount" id="amount" class="form-control" placeholder="e.g. 1000" required>
                    </div>

                    <div class="form-group">
                        <label for="transaction_hash">Transaction Hash / TXID</label>
                        <input type="text" name="transaction_hash" id="transaction_hash" class="form-control" placeholder="Paste your transfer transaction hash" required>
                    </div>

                    <button type="submit" name="submit_deposit" class="btn-submit">
                        <i class="fa-solid fa-check"></i> Submit Deposit Notification
                    </button>
                </form>
            </div>
        </div>

        <!-- Deposit History -->
        <div class="history-card">
            <div class="card-title">
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--text-muted);"></i> Deposit Requests History
            </div>

            <?php if (!empty($deposit_history)): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Transaction Hash</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deposit_history as $dep): ?>
                            <tr>
                                <td><strong>#DEP-<?= (int)$dep['id'] ?></strong></td>
                                <td><?= e($dep['payment_method']) ?></td>
                                <td>$<?= number_format($dep['amount'], 2) ?></td>
                                <td style="font-family: monospace; font-size: 12px;"><?= e(substr($dep['transaction_hash'], 0, 16)) ?>...</td>
                                <td>
                                    <span class="badge-status status-<?= e($dep['status']) ?>">
                                        <?= e($dep['status']) ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-muted); font-size: 13px;"><?= e(date('M d, Y - H:i', strtotime($dep['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">No deposit requests found.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Floating Support -->
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

    <script>
        function copyAddress(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('Wallet address copied to clipboard!');
            });
        }
    </script>
</body>
</html>
