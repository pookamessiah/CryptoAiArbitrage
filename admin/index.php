<?php
// admin/index.php - Clean Admin Operations & Management Console
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
}

// 2. Handle User Info Updates (Edit Balances, Status, & Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $target_user_id = (int)$_POST['user_id'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $real_balance = filter_var($_POST['real_balance'], FILTER_VALIDATE_FLOAT);
    $demo_balance = filter_var($_POST['demo_balance'], FILTER_VALIDATE_FLOAT);
    $status = $_POST['status'];
    $new_password = trim($_POST['new_password'] ?? '');

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmtUpdateU = $pdo->prepare("
            UPDATE users SET fullname = ?, email = ?, real_balance = ?, demo_balance = ?, status = ?, password = ? WHERE id = ?
        ");
        $stmtUpdateU->execute([$fullname, $email, $real_balance, $demo_balance, $status, $hashed_password, $target_user_id]);
    } else {
        $stmtUpdateU = $pdo->prepare("
            UPDATE users SET fullname = ?, email = ?, real_balance = ?, demo_balance = ?, status = ? WHERE id = ?
        ");
        $stmtUpdateU->execute([$fullname, $email, $real_balance, $demo_balance, $status, $target_user_id]);
    }
    $alert_message = "User #{$target_user_id} profile & balances updated successfully!";
}

// 3. Handle Direct Dashboard Message to User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_user_message'])) {
    $target_user_id = (int)$_POST['user_id'];
    $message_text = trim($_POST['message_text']);

    if (!empty($message_text)) {
        try {
            $stmtMsg = $pdo->prepare("INSERT INTO user_messages (user_id, message) VALUES (?, ?)");
            $stmtMsg->execute([$target_user_id, $message_text]);
            $alert_message = "Dashboard message sent successfully to User #{$target_user_id}!";
        } catch (Exception $e) {
            $alert_message = "Error: Please make sure the user_messages table exists in your database.";
        }
    }
}

// Fetch System Data for View
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
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

        .admin-links { display: flex; gap: 20px; }
        .admin-links a { color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 14px; }
        .admin-links a:hover { color: var(--green); }

        .container { width: min(1300px, calc(100% - 32px)); margin: 0 auto; }

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
            text-decoration: none;
            display: inline-block;
        }

        .btn-green { background: var(--green); color: #000; }
        .btn-blue { background: var(--accent-blue); color: #fff; }
        .btn-purple { background: #6b46c1; color: #fff; }

        .modal-row {
            display: flex;
            gap: 6px;
            align-items: center;
            margin-top: 4px;
        }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <div class="nav-container">
            <h2 style="font-size: 20px;"><i class="fa-solid fa-user-shield" style="color: var(--green);"></i> Admin Portal</h2>
            <div class="admin-links">
                <a href="index.php"><i class="fa-solid fa-gauge"></i> Overview & Users</a>
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

        <!-- User Accounts, Balances, Passwords & Individual Trade View Button -->
        <div class="section-box">
            <div class="section-title"><i class="fa-solid fa-users" style="color: var(--warning);"></i> Manage Users, Balances, Passwords & Individual Trades</div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Details</th>
                        <th>Real Balance ($)</th>
                        <th>Demo Balance ($)</th>
                        <th>Status</th>
                        <th>Change Password</th>
                        <th>Send Dashboard Message</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= (int)$u['id'] ?></td>
                            <td>
                                <form method="POST" action="index.php">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <input type="text" name="fullname" class="form-control" value="<?= e($u['fullname']) ?>" required style="margin-bottom: 4px; width: 100%;">
                                    <input type="email" name="email" class="form-control" value="<?= e($u['email']) ?>" required style="width: 100%;">
                            </td>
                            <td><input type="number" step="any" name="real_balance" class="form-control" style="width: 90px;" value="<?= (float)$u['real_balance'] ?>" required></td>
                            <td><input type="number" step="any" name="demo_balance" class="form-control" style="width: 90px;" value="<?= (float)$u['demo_balance'] ?>" required></td>
                            <td>
                                <select name="status" class="form-select">
                                    <option value="active" <?= $u['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="suspended" <?= $u['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                </select>
                            </td>
                            <td>
                                <input type="password" name="new_password" class="form-control" placeholder="New Password" style="width: 110px;">
                            </td>
                            <td>
                                <div class="modal-row">
                                    <input type="text" name="message_text" class="form-control" placeholder="Type notification..." style="width: 130px;">
                                    <button type="submit" name="send_user_message" class="btn-sm btn-purple" title="Send Notification"><i class="fa-solid fa-paper-plane"></i></button>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 4px; flex-direction: column;">
                                    <button type="submit" name="update_user" class="btn-sm btn-blue">Save Profile</button>
                                    <a href="user-trades.php?user_id=<?= (int)$u['id'] ?>" class="btn-sm btn-green" style="text-align: center;"><i class="fa-solid fa-eye"></i> View Trades</a>
                                </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>
</body>
</html>
