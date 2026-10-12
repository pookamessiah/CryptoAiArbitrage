<?php
// admin/user-trades.php - Manage Individual User Trades
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';

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

// Verify Admin Privileges
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$stmtAdmin = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_admin = 1");
$stmtAdmin->execute([(int)$_SESSION['user_id']]);
if (!$stmtAdmin->fetch()) {
    die("Access Denied: Admin Privileges Required.");
}

$target_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($target_user_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch Target User Info
$stmtU = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtU->execute([$target_user_id]);
$target_user = $stmtU->fetch();
if (!$target_user) {
    die("User not found.");
}

$alert_message = '';

// Handle Trade Status Overrides & Payouts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_trade'])) {
    $trade_id = (int)$_POST['trade_id'];
    $new_status = $_POST['status'];
    $admin_comment = trim($_POST['admin_comment'] ?? '');
    $edited_amount = filter_var($_POST['edited_amount'], FILTER_VALIDATE_FLOAT);
    $realized_profit = filter_var($_POST['realized_profit'], FILTER_VALIDATE_FLOAT);

    $stmtT = $pdo->prepare("SELECT * FROM user_trades WHERE id = ? AND user_id = ?");
    $stmtT->execute([$trade_id, $target_user_id]);
    $trade = $stmtT->fetch();

    if ($trade) {
        try {
            $pdo->beginTransaction();

            $old_status = $trade['status'];
            $amount_to_use = $edited_amount !== false ? $edited_amount : $trade['amount'];
            $profit_to_use = $realized_profit !== false ? $realized_profit : $trade['potential_profit'];
            $acc_type = $trade['account_type'];
            $balance_col = ($acc_type === 'demo') ? 'demo_balance' : 'real_balance';

            // If changing from non-completed to 'completed', credit user balance
            if ($new_status === 'completed' && $old_status !== 'completed') {
                $total_payout = $amount_to_use + $profit_to_use;
                $updUser = $pdo->prepare("UPDATE users SET {$balance_col} = {$balance_col} + ? WHERE id = ?");
                $updUser->execute([$total_payout, $target_user_id]);
            }
            // If changing from completed to something else, reverse the payout
            elseif ($old_status === 'completed' && $new_status !== 'completed') {
                $total_payout = (float)$trade['amount'] + (float)$trade['potential_profit'];
                $updUser = $pdo->prepare("UPDATE users SET {$balance_col} = {$balance_col} - ? WHERE id = ?");
                $updUser->execute([$total_payout, $target_user_id]);
            }

            // Update Trade Record
            $stmtUT = $pdo->prepare("
                UPDATE user_trades 
                SET status = ?, amount = ?, potential_profit = ?, realized_profit = ?, admin_comment = ? 
                WHERE id = ?
            ");
            $stmtUT->execute([$new_status, $amount_to_use, $profit_to_use, $profit_to_use, $admin_comment, $trade_id]);

            $pdo->commit();
            $alert_message = "Trade #{$trade_id} updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $alert_message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch Trades for this user
$stmtTrades = $pdo->prepare("SELECT * FROM user_trades WHERE user_id = ? ORDER BY id DESC");
$stmtTrades->execute([$target_user_id]);
$user_trades = $stmtTrades->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trades for <?= e($target_user['fullname']) ?> | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0b0e11;
            --bg-card: #181a20;
            --text-main: #eaecef;
            --text-muted: #848e9c;
            --green: #0ecb81;
            --red: #f6465d;
            --warning: #ffc107;
            --accent-blue: #2b6cb0;
            --border-color: #2b313a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Segoe UI', sans-serif; padding-bottom: 60px; }

        .admin-nav { background: #181a20; border-bottom: 1px solid var(--border-color); padding: 16px 0; margin-bottom: 30px; }
        .nav-container { width: min(1200px, calc(100% - 32px)); margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .admin-links { display: flex; gap: 20px; }
        .admin-links a { color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 14px; }
        .admin-links a:hover { color: var(--green); }

        .container { width: min(1200px, calc(100% - 32px)); margin: 0 auto; }
        .section-box { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; }
        
        .admin-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
        .admin-table th { padding: 12px; color: var(--text-muted); border-bottom: 1px solid var(--border-color); }
        .admin-table td { padding: 12px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }

        .form-control, .form-select {
            background: #1e2329;
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 13px;
            outline: none;
        }

        .btn-sm { padding: 6px 12px; border-radius: 4px; font-weight: 700; border: none; cursor: pointer; font-size: 12px; }
        .btn-green { background: var(--green); color: #000; }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <div class="nav-container">
            <h2 style="font-size: 20px;"><i class="fa-solid fa-bolt" style="color: var(--green);"></i> User Trades: <?= e($target_user['fullname']) ?></h2>
            <div class="admin-links">
                <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to Users</a>
                <a href="deposits.php">Manage Deposits</a>
                <a href="withdrawals.php">Manage Withdrawals</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="section-box">
            <p style="margin-bottom: 20px; color: var(--text-muted);">
                Viewing all trades for <strong><?= e($target_user['fullname']) ?></strong> (<?= e($target_user['email']) ?>). Real Balance: $<?= number_format($target_user['real_balance'], 2) ?> | Demo Balance: $<?= number_format($target_user['demo_balance'], 2) ?>
            </p>

            <?php if (!empty($alert_message)): ?>
                <div style="padding: 12px; background: rgba(14, 203, 129, 0.15); color: var(--green); border-radius: 6px; margin-bottom: 20px;"><?= e($alert_message) ?></div>
            <?php endif; ?>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Trade ID</th>
                        <th>Account</th>
                        <th>Amount ($)</th>
                        <th>Profit ($)</th>
                        <th>Status</th>
                        <th>Admin Comment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($user_trades)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">No trade executions found for this user.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($user_trades as $t): ?>
                            <tr>
                                <form method="POST" action="">
                                    <input type="hidden" name="trade_id" value="<?= (int)$t['id'] ?>">
                                    <td><strong>#TRD-<?= (int)$t['id'] ?></strong></td>
                                    <td><span style="text-transform: uppercase; font-size: 11px;"><?= e($t['account_type']) ?></span></td>
                                    <td><input type="number" step="any" name="edited_amount" class="form-control" style="width: 90px;" value="<?= (float)$t['amount'] ?>"></td>
                                    <td><input type="number" step="any" name="realized_profit" class="form-control" style="width: 90px;" value="<?= (float)($t['realized_profit'] ?: $t['potential_profit']) ?>"></td>
                                    <td>
                                        <select name="status" class="form-select">
                                            <option value="pending" <?= $t['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="in_progress" <?= $t['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                            <option value="completed" <?= $t['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $t['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="admin_comment" class="form-control" placeholder="Add note..." value="<?= e($t['admin_comment']) ?>">
                                    </td>
                                    <td>
                                        <button type="submit" name="update_trade" class="btn-sm btn-green">Update Trade</button>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
