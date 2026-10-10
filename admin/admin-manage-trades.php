<?php
// admin-manage-trades.php - Admin Trade Override Script
session_start();
require_once 'config/db.php';

// Ensure user is authenticated as an admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("Unauthorized access.");
}

$db = new Database();
$pdo = $db->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['trade_id'])) {
    $trade_id = (int)$_POST['trade_id'];
    $action = $_POST['action']; // 'approve', 'reject', 'refund'

    try {
        $pdo->beginTransaction();

        // Fetch trade record lock
        $stmt = $pdo->prepare("SELECT * FROM user_trades WHERE id = ? FOR UPDATE");
        $stmt->execute([$trade_id]);
        $trade = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($trade) {
            $user_id = $trade['user_id'];
            $account_type = $trade['account_type']; // 'real' or 'demo'
            $balance_col = ($account_type === 'demo') ? 'demo_balance' : 'real_balance';

            if ($action === 'approve') {
                // If trade was pending, credit total return (amount + profit)
                if ($trade['status'] === 'pending') {
                    $payout = (float)$trade['amount'] + (float)$trade['potential_profit'];
                    
                    $updUser = $pdo->prepare("UPDATE users SET {$balance_col} = {$balance_col} + ? WHERE id = ?");
                    $updUser->execute([$payout, $user_id]);

                    $updTrade = $pdo->prepare("UPDATE user_trades SET status = 'completed' WHERE id = ?");
                    $updTrade->execute([$trade_id]);
                }
            } 
            elseif ($action === 'reject') {
                // If trade was previously completed, reverse payout
                if ($trade['status'] === 'completed') {
                    $payout = (float)$trade['amount'] + (float)$trade['potential_profit'];

                    $updUser = $pdo->prepare("UPDATE users SET {$balance_col} = {$balance_col} - ? WHERE id = ?");
                    $updUser->execute([$payout, $user_id]);
                }

                $updTrade = $pdo->prepare("UPDATE user_trades SET status = 'lost' WHERE id = ?");
                $updTrade->execute([$trade_id]);
            }
            elseif ($action === 'refund') {
                // Refund initial principal amount only
                if ($trade['status'] === 'pending') {
                    $refund = (float)$trade['amount'];

                    $updUser = $pdo->prepare("UPDATE users SET {$balance_col} = {$balance_col} + ? WHERE id = ?");
                    $updUser->execute([$refund, $user_id]);

                    $updTrade = $pdo->prepare("UPDATE user_trades SET status = 'refunded' WHERE id = ?");
                    $updTrade->execute([$trade_id]);
                }
            }
        }

        $pdo->commit();
        header("Location: admin-manage-trades.php?msg=success");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

// Fetch all trades for admin viewing
$trades = $pdo->query("SELECT t.*, u.email FROM user_trades t JOIN users u ON t.user_id = u.id ORDER BY t.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Trade Overrides</title>
    <style>
        body { background: #0b0e11; color: #fff; font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: #181a20; }
        th, td { padding: 12px; border: 1px solid #2b313a; text-align: left; }
        .btn { padding: 6px 12px; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; }
        .btn-green { background: #0ecb81; color: #000; }
        .btn-red { background: #f6465d; color: #fff; }
        .btn-gray { background: #848e9c; color: #fff; }
    </style>
</head>
<body>
    <h2>Admin Trade Management & Overrides</h2>
    <table>
        <thead>
            <tr>
                <th>Trade ID</th>
                <th>User Email</th>
                <th>Account</th>
                <th>Amount</th>
                <th>Profit</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trades as $t): ?>
            <tr>
                <td>#<?= $t['id'] ?></td>
                <td><?= htmlspecialchars($t['email']) ?></td>
                <td><?= ucfirst($t['account_type']) ?></td>
                <td>$<?= number_format($t['amount'], 2) ?></td>
                <td>+$<?= number_format($t['potential_profit'], 2) ?></td>
                <td><strong><?= strtoupper($t['status']) ?></strong></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="trade_id" value="<?= $t['id'] ?>">
                        <button type="submit" name="action" value="approve" class="btn btn-green">Approve / Win</button>
                        <button type="submit" name="action" value="reject" class="btn btn-red">Mark Lost</button>
                        <button type="submit" name="action" value="refund" class="btn btn-gray">Refund</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
