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
// DATABASE CONNECTION
// ---------------------------------------------
try {
    $pdo = (new Database())->connect();
} catch (Exception $e) {
    die("Database connection failed.");
}

// ---------------------------------------------
// USER INFORMATION
// ---------------------------------------------
$stmt = $pdo->prepare("SELECT country, balance FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_country = trim($user['country'] ?? '');
$current_wallet_balance = (float)($user['balance'] ?? 0.00);

// ---------------------------------------------
// REGION SETTINGS
// ---------------------------------------------
$stmt = $pdo->prepare("SELECT currency, exchange_rates FROM region_settings WHERE country = ? LIMIT 1");
$stmt->execute([$user_country]);
$region = $stmt->fetch(PDO::FETCH_ASSOC);

$localCurrency = $region['currency'] ?? 'USD';
$localRate     = (float)($region['exchange_rates'] ?? 1);

// ---------------------------------------------
// CURRENCY SYMBOLS
// ---------------------------------------------
$symbols = [
    'USD' => '$', 'EUR' => 'e', 'GBP' => '£', 'NGN' => '₦', 
    'CAD' => 'C$', 'AUD' => 'A$', 'KES' => 'KSh', 'ZAR' => 'R', 'GHS' => 'GH₵'
];

// ---------------------------------------------
// DEFAULT TO LOCAL CURRENCY / TOGGLE LOGIC
// ---------------------------------------------
if (isset($_GET['currency'])) {
    if (strtoupper($_GET['currency']) === 'USD') {
        $_SESSION['selected_currency'] = 'USD';
    } elseif (strtoupper($_GET['currency']) === 'LOCAL') {
        $_SESSION['selected_currency'] = $localCurrency;
    }
}

$displayCurrency = $_SESSION['selected_currency'] ?? $localCurrency;
$displayRate     = ($displayCurrency === 'USD') ? 1 : $localRate;
$displaySymbol   = $symbols[$displayCurrency] ?? '$';

$showCurrencyPrompt = (strtolower($user_country) !== 'united states');

// ---------------------------------------------
// CAPTURE FUNDING AMOUNT DYNAMICS
// ---------------------------------------------
$input_amount = isset($_REQUEST['amount']) ? max(0, (float)$_REQUEST['amount']) : 0;
$convertedTotal = $input_amount * $displayRate;

// ---------------------------------------------
// FETCH PAYMENT METHODS WHERE redirect_link = 'no'
// ---------------------------------------------
$stmt = $pdo->prepare("
    SELECT payment_id, image_path, error_msg, is_active, redirect
    FROM payment_methods 
    WHERE redirect = 'no'
    ORDER BY payment_id ASC
");
$stmt->execute();
$paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------
// SUPPORT CONTACTS
// ---------------------------------------------
$support = ['email' => '', 'telegram' => '', 'whatsapp' => ''];
$stmt = $pdo->query("SELECT email, telegram, whatsapp FROM admins LIMIT 1");
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if ($admin) {
    $support = $admin;
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include "../inc/head.php"; ?>
<body class="bg-gradient-to-br from-slate-100 via-white to-slate-200 min-h-screen">
<?php include "../inc/header.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showPaymentError(message){
    Swal.fire({
        icon:'warning',
        title:'Payment Method Unavailable',
        text:message,
        confirmButtonColor:'#2563eb'
    });
}

function checkAmountSelection(e, targetUrl) {
    const amt = document.getElementById('wallet_amount_input').value;
    if (!amt || parseFloat(amt) <= 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Amount Required',
            text: 'Please enter a valid amount to deposit.',
            confirmButtonColor: '#2563eb'
        });
        return false;
    }
    window.location.href = targetUrl + "&amount=" + encodeURIComponent(amt);
    return false;
}
</script>

<div class="max-w-7xl mx-auto px-5 py-10">

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 text-red-700 rounded-r-xl shadow-sm animate-pulse">
            <p class="font-bold">Account Alert</p>
            <p class="text-sm"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></p>
        </div>
    <?php endif; ?>

    <div class="rounded-3xl bg-gradient-to-r from-blue-700 via-indigo-700 to-slate-900 text-white p-8 shadow-2xl mb-8">
        <div class="flex flex-col lg:flex-row justify-between gap-8">
            <div>
                <h1 class="text-4xl font-black">Top Up Wallet</h1>
                <p class="text-blue-100 mt-2">Add funds to your account balance using any of our secure payment options below.</p>
                <div class="mt-6 inline-flex items-center bg-white/20 rounded-full px-5 py-2 text-sm">
                    Available Balance: <span class="font-bold font-mono ml-2"><?= $displaySymbol . number_format($current_wallet_balance * $displayRate, 2) . ' ' . $displayCurrency; ?></span>
                </div>
            </div>

            <div class="flex gap-4 items-start">
                <div class="relative group">
                    <button class="bg-white text-slate-900 font-bold px-6 py-3 rounded-xl shadow hover:shadow-lg transition">
                        <i class="fas fa-headset mr-2 text-blue-600"></i> Contact Support
                    </button>
                    <div class="hidden group-hover:block absolute right-0 mt-3 bg-white rounded-2xl shadow-xl w-60 overflow-hidden z-50">
                        <a href="https://wa.me/<?php echo urlencode($support['whatsapp']); ?>" target="_blank" class="flex items-center gap-3 px-5 py-4 hover:bg-green-50">
                            <i class="fab fa-whatsapp text-green-600"></i> WhatsApp
                        </a>
                        <a href="https://t.me/<?php echo urlencode($support['telegram']); ?>" target="_blank" class="flex items-center gap-3 px-5 py-4 hover:bg-sky-50">
                            <i class="fab fa-telegram-plane text-sky-600"></i> Telegram
                        </a>
                        <a href="mailto:<?php echo htmlspecialchars($support['email']); ?>" class="flex items-center gap-3 px-5 py-4 hover:bg-blue-50">
                            <i class="fas fa-envelope text-blue-600"></i> Email
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-12 gap-8">
        <div class="lg:col-span-7 space-y-6">

            <?php if($showCurrencyPrompt): ?>
            <div class="rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 p-6">
                <div class="flex justify-between items-center flex-wrap gap-4">
                    <div>
                        <h3 class="font-bold text-slate-900">
                            <?= ($displayCurrency === 'USD') ? 'Viewing prices in USD ($)' : 'Want to pay with USD instead?' ?>
                        </h3>
                        <p class="text-sm text-slate-500">Your currency is currently set to: <strong><?php echo $displayCurrency; ?></strong>.</p>
                    </div>
                    <?php if ($displayCurrency === 'USD'): ?>
                        <a href="?currency=local&amount=<?= $input_amount; ?>" class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-6 py-3 font-semibold transition">Switch to <?php echo $localCurrency; ?></a>
                    <?php else: ?>
                        <a href="?currency=USD&amount=<?= $input_amount; ?>" class="bg-orange-500 hover:bg-orange-600 text-white rounded-xl px-6 py-3 font-semibold transition">Switch to USD</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-3xl shadow-xl p-8">
                <h2 class="text-2xl font-black mb-2">1. Enter Deposit Amount</h2>
                <p class="text-slate-500 mb-6">Specify the amount of money you want to add to your wallet.</p>
                
                <div class="relative rounded-2xl shadow-sm max-w-md">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <span class="text-slate-400 font-bold text-lg"><?= $displaySymbol ?></span>
                    </div>
                    <input type="number" step="0.01" min="1" name="amount" id="wallet_amount_input" value="<?= $input_amount > 0 ? htmlspecialchars($input_amount) : '' ?>" class="w-full border border-slate-300 rounded-2xl pl-10 pr-16 py-4 font-bold text-slate-900 focus:outline-none focus:border-blue-600 text-lg" placeholder="0.00" oninput="document.getElementById('summary_amount_box').innerText = (this.value ? parseFloat(this.value).toFixed(2) : '0.00')">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                        <span class="text-sm font-bold text-slate-400 font-mono"><?= $displayCurrency ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-xl p-8">
                <h2 class="text-2xl font-black mb-2">2. Choose Payment Method</h2>
                <p class="text-slate-500 mb-8">Select your preferred payment gateway below to continue.</p>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                    <?php foreach($paymentMethods as $method): ?>
                        <?php if($method['is_active'] == 'yes'): ?>
                            <?php 
                            $targetUrl = "secure-payment-gateway?fund-wallet=1&payment_id=" . $method['payment_id'] . "&currency=" . urlencode($displayCurrency);
                            ?>
                            <a href="<?php echo htmlspecialchars($targetUrl); ?>" onclick="return checkAmountSelection(event, '<?php echo $targetUrl; ?>')" class="group rounded-2xl border border-slate-200 bg-white hover:border-blue-600 hover:shadow-xl transition p-6 flex flex-col justify-between items-center min-h-[140px]">
                                <img src="../uploads/payment-methods/<?php echo htmlspecialchars($method['image_path']); ?>" class="mx-auto h-16 object-contain group-hover:scale-105 transition">
                                <span class="text-xs uppercase tracking-wider text-blue-600 font-bold mt-2 opacity-0 group-hover:opacity-100 transition-all">Pay Now &rarr;</span>
                            </a>
                        <?php else: ?>
                            <button type="button" onclick="showPaymentError('<?php echo htmlspecialchars(addslashes($method['error_msg'])); ?>')" class="rounded-2xl border bg-gray-50 opacity-60 cursor-not-allowed p-6 min-h-[140px] w-full">
                                <img src="../uploads/payment-methods/<?php echo htmlspecialchars($method['image_path']); ?>" class="mx-auto h-16 object-contain">
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="lg:col-span-5">
            <div class="rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 text-white shadow-2xl p-8 sticky top-6">
                <h2 class="text-2xl font-black mb-6">Deposit Summary</h2>
                
                <div class="space-y-5">
                    <div class="flex justify-between border-b border-white/10 pb-4">
                        <div>
                            <div class="font-semibold">Deposit Amount</div>
                            <div class="text-sm text-white/70">Funds to add</div>
                        </div>
                        <div class="font-bold font-mono">
                            <?= $displaySymbol ?><span id="summary_amount_box"><?= number_format($convertedTotal, 2) ?></span>
                        </div>
                    </div>
                </div>

                <div class="border-t border-white/20 mt-8 pt-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-white/70">Total Balance to Credit</div>
                            <div class="text-xs text-amber-300 mt-1">* Subject to manual approval and verification</div>
                        </div>
                        <i class="fas fa-wallet text-5xl text-blue-400"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include "../inc/footer.php"; ?>
</body>
</html>
