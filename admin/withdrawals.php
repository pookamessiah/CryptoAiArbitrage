<?php
// admin/withdrawals.php - Withdrawal Verification and Processing Center
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

$alert_message = '';

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_withdrawal'])) {
    $withdrawal_id = (int)$_POST['withdrawal_id'];
    $action = $_POST['action']; // 'approved' or 'rejected'

    $stmtW = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
    $stmtW->execute([$withdrawal_id]);
    $w = $stmtW->fetch();

    if ($w && $w['status'] === 'pending') {
        try {
            $pdo->beginTransaction();

            if ($action === 'rejected') {
                // Refund funds back to User's Real Balance
                $stmtRefund = $pdo->prepare("UPDATE users SET real_balance = real_balance + ? WHERE id = ?");
                $stmtRefund->execute([(float)$w['amount'], (int)$w['user_id']]);
            }

            // Update Withdrawal Record Status
            $stmtUpd = $pdo->prepare("UPDATE withdrawals SET status = ? WHERE id = ?");
            $stmtUpd->execute([$action, $withdrawal_id]);

            $pdo->commit();
            $alert_message = "Withdrawal #{$withdrawal_id} has been {$action} successfully!";

        } catch (Exception $e) {
            $pdo->rollBack();
            $alert_message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch all withdrawals
$withdrawals = $pdo->query("
    SELECT w.*, u.fullname, u.email 
    FROM withdrawals w 
    JOIN users u ON w.user_id = u.id 
    ORDER BY w.id DESC
")->fetchAll();
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
        .nav-container { width: min(1200px, calc(100% - 32px)); margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .admin-links { display: flex; gap: 20px; }
        .admin-links a { color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 14px; }

        .container { width: min(1200px, calc(100% - 32px)); margin: 0 auto; }
        .section-box { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; }
        
        .admin-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
        .admin-table th { padding: 12px; color: var(--text-muted); border-bottom: 1px solid var(--border-color); }
        .admin-table td { padding: 12px; border-bottom: 1px solid var(--border-color); }

        .badge-status { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-pending { background: rgba(255, 193, 7, 0.15); color: var(--warning); }
        .status-approved { background: rgba(14, 203, 129, 0.15); color: var(--green); }
        .status-rejected { background: rgba(246, 70, 93, 0.15); color: var(--red); }

        .btn-approve { background: var(--green); color: #000; border: none; padding: 6px 12px; border-radius: 4px; font-weight: 700; cursor: pointer; }
        .btn-reject { background: var(--red); color: #fff; border: none; padding: 6px 12px; border-radius: 4px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <div class="nav-container">
            <h2 style="font-size: 20px;"><i class="fa-solid fa-money-bill-transfer" style="color: var(--accent-blue);"></i> Withdrawal Payouts</h2>
            <div class="admin-links">
                <a href="index.php"><i class="fa-solid fa-gauge"></i> Overview & Trades</a>
                <a href="deposits.php"><i class="fa-solid fa-wallet"></i> Manage Deposits</a>
                <a href="withdrawals.php"><i class="fa-solid fa-money-bill-transfer"></i> Manage Withdrawals</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="section-box">
            <?php if (!empty($alert_message)): ?>
                <div style="padding: 12px; background: rgba(14, 203, 129, 0.15); color: var(--green); border-radius: 6px; margin-bottom: 20px;"><?= e($alert_message) ?></div>
            <?php endif; ?>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ref ID</th>
                        <th>User</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Destination Address</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withdrawals as $w): ?>
                        <tr>
                            <td><strong>#WD-<?= (int)$w['id'] ?></strong></td>
                            <td><?= e($w['fullname']) ?><br><span style="color: var(--text-muted); font-size: 11px;"><?= e($w['email']) ?></span></td>
                            <td><?= e($w['withdrawal_method']) ?></td>
                            <td>$<?= number_format($w['amount'], 2) ?></td>
                            <td style="font-family: monospace; font-size: 11px;"><?= e($w['wallet_address']) ?></td>
                            <td><span class="badge-status status-<?= e($w['status']) ?>"><?= e($w['status']) ?></span></td>
                            <td><?= e(date('M d, H:i', strtotime($w['created_at']))) ?></td>
                            <td>
                                <?php if ($w['status'] === 'pending'): ?>
                                    <form method="POST" action="withdrawals.php" style="display: flex; gap: 6px;">
                                        <input type="hidden" name="withdrawal_id" value="<?= (int)$w['id'] ?>">
                                        <button type="submit" name="action_withdrawal" value="action_withdrawal" onclick="this.form.action.value='approved'" class="btn-approve"><i class="fa-solid fa-check"></i> Approve Payout</button>
                                        <button type="submit" name="action_withdrawal" value="action_withdrawal" onclick="this.form.action.value='rejected'" class="btn-reject"><i class="fa-solid fa-xmark"></i> Reject & Refund</button>
                                        <input type="hidden" name="action" value="approved">
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 12px;">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
