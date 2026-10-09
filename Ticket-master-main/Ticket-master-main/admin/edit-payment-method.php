<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

$payment = null;
$typeDetails = [];
$instruction = '';

/* --------------------------------------------------
     GET PAYMENT METHOD ID
-------------------------------------------------- */
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "Invalid payment method ID.";
    header("Location: manage-payment-methods.php");
    exit;
}

/* --------------------------------------------------
     FETCH PAYMENT METHOD & DYNAMIC DETAILS
-------------------------------------------------- */
try {

    $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE payment_id = ?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        $_SESSION['error'] = "Payment method not found.";
        header("Location: manage-payment-methods.php");
        exit;
    }

    // Pull current type details based on active gateway assignment
    if ($payment['type'] === 'gift_card') {
        $subStmt = $pdo->prepare("SELECT * FROM pay_gift_card WHERE payment_id = ? LIMIT 1");
        $subStmt->execute([$id]);
        $typeDetails = $subStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } elseif ($payment['type'] === 'bank') {
        $subStmt = $pdo->prepare("SELECT * FROM pay_bank WHERE payment_id = ? LIMIT 1");
        $subStmt->execute([$id]);
        $typeDetails = $subStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } elseif ($payment['type'] === 'crypto') {
        $subStmt = $pdo->prepare("SELECT * FROM pay_crypto WHERE payment_id = ? LIMIT 1");
        $subStmt->execute([$id]);
        $typeDetails = $subStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } elseif ($payment['type'] === 'e_pay') {
        $subStmt = $pdo->prepare("SELECT * FROM pay_e_pay WHERE payment_id = ? LIMIT 1");
        $subStmt->execute([$id]);
        $typeDetails = $subStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $instruction = $typeDetails['instruction'] ?? '';

} catch (PDOException $e) {
    $_SESSION['error'] = "Database lookup failed: " . $e->getMessage();
    header("Location: manage-payment-methods.php");
    exit;
}

/* --------------------------------------------------
     HANDLE UPDATE
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $oldType     = $payment['type'];
        $newType     = $_POST['type'] ?? '';
        $error_msg   = trim($_POST['error_msg'] ?? '');
        $is_active   = $_POST['is_active'] ?? 'yes';
        $instruction = trim($_POST['instruction'] ?? '');
        
        // Dynamic routing attributes
        $redirect      = $_POST['redirect'] ?? 'no';
        $redirect_link = trim($_POST['redirect_link'] ?? '');

        if (!in_array($newType, ['bank', 'gift_card', 'crypto', 'e_pay'])) {
            throw new Exception("Invalid payment type choice.");
        }

        if (!in_array($is_active, ['yes', 'no'])) {
            $is_active = 'yes';
        }

        if (!in_array($redirect, ['yes', 'no'])) {
            $redirect = 'no';
        }

        if ($redirect === 'yes' && empty($redirect_link)) {
            throw new Exception("Please provide a valid destination URL if external redirection behavior is enabled.");
        }

        $imageName = $payment['image_path'];

        /* IMAGE UPLOAD processing */
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . "/../uploads/payment-methods/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $imageName = time() . '_' . basename($_FILES['image']['name']);
            $target = $uploadDir . $imageName;
            move_uploaded_file($_FILES['image']['tmp_name'], $target);
        }

        // Start transaction for compound write actions
        $pdo->beginTransaction();

        /* 1. UPDATE CORE METHOD PROPERTIES (Including routing configurations) */
        $stmt = $pdo->prepare("
            UPDATE payment_methods
            SET image_path = ?, error_msg = ?, is_active = ?, type = ?, redirect = ?, redirect_link = ?
            WHERE payment_id = ?
        ");
        $stmt->execute([
            $imageName, 
            $error_msg, 
            $is_active, 
            $newType, 
            $redirect, 
            ($redirect === 'yes' ? $redirect_link : null), 
            $id
        ]);

        /* 2. SYNC CHILD ENTRY BLOCKS */
        // Clear conflicting parameters if structural shifting occurred
        if ($oldType !== $newType) {
            $pdo->prepare("DELETE FROM pay_gift_card WHERE payment_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM pay_bank WHERE payment_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM pay_crypto WHERE payment_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM pay_e_pay WHERE payment_id = ?")->execute([$id]);
        }

        if ($newType === 'gift_card') {
            $card_name = trim($_POST['card_name'] ?? '');
            if (empty($card_name)) throw new Exception("Voucher identity title value is required.");

            $check = $pdo->prepare("SELECT id FROM pay_gift_card WHERE payment_id = ?");
            $check->execute([$id]);
            
            if ($check->fetch()) {
                $stmt = $pdo->prepare("UPDATE pay_gift_card SET card_name = ?, instruction = ? WHERE payment_id = ?");
                $stmt->execute([$card_name, $instruction, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO pay_gift_card (payment_id, card_name, instruction) VALUES (?, ?, ?)");
                $stmt->execute([$id, $card_name, $instruction]);
            }

        } elseif ($newType === 'bank') {
            $bank_name      = trim($_POST['bank_name'] ?? '');
            $account_name   = trim($_POST['account_name'] ?? '');
            $account_number = trim($_POST['account_number'] ?? '');

            if (empty($bank_name) || empty($account_name) || empty($account_number)) {
                throw new Exception("All core bank wire details are required.");
            }

            $check = $pdo->prepare("SELECT id FROM pay_bank WHERE payment_id = ?");
            $check->execute([$id]);

            if ($check->fetch()) {
                $stmt = $pdo->prepare("UPDATE pay_bank SET bank_name = ?, account_name = ?, account_number = ?, instruction = ? WHERE payment_id = ?");
                $stmt->execute([$bank_name, $account_name, $account_number, $instruction, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO pay_bank (payment_id, bank_name, account_name, account_number, instruction) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id, $bank_name, $account_name, $account_number, $instruction]);
            }

        } elseif ($newType === 'crypto') {
            $coin    = trim($_POST['coin'] ?? '');
            $chain   = trim($_POST['chain'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($coin) || empty($chain) || empty($address)) {
                throw new Exception("All crypto public data keys are required.");
            }

            $check = $pdo->prepare("SELECT id FROM pay_crypto WHERE payment_id = ?");
            $check->execute([$id]);

            if ($check->fetch()) {
                $stmt = $pdo->prepare("UPDATE pay_crypto SET coin = ?, chain = ?, address = ?, instruction = ? WHERE payment_id = ?");
                $stmt->execute([$coin, $chain, $address, $instruction, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO pay_crypto (payment_id, coin, chain, address, instruction) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id, $coin, $chain, $address, $instruction]);
            }

        } elseif ($newType === 'e_pay') {
            $method_name = trim($_POST['method_name'] ?? '');
            $email_phone = trim($_POST['email_phone'] ?? '');

            if (empty($method_name) || empty($email_phone)) {
                throw new Exception("All dynamic electronic gateway configurations require identifiers.");
            }

            $check = $pdo->prepare("SELECT id FROM pay_e_pay WHERE payment_id = ?");
            $check->execute([$id]);

            if ($check->fetch()) {
                $stmt = $pdo->prepare("UPDATE pay_e_pay SET method_name = ?, email_phone = ?, instruction = ? WHERE payment_id = ?");
                $stmt->execute([$method_name, $email_phone, $instruction, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO pay_e_pay (payment_id, method_name, email_phone, instruction) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id, $method_name, $email_phone, $instruction]);
            }
        }

        $pdo->commit();
        $_SESSION['success'] = "Payment configurations written successfully.";
        header("Location: manage-payment-methods.php");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
        header("Location: edit-payment-method.php?id=" . $id);
        exit;
    }
}
?>

<main style="max-width:700px;margin:2rem auto;padding:0 15px;">

<h1 style="text-align:center;margin-bottom:2rem;">Edit Payment Method</h1>

<?php if (!empty($_SESSION['error'])): ?>
<div style="background:#f85149;color:#fff;padding:1rem;border-radius:8px;margin-bottom:1rem;">
    <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<?php if (!empty($_SESSION['success'])): ?>
<div style="background:#238636;color:#fff;padding:1rem;border-radius:8px;margin-bottom:1rem;">
    <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" style="background:var(--card);color:#f0f6fc;padding:2rem;border-radius:10px;border:1px solid var(--border);">

    <label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Payment Type</label>
    <select name="type" id="edit_type_selector" required onchange="toggleEditFields()"
            style="width:100%;padding:.7rem;margin-bottom:1rem;background:#161b22;color:#fff;border:1px solid #30363d;border-radius:6px;">
        <option value="bank" <?= $payment['type']=='bank' ? 'selected' : '' ?>>Bank Wire Transfer</option>
        <option value="gift_card" <?= $payment['type']=='gift_card' ? 'selected' : '' ?>>Gift Card Voucher</option>
        <option value="crypto" <?= $payment['type']=='crypto' ? 'selected' : '' ?>>Cryptocurrency Asset Address</option>
        <option value="e_pay" <?= $payment['type']=='e_pay' ? 'selected' : '' ?>>E-Pay Client Wallet Profile</option>
    </select>

    <div id="edit_dynamic_wrapper" style="background:#161b22; padding:20px; border-radius:8px; margin-bottom:1rem; border:1px solid #30363d;">
        
        <div id="fields_bank" class="type-fields" style="display:none;">
            <label style="display:block;margin-bottom:0.3rem;">Target Bank Name</label>
            <input type="text" name="bank_name" value="<?= htmlspecialchars($typeDetails['bank_name'] ?? '') ?>" style="width:100%;padding:.5rem;margin-bottom:0.8rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;">
            
            <label style="display:block;margin-bottom:0.3rem;">Account Holder Name</label>
            <input type="text" name="account_name" value="<?= htmlspecialchars($typeDetails['account_name'] ?? '') ?>" style="width:100%;padding:.5rem;margin-bottom:0.8rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;">
            
            <label style="display:block;margin-bottom:0.3rem;">Account Number Reference</label>
            <input type="text" name="account_number" value="<?= htmlspecialchars($typeDetails['account_number'] ?? '') ?>" style="width:100%;padding:.5rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;">
        </div>

        <div id="fields_gift_card" class="type-fields" style="display:none;">
            <label style="display:block;margin-bottom:0.3rem;">Gift Card Name/Brand</label>
            <input type="text" name="card_name" value="<?= htmlspecialchars($typeDetails['card_name'] ?? '') ?>" style="width:100%;padding:.5rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="e.g. Amazon Gift Card">
        </div>

        <div id="fields_crypto" class="type-fields" style="display:none;">
            <label style="display:block;margin-bottom:0.3rem;">Coin Asset Designation</label>
            <input type="text" name="coin" value="<?= htmlspecialchars($typeDetails['coin'] ?? '') ?>" style="width:100%;padding:.5rem;margin-bottom:0.8rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="e.g. Ethereum (ETH)">
            
            <label style="display:block;margin-bottom:0.3rem;">Network Blockchain Map</label>
            <input type="text" name="chain" value="<?= htmlspecialchars($typeDetails['chain'] ?? '') ?>" style="width:100%;padding:.5rem;margin-bottom:0.8rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="e.g. ERC-20">
            
            <label style="display:block;margin-bottom:0.3rem;">Destination Public Wallet Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($typeDetails['address'] ?? '') ?>" style="width:100%;padding:.5rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;">
        </div>

        <div id="fields_e_pay" class="type-fields" style="display:none;">
            <label style="display:block;margin-bottom:0.3rem;">Platform System Title</label>
            <input type="text" name="method_name" value="<?= htmlspecialchars($typeDetails['method_name'] ?? '') ?>" style="width:100%;padding:.5rem;margin-bottom:0.8rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;" placeholder="e.g. Skrill">
            
            <label style="display:block;margin-bottom:0.3rem;">Merchant Receiving ID Alias (Email/Phone)</label>
            <input type="text" name="email_phone" value="<?= htmlspecialchars($typeDetails['email_phone'] ?? '') ?>" style="width:100%;padding:.5rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;">
        </div>

    </div>

    <div style="background:#161b22; padding:20px; border-radius:8px; margin-bottom:1rem; border:1px solid #30363d;">
        <label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Gateway Action Routing</label>
        <select name="redirect" id="edit_redirect_selector" onchange="toggleEditRedirectLinkField()"
                style="width:100%;padding:.7rem;margin-bottom:1rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:6px;">
            <option value="no" <?= ($payment['redirect'] ?? 'no') === 'no' ? 'selected' : '' ?>>Open Internal Checkout Flow (Native)</option>
            <option value="yes" <?= ($payment['redirect'] ?? 'no') === 'yes' ? 'selected' : '' ?>>Redirect User to External URL</option>
        </select>

        <div id="edit_redirect_url_wrapper" style="display:none;">
            <label style="display:block;margin-bottom:0.3rem;">Target Destination Link URL</label>
            <input type="url" name="redirect_link" id="edit_redirect_link_input"
                   value="<?= htmlspecialchars($payment['redirect_link'] ?? '') ?>" 
                   placeholder="https://external-payment-processor.com/pay" 
                   style="width:100%;padding:.5rem;background:#0d1117;color:#fff;border:1px solid #30363d;border-radius:4px;">
        </div>
    </div>

    <label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Gateway Directives & Instructions</label>
    <textarea name="instruction" style="width:100%;padding:.7rem;margin-bottom:1rem;background:#161b22;color:#fff;border:1px solid #30363d;border-radius:6px;height:100px;resize:vertical;"><?= htmlspecialchars($instruction) ?></textarea>

    <label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Fallback Inactive Error Message</label>
    <textarea name="error_msg" style="width:100%;padding:.7rem;margin-bottom:1rem;background:#161b22;color:#fff;border:1px solid #30363d;border-radius:6px;height:100px;resize:vertical;"><?= htmlspecialchars($payment['error_msg']) ?></textarea>

    <label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Status Configuration</label>
    <select name="is_active" style="width:100%;padding:.7rem;margin-bottom:1.5rem;background:#161b22;color:#fff;border:1px solid #30363d;border-radius:6px;">
        <option value="yes" <?= $payment['is_active']=='yes' ? 'selected' : '' ?>>Active</option>
        <option value="no" <?= $payment['is_active']=='no' ? 'selected' : '' ?>>Inactive</option>
    </select>

    <label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Current Image Media</label>
    <div style="margin:10px 0 20px;">
        <?php if (!empty($payment['image_path'])): ?>
            <img src="../uploads/payment-methods/<?= htmlspecialchars($payment['image_path']) ?>"
                 style="max-width:220px;max-height:120px;object-fit:contain;border-radius:8px;border:1px solid #30363d;padding:8px;background:#161b22;">
        <?php else: ?>
            <span style="color:#888;">No visual layout assets specified.</span>
        <?php endif; ?>
    </div>

    <label style="display:block;margin-bottom:0.3rem;font-weight:bold;">Replace Layout Asset Graphic (Optional)</label>
    <input type="file" name="image" style="width:100%;margin-bottom:1.5rem;">

    <div style="display:flex;gap:10px;">
        <a href="manage-payment-methods.php" class="btn" style="background:#21262d;color:#c9d1d9;border:1px solid #30363d;text-align:center;width:30%;padding:.7rem;text-decoration:none;border-radius:6px;">Cancel</a>
        <button type="submit" class="btn" style="width:70%;padding:.7rem;font-weight:bold;">
            <i class="fas fa-save"></i> Save System Configuration Changes
        </button>
    </div>

</form>

</main>

<script>
function toggleEditFields(){
    const selectedType = document.getElementById('edit_type_selector').value;
    
    // Conceal conflicting active input layers
    const structuralElements = document.getElementsByClassName('type-fields');
    for (let i = 0; i < structuralElements.length; i++) {
        structuralElements[i].style.display = 'none';
    }
    
    // Project matching target subview fields
    const targetElement = document.getElementById('fields_' + selectedType);
    if(targetElement) {
        targetElement.style.display = 'block';
    }
}

function toggleEditRedirectLinkField() {
    const redirectSelector = document.getElementById('edit_redirect_selector');
    const redirectWrapper = document.getElementById('edit_redirect_url_wrapper');
    const redirectInput = document.getElementById('edit_redirect_link_input');
    
    if (redirectSelector && redirectWrapper && redirectInput) {
        if (redirectSelector.value === 'yes') {
            redirectWrapper.style.display = 'block';
            redirectInput.setAttribute('required', 'required');
        } else {
            redirectWrapper.style.display = 'none';
            redirectInput.removeAttribute('required');
        }
    }
}

// Ensure interface layers balance accurately upon component rendering cycles
document.addEventListener("DOMContentLoaded", function() {
    toggleEditFields();
    toggleEditRedirectLinkField();
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
