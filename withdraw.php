<?php
// withdraw.php - Funds Withdrawal Request Page
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

// Fetch latest user details
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

$error_message = '';
$success_message = '';

// Process Withdrawal Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_withdrawal'])) {
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
    $withdrawal_method = trim($_POST['withdrawal_method'] ?? '');
    $wallet_address = trim($_POST['wallet_address'] ?? '');

    if (!$amount || $amount <= 0) {
        $error_message = "Please enter a valid withdrawal amount.";
    } elseif ($amount > (float)$user['real_balance']) {
        $error_message = "Insufficient Real Account balance. Available: $" . number_format($user['real_balance'], 2);
    } elseif (empty($withdrawal_method)) {
        $error_message = "Please select a withdrawal method.";
    } elseif (empty($wallet_address)) {
        $error_message = "Please enter your destination wallet address.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Deduct amount from user's real balance
            $stmtDeduct = $pdo->prepare("UPDATE users SET real_balance = real_balance - ? WHERE id = ?");
            $stmtDeduct->execute([$amount, $user_id]);

            // 2. Insert record into withdrawals
            $stmtInsert = $pdo->prepare("
                INSERT INTO withdrawals (user_id, amount, withdrawal_method, wallet_address, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmtInsert->execute([$user_id, $amount, $withdrawal_method, $wallet_address]);

            $pdo->commit();

            // Refresh user variable
            $user['real_balance'] -= $amount;
            $success_message = "Withdrawal request of $" . number_format($amount, 2) . " submitted successfully! The admin team will process your transfer.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Withdrawal request failed: " . $e->getMessage();
        }
    }
}

// Fetch Withdrawal History for User
$stmtHistory = $pdo->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY id DESC");
$stmtHistory->execute([$user_id]);
$withdrawal_history = $stmtHistory->fetchAll();

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
    <title>Withdraw Funds | ArbitragePro</title>
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
            width: min(900px, calc(100% - 32px));
            margin: 32px auto;
        }

        .page-header { margin-bottom: 24px; }
        .page-header h2 { font-size: 26px; font-weight: 800; }
        .page-header p { color: var(--text-muted); font-size: 14px; }

        .card-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 28px;
            margin-bottom: 32px;
        }

        .balance-box {
            background: #1e2329;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .alert-danger { background: rgba(246, 70, 93, 0.15); border: 1px solid var(--red); color: #ff8091; }
        .alert-success { background: rgba(14, 203, 129, 0.15); border: 1px solid var(--green); color: #80ffd2; }

        .form-group { margin-bottom: 18px; }
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
            background: var(--accent-blue);
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        /* History Table */
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
    </style>
</head>
<body>

    <?php include 'inc/navbar.php'; ?>

    <main class="container">
        <div class="page-header">
            <h2>Withdraw Funds</h2>
            <p>Request payout to your crypto wallet. Transfers are processed manually by our operations team.</p>
        </div>

        <div class="card-box">
            <div class="balance-box">
                <div>
                    <span style="font-size: 13px; color: var(--text-muted);">Available Real Balance</span>
                    <h3 style="font-size: 24px; color: var(--green);">$<?= number_format($user['real_balance'], 2) ?> USDT</h3>
                </div>
                <i class="fa-solid fa-wallet" style="font-size: 32px; color: var(--text-muted);"></i>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert-box alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error_message) ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert-box alert-success"><i class="fa-solid fa-circle-check"></i> <?= e($success_message) ?></div>
            <?php endif; ?>

            <form method="POST" action="withdraw.php">
                <div class="form-group">
                    <label for="withdrawal_method">Payout Crypto Network</label>
                    <select name="withdrawal_method" id="withdrawal_method" class="form-select" required>
                        <option value="USDT (TRC20)">USDT (TRC20)</option>
                        <option value="BTC (Bitcoin)">BTC (Bitcoin)</option>
                        <option value="ETH (ERC20)">ETH (ERC20)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="amount">Withdrawal Amount (USDT)</label>
                    <input type="number" step="any" name="amount" id="amount" class="form-control" placeholder="e.g. 500" max="<?= (float)$user['real_balance'] ?>" required>
                </div>

                <div class="form-group">
                    <label for="wallet_address">Destination Wallet Address</label>
                    <input type="text" name="wallet_address" id="wallet_address" class="form-control" placeholder="Paste your destination wallet address" required>
                </div>

                <button type="submit" name="request_withdrawal" class="btn-submit">
                    <i class="fa-solid fa-money-bill-transfer"></i> Submit Payout Request
                </button>
            </form>
        </div>

        <!-- Withdrawal History -->
        <div class="card-box">
            <h3 style="font-size: 18px; margin-bottom: 18px;"><i class="fa-solid fa-clock-rotate-left"></i> Withdrawal Requests History</h3>

            <?php if (!empty($withdrawal_history)): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Destination Address</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawal_history as $w): ?>
                            <tr>
                                <td><strong>#WD-<?= (int)$w['id'] ?></strong></td>
                                <td><?= e($w['withdrawal_method']) ?></td>
                                <td>$<?= number_format($w['amount'], 2) ?></td>
                                <td style="font-family: monospace; font-size: 12px;"><?= e(substr($w['wallet_address'], 0, 16)) ?>...</td>
                                <td>
                                    <span class="badge-status status-<?= e($w['status']) ?>">
                                        <?= e($w['status']) ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-muted); font-size: 13px;"><?= e(date('M d, Y - H:i', strtotime($w['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 10px;">No withdrawal requests recorded yet.</p>
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

</body>
</html>
