<?php
// admin/deposits.php - Deposit Verification & Wallet Address Management Center
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

// Ensure deposit_wallets table exists and has defaults
$pdo->exec("
    CREATE TABLE IF NOT EXISTS deposit_wallets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        network_name VARCHAR(100) NOT NULL,
        wallet_address VARCHAR(255) NOT NULL
    )
");
$stmtCheck = $pdo->query("SELECT COUNT(*) FROM deposit_wallets");
if ($stmtCheck->fetchColumn() == 0) {
    $pdo->exec("
        INSERT INTO deposit_wallets (network_name, wallet_address) VALUES 
        ('USDT (TRC20)', 'T9xZ3mL7qP1vK8wN4R5yT0uY6eS2a1b3c4'),
        ('BTC (Bitcoin)', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa'),
        ('ETH (ERC20)', '0x71C7656EC7ab88b098defB751B7401B5f6d8976F')
    ");
}

$alert_message = '';
$alert_type = 'success';

// Handle Wallet Address Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_wallets'])) {
    foreach ($_POST['wallets'] as $id => $address) {
        $wallet_id = (int)$id;
        $clean_address = trim($address);
        if (!empty($clean_address)) {
            $stmtUpdWallet = $pdo->prepare("UPDATE deposit_wallets SET wallet_address = ? WHERE id = ?");
            $stmtUpdWallet->execute([$clean_address, $wallet_id]);
        }
    }
    $alert_message = "Deposit wallet addresses updated successfully!";
}

// Handle Deposit Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit_id'], $_POST['action'])) {
    $deposit_id = (int)$_POST['deposit_id'];
    $action = $_POST['action']; // 'approved' or 'rejected'

    $stmtDep = $pdo->prepare("SELECT * FROM deposits WHERE id = ?");
    $stmtDep->execute([$deposit_id]);
    $dep = $stmtDep->fetch();

    if ($dep && $dep['status'] === 'pending') {
        try {
            $pdo->beginTransaction();

            if ($action === 'approved') {
                $stmtCred = $pdo->prepare("UPDATE users SET real_balance = real_balance + ? WHERE id = ?");
                $stmtCred->execute([(float)$dep['amount'], (int)$dep['user_id']]);
            }

            $stmtUpd = $pdo->prepare("UPDATE deposits SET status = ? WHERE id = ?");
            $stmtUpd->execute([$action, $deposit_id]);

            $pdo->commit();
            $alert_message = "Deposit #{$deposit_id} has been {$action} successfully!";
            $alert_type = 'success';

        } catch (Exception $e) {
            $pdo->rollBack();
            $alert_message = "Error: " . $e->getMessage();
            $alert_type = 'danger';
        }
    }
}

// Fetch all editable deposit wallets
$wallets = $pdo->query("SELECT * FROM deposit_wallets")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all deposits
$deposits = $pdo->query("
    SELECT d.*, u.fullname, u.email 
    FROM deposits d 
    JOIN users u ON d.user_id = u.id 
    ORDER BY d.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Deposits & Wallets | Admin</title>
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
        .section-box { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; margin-bottom: 30px; }
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; color: #fff; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 6px; }
        .form-control { width: 100%; background: #1e2329; border: 1px solid var(--border-color); color: #fff; padding: 10px 14px; border-radius: 6px; font-size: 14px; font-family: monospace; }
        
        .btn-action { background: var(--green); color: #000; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 14px; }
        .btn-approve { background: var(--green); color: #000; border: none; padding: 6px 12px; border-radius: 4px; font-weight: 700; cursor: pointer; }
        .btn-reject { background: var(--red); color: #fff; border: none; padding: 6px 12px; border-radius: 4px; font-weight: 700; cursor: pointer; }

        .admin-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
        .admin-table th { padding: 12px; color: var(--text-muted); border-bottom: 1px solid var(--border-color); }
        .admin-table td { padding: 12px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }

        .badge-status { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-pending { background: rgba(255, 193, 7, 0.15); color: var(--warning); }
        .status-approved { background: rgba(14, 203, 129, 0.15); color: var(--green); }
        .status-rejected { background: rgba(246, 70, 93, 0.15); color: var(--red); }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <div class="nav-container">
            <h2 style="font-size: 20px;"><i class="fa-solid fa-wallet" style="color: var(--green);"></i> Deposit & Wallet Administration</h2>
            <div class="admin-links">
                <a href="index.php"><i class="fa-solid fa-gauge"></i> Overview & Trades</a>
                <a href="deposits.php" style="color: var(--green);"><i class="fa-solid fa-wallet"></i> Manage Deposits</a>
                <a href="withdrawals.php"><i class="fa-solid fa-money-bill-transfer"></i> Manage Withdrawals</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <?php if (!empty($alert_message)): ?>
            <div style="padding: 14px 18px; background: rgba(14, 203, 129, 0.15); color: var(--green); border: 1px solid var(--green); border-radius: 8px; margin-bottom: 24px; font-weight: 600;">
                <i class="fa-solid fa-circle-check"></i> <?= e($alert_message) ?>
            </div>
        <?php endif; ?>

        <!-- SECTION 1: EDIT DEPOSIT WALLET ADDRESSES -->
        <div class="section-box">
            <div class="section-title">
                <i class="fa-solid fa-qrcode" style="color: var(--accent-blue);"></i> Edit Official Crypto Deposit Addresses
            </div>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">
                Update the wallet addresses shown to users when they make a deposit. Changes take effect instantly across the platform.
            </p>

            <form method="POST" action="deposits.php">
                <div class="form-grid">
                    <?php foreach ($wallets as $w): ?>
                        <div class="form-group">
                            <label><?= e($w['network_name']) ?> Address</label>
                            <input type="text" name="wallets[<?= (int)$w['id'] ?>]" value="<?= e($w['wallet_address']) ?>" class="form-control" required>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="update_wallets" class="btn-action">
                    <i class="fa-solid fa-floppy-disk"></i> Save Wallet Addresses
                </button>
            </form>
        </div>

        <!-- SECTION 2: VERIFY & APPROVE DEPOSITS -->
        <div class="section-box">
            <div class="section-title">
                <i class="fa-solid fa-list-check" style="color: var(--green);"></i> User Deposit Requests & Verification
            </div>

            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>User</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>TXID Hash</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($deposits)): ?>
                            <?php foreach ($deposits as $d): ?>
                                <tr>
                                    <td><strong>#DEP-<?= (int)$d['id'] ?></strong></td>
                                    <td><?= e($d['fullname']) ?><br><span style="color: var(--text-muted); font-size: 11px;"><?= e($d['email']) ?></span></td>
                                    <td><?= e($d['payment_method']) ?></td>
                                    <td>$<?= number_format($d['amount'], 2) ?></td>
                                    <td style="font-family: monospace; font-size: 11px;"><?= e($d['transaction_hash']) ?></td>
                                    <td><span class="badge-status status-<?= e($d['status']) ?>"><?= e($d['status']) ?></span></td>
                                    <td><?= e(date('M d, H:i', strtotime($d['created_at']))) ?></td>
                                    <td>
                                        <?php if ($d['status'] === 'pending'): ?>
                                            <form method="POST" action="deposits.php" style="display: flex; gap: 6px;">
                                                <input type="hidden" name="deposit_id" value="<?= (int)$d['id'] ?>">
                                                <button type="submit" name="action" value="approved" class="btn-approve"><i class="fa-solid fa-check"></i> Approve</button>
                                                <button type="submit" name="action" value="rejected" class="btn-reject"><i class="fa-solid fa-xmark"></i> Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 12px;">Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">No deposit requests submitted yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
