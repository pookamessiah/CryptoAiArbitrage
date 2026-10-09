<?php
// admin/dashboard.php
require_once __DIR__ . '/inc/header.php';

/* UPDATE CONTACT DETAILS */
$success = '';

if (isset($_POST['update_contacts'])) {

    $whatsapp = trim($_POST['whatsapp']);
    $telegram = trim($_POST['telegram']);
    $email     = trim($_POST['email']);

    try {

        $stmt = $pdo->prepare("
            UPDATE admins
            SET whatsapp = ?, telegram = ?, email = ?
            LIMIT 1
        ");

        $stmt->execute([
            $whatsapp,
            $telegram,
            $email
        ]);

        $success = "Contact details updated successfully.";

    } catch (PDOException $e) {

        $success = "Error: " . $e->getMessage();
    }
}

$stmt = $pdo->query("
    SELECT whatsapp, telegram, email
    FROM admins
    LIMIT 1
");

$currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

$currentWhatsapp = $currentAdmin['whatsapp'] ?? '';
$currentTelegram = $currentAdmin['telegram'] ?? '';
$currentEmail     = $currentAdmin['email'] ?? '';

/* --------------------------------------------------
   DASHBOARD STATS
-------------------------------------------------- */

try {

    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM artists");
    $total_artists = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM concerts");
    $total_concerts = (int)$stmt->fetchColumn();

    /* NEW: tickets count */
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets");
    $total_tickets = (int)$stmt->fetchColumn();

    /* NEW: payment_methods count */
    $stmt = $pdo->query("SELECT COUNT(*) FROM payment_methods");
    $total_payment_methods = (int)$stmt->fetchColumn();

    /* NEW: region_settings count */
    $stmt = $pdo->query("SELECT COUNT(*) FROM region_settings");
    $total_region_settings = (int)$stmt->fetchColumn();

        /* NEW: tickets count */
    $stmt = $pdo->query("SELECT COUNT(*) FROM deposits");
    $total_deposits = (int)$stmt->fetchColumn();
    
} catch (PDOException $e) {

    echo '<div style="background:#f85149;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;">';
    echo "<strong>Database Error:</strong> " . htmlspecialchars($e->getMessage());
    echo '</div>';

    $total_users = 0;
    $total_artists = 0;
    $total_concerts = 0;
    $total_tickets = 0;
    $total_payments_methods = 0;
    $total_region_settings = 0;
    $total_deposits = 0;
}
?>

<main style="margin-top: 1.5rem;">

<h1 style="text-align:center; margin-bottom:2.5rem; font-size:2.1rem;">
Dashboard Overview
</h1>

<!-- WHATSAPP SETTINGS -->
<div style="
max-width:500px;
margin:0 auto 2rem auto;
background:#161b22;
padding:1.5rem;
border-radius:12px;
border:1px solid #30363d;
">

<h2 style="margin-bottom:1rem; text-align:center;">
    Support Contact Details
</h2>

<?php if(!empty($success)): ?>
  <div style="
    background:#238636;
    color:white;
    padding:12px;
    border-radius:8px;
    margin-bottom:1rem;
    text-align:center;
  ">
    <?= htmlspecialchars($success) ?>
  </div>
<?php endif; ?>

<form method="POST">

    <label style="display:block;margin-bottom:6px;">WhatsApp</label>
    <input
        type="text"
        name="whatsapp"
        value="<?= htmlspecialchars($currentWhatsapp) ?>"
        placeholder="WhatsApp Number"
        style="
            width:100%;
            padding:14px;
            border-radius:8px;
            border:1px solid #30363d;
            background:#0d1117;
            color:#fff;
            margin-bottom:15px;
        "
    >

    <label style="display:block;margin-bottom:6px;">Telegram</label>
    <input
        type="text"
        name="telegram"
        value="<?= htmlspecialchars($currentTelegram) ?>"
        placeholder="telegram_username"
        style="
            width:100%;
            padding:14px;
            border-radius:8px;
            border:1px solid #30363d;
            background:#0d1117;
            color:#fff;
            margin-bottom:15px;
        "
    >

    <label style="display:block;margin-bottom:6px;">Support Email</label>
    <input
        type="email"
        name="email"
        value="<?= htmlspecialchars($currentEmail) ?>"
        placeholder="support@example.com"
        style="
            width:100%;
            padding:14px;
            border-radius:8px;
            border:1px solid #30363d;
            background:#0d1117;
            color:#fff;
            margin-bottom:20px;
        "
    >

    <button
        type="submit"
        name="update_contacts"
        style="
            width:100%;
            padding:14px;
            border:none;
            border-radius:8px;
            background:#238636;
            color:#fff;
            font-size:15px;
            font-weight:bold;
            cursor:pointer;
        "
    >
        Update Contact Details
    </button>

</form>

</div>

<!-- STATS -->
<div class="stats-grid">

    <div class="card">
        <div class="card-icon" style="color:#58a6ff;">
            <i class="fas fa-users"></i>
        </div>
        <div class="card-value"><?= number_format($total_users) ?></div>
        <div class="card-label">Users</div>
    </div>

    <div class="card">
        <div class="card-icon" style="color:#a59c64;">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="card-value"><?= number_format($total_deposits) ?></div>
        <div class="card-label">Deposits</div>
    </div>

    <div class="card">
        <div class="card-icon" style="color:#ff7b72;">
            <i class="fas fa-microphone"></i>
        </div>
        <div class="card-value"><?= number_format($total_artists) ?></div>
        <div class="card-label">Artists</div>
    </div>

    <div class="card">
        <div class="card-icon" style="color:#3fb950;">
            <i class="fas fa-music"></i>
        </div>
        <div class="card-value"><?= number_format($total_concerts) ?></div>
        <div class="card-label">Concerts</div>
    </div>

    <!-- NEW TICKETS CARD -->
    <div class="card">
        <div class="card-icon" style="color:#d29922;">
            <i class="fas fa-ticket"></i>
        </div>
        <div class="card-value"><?= number_format($total_tickets) ?></div>
        <div class="card-label">Tickets</div>
    </div>

    <!-- NEW TICKETS CARD -->
    <div class="card">
        <div class="card-icon" style="color:#272673;">
            <i class="fas fa-credit-card"></i>
        </div>
        <div class="card-value"><?= number_format($total_payment_methods) ?></div>
        <div class="card-label">Payment Methods</div>
    </div>

    <!-- NEW TICKETS CARD -->
    <div class="card">
        <div class="card-icon" style="color:#fff;">
            <i class="fas fa-globe"></i>
        </div>
        <div class="card-value"><?= number_format($total_region_settings) ?></div>
        <div class="card-label">Region Settings</div>
    </div>

</div>

<!-- MANAGEMENT -->
<h2 style="text-align:center; margin:3rem 0 1.8rem; font-size:1.7rem;">
Management Sections
</h2>

<div class="actions-grid">

    <a href="manage-users.php" class="btn">
        <i class="fas fa-users"></i> Manage Users
    </a>

    <a href="manage-deposits.php" class="btn">
        <i class="fas fa-money-bill-wave"></i> Manage Deposits
    </a>

    <a href="manage-artists.php" class="btn">
        <i class="fas fa-microphone"></i> Manage Artists
    </a>

    <a href="manage-concerts.php" class="btn">
        <i class="fas fa-microphone-lines"></i> Manage Concerts
    </a>

    <a href="manage-tickets.php" class="btn">
        <i class="fas fa-ticket"></i> Manage Tickets
    </a>

    <a href="manage-payment-methods.php" class="btn">
        <i class="fas fa-credit-card"></i> Manage Payment Methods
    </a>

    <a href="manage-region-settings.php" class="btn">
        <i class="fas fa-globe"></i> Manage Region Settings
    </a>

</div>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
