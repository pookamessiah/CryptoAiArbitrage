<?php
// admin/index.php - Central Operations & Management Console
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

// Security Check: Verify Admin Authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];
$stmtAdmin = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_admin = 1");
$stmtAdmin->execute([$admin_id]);
if (!$stmtAdmin->fetch()) {
    die("<h2 style='color: red; font-family: sans-serif; text-align: center; margin-top: 50px;'>Access Denied: Admin Privileges Required.</h2>");
}

$alert_message = '';
$alert_type = '';

// 1. Handle Support Info Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_support'])) {
    $phone = trim($_POST['phone'] ?? '');
    $telegram = trim($_POST['telegram'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $stmtCheckSup = $pdo->query("SELECT id FROM support_info LIMIT 1");
    if ($stmtCheckSup->fetch()) {
        $stmtSup = $pdo->prepare("UPDATE support_info SET phone = ?, telegram = ?, email = ? WHERE id = 1");
        $stmtSup->execute([$phone, $telegram, $email]);
    } else {
        $stmtSup = $pdo->prepare("INSERT INTO support_info (phone, telegram, email) VALUES (?, ?, ?)");
        $stmtSup->execute([$phone, $telegram, $email]);
    }
    $alert_message = "Support contact info updated successfully!";
    $alert_type = "success";
}

// 2. Handle User Info Updates (Edit Balances / Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $target_user_id = (int)$_POST['user_id'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $real_balance = filter_var($_POST['real_balance'], FILTER_VALIDATE_FLOAT);
    $demo_balance = filter_var($_POST['demo_balance'], FILTER_VALIDATE_FLOAT);
    $status = $_POST['status'];

    $stmtUpdateU = $pdo->prepare("
        UPDATE users SET fullname = ?, email = ?, real_balance = ?, demo_balance = ?, status = ? WHERE id = ?
    ");
    if ($stmtUpdateU->execute([$fullname, $email, $real_balance, $demo_balance, $status, $target_user_id])) {
        $alert_message = "User #{$target_user_id} updated successfully!";
        $alert_type = "success";
    }
}

// 3. Handle Trade Triggering & Outcome Resolution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_trade_status'])) {
    $trade_id = (int)$_POST['trade_id'];
    $new_status = $_POST['status'];
    $admin_comment = trim($_POST['admin_comment'] ?? '');
    $realized_profit = filter_var($_POST['realized_profit'], FILTER_VALIDATE_FLOAT);

    // Fetch existing trade info
    $stmtT = $pdo->prepare("SELECT * FROM user_trades WHERE id = ?");
    $stmtT->execute([$trade_id]);
    $trade = $stmtT->fetch();

    if ($trade) {
        $old_status = $trade['status'];

        // If transition to 'completed' for the first time, credit capital + profit to user
        if ($new_status === 'completed' && $old_status !== 'completed') {
            $total_payout = (float)$trade['amount'] + ($realized_profit !== false ? $realized_profit : (float)$trade['potential_profit']);
            $acc_type = $trade['account_type'];
            $u_id = $trade['user_id'];

            if ($acc_type === 'real') {
                $stmtCred = $pdo->prepare("UPDATE users SET real_balance = real_balance + ? WHERE id = ?");
            } else {
                $stmtCred = $pdo->prepare("UPDATE users SET demo_balance = demo_balance + ? WHERE id = ?");
            }
            $stmtCred->execute([$total_payout, $u_id]);
        }

        // Update Trade Record
        $stmtUT = $pdo->prepare("
            UPDATE user_trades 
            SET status = ?, realized_profit = ?, admin_comment = ?, completed_at = NOW() 
            WHERE id = ?
        ");
        $stmtUT->execute([$new_status, $realized_profit !== false ? $realized_profit : $trade['potential_profit'], $admin_comment, $trade_id]);

        $alert_message = "Trade #{$trade_id} updated to '{$new_status}' successfully!";
        $alert_type = "success";
    }
}

// Fetch System Data for View
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
$all_trades = $pdo->query("
    SELECT ut.*, u.fullname, u.email, ad.symbol, ad.coin_name 
    FROM user_trades ut 
    JOIN users u ON ut.user_id = u.id 
    JOIN arbitrage_deals ad ON ut.deal_id = ad.id 
    ORDER BY ut.id DESC
")->fetchAll();

$support_info = $pdo->query("SELECT * FROM support_info LIMIT 1")->fetch() ?: ['phone' => '', 'telegram' => '', 'email' => ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Command Center | ArbitragePro</title>
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

        .admin-nav {
            background: #181a20;
            border-bottom: 1px solid var(--border-color);
            padding: 16px 0;
            margin-bottom: 30px;
        }

        .nav-container {
            width: min(1300px, calc(100% - 32px));
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-links {
            display: flex;
            gap: 20px;
        }

        .admin-links a {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .admin-links a:hover { color: var(--green); }

        .container {
            width: min(1300px, calc(100% - 32px));
            margin: 0 auto;
        }

        .section-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
            overflow-x: auto;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            background: rgba(14, 203, 129, 0.15);
            border: 1px solid var(--green);
            color: #80ffd2;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }

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

        .btn-sm {
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-green { background: var(--green); color: #000; }
        .btn-blue { background: var(--accent-blue); color: #fff; }

        .badge-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pending { background: rgba(255, 193, 7, 0.15); color: var(--warning); }
        .status-in_progress { background: rgba(43, 108, 176, 0.2); color: #63b3ed; }
        .status-completed { background: rgba(14, 203, 129, 0.15); color: var(--green); }
        .status-cancelled { background: rgba(246, 70, 93, 0.15); color: var(--red); }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <div class="nav-container">
            <h2 style="font-size: 20px;"><i class="fa-solid fa-user-shield" style="color: var(--green);"></i> Admin Portal</h2>
            <div class="admin-links">
                <a href="index.php"><i class="fa-solid fa-gauge"></i> Overview & Trades</a>
                <a href="deposits.php"><i class="fa-solid fa-wallet"></i> Manage Deposits</a>
                <a href="withdrawals.php"><i class="fa-solid fa-money-bill-transfer"></i> Manage Withdrawals</a>
                <a href="../index.php" target="_blank"><i class="fa-solid fa-globe"></i> View Website</a>
            </div>
        </div>
    </nav>

    <main class="container">

        <?php if (!empty($alert_message)): ?>
            <div class="alert-box"><i class="fa-solid fa-check-circle"></i> <?= e($alert_message) ?></div>
        <?php endif; ?>

        <!-- Support Information Manager -->
        <div class="section-box">
            <div class="section-title"><i class="fa-solid fa-headset" style="color: var(--accent-blue);"></i> Floating Support Contact Info</div>
            <form method="POST" action="index.php" style="display: flex; gap: 16px; flex-wrap: wrap;">
                <div>
                    <label style="font-size: 12px; color: var(--text-muted); display: block;">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($support_info['phone']) ?>" required>
                </div>
                <div>
                    <label style="font-size: 12px; color: var(--text-muted); display: block;">Telegram Handle</label>
                    <input type="text" name="telegram" class="form-control" value="<?= e($support_info['telegram']) ?>" required>
                </div>
                <div>
                    <label style="font-size: 12px; color: var(--text-muted); display: block;">Support Email</label>
                    <input type="email" name="email" class="form-control" value="<?= e($support_info['email']) ?>" required>
                </div>
                <div style="align-self: flex-end;">
                    <button type="submit" name="update_support" class="btn-sm btn-blue"><i class="fa-solid fa-floppy-disk"></i> Save Contacts</button>
                </div>
            </form>
        </div>

        <!-- Live Trade Control Queue -->
        <div class="section-box">
            <div class="section-title"><i class="fa-solid fa-bolt" style="color: var(--green);"></i> User Executed Trades Panel</div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Trade ID</th>
                        <th>User</th>
                        <th>Pair</th>
                        <th>Account</th>
                        <th>Amount</th>
                        <th>Est. Profit</th>
                        <th>Realized Profit</th>
                        <th>Status</th>
                        <th>Admin Comment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_trades as $t): ?>
                        <tr>
                            <form method="POST" action="index.php">
                                <input type="hidden" name="trade_id" value="<?= (int)$t['id'] ?>">
                                <td><strong>#TRD-<?= (int)$t['id'] ?></strong></td>
                                <td><?= e($t['fullname']) ?><br><span style="color: var(--text-muted); font-size: 11px;"><?= e($t['email']) ?></span></td>
                                <td><?= e($t['symbol']) ?>/USDT</td>
                                <td><span style="text-transform: uppercase; font-size: 11px; color: var(--text-muted);"><?= e($t['account_type']) ?></span></td>
                                <td>$<?= number_format($t['amount'], 2) ?></td>
                                <td style="color: var(--green);">+$<?= number_format($t['potential_profit'], 2) ?></td>
                                <td>
                                    $<input type="number" step="any" name="realized_profit" class="form-control" style="width: 80px;" value="<?= e($t['realized_profit'] ?: $t['potential_profit']) ?>">
                                </td>
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
                                    <button type="submit" name="update_trade_status" class="btn-sm btn-green">Update</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- User Accounts & Balance Management -->
        <div class="section-box">
            <div class="section-title"><i class="fa-solid fa-users" style="color: var(--warning);"></i> Manage Users & Balances</div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Real Balance ($)</th>
                        <th>Demo Balance ($)</th>
                        <th>Account Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <form method="POST" action="index.php">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <td>#<?= (int)$u['id'] ?></td>
                                <td><input type="text" name="fullname" class="form-control" value="<?= e($u['fullname']) ?>" required></td>
                                <td><input type="email" name="email" class="form-control" value="<?= e($u['email']) ?>" required></td>
                                <td><input type="number" step="any" name="real_balance" class="form-control" style="width: 100px;" value="<?= (float)$u['real_balance'] ?>" required></td>
                                <td><input type="number" step="any" name="demo_balance" class="form-control" style="width: 100px;" value="<?= (float)$u['demo_balance'] ?>" required></td>
                                <td>
                                    <select name="status" class="form-select">
                                        <option value="active" <?= $u['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="suspended" <?= $u['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit" name="update_user" class="btn-sm btn-blue">Save Changes</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>
</body>
</html>
