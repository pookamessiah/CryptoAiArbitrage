<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['auth_error'] = "Please login to access your dashboard.";
    $_SESSION['redirect_after_auth'] = $_SERVER['REQUEST_URI'];
    header("Location: ../oauth.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$pdo = null;
try {
    if (class_exists('Database')) {
        $dbInstance = new Database();
        $pdo = $dbInstance->connect(); 
    }
} catch (Exception $e) {}

$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email_address = trim($_POST['email']);
    $phone_number = trim($_POST['phone']);

    if (!empty($full_name) && !empty($email_address)) {
        try {
            if ($pdo !== null) {
                $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $update_stmt->execute([$full_name, $email_address, $phone_number, $user_id]);
            }
            $success_message = "Profile updated successfully.";
        } catch (Exception $e) {
            $error_message = "Error saving changes: " . $e->getMessage();
        }
    } else {
        $error_message = "Name and Email are required fields.";
    }
}

// Initializing UI Arrays
$user_profile = [
    'name'    => 'Jane Doe',
    'email'   => 'janedoe@example.com',
    'phone'   => '+1 (555) 019-2834',
    'balance' => 0.00
];

$admin_messages = [];
$recent_orders = [];
$transaction_history = [];

$admin_tickets = [
    ['file_path' => 'https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=600&q=80', 'description' => 'VIP Golden Circle Early Entry Pass. Valid for standard stadium layouts. Download and save to your phone.']
];

$support = ['email' => '', 'telegram' => '', 'whatsapp' => ''];

if ($pdo !== null) {
    try {
        // Fetch Admin Support Contacts
        $support_stmt = $pdo->query("SELECT email, telegram, whatsapp FROM admins LIMIT 1");
        $admin_data = $support_stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin_data) {
            $support = $admin_data;
        }

        // Fetch User Info
        $user_stmt = $pdo->prepare("SELECT full_name, email, phone, balance FROM users WHERE id = ? LIMIT 1");
        $user_stmt->execute([$user_id]);
        $fetched_user = $user_stmt->fetch();
        if ($fetched_user) {
            $user_profile = [
                'name'    => $fetched_user['full_name'] ?? 'N/A',
                'email'   => $fetched_user['email'] ?? 'N/A',
                'phone'   => $fetched_user['phone'] ?? 'N/A',
                'balance' => (float) ($fetched_user['balance'] ?? 0.00)
            ];
        }

        // Fetch System Alerts
        $msg_stmt = $pdo->prepare("SELECT message, created_at FROM users WHERE id = ? AND message IS NOT NULL AND message != '' ORDER BY id DESC");
        $msg_stmt->execute([$user_id]);
        $raw_messages = $msg_stmt->fetchAll();
        foreach ($raw_messages as $m_row) {
            $admin_messages[] = [
                'title'      => 'New Notification',
                'content'    => $m_row['message'],
                'created_at' => $m_row['created_at'] ?? date('Y-m-d H:i:s')
            ];
        }

        // Fetch Orders
        $order_stmt = $pdo->prepare("
            SELECT 
                o.order_id, 
                o.status AS order_status, 
                o.created_at AS purchase_date,
                a.artist_name AS show_title,
                c.title AS concert_title,
                t.ticket_name,
                t.section_name,
                t.row_name,
                t.seat_name
            FROM orders o
            INNER JOIN tickets t ON o.ticket_id = t.ticket_id
            INNER JOIN concerts c ON t.concert_id = c.concert_id
            INNER JOIN artists a ON c.artist_id = a.artist_id
            WHERE o.user_id = ?
            ORDER BY o.order_id DESC LIMIT 40
        ");
        $order_stmt->execute([$user_id]);
        $raw_orders = $order_stmt->fetchAll();
        foreach ($raw_orders as $or) {
            $seat_details = trim(sprintf(
                "%s (%s, %s, %s)", 
                $or['ticket_name'], 
                $or['section_name'], 
                $or['row_name'], 
                $or['seat_name']
            ));

            $recent_orders[] = [
                'id'     => 'TM-' . $or['order_id'],
                'title'  => $or['show_title'],
                'venue'  => $or['concert_title'],
                'seats'  => !empty($seat_details) ? $seat_details : 'General Admission',
                'status' => $or['order_status'], 
                'date'   => date('M d, Y', strtotime($or['purchase_date']))
            ];
        }
        
        // Fetch Transactions
        $tx_stmt = $pdo->prepare("
            SELECT 
                d.deposit_id, 
                d.created_at, 
                d.amount, 
                d.status,
                p.image_path
            FROM deposits d
            LEFT JOIN payment_methods p ON d.payment_id = p.payment_id
            WHERE d.user_id = ?
            ORDER BY d.deposit_id DESC LIMIT 30
        ");
        $tx_stmt->execute([$user_id]);
        $raw_txs = $tx_stmt->fetchAll();
        foreach ($raw_txs as $tx) {
            $transaction_history[] = [
                'ref'      => 'DEP-' . $tx['deposit_id'],
                'date'     => date('M d, Y', strtotime($tx['created_at'])),
                'method'   => !empty($tx['image_path']) ? '../uploads/payment-methods/' . $tx['image_path'] : 'Standard Gateway',
                'amount'   => $tx['amount'],
                'currency' => 'USD',
                'status'   => ($tx['status'] === 'confirmed' || $tx['status'] === 'completed' || $tx['status'] === 'Successful') ? 'Successful' : 'Processing'
            ];
        }

    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">

<?php include "../inc/head.php"; ?>

<body class="bg-gradient-to-br from-slate-100 via-white to-slate-200 min-h-screen">

    <?php include "../inc/header.php"; ?>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 p-4 text-emerald-800 rounded-r-xl shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600 text-lg"></i>
                <p class="font-bold">Success</p>
            </div>
            <p class="text-sm mt-1"><?php echo htmlspecialchars($_SESSION['flash_success']); ?></p>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 text-red-800 rounded-r-xl shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                <p class="font-bold">Notice</p>
            </div>
            <p class="text-sm mt-1"><?php echo htmlspecialchars($_SESSION['flash_error']); ?></p>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-5 py-10">

        <div class="rounded-3xl bg-gradient-to-r from-blue-700 via-indigo-700 to-slate-900 text-white p-8 shadow-2xl mb-8">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8">
                <div>
                    <h1 class="text-4xl font-black">Dashboard</h1>
                    <p class="text-blue-100 mt-2">
                        Welcome back, <span class="font-bold text-white"><?php echo htmlspecialchars($user_profile['name']); ?></span>. Manage your tickets, check your balance, and view your orders here.
                    </p>
                    <div class="mt-6 inline-flex items-center bg-white/20 rounded-full px-5 py-2 text-sm">
                        User ID: 
                        <span class="font-mono ml-2 font-bold text-yellow-300">
                            T6MST90C67<?php echo $user_id; ?>
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 items-center shrink-0 relative z-10">
                    <a href="fund-wallet.php" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-3 rounded-xl shadow hover:shadow-lg transition flex items-center gap-2">
                        <i class="fas fa-plus-circle"></i> Top Up Wallet
                    </a>
                    
                    <div class="relative group">
                        <button class="bg-white text-slate-900 font-bold px-6 py-3 rounded-xl shadow hover:shadow-lg transition flex items-center gap-2">
                            <i class="fas fa-headset text-blue-600"></i> Contact Support
                        </button>
                        <div class="hidden group-hover:block absolute right-0 top-full mt-2 bg-white rounded-2xl shadow-xl w-60 overflow-hidden z-50 text-slate-950">
                            <a href="https://wa.me/<?php echo urlencode($support['whatsapp']); ?>" target="_blank" class="flex items-center gap-3 px-5 py-4 hover:bg-green-50 transition border-b border-slate-100">
                                <i class="fab fa-whatsapp text-green-600 text-lg"></i> WhatsApp
                            </a>
                            <a href="https://t.me/<?php echo urlencode($support['telegram']); ?>" target="_blank" class="flex items-center gap-3 px-5 py-4 hover:bg-sky-50 transition border-b border-slate-100">
                                <i class="fab fa-telegram-plane text-sky-600 text-lg"></i> Telegram
                            </a>
                            <a href="mailto:<?php echo htmlspecialchars($support['email']); ?>" class="flex items-center gap-3 px-5 py-4 hover:bg-blue-50 transition">
                                <i class="fas fa-envelope text-blue-600 text-lg"></i> Email Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow mb-8 overflow-x-auto scrollbar-none">
            <div class="flex items-center space-x-2 min-w-max">
                <a href="#tickets-section" class="bg-slate-100 hover:bg-blue-600 text-slate-700 hover:text-white px-5 py-2.5 rounded-xl font-bold text-sm transition shadow-sm">
                    <i class="fas fa-ticket-alt mr-1.5"></i> My Tickets
                </a>
                <a href="#notifications-section" class="bg-slate-100 hover:bg-blue-600 text-slate-700 hover:text-white px-5 py-2.5 rounded-xl font-bold text-sm transition shadow-sm">
                    <i class="fas fa-bell mr-1.5"></i> Notifications
                </a>
                <a href="#profile-section" class="bg-slate-100 hover:bg-blue-600 text-slate-700 hover:text-white px-5 py-2.5 rounded-xl font-bold text-sm transition shadow-sm">
                    <i class="fas fa-user-cog mr-1.5"></i> Profile Settings
                </a>
                <a href="#orders-section" class="bg-slate-100 hover:bg-blue-600 text-slate-700 hover:text-white px-5 py-2.5 rounded-xl font-bold text-sm transition shadow-sm">
                    <i class="fas fa-shopping-bag mr-1.5"></i> Recent Orders
                </a>
                <a href="#transactions-section" class="bg-slate-100 hover:bg-blue-600 text-slate-700 hover:text-white px-5 py-2.5 rounded-xl font-bold text-sm transition shadow-sm">
                    <i class="fas fa-receipt mr-1.5"></i> Transaction History
                </a>
            </div>
        </div>

        <div class="grid lg:grid-cols-12 gap-8 items-start">
            
            <div class="lg:col-span-7 space-y-8">
                
                <div id="tickets-section" class="bg-white rounded-3xl shadow-xl p-8">
                    <h2 class="text-2xl font-black mb-2 flex items-center gap-2">
                        <i class="fas fa-ticket-alt text-indigo-600"></i> Download Tickets
                    </h2>
                    <p class="text-slate-500 mb-6">
                        View and download tickets for your upcoming events.
                    </p>
                    <div class="space-y-4">
                        <?php foreach ($admin_tickets as $ticket): ?>
                            <div class="group rounded-2xl border border-slate-200 bg-white hover:border-blue-600 hover:shadow-xl transition overflow-hidden">
                                <div class="h-48 bg-slate-950 overflow-hidden relative">
                                    <img src="<?php echo htmlspecialchars($ticket['file_path']); ?>" onerror="this.src='https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=600&q=80';" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent"></div>
                                </div>
                                <div class="p-6">
                                    <p class="text-sm text-slate-600 leading-relaxed font-semibold mb-4">
                                        <?php echo htmlspecialchars($ticket['description']); ?>
                                    </p>
                                    <a href="<?php echo htmlspecialchars($ticket['file_path']); ?>" target="_blank" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl px-6 py-3 font-bold transition shadow shadow-blue-600/20 text-xs uppercase tracking-wider">
                                        <i class="fas fa-download"></i> Download Ticket
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="orders-section" class="bg-white rounded-3xl shadow-xl p-8">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-black">Recent Orders</h2>
                            <p class="text-slate-500 text-sm mt-1">Review your recent ticket purchases.</p>
                        </div>
                        <?php if (count($recent_orders) > 3): ?>
                            <button onclick="openOrdersModal()" class="bg-slate-100 hover:bg-slate-200 text-slate-900 font-bold px-4 py-2 rounded-xl text-xs transition uppercase tracking-wide">
                                View All
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-4">
                        <?php if (!empty($recent_orders)): ?>
                            <?php 
                            $limited_orders = array_slice($recent_orders, 0, 3);
                            foreach ($limited_orders as $order): 
                            ?>
                                <div class="rounded-2xl border border-slate-200 p-5 bg-white hover:border-slate-300 transition flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-mono font-bold text-blue-600 bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-lg"><?php echo htmlspecialchars($order['id']); ?></span>
                                            <?php 
                                                $status = strtolower($order['status']);
                                                $badge_cls = ($status === 'confirmed' || $status === 'completed' || $status === 'success') ? 'bg-green-500 text-white' : (($status === 'processing' || $status === 'pending') ? 'bg-yellow-500 text-slate-900' : 'bg-blue-600 text-white');
                                            ?>
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider <?php echo $badge_cls; ?>">
                                                <?php echo htmlspecialchars($order['status']); ?>
                                            </span>
                                        </div>
                                        <h4 class="font-bold text-slate-900 text-base"><?php echo htmlspecialchars($order['title']); ?></h4>
                                        <p class="text-xs text-slate-500 font-medium">
                                            <i class="fas fa-map-marker-alt text-slate-400 mr-1"></i> <?php echo htmlspecialchars($order['venue']); ?> • <span class="font-bold text-slate-700"><?php echo htmlspecialchars($order['seats']); ?></span>
                                        </p>
                                    </div>
                                    <div class="text-left sm:text-right shrink-0 border-t sm:border-t-0 border-slate-100 pt-3 sm:pt-0">
                                        <span class="text-sm font-black text-slate-800 block font-mono"><?php echo htmlspecialchars($order['date']); ?></span>
                                        <span class="text-[11px] text-slate-400 font-medium">Purchased on</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-slate-400 italic text-center py-6">You have no recent orders.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="transactions-section" class="bg-white rounded-3xl shadow-xl p-8">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-black">Transaction History</h2>
                            <p class="text-slate-500 text-sm mt-1">A record of your recent deposits and payments.</p>
                        </div>
                        <?php if (count($transaction_history) > 3): ?>
                            <button onclick="openTxModal()" class="bg-slate-100 hover:bg-slate-200 text-slate-900 font-bold px-4 py-2 rounded-xl text-xs transition uppercase tracking-wide">
                                View All
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <?php if (!empty($transaction_history)): ?>
                            <table class="w-full text-left text-sm text-slate-600 border-collapse">
                                <thead class="bg-slate-900 text-white uppercase tracking-wider text-[11px] font-bold">
                                    <tr>
                                        <th class="p-4 font-mono">Reference</th>
                                        <th class="p-4">Date</th>
                                        <th class="p-4">Method</th>
                                        <th class="p-4 text-right">Amount</th>
                                        <th class="p-4 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    <?php 
                                    $limited_tx = array_slice($transaction_history, 0, 3);
                                    foreach ($limited_tx as $txn): 
                                    ?>
                                        <tr class="hover:bg-slate-50/80 transition">
                                            <td class="p-4 font-mono font-bold text-slate-900"><?php echo htmlspecialchars($txn['ref']); ?></td>
                                            <td class="p-4 text-slate-500 font-bold font-mono"><?php echo htmlspecialchars($txn['date']); ?></td>
                                            <td class="p-4 text-slate-500 font-bold">
                                                <?php if (strpos($txn['method'], '../uploads/') === 0): ?>
                                                    <img src="<?php echo htmlspecialchars($txn['method']); ?>" alt="Method" class="h-6 w-auto object-contain rounded max-w-[60px]">
                                                <?php else: ?>
                                                    <span><?php echo htmlspecialchars($txn['method']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 text-right font-black text-blue-600 font-mono">
                                                $<?php echo number_format($txn['amount'], 2); ?>
                                            </td>
                                            <td class="p-4 text-center">
                                                <span class="font-bold tracking-wide text-xs px-2.5 py-1 rounded-lg <?php echo ($txn['status'] === 'Successful') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo htmlspecialchars($txn['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-sm text-slate-400 italic text-center py-6">No transactions found.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="lg:col-span-5 space-y-8 sticky top-6">
                
                <div class="rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 text-white shadow-2xl p-8">
                    <h2 class="text-2xl font-black mb-6">My Wallet</h2>
                    
                    <div class="space-y-5">
                        <div class="flex justify-between border-b border-white/10 pb-4">
                            <div>
                                <div class="font-semibold text-white/90">Full Name</div>
                                <div class="text-sm text-white/60">Account Holder</div>
                            </div>
                            <div class="font-bold text-right text-indigo-300">
                                <?php echo htmlspecialchars($user_profile['name']); ?>
                            </div>
                        </div>

                        <div class="flex justify-between border-b border-white/10 pb-4">
                            <div>
                                <div class="font-semibold text-white/90">Email Address</div>
                                <div class="text-sm text-white/60">Primary Contact</div>
                            </div>
                            <div class="font-mono text-xs font-bold text-right text-indigo-300 max-w-[180px] break-all">
                                <?php echo htmlspecialchars($user_profile['email']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-white/20 mt-8 pt-6">
                        <div class="flex justify-between items-center flex-wrap gap-4">
                            <div>
                                <div class="text-white/70 font-semibold">Wallet Balance</div>
                                <div class="text-4xl font-black mt-2 font-mono tracking-tight text-emerald-400">
                                    $<?php echo number_format($user_profile['balance'], 2); ?>
                                </div>
                            </div>
                            
                            <a href="fund-wallet.php" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-5 py-3 rounded-xl shadow transition text-xs uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fas fa-plus-circle text-sm"></i> Top Up
                            </a>
                        </div>
                    </div>
                </div>

                <div id="notifications-section" class="bg-white rounded-3xl shadow-xl p-8">
                    <h3 class="text-xl font-black text-slate-900 mb-2 flex items-center gap-2">
                        <i class="fas fa-bell text-amber-500 animate-pulse"></i> Notifications
                    </h3>
                    <p class="text-slate-500 text-xs mb-6">Updates and messages regarding your account.</p>
                    
                    <div class="space-y-4 max-h-[300px] overflow-y-auto pr-1">
                        <?php if (!empty($admin_messages)): ?>
                            <?php foreach ($admin_messages as $msg): ?>
                                <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 p-5 rounded-2xl space-y-2 shadow-sm">
                                    <div class="flex justify-between items-center gap-2">
                                        <h5 class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($msg['title']); ?></h5>
                                        <span class="text-[10px] text-amber-700 font-bold font-mono bg-white px-2 py-0.5 rounded border border-amber-200 shadow-sm shrink-0"><?php echo htmlspecialchars($msg['created_at']); ?></span>
                                    </div>
                                    <p class="text-xs font-medium text-slate-700 leading-relaxed"><?php echo htmlspecialchars($msg['content']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-xs text-slate-400 italic text-center py-6 bg-slate-50 border border-dashed border-slate-200 rounded-xl">You have no new notifications.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow border border-slate-100">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-lock text-2xl text-green-500 mb-2"></i>
                            <div class="font-bold text-xs text-slate-800">Secure</div>
                        </div>
                        <div class="flex flex-col items-center">
                            <i class="fas fa-shield-alt text-2xl text-blue-500 mb-2"></i>
                            <div class="font-bold text-xs text-slate-800">Verified</div>
                        </div>
                        <div class="flex flex-col items-center">
                            <i class="fas fa-bolt text-2xl text-yellow-500 mb-2"></i>
                            <div class="font-bold text-xs text-slate-800">Fast Sync</div>
                        </div>
                    </div>
                </div>

                <div id="profile-section" class="bg-white rounded-3xl shadow-xl p-8">
                    <h2 class="text-xl font-black mb-1 flex items-center gap-2">
                        <i class="fas fa-user-cog text-slate-700"></i> Profile Settings
                    </h2>
                    <p class="text-slate-400 text-xs mb-6">Update your account information.</p>

                    <?php if (!empty($success_message)): ?>
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold text-xs p-4 rounded-xl mb-4">
                            <i class="fas fa-check-circle mr-1.5 text-emerald-600"></i> <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="bg-rose-50 border border-rose-200 text-rose-800 font-bold text-xs p-4 rounded-xl mb-4">
                            <i class="fas fa-exclamation-triangle mr-1.5 text-rose-600"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="dashboard.php" method="POST" class="space-y-4">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_profile['name']); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-semibold focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user_profile['email']); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-semibold focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user_profile['phone']); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-semibold focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 outline-none transition">
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-3 px-4 rounded-xl shadow transition text-xs uppercase tracking-wide">
                            Save Changes
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- ================= MODALS ================= -->

    <!-- Orders Modal -->
    <div id="ordersModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 shadow-2xl">
        <div class="bg-white rounded-3xl max-w-2xl w-full flex flex-col max-h-[85vh] overflow-hidden transform transition-all animate-in fade-in zoom-in duration-200">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div>
                    <h3 class="text-xl font-black text-slate-900">Order History</h3>
                    <p class="text-xs text-slate-500 mt-0.5">A complete list of your ticket orders.</p>
                </div>
                <button onclick="closeOrdersModal()" class="text-slate-400 hover:text-slate-700 text-xl bg-slate-200/50 hover:bg-slate-200 w-8 h-8 rounded-full flex items-center justify-center transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto space-y-4">
                <?php foreach ($recent_orders as $order): ?>
                    <div class="rounded-2xl border border-slate-200 p-4 bg-slate-50/50 hover:border-slate-300 transition flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono font-bold text-blue-600 bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-lg"><?php echo htmlspecialchars($order['id']); ?></span>
                                <?php 
                                    $status = strtolower($order['status']);
                                    $badge_cls = ($status === 'confirmed' || $status === 'completed' || $status === 'success') ? 'bg-green-500 text-white' : (($status === 'processing' || $status === 'pending') ? 'bg-yellow-500 text-slate-900' : 'bg-blue-600 text-white');
                                ?>
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider <?php echo $badge_cls; ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($order['title']); ?></h4>
                            <p class="text-[11px] text-slate-500 font-medium">
                                <i class="fas fa-map-marker-alt text-slate-400 mr-1"></i> <?php echo htmlspecialchars($order['venue']); ?> • <span class="font-bold text-slate-700"><?php echo htmlspecialchars($order['seats']); ?></span>
                            </p>
                        </div>
                        <div class="text-left sm:text-right shrink-0">
                            <span class="text-xs font-black text-slate-800 block font-mono"><?php echo htmlspecialchars($order['date']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Transactions Modal -->
    <div id="txModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 shadow-2xl">
        <div class="bg-white rounded-3xl max-w-4xl w-full flex flex-col max-h-[85vh] overflow-hidden transform transition-all animate-in fade-in zoom-in duration-200">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div>
                    <h3 class="text-xl font-black text-slate-900">All Transactions</h3>
                    <p class="text-xs text-slate-500 mt-0.5">A complete history of your wallet deposits and payments.</p>
                </div>
                <button onclick="closeTxModal()" class="text-slate-400 hover:text-slate-700 text-xl bg-slate-200/50 hover:bg-slate-200 w-8 h-8 rounded-full flex items-center justify-center transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto">
                <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-inner">
                    <table class="w-full text-left text-sm text-slate-600 border-collapse">
                        <thead class="bg-slate-900 text-white uppercase tracking-wider text-[11px] font-bold sticky top-0">
                            <tr>
                                <th class="p-4 font-mono">Reference</th>
                                <th class="p-4">Date</th>
                                <th class="p-4">Method</th>
                                <th class="p-4 text-right">Amount</th>
                                <th class="p-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <?php foreach ($transaction_history as $txn): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-4 font-mono font-bold text-slate-900"><?php echo htmlspecialchars($txn['ref']); ?></td>
                                    <td class="p-4 text-slate-500 font-bold font-mono text-xs"><?php echo htmlspecialchars($txn['date']); ?></td>
                                    <td class="p-4 text-slate-500 font-bold text-xs">
                                        <?php if (strpos($txn['method'], '../uploads/') === 0): ?>
                                            <img src="<?php echo htmlspecialchars($txn['method']); ?>" alt="Method" class="h-6 w-auto object-contain rounded max-w-[60px]">
                                        <?php else: ?>
                                            <span><?php echo htmlspecialchars($txn['method']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-right font-black text-blue-600 font-mono">
                                        $<?php echo number_format($txn['amount'], 2); ?>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="font-bold tracking-wide text-xs px-2.5 py-1 rounded-lg <?php echo ($txn['status'] === 'Successful') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo htmlspecialchars($txn['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function openOrdersModal() {
            document.getElementById('ordersModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeOrdersModal() {
            document.getElementById('ordersModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function openTxModal() {
            document.getElementById('txModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeTxModal() {
            document.getElementById('txModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Close modals on outside click
        window.onclick = function(event) {
            const ordersModal = document.getElementById('ordersModal');
            const txModal = document.getElementById('txModal');
            if (event.target === ordersModal) { closeOrdersModal(); }
            if (event.target === txModal) { closeTxModal(); }
        }
    </script>

</body>
</html>
