<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

$message = '';
$error   = '';

/* --------------------------------------------------
   FETCH PAYMENT METHODS
-------------------------------------------------- */
try {
    $stmt = $pdo->query("SELECT * FROM payment_methods ORDER BY payment_id DESC");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $payments = [];
}

/* --------------------------------------------------
   HANDLE ACTIONS
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {

    $action = $_POST['action'] ?? '';

    try {

        /* ---------------- ADD PAYMENT METHOD ---------------- */
        if ($action === 'add') {

            $type          = $_POST['type'] ?? '';
            $error_msg     = trim($_POST['error_msg'] ?? '');
            $is_active     = $_POST['is_active'] ?? 'yes';
            $instruction   = trim($_POST['instruction'] ?? '');
            
            // New redirect configuration fields
            $redirect      = $_POST['redirect'] ?? 'no';
            $redirect_link = trim($_POST['redirect_link'] ?? '');

            if (!in_array($type, ['bank', 'gift_card', 'crypto', 'e_pay'])) {
                throw new Exception("Invalid payment type.");
            }
            
            if (!in_array($is_active, ['yes', 'no'])) {
                $is_active = 'yes';
            }

            if (!in_array($redirect, ['yes', 'no'])) {
                $redirect = 'no';
            }

            if ($redirect === 'yes' && empty($redirect_link)) {
                throw new Exception("Please provide a valid redirect link if redirect status is enabled.");
            }

            if (empty($_FILES['image']['name'])) {
                throw new Exception("Please upload an image.");
            }

            // Start Transaction to guarantee database alignment across multiple tables
            $pdo->beginTransaction();

            $uploadDir = __DIR__ . "/../uploads/payment-methods/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $imageName = time() . '_' . basename($_FILES['image']['name']);
            $target = $uploadDir . $imageName;

            move_uploaded_file($_FILES['image']['tmp_name'], $target);

            // 1. Insert into core table (including new redirect and redirect_link parameters)
            $stmt = $pdo->prepare("
                INSERT INTO payment_methods
                (image_path, error_msg, is_active, type, redirect, redirect_link)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $imageName,
                $error_msg,
                $is_active,
                $type,
                $redirect,
                !empty($redirect_link) ? $redirect_link : null
            ]);

            $new_payment_id = (int)$pdo->lastInsertId();

            // 2. Conditionally insert context entries into dynamic relational child tables
            if ($type === 'gift_card') {
                $card_name = trim($_POST['card_name'] ?? '');
                if (empty($card_name)) throw new Exception("Gift card asset name is required.");

                $stmt = $pdo->prepare("INSERT INTO pay_gift_card (payment_id, card_name, instruction) VALUES (?, ?, ?)");
                $stmt->execute([$new_payment_id, $card_name, $instruction]);

            } elseif ($type === 'bank') {
                $bank_name      = trim($_POST['bank_name'] ?? '');
                $account_name   = trim($_POST['account_name'] ?? '');
                $account_number = trim($_POST['account_number'] ?? '');
                
                if (empty($bank_name) || empty($account_name) || empty($account_number)) {
                    throw new Exception("All core bank routing parameters are required.");
                }

                $stmt = $pdo->prepare("INSERT INTO pay_bank (payment_id, bank_name, account_name, account_number, instruction) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$new_payment_id, $bank_name, $account_name, $account_number, $instruction]);

            } elseif ($type === 'crypto') {
                $coin    = trim($_POST['coin'] ?? '');
                $chain   = trim($_POST['chain'] ?? '');
                $address = trim($_POST['address'] ?? '');

                if (empty($coin) || empty($chain) || empty($address)) {
                    throw new Exception("All cryptographic asset details are required.");
                }

                $stmt = $pdo->prepare("INSERT INTO pay_crypto (payment_id, coin, chain, address, instruction) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$new_payment_id, $coin, $chain, $address, $instruction]);

            } elseif ($type === 'e_pay') {
                $method_name = trim($_POST['method_name'] ?? '');
                $email_phone = trim($_POST['email_phone'] ?? '');

                if (empty($method_name) || empty($email_phone)) {
                    throw new Exception("E-Pay configuration alias attributes are required.");
                }

                $stmt = $pdo->prepare("INSERT INTO pay_e_pay (payment_id, method_name, email_phone, instruction) VALUES (?, ?, ?, ?)");
                $stmt->execute([$new_payment_id, $method_name, $email_phone, $instruction]);
            }

            $pdo->commit();
            $_SESSION['success'] = "Payment method and its type settings configuration saved.";

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        /* ---------------- DELETE PAYMENT METHOD ---------------- */
        if ($action === 'delete') {

            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception("Invalid payment ID.");
            }

            $pdo->beginTransaction();

            // Drop historical dependency entries across all operational transaction channels
            $pdo->prepare("DELETE FROM pay_gift_card WHERE payment_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM pay_bank WHERE payment_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM pay_crypto WHERE payment_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM pay_e_pay WHERE payment_id = ?")->execute([$id]);

            // Clear parent record entry row profile
            $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE payment_id=?");
            $stmt->execute([$id]);

            $pdo->commit();
            $_SESSION['success'] = "Payment method and associated metadata profiles purged successfully.";

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

?>

<main>

<h1 style="text-align:center;margin:2rem 0;">Manage Payment Methods</h1>

<?php if (!empty($_SESSION['success'])): ?>
<div style="background:#238636;color:#fff;padding:1rem;border-radius:8px;text-align:center;max-width:900px;margin:1rem auto;">
    <?= htmlspecialchars($_SESSION['success']) ?>
</div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
<div style="background:#f85149;color:#fff;padding:1rem;border-radius:8px;text-align:center;max-width:900px;margin:1rem auto;">
    <?= htmlspecialchars($_SESSION['error']) ?>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div style="text-align:center;margin-bottom:2rem;">
    <button onclick="openModal()" class="btn">
        + Add Payment Method
    </button>
</div>

<div style="max-width:1100px;margin:0 auto;overflow-x:auto;padding:0 10px;">

<table style="width:100%;border-collapse:collapse;background:var(--card);border:1px solid var(--border);border-radius:10px;min-width:850px;">

<thead>
<tr style="background:#111827;text-align:left;">
    <th style="padding:12px;">Image</th>
    <th style="padding:12px;">Type</th>
    <th style="padding:12px;">Redirect Link</th>
    <th style="padding:12px;">Error Message</th>
    <th style="padding:12px;">Status</th>
    <th style="padding:12px;">Actions</th>
</tr>
</thead>

<tbody>

<?php foreach($payments as $payment): ?>

<tr style="border-top:1px solid var(--border);">

<td style="padding:12px;">
    <img src="../uploads/payment-methods/<?= htmlspecialchars($payment['image_path']) ?>"
         style="width:70px;height:50px;object-fit:contain;border-radius:6px;">
</td>

<td style="padding:12px;">
   <?= match ($payment['type']) {
       'gift_card' => 'Gift Card',
       'bank' => 'Bank',
       'e_pay' => 'E-Pay',
       'crypto' => 'Crypto',
       default => ucfirst(htmlspecialchars($payment['type']))
   } ?>
</td>

<td style="padding:12px; font-size: 14px;">
    <?php if(($payment['redirect'] ?? 'no') === 'yes'): ?>
        <span style="color:var(--text-muted); font-family:monospace; display:block; max-width:200px; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($payment['redirect_link'] ?? '') ?>">
            <?= htmlspecialchars($payment['redirect_link'] ?? 'No URL saved') ?>
        </span>
    <?php else: ?>
        <span style="color:#58a6ff;">Native Gateway</span>
    <?php endif; ?>
</td>

<td style="padding:12px;max-width:250px;">
    <?= htmlspecialchars(mb_strimwidth($payment['error_msg'],0,60,'...')) ?>
</td>

<td style="padding:12px;">

<?php if($payment['is_active']=='yes'): ?>

<span style="background:#238636;padding:5px 10px;border-radius:20px;color:#fff;">
Active
</span>

<?php else: ?>

<span style="background:#dc3545;padding:5px 10px;border-radius:20px;color:#fff;">
Inactive
</span>

<?php endif; ?>

</td>

<td style="padding:12px;white-space:nowrap;">

<a href="edit-payment-method.php?id=<?= $payment['payment_id'] ?>"
   class="btn green"
   style="padding:6px 10px;font-size:13px;">
    Edit
</a>

<form method="POST"
      style="display:inline-block;margin-left:5px;"
      onsubmit="return confirm('Delete this payment method along with its dynamic type configurations?');">

<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= $payment['payment_id'] ?>">

<button class="btn red"
        style="padding:6px 10px;font-size:13px;">
Delete
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<div id="paymentModal"
style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
background:rgba(0,0,0,.7);overflow-y:auto;padding:20px;z-index:9999;">

<div style="
background:#0d1117;
color:#f0f6fc;
max-width:550px;
margin:20px auto;
padding:2rem;
border-radius:10px;
box-shadow:0 10px 25px rgba(0,0,0,0.5);
">

<h2 style="margin-top:0;">Add Payment Method</h2>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="action" value="add">

<label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Type Mapping Selector</label>
<select name="type" id="type_selector" onchange="toggleFormFields()"
style="width:100%;padding:.7rem;margin-bottom:1rem;background:#161b22;color:#fff;border:1px solid #30363d;border-radius:6px;">
    <option value="bank">Bank Wire Transfer</option>
    <option value="gift_card">Gift Card Voucher</option>
    <option value="crypto">Cryptocurrency Asset Address</option>
    <option value="e_pay">E-Pay Client Wallet Profile</option>
</select>

<div id="dynamic_wrapper" style="background:#161b22; padding:15px; border-radius:8px; margin-bottom:1rem; border:1px solid #30363d;">
    
    <div id="fields_bank" class="type-fields">
        <label style="display:block;margin-bottom:0.3rem;">Target Bank Name</label>
        <input type="text" name="bank_name" style="width:100%;padding:.5rem;margin-bottom:0.8rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="e.g. Chase Bank">
        
        <label style="display:block;margin-bottom:0.3rem;">Account Holder Name</label>
        <input type="text" name="account_name" style="width:100%;padding:.5rem;margin-bottom:0.8rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="e.g. John Doe Holdings">
        
        <label style="display:block;margin-bottom:0.3rem;">Account Number</label>
        <input type="text" name="account_number" style="width:100%;padding:.5rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="e.g. 123456789012">
    </div>

    <div id="fields_gift_card" class="type-fields" style="display:none;">
        <label style="display:block;margin-bottom:0.3rem;">Gift Card Name/Brand</label>
        <input type="text" name="card_name" style="width:100%;padding:.5rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="e.g. Apple Store Gift Card">
    </div>

    <div id="fields_crypto" class="type-fields" style="display:none;">
        <label style="display:block;margin-bottom:0.3rem;">Coin Ticker Asset</label>
        <input type="text" name="coin" style="width:100%;padding:.5rem;margin-bottom:0.8rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="e.g. Bitcoin (BTC) or USDT">
        
        <label style="display:block;margin-bottom:0.3rem;">Network Blockchain Specification</label>
        <input type="text" name="chain" style="width:100%;padding:.5rem;margin-bottom:0.8rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="e.g. ERC-20, TRC-20, Native">
        
        <label style="display:block;margin-bottom:0.3rem;">Public Destination Wallet Address</label>
        <input type="text" name="address" style="width:100%;padding:.5rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="e.g. 1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa">
    </div>

    <div id="fields_e_pay" class="type-fields" style="display:none;">
        <label style="display:block;margin-bottom:0.3rem;">Platform System Name</label>
        <input type="text" name="method_name" style="width:100%;padding:.5rem;margin-bottom:0.8rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="e.g. PayPal, Venmo, CashApp">
        
        <label style="display:block;margin-bottom:0.3rem;">Merchant Access Alias (Email / Phone)</label>
        <input type="text" name="email_phone" style="width:100%;padding:.5rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="merchant@epay-link.com">
    </div>
</div>

<div style="background:#161b22; padding:15px; border-radius:8px; margin-bottom:1rem; border:1px solid #30363d;">
    <label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Gateway Action Routing</label>
    <select name="redirect" id="redirect_selector" onchange="toggleRedirectLinkField()"
    style="width:100%;padding:.7rem;margin-bottom:1rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:6px;">
        <option value="no">Open Internal Checkout Flow (Native)</option>
        <option value="yes">Redirect User to External URL</option>
    </select>

    <div id="redirect_url_wrapper" style="display:none;">
        <label style="display:block;margin-bottom:0.3rem;">Target Destination Link Url</label>
        <input type="url" name="redirect_link" placeholder="https://external-payment-processor.com/pay" 
        style="width:100%;padding:.5rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;">
    </div>
</div>

<label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Gateway User Payment Instructions</label>
<textarea name="instruction" style="width:100%;padding:.7rem;margin-bottom:1rem;background:#161b22;color:#fff;border:1px solid #30363d;border-radius:6px;" rows="3" placeholder="Provide instructions on how to make payments..."></textarea>

<label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Fallback Alternative Error Message</label>
<textarea name="error_msg" style="width:100%;padding:.7rem;margin-bottom:1rem;background:#161b22;color:#fff;border:1px solid #30363d;border-radius:6px;" rows="2" placeholder="Displayed if this payment method is set to inactive..."></textarea>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:1rem;">
    <div>
        <label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Status Configuration</label>
        <select name="is_active" style="width:100%;padding:.7rem;background:#161b22;color:#fff;border:1px solid #30363d;border-radius:6px;">
            <option value="yes">Active</option>
            <option value="no">Inactive</option>
        </select>
    </div>
    <div>
        <label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Logo Graphic Upload</label>
        <input type="file" name="image" required style="width:100%; padding-top: 8px;">
    </div>
</div>

<button class="btn" style="width:100%; padding: 0.8rem; font-weight: bold; font-size:15px; margin-top:5px;">
    Save Payment Profile
</button>

</form>

<button onclick="closeModal()" class="btn red" style="width:100%; margin-top:10px; padding: 0.6rem;">
    Close
</button>

</div>

</div>

<script>

function openModal(){
    document.getElementById('paymentModal').style.display='block';
    toggleFormFields(); 
    toggleRedirectLinkField(); // Establish redirect configuration visibility
}

function closeModal(){
    document.getElementById('paymentModal').style.display='none';
}

function toggleFormFields(){
    const selectedType = document.getElementById('type_selector').value;
    
    // Hide all dynamic elements
    const elements = document.getElementsByClassName('type-fields');
    for (let i = 0; i < elements.length; i++) {
        elements[i].style.display = 'none';
    }
    
    // Display targeted entry elements container 
    const targetedElement = document.getElementById('fields_' + selectedType);
    if(targetedElement) {
        targetedElement.style.display = 'block';
    }
}

function toggleRedirectLinkField() {
    const redirectSelector = document.getElementById('redirect_selector');
    const redirectWrapper = document.getElementById('redirect_url_wrapper');
    
    if (redirectSelector && redirectWrapper) {
        if (redirectSelector.value === 'yes') {
            redirectWrapper.style.display = 'block';
            redirectWrapper.querySelector('input').setAttribute('required', 'required');
        } else {
            redirectWrapper.style.display = 'none';
            redirectWrapper.querySelector('input').removeAttribute('required');
        }
    }
}

</script>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
