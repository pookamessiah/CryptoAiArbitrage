<?php
// admin/withdrawals.php - Advanced Withdrawal Verification & Messaging Center
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

// Ensure user_messages table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS user_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Handle Status Change & Notifications (POST Handler with PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdrawal_id'], $_POST['new_status'])) {
    $withdrawal_id = (int)$_POST['withdrawal_id'];
    $new_status = trim($_POST['new_status']);
    $admin_note = trim($_POST['admin_note'] ?? '');

    $valid_statuses = ['pending', 'approved', 'processing', 'completed', 'rejected'];
    if (!in_array($new_status, $valid_statuses)) {
        $new_status = 'pending';
    }

    $stmtW = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
    $stmtW->execute([$withdrawal_id]);
    $w = $stmtW->fetch();

    if ($w) {
        $old_status = $w['status'];
        try {
            $pdo->beginTransaction();

            if ($new_status === 'rejected' && $old_status !== 'rejected') {
                $stmtRefund = $pdo->prepare("UPDATE users SET real_balance = real_balance + ? WHERE id = ?");
                $stmtRefund->execute([(float)$w['amount'], (int)$w['user_id']]);
            } elseif ($old_status === 'rejected' && in_array($new_status, ['approved', 'processing', 'completed'])) {
                $stmtUCheck = $pdo->prepare("SELECT real_balance FROM users WHERE id = ?");
                $stmtUCheck->execute([(int)$w['user_id']]);
                $uBal = $stmtUCheck->fetchColumn();

                if ((float)$uBal < (float)$w['amount']) {
                    throw new Exception("User has insufficient balance for this re-activation adjustment.");
                }

                $stmtDeduct = $pdo->prepare("UPDATE users SET real_balance = real_balance - ? WHERE id = ?");
                $stmtDeduct->execute([(float)$w['amount'], (int)$w['user_id']]);
            }

            $stmtUpd = $pdo->prepare("UPDATE withdrawals SET status = ? WHERE id = ?");
            $stmtUpd->execute([$new_status, $withdrawal_id]);

            $notification_text = "Your withdrawal request #WD-{$withdrawal_id} for $" . number_format($w['amount'], 2) . " has been updated to: <strong>" . strtoupper($new_status) . "</strong>.";
            if (!empty($admin_note)) {
                $notification_text .= "<br>Admin Note: " . e($admin_note);
            }

            $stmtMsg = $pdo->prepare("INSERT INTO user_messages (user_id, message, is_read) VALUES (?, ?, 0)");
            $stmtMsg->execute([(int)$w['user_id'], $notification_text]);

            $pdo->commit();
            $_SESSION['admin_alert'] = "Withdrawal #{$withdrawal_id} status successfully updated to " . strtoupper($new_status) . "!";
            $_SESSION['alert_type'] = 'success';

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['admin_alert'] = "Error: " . $e->getMessage();
            $_SESSION['alert_type'] = 'danger';
        }
    }

    // Redirect to prevent form resubmission on refresh
    header("Location: withdrawals.php");
    exit();
}

// Retrieve flash messages
$alert_message = $_SESSION['admin_alert'] ?? '';
$alert_type = $_SESSION['alert_type'] ?? 'success';
unset($_SESSION['admin_alert'], $_SESSION['alert_type']);

// Fetch all withdrawals
$withdrawals = $pdo->query("
    SELECT w.*, u.fullname, u.email 
    FROM withdrawals w 
    JOIN users u ON w.user_id = u.id 
    ORDER BY w.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Withdrawals | Admin</title>
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
        .nav-container { width: min(1200px, calc(100% - 32px)); margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .admin-links { display: flex; gap: 20px; }
        .admin-links a { color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 14px; }
        .admin-links a:hover { color: var(--green); }

        .container { width: min(1200px, calc(100% - 32px)); margin: 0 auto; }
        .section-box { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; }
        
        .admin-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
        .admin-table th { padding: 12px; color: var(--text-muted); border-bottom: 1px solid var(--border-color); }
        .admin-table td { padding: 14px 12px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }

        .badge-status { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .status-pending { background: rgba(255, 193, 7, 0.15); color: var(--warning); }
        .status-approved, .status-completed { background: rgba(14, 203, 129, 0.15); color: var(--green); }
        .status-processing { background: rgba(43, 108, 176, 0.2); color: #63b3ed; }
        .status-rejected { background: rgba(246, 70, 93, 0.15); color: var(--red); }

        .update-form { display: flex; flex-direction: column; gap: 8px; background: #1e2329; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); min-width: 240px; }
        .form-select, .form-input { background: var(--bg-dark); border: 1px solid var(--border-color); color: #fff; padding: 6px 10px; border-radius: 4px; font-size: 12px; outline: none; }
        .btn-update { background: var(--accent-blue); color: #fff; border: none; padding: 6px 10px; border-radius: 4px; font-weight: 700; cursor: pointer; font-size: 12px; }
        .btn-update:hover { background: #3182ce; }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <div class="nav-container">
            <h2 style="font-size: 20px;"><i class="fa-solid fa-money-bill-transfer" style="color: var(--accent-blue);"></i> Withdrawal Payouts & Management</h2>
            <div class="admin-links">
                <a href="index.php"><i class="fa-solid fa-gauge"></i> Overview & Trades</a>
                <a href="deposits.php"><i class="fa-solid fa-wallet"></i> Manage Deposits</a>
                <a href="withdrawals.php" style="color: var(--green);"><i class="fa-solid fa-money-bill-transfer"></i> Manage Withdrawals</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="section-box">
            <?php if (!empty($alert_message)): ?>
                <div style="padding: 12px 16px; background: <?= $alert_type === 'danger' ? 'rgba(246, 70, 93, 0.15)' : 'rgba(14, 203, 129, 0.15)' ?>; color: <?= $alert_type === 'danger' ? 'var(--red)' : 'var(--green)' ?>; border-radius: 6px; margin-bottom: 20px; font-weight: 600;">
                    <i class="fa-solid fa-circle-info"></i> <?= e($alert_message) ?>
                </div>
            <?php endif; ?>

            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>User</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Destination Address</th>
                            <th>Current Status</th>
                            <th>Date</th>
                            <th>Manage & Notify User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($withdrawals)): ?>
                            <?php foreach ($withdrawals as $w): ?>
                                <tr>
                                    <td><strong>#WD-<?= (int)$w['id'] ?></strong></td>
                                    <td><?= e($w['fullname']) ?><br><span style="color: var(--text-muted); font-size: 11px;"><?= e($w['email']) ?></span></td>
                                    <td><?= e($w['withdrawal_method']) ?></td>
                                    <td>$<?= number_format($w['amount'], 2) ?></td>
                                    <td style="font-family: monospace; font-size: 11px; word-break: break-all; max-width: 180px;"><?= e($w['wallet_address']) ?></td>
                                    <td>
                                        <span class="badge-status status-<?= e($w['status']) ?>">
                                            <?= e($w['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= e(date('M d, H:i', strtotime($w['created_at']))) ?></td>
                                    <td>
                                        <form method="POST" action="withdrawals.php" class="update-form">
                                            <input type="hidden" name="withdrawal_id" value="<?= (int)$w['id'] ?>">
                                            
                                            <select name="new_status" class="form-select" required>
                                                <option value="pending" <?= $w['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="approved" <?= $w['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                                                <option value="processing" <?= $w['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                <option value="completed" <?= $w['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="rejected" <?= $w['status'] === 'rejected' ? 'selected' : '' ?>>Rejected (Refunds Balance)</option>
                                            </select>

                                            <input type="text" name="admin_note" class="form-input" placeholder="Optional notification note...">

                                            <button type="submit" class="btn-update">
                                                <i class="fa-solid fa-paper-plane"></i> Update & Notify
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">No withdrawal requests found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
