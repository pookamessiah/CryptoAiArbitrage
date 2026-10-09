<?php
session_start();
require_once '../config/db.php';

// ---------------------------------------------
// AUTH CHECK
// ---------------------------------------------
if (!isset($_SESSION['user_id'])) {
    $_SESSION['auth_error'] = "Please login to continue checkout.";
    $_SESSION['redirect_after_auth'] = $_SERVER['REQUEST_URI'];
    header("Location: ../oauth");
    exit;
}
$user_id = (int) $_SESSION['user_id'];

// ---------------------------------------------
// DETECT FLOW MODE: WALLET FUNDING VS PASS CHECKOUT
// ---------------------------------------------
$isWalletFunding = isset($_GET['fund-wallet']) && (int)$_GET['fund-wallet'] === 1;

$order_ids = [];
$input_amount = 0.00;

if ($isWalletFunding) {
    // Wallet Flow: Read absolute input load amounts
    $input_amount = isset($_REQUEST['amount']) ? max(0, (float)$_REQUEST['amount']) : 0;
    if ($input_amount <= 0) {
        $_SESSION['flash_error'] = "Please specify a valid amount to fund your wallet.";
        header("Location: fund-wallet.php");
        exit;
    }
} else {
    // Standard Ticket Flow: Validate active session allocations
    if (!isset($_SESSION['checkout_order_ids']) || !is_array($_SESSION['checkout_order_ids']) || empty($_SESSION['checkout_order_ids'])) {
        $_SESSION['flash_error'] = "Invalid checkout session or missing order references.";
        header("Location: dashboard");
        exit;
    }
    $order_ids = array_map('intval', $_SESSION['checkout_order_ids']);
}

$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
$displayCurrency = isset($_GET['currency']) ? trim(strtoupper($_GET['currency'])) : 'USD';

if ($payment_id <= 0) {
    $_SESSION['flash_error'] = "Invalid payment gateway allocation specified.";
    header("Location: " . ($isWalletFunding ? "fund-wallet.php" : "dashboard"));
    exit;
}

// ---------------------------------------------
// DATABASE CONNECTION
// ---------------------------------------------
try {
    $pdo = (new Database())->connect();
} catch (Exception $e) {
    die("Database connection failed.");
}

// ---------------------------------------------
// FETCH SYSTEM & PAYMENT CORE INFO
// ---------------------------------------------
$stmt = $pdo->prepare("SELECT payment_id, image_path, type, error_msg, is_active FROM payment_methods WHERE payment_id = ? LIMIT 1");
$stmt->execute([$payment_id]);
$methodCore = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$methodCore || $methodCore['is_active'] !== 'yes') {
    $_SESSION['flash_error'] = "The selected payment method is currently deactivated or unavailable.";
    header("Location: " . ($isWalletFunding ? "fund-wallet.php" : "dashboard"));
    exit;
}

$payment_type = $methodCore['type']; 
$payment_logo = "../uploads/payment-methods/" . htmlspecialchars($methodCore['image_path']);

// ---------------------------------------------
// DYNAMIC METADATA DISPATCHER
// ---------------------------------------------
$gatewayDetails = [];
$instructions = "Please send payment to the details below and upload proof.";

switch ($payment_type) {
    case 'gift_card':
        $stmt = $pdo->prepare("SELECT card_name, instruction FROM pay_gift_card WHERE payment_id = ? LIMIT 1");
        $stmt->execute([$payment_id]);
        $gatewayDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($gatewayDetails) $instructions = $gatewayDetails['instruction'];
        break;

    case 'bank':
        $stmt = $pdo->prepare("SELECT bank_name, account_name, account_number, instruction FROM pay_bank WHERE payment_id = ? LIMIT 1");
        $stmt->execute([$payment_id]);
        $gatewayDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($gatewayDetails) $instructions = $gatewayDetails['instruction'];
        break;

    case 'crypto':
        $stmt = $pdo->prepare("SELECT coin, chain, address, instruction FROM pay_crypto WHERE payment_id = ? LIMIT 1");
        $stmt->execute([$payment_id]);
        $gatewayDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($gatewayDetails) $instructions = $gatewayDetails['instruction'];
        break;

    case 'e_pay':
        $stmt = $pdo->prepare("SELECT method_name, email_phone, instruction FROM pay_e_pay WHERE payment_id = ? LIMIT 1");
        $stmt->execute([$payment_id]);
        $gatewayDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($gatewayDetails) $instructions = $gatewayDetails['instruction'];
        break;

    default:
        $_SESSION['flash_error'] = "Unsupported gateway execution variant encountered.";
        header("Location: " . ($isWalletFunding ? "fund-wallet.php" : "dashboard"));
        exit;
}

if (!$gatewayDetails) {
    $_SESSION['flash_error'] = "Gateway setup configuration records are missing for this option.";
    header("Location: " . ($isWalletFunding ? "fund-wallet.php" : "dashboard"));
    exit;
}

// ---------------------------------------------
// USER & REGIONAL FX CALCULATIONS
// ---------------------------------------------
$stmt = $pdo->prepare("SELECT country, balance FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_country = trim($user['country'] ?? '');
$user_balance = (float)($user['balance'] ?? 0.00);

$stmt = $pdo->prepare("SELECT exchange_rates FROM region_settings WHERE country = ? LIMIT 1");
$stmt->execute([$user_country]);
$region = $stmt->fetch(PDO::FETCH_ASSOC);
$localRate = (float)($region['exchange_rates'] ?? 1);

$displayRate = ($displayCurrency === 'USD') ? 1 : $localRate;

// Determine absolute contextual pricing total
if ($isWalletFunding) {
    $final_payable_amount = $input_amount * $displayRate;
    $deposit_description = "Wallet funding request deposit via raw external gateway verification entry.";
} else {
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT o.*, t.price 
        FROM orders o
        INNER JOIN tickets t ON o.ticket_id = t.ticket_id
        WHERE o.order_id IN ($placeholders)
    ");
    $stmt->execute($order_ids);
    $all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$all_orders) {
        $_SESSION['flash_error'] = "Linked record references for this checkout window were not found.";
        header("Location: dashboard");
        exit;
    }

    $total_amount = 0;
    foreach ($all_orders as $order) {
        if ((int)$order['user_id'] !== $user_id) {
            $_SESSION['flash_error'] = "Unauthorized system order access context assignment mapping.";
            header("Location: dashboard");
            exit;
        }
        $total_amount += (float)$order['price'];
    }
    $final_payable_amount = $total_amount * $displayRate;
    $deposit_description = "Direct platform pass purchase checkout acquisition pipeline process allocation.";
}

$symbols = ['USD'=>'$', 'EUR'=>'€', 'GBP'=>'£', 'NGN'=>'₦', 'CAD'=>'C$', 'AUD'=>'A$', 'KES'=>'KSh', 'ZAR'=>'R', 'GHS'=>'GH₵'];
$displaySymbol = $symbols[$displayCurrency] ?? '$';

// ---------------------------------------------
// INTERCEPT: PAY WITH WALLET BALANCE (TICKET ROUTE ONLY)
// ---------------------------------------------
$isBalancePayment = isset($_GET['pay-with-balance']) && (int)$_GET['pay-with-balance'] === 1 && !$isWalletFunding;

if ($isBalancePayment) {
    if ($user_balance < $final_payable_amount) {
        $_SESSION['flash_error'] = "Inadequate account balance. You require an additional " . $displaySymbol . number_format(($final_payable_amount - $user_balance), 2) . " to clear this checkout sequence.";
        header("Location: fund-wallet.php");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_balance_payment'])) {
        try {
            $pdo->beginTransaction();

            $deductStmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
            $deductStmt->execute([$final_payable_amount, $user_id, $final_payable_amount]);

            if ($deductStmt->rowCount() === 0) {
                throw new Exception("Account mutation safety locks tripped. Balance update declined.");
            }

            $order_ids_string = implode(',', $order_ids);
            $logDeposit = $pdo->prepare("
                INSERT INTO deposits (user_id, payment_id, order_ids, amount, currency, payment_type, description, fund_wallet, submitted_details, proof_file, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'no', ?, 'Wallet Deduction Balance', 'approved')
            ");
            $logDeposit->execute([
                $user_id,
                $payment_id,
                $order_ids_string,
                $final_payable_amount,
                $displayCurrency,
                'wallet_balance',
                'Ticket acquisition cleared instantly via internal wallet digital balance allocation deduction deduction.',
                json_encode(['payment_type' => 'account_balance_deduction'])
            ]);

            $updatePlaceholders = implode(',', array_fill(0, count($order_ids), '?'));
            $updateStmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'paid' 
                WHERE order_id IN ($updatePlaceholders) AND user_id = ?
            ");
            $updateParams = array_merge($order_ids, [$user_id]);
            $updateStmt->execute($updateParams);

            $pdo->commit();
            unset($_SESSION['checkout_order_ids']);
            
            $_SESSION['flash_success'] = "Order settled completely using your wallet balance! Your passes are active.";
            header("Location: dashboard");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Balance checkout failure: " . $e->getMessage();
        }
    }
}

// ---------------------------------------------
// POST FORM HANDLING: MANUAL RECEIPT PROCESSING
// ---------------------------------------------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isBalancePayment) {
    $submittedData = [];
    
    if ($payment_type === 'gift_card') {
        $submittedData['card_code'] = trim($_POST['card_code'] ?? '');
        $submittedData['card_pin'] = trim($_POST['card_pin'] ?? '');
        if (empty($submittedData['card_code'])) $errors[] = "Gift card code field is required.";
    } elseif ($payment_type === 'bank') {
        $submittedData['sender_bank'] = trim($_POST['sender_bank'] ?? '');
        $submittedData['sender_account_name'] = trim($_POST['sender_account_name'] ?? '');
        if (empty($submittedData['sender_account_name'])) $errors[] = "Sender account name field is required.";
    } elseif ($payment_type === 'crypto') {
        $submittedData['txid_hash'] = trim($_POST['txid_hash'] ?? '');
        $submittedData['sender_wallet'] = trim($_POST['sender_wallet'] ?? '');
        if (empty($submittedData['txid_hash'])) $errors[] = "Transaction Hash/TXID link field is required.";
    } elseif ($payment_type === 'e_pay') {
        $submittedData['sender_email_phone'] = trim($_POST['sender_email_phone'] ?? '');
        $submittedData['transaction_reference'] = trim($_POST['transaction_reference'] ?? '');
        if (empty($submittedData['transaction_reference'])) $errors[] = "Transaction Reference is required.";
    }

    $uploadedFilePath = null;
    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['proof_file']['tmp_name'];
        $fileName = $_FILES['proof_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/proofs/';
            
            if(!is_dir($uploadFileDir)){
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $uploadedFilePath = "uploads/proofs/" . $newFileName;
            } else {
                $errors[] = 'There was an error moving the uploaded validation file.';
            }
        } else {
            $errors[] = 'Upload failed. Allowed file formats: ' . implode(',', $allowedExtensions);
        }
    } else {
        $errors[] = 'Proof of transaction document or screenshot image is required.';
    }

    if (empty($errors)) {
        $order_ids_string = $isWalletFunding ? null : implode(',', $order_ids);
        $json_details = json_encode($submittedData);
        
        try {
            $pdo->beginTransaction();

            // 1. Insert the log entry to tracking deposits layout 
            $insertStmt = $pdo->prepare("
                INSERT INTO deposits (user_id, payment_id, order_ids, amount, currency, payment_type, description, fund_wallet, submitted_details, proof_file, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $insertStmt->execute([
                $user_id, 
                $payment_id, 
                $order_ids_string, 
                $final_payable_amount, 
                $displayCurrency, 
                $payment_type,
                $deposit_description,
                $isWalletFunding ? 'yes' : 'no',
                $json_details, 
                $uploadedFilePath
            ]);

            // 2. Modify processing parameters based on funding mode context
            if (!$isWalletFunding) {
                $updatePlaceholders = implode(',', array_fill(0, count($order_ids), '?'));
                $updateStmt = $pdo->prepare("
                    UPDATE orders 
                    SET status = 'processing' 
                    WHERE order_id IN ($updatePlaceholders) AND user_id = ?
                ");
                $updateParams = array_merge($order_ids, [$user_id]);
                $updateStmt->execute($updateParams);

                unset($_SESSION['checkout_order_ids']);
                $_SESSION['flash_success'] = "Your order payment proof was sent successfully! Check back shortly.";
            } else {
                $_SESSION['flash_success'] = "Wallet deposit request submitted. Payment Under Review";
            }

            $pdo->commit();
            header("Location: dashboard");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Failed saving parameters to database: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include "../inc/head.php"; ?>
<?php include "../inc/navbar.php"; ?>
<body class="bg-gradient-to-br from-slate-100 via-white to-slate-200 min-h-screen">
<?php include "../inc/header.php"; ?>

<div class="max-w-4xl mx-auto px-5 py-10">
    <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-blue-800 to-indigo-900 p-8 text-white flex items-center justify-between">
            <div>
                <span class="text-xs uppercase font-bold tracking-widest bg-white/20 px-3 py-1 rounded-full">Secure Payment Hub</span>
                <h1 class="text-3xl font-black mt-2">
                    <?= $isWalletFunding ? "Wallet Funding Processing" : ($isBalancePayment ? "Continue with Wallet Balance" : "Gateway Validation") ?>
                </h1>
            </div>
            <img src="<?php echo $payment_logo; ?>" alt="Payment Method Logo" class="h-16 w-auto object-contain bg-white/10 p-2 rounded-xl">
        </div>

        <div class="p-8">
            <?php if (!empty($errors)): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 text-red-700 rounded-r-xl">
                    <p class="font-bold">Please resolve the following system configuration messages:</p>
                    <ul class="list-disc pl-5 text-sm mt-1">
                        <?php foreach($errors as $error) echo "<li>".htmlspecialchars($error)."</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($isBalancePayment): ?>
                <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6 mb-8 text-center">
                    <h3 class="font-bold text-emerald-900 text-xl flex items-center justify-center gap-2 mb-2">
                        <i class="fas fa-wallet text-emerald-600"></i> Wallet Balance
                    </h3>
                    <p class="text-slate-600 mb-4">Paying with your wallet balance. No manual receipts or uploads required.</p>
                    
                    <div class="inline-grid grid-cols-2 gap-8 text-left bg-white p-4 rounded-xl border border-emerald-100 shadow-sm">
                        <div>
                            <span class="text-xs text-slate-400 block font-medium uppercase tracking-wider">Your Balance</span>
                            <span class="text-lg font-bold text-slate-800"><?= $displaySymbol . number_format($user_balance, 2) ?></span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 block font-medium uppercase tracking-wider">Order Total</span>
                            <span class="text-lg font-bold text-blue-700"><?= $displaySymbol . number_format($final_payable_amount, 2) ?></span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="confirm_balance_payment" value="1">
                    <div class="flex items-center justify-between gap-4 pt-4">
                        <a href="checkout?currency=<?php echo urlencode($displayCurrency); ?>" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold px-6 py-3.5 rounded-xl text-center transition text-sm">
                            Cancel
                        </a>
                        <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-black px-6 py-3.5 rounded-xl shadow-lg hover:shadow-xl transition text-center tracking-wide text-sm">
                            Confirm Order Payment & Secure Tickets <i class="fas fa-check-circle ml-1"></i>
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <div class="bg-blue-50/70 border border-blue-200 rounded-2xl p-6 mb-8">
                    <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2 mb-2">
                        <i class="fas fa-info-circle text-blue-600"></i> Payment Instructions
                    </h3>
                    <p class="text-slate-700 whitespace-pre-line leading-relaxed"><?php echo htmlspecialchars($instructions); ?></p>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 mb-8">
                    <h3 class="font-bold text-slate-800 mb-4 pb-2 border-b border-slate-200 text-sm uppercase tracking-wider">Send Funds to the Following Target Account</h3>
                    
                    <?php if ($payment_type === 'gift_card'): ?>
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <span class="text-xs text-slate-400 block font-medium">Acceptable Card Asset Class</span>
                                <span class="text-lg font-bold text-slate-900"><?php echo htmlspecialchars($gatewayDetails['card_name']); ?></span>
                            </div>
                            <div class="shrink-0">
                                <a href="../giftcard" target="_blank" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl shadow-sm transition-all focus:outline-none">
                                    <i class="fas fa-shopping-bag text-[11px]"></i> Buy Giftcard Guide <i class="fas fa-external-link-alt text-[10px] text-slate-400"></i>
                                </a>
                            </div>
                        </div>

                    <?php elseif ($payment_type === 'bank'): ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <span class="text-xs text-slate-400 block font-medium">Bank Name</span>
                                <span class="text-base font-bold text-slate-900"><?php echo htmlspecialchars($gatewayDetails['bank_name']); ?></span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block font-medium">Account Name</span>
                                <span class="text-base font-bold text-slate-900"><?php echo htmlspecialchars($gatewayDetails['account_name']); ?></span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block font-medium">Account Number</span>
                                <span class="text-base font-mono font-bold text-blue-700 select-all"><?php echo htmlspecialchars($gatewayDetails['account_number']); ?></span>
                            </div>
                        </div>

                    <?php elseif ($payment_type === 'crypto'): ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <span class="text-xs text-slate-400 block font-medium">Digital Currency Asset</span>
                                <span class="text-base font-bold text-slate-900"><?php echo htmlspecialchars($gatewayDetails['coin']); ?></span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block font-medium">Network Chain Mapping</span>
                                <span class="text-xs font-bold inline-block px-2 py-0.5 rounded bg-amber-100 text-amber-800 uppercase mt-1"><?php echo htmlspecialchars($gatewayDetails['chain']); ?></span>
                            </div>
                            <div class="md:col-span-3">
                                <span class="text-xs text-slate-400 block font-medium">Wallet Address Destination</span>
                                <span class="text-sm font-mono font-bold text-indigo-700 block break-all select-all mt-1 bg-white p-3 border border-slate-200 rounded-xl"><?php echo htmlspecialchars($gatewayDetails['address']); ?></span>
                            </div>
                        </div>

                    <?php elseif ($payment_type === 'e_pay'): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <span class="text-xs text-slate-400 block font-medium">Digital E-Pay Platform</span>
                                <span class="text-base font-bold text-slate-900"><?php echo htmlspecialchars($gatewayDetails['method_name']); ?></span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block font-medium">Merchant Address Alias (Email/Phone)</span>
                                <span class="text-base font-mono font-bold text-blue-700 select-all"><?php echo htmlspecialchars($gatewayDetails['email_phone']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                        <h3 class="font-bold text-slate-900 text-base mb-2">Submit Your Verification Data</h3>
                        
                        <div class="p-4 bg-emerald-50 text-emerald-900 rounded-xl flex justify-between items-center mb-4">
                            <span class="text-sm font-medium">Required Payable Amount (Calculated Rate Total):</span>
                            <span class="text-xl font-black"><?php echo $displaySymbol . number_format($final_payable_amount, 2) . ' ' . $displayCurrency; ?></span>
                        </div>

                        <?php if ($payment_type === 'gift_card'): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Gift Card Voucher Code/Number</label>
                                    <input type="text" name="card_code" required class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 font-mono" placeholder="XXXX-XXXX-XXXX-XXXX">
                                </div>
                                <div>
                                    <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Card Security Code / Pin (Optional)</label>
                                    <input type="text" name="card_pin" class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 font-mono" placeholder="1234">
                                </div>
                            </div>

                        <?php elseif ($payment_type === 'bank'): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Your Executing Bank Name</label>
                                    <input type="text" name="sender_bank" required class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500" placeholder="e.g. Chase Bank">
                                </div>
                                <div>
                                    <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Sender Bank Account Holder Name</label>
                                    <input type="text" name="sender_account_name" required class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500" placeholder="John Doe">
                                </div>
                            </div>

                        <?php elseif ($payment_type === 'crypto'): ?>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Transaction ID / TxHash String</label>
                                    <input type="text" name="txid_hash" required class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 font-mono" placeholder="0xabc123...">
                                </div>
                                <div>
                                    <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Your External Wallet Outgoing Address (Optional)</label>
                                    <input type="text" name="sender_wallet" class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 font-mono" placeholder="Source wallet hash">
                                </div>
                            </div>

                        <?php elseif ($payment_type === 'e_pay'): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Your Dynamic E-Pay Identifier (Email/Phone)</label>
                                    <input type="text" name="sender_email_phone" required class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500" placeholder="john.doe@example.com">
                                </div>
                                <div>
                                    <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Transaction Ref / Order Reference</label>
                                    <input type="text" name="transaction_reference" required class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 font-mono" placeholder="PAY-998231">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="pt-2">
                            <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Upload Receipt Image / Payment Proof Documents</label>
                            <input type="file" name="proof_file" required class="w-full border border-dashed border-slate-300 bg-slate-50/50 rounded-xl px-4 py-4 text-sm focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-700 file:text-white hover:file:bg-slate-800 cursor-pointer">
                            <small class="text-slate-400 block mt-1">Accepted formats: JPEG, PNG, PDF.</small>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4 pt-4">
                        <a href="<?= $isWalletFunding ? "fund-wallet.php?currency=" . urlencode($displayCurrency) . "&amount=" . $input_amount : "checkout?currency=" . urlencode($displayCurrency); ?>" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold px-6 py-3.5 rounded-xl text-center transition text-sm">
                            <i class="fas fa-chevron-left mr-1"></i> Go Back
                        </a>
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black px-6 py-3.5 rounded-xl shadow-lg hover:shadow-xl transition text-center tracking-wide text-sm">
                            Confirm & Process Payment Data <i class="fas fa-shield-alt ml-1"></i>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include "../inc/footer.php"; ?>
</body>
</html>
