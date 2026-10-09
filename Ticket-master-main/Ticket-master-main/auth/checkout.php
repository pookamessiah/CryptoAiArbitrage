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
// ERROR REPORTING (DEV ONLY)
// ---------------------------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ---------------------------------------------
// GET ORDER IDS
// ---------------------------------------------
if (
    !isset($_SESSION['checkout_order_ids']) ||
    !is_array($_SESSION['checkout_order_ids']) ||
    empty($_SESSION['checkout_order_ids'])
) {
    die("Invalid order reference.");
}

$order_ids = array_map('intval', $_SESSION['checkout_order_ids']);

// ---------------------------------------------
// DATABASE
// ---------------------------------------------
try {

    $pdo = (new Database())->connect();

} catch (Exception $e) {

    die("Database connection failed.");

}

// ---------------------------------------------
// FETCH ORDERS
// ---------------------------------------------
$placeholders = implode(',', array_fill(0, count($order_ids), '?'));

$stmt = $pdo->prepare("
    SELECT
        o.*,
        t.ticket_name,
        t.price,
        c.title
    FROM orders o
    INNER JOIN tickets t ON o.ticket_id=t.ticket_id
    INNER JOIN concerts c ON t.concert_id=c.concert_id
    WHERE o.order_id IN ($placeholders)
");

$stmt->execute($order_ids);

$all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$all_orders) {
    die("Order not found.");
}

// ---------------------------------------------
// SECURITY CHECK
// ---------------------------------------------
foreach ($all_orders as $order) {

    if ((int)$order['user_id'] !== $user_id) {
        die("Unauthorized access.");
    }

}

$order_items = $all_orders;

// ---------------------------------------------
// USER INFORMATION
// ---------------------------------------------
$stmt = $pdo->prepare("
SELECT
    country
FROM users
WHERE id=?
LIMIT 1
");

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_country = trim($user['country'] ?? '');

// ---------------------------------------------
// REGION SETTINGS
// ---------------------------------------------
$stmt = $pdo->prepare("
SELECT
    currency,
    exchange_rates
FROM region_settings
WHERE country=?
LIMIT 1
");

$stmt->execute([$user_country]);

$region = $stmt->fetch(PDO::FETCH_ASSOC);

$localCurrency = $region['currency'] ?? 'USD';
$localRate     = (float)($region['exchange_rates'] ?? 1);

// ---------------------------------------------
// CURRENCY SYMBOLS
// ---------------------------------------------
$symbols = [

    'USD' => '$',
    'EUR' => '€',
    'GBP' => '£',
    'NGN' => '₦',
    'CAD' => 'C$',
    'AUD' => 'A$',
    'KES' => 'KSh',
    'ZAR' => 'R',
    'GHS' => 'GH₵'

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

if ($displayCurrency === 'USD') {
    $displayRate = 1;
} else {
    $displayCurrency = $localCurrency;
    $displayRate     = $localRate;
}

$displaySymbol = $symbols[$displayCurrency] ?? '$';

// ---------------------------------------------
// SHOW CONVERSION PROMPT?
// Hide for United States users.
// ---------------------------------------------
$showCurrencyPrompt =
    strtolower($user_country) !== 'united states';

// ---------------------------------------------
// PAYMENT METHODS (UPDATED WITH REDIRECT COLUMNS)
// ---------------------------------------------
$stmt = $pdo->query("
SELECT
    payment_id,
    image_path,
    error_msg,
    is_active,
    redirect,
    redirect_link
FROM payment_methods
ORDER BY payment_id ASC
");

$paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------
// SUPPORT CONTACTS
// ---------------------------------------------
$support = [

    'email' => '',
    'telegram' => '',
    'whatsapp' => ''

];

$stmt = $pdo->query("
SELECT
    email,
    telegram,
    whatsapp
FROM admins
LIMIT 1
");

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {

    $support = $admin;

}

// ---------------------------------------------
// TOTAL
// ---------------------------------------------
$total_amount = 0;

$order_title = $order_items[0]['title'] ?? 'Order';

foreach ($order_items as $item) {

    $total_amount += (float)$item['price'];

}

$convertedTotal = $total_amount * $displayRate;
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
</script>

<div class="max-w-7xl mx-auto px-5 py-10">

    <div class="rounded-3xl bg-gradient-to-r from-blue-700 via-indigo-700 to-slate-900 text-white p-8 shadow-2xl mb-8">

        <div class="flex flex-col lg:flex-row justify-between gap-8">

            <div>

                <h1 class="text-4xl font-black">
                    Secure Checkout
                </h1>

                <p class="text-blue-100 mt-2">
                    Complete your payment securely using your preferred payment method.
                </p>

                <div class="mt-6 inline-flex items-center bg-white/20 rounded-full px-5 py-2 text-sm">

                    Batch Reference:
                    <span class="font-mono ml-2">
                        #TM-<?php echo implode('-', $order_ids); ?>
                    </span>

                </div>

            </div>

            <div class="flex gap-4 items-start">

                <div class="relative group">

                    <button class="bg-white text-slate-900 font-bold px-6 py-3 rounded-xl shadow hover:shadow-lg transition">

                        <i class="fas fa-headset mr-2 text-blue-600"></i>

                        Contact Support

                    </button>

                    <div class="hidden group-hover:block absolute right-0 mt-3 bg-white rounded-2xl shadow-xl w-60 overflow-hidden z-50">

                        <a href="https://wa.me/<?php echo urlencode($support['whatsapp']); ?>" target="_blank"
                           class="flex items-center gap-3 px-5 py-4 hover:bg-green-50">

                            <i class="fab fa-whatsapp text-green-600"></i>

                            WhatsApp

                        </a>

                        <a href="https://t.me/<?php echo urlencode($support['telegram']); ?>" target="_blank"
                           class="flex items-center gap-3 px-5 py-4 hover:bg-sky-50">

                            <i class="fab fa-telegram-plane text-sky-600"></i>

                            Telegram

                        </a>

                        <a href="mailto:<?php echo htmlspecialchars($support['email']); ?>"
                           class="flex items-center gap-3 px-5 py-4 hover:bg-blue-50">

                            <i class="fas fa-envelope text-blue-600"></i>

                            Email

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
                            <?php if ($displayCurrency === 'USD'): ?>
                                Viewing prices in USD ($)
                            <?php else: ?>
                                Want to pay with USD instead?
                            <?php endif; ?>
                        </h3>
            
                        <p class="text-sm text-slate-500">
                            Your prices are currently displayed in <strong><?php echo $displayCurrency; ?></strong>.
                        </p>
                    </div>
            
                    <?php if ($displayCurrency === 'USD'): ?>
                        <a href="?currency=local"
                           class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-6 py-3 font-semibold transition">
                            Switch to <?php echo $localCurrency; ?>
                        </a>
                    <?php else: ?>
                        <a href="?currency=USD"
                           class="bg-orange-500 hover:bg-orange-600 text-white rounded-xl px-6 py-3 font-semibold transition">
                            Switch to USD
                        </a>
                    <?php endif; ?>
            
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl p-6 shadow">

                <div class="grid md:grid-cols-3 gap-5 text-center">

                    <div>

                        <i class="fas fa-lock text-3xl text-green-500 mb-3"></i>

                        <div class="font-bold">
                            SSL Encrypted
                        </div>

                    </div>

                    <div>

                        <i class="fas fa-shield-alt text-3xl text-blue-500 mb-3"></i>

                        <div class="font-bold">
                            Secure Payments
                        </div>

                    </div>

                    <div>

                        <i class="fas fa-bolt text-3xl text-yellow-500 mb-3"></i>

                        <div class="font-bold">
                            Instant Processing
                        </div>

                    </div>

                </div>

            </div>

            <div class="bg-white rounded-3xl shadow-xl p-8">

                <h2 class="text-2xl font-black mb-2">

                    Choose Payment Method

                </h2>

                <p class="text-slate-500 mb-8">

                    Click your preferred payment method below.

                </p>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-6">

                    <?php foreach($paymentMethods as $method): ?>

                        <?php if($method['is_active']=='yes'): ?>

                            <?php 
                            // Determine target URL based on the redirect column rules
                            if ($method['redirect'] === 'yes' && !empty($method['redirect_link'])) {
                                $targetUrl = $method['redirect_link'];
                            } else {
                                $targetUrl = "secure-payment-gateway?payment_id=" . $method['payment_id'] . "&currency=" . urlencode($displayCurrency);
                            }
                            ?>

                            <a href="<?php echo htmlspecialchars($targetUrl); ?>"
                               class="group rounded-2xl border border-slate-200 bg-white hover:border-blue-600 hover:shadow-xl transition p-6">

                                <img
                                    src="../uploads/payment-methods/<?php echo htmlspecialchars($method['image_path']); ?>"
                                    class="mx-auto h-20 object-contain group-hover:scale-105 transition">

                            </a>

                        <?php else: ?>

                            <button
                                type="button"
                                onclick="showPaymentError('<?php echo htmlspecialchars(addslashes($method['error_msg'])); ?>')"
                                class="rounded-2xl border bg-gray-50 opacity-60 cursor-not-allowed p-6">

                                <img
                                    src="../uploads/payment-methods/<?php echo htmlspecialchars($method['image_path']); ?>"
                                    class="mx-auto h-20 object-contain">

                            </button>

                        <?php endif; ?>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

        <div class="lg:col-span-5">

            <div class="rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 text-white shadow-2xl p-8 sticky top-6">

                <h2 class="text-2xl font-black mb-6">

                    Order Summary

                </h2>

                <div class="space-y-5">

                    <?php foreach($order_items as $item): ?>

                        <div class="flex justify-between border-b border-white/10 pb-4">

                            <div>

                                <div class="font-semibold">

                                    <?php echo htmlspecialchars($item['title']); ?>

                                </div>

                                <div class="text-sm text-white/70">

                                    <?php echo htmlspecialchars($item['ticket_name']); ?>

                                </div>

                            </div>

                            <div class="font-bold">

                                <?php
                                echo $displaySymbol .
                                number_format(
                                    $item['price'] * $displayRate,
                                    2
                                );
                                ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="border-t border-white/20 mt-8 pt-6">

                    <div class="flex justify-between items-center">

                        <div>

                            <div class="text-white/70">

                                Total Amount

                            </div>

                            <div class="text-4xl font-black mt-2">

                                <?php
                                echo $displaySymbol .
                                number_format($convertedTotal,2);
                                ?>

                            </div>

                        </div>

                        <i class="fas fa-check-circle text-5xl text-green-400"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include "../inc/footer.php"; ?>

</body>
</html>
