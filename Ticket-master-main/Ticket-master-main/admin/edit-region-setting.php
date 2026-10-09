<?php

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

$region = null;

/* --------------------------------------------------
   GET REGION ID
-------------------------------------------------- */

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "Invalid region ID.";
    header("Location: manage-region-settings.php");
    exit;
}

/* --------------------------------------------------
   FETCH REGION
-------------------------------------------------- */

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM region_settings
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $region = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$region) {
        $_SESSION['error'] = "Region not found.";
        header("Location: manage-region-settings.php");
        exit;
    }

} catch (PDOException $e) {

    $_SESSION['error'] = $e->getMessage();
    header("Location: manage-region-settings.php");
    exit;

}

/* --------------------------------------------------
   HANDLE UPDATE
-------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $country = trim($_POST['country'] ?? '');
        $currency = strtoupper(trim($_POST['currency'] ?? ''));
        $exchange_rate = trim($_POST['exchange_rates'] ?? '');

        if ($country == '') {
            throw new Exception("Country is required.");
        }

        if (strlen($currency) != 3) {
            throw new Exception("Currency code must be exactly 3 letters.");
        }

        if (!is_numeric($exchange_rate)) {
            throw new Exception("Exchange rate must be numeric.");
        }

        $stmt = $pdo->prepare("
            UPDATE region_settings
            SET
                country = ?,
                currency = ?,
                exchange_rates = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $country,
            $currency,
            $exchange_rate,
            $id
        ]);

        $_SESSION['success'] = "Region updated successfully.";

        header("Location: manage-region-settings.php");
        exit;

    } catch (Exception $e) {

        $_SESSION['error'] = $e->getMessage();

        header("Location: manage-region-settings.php");
        exit;

    }

}

?>

<main style="max-width:700px;margin:2rem auto;padding:0 15px;">

<h1 style="text-align:center;margin-bottom:2rem;">
Edit Region Setting
</h1>

<?php if (!empty($_SESSION['error'])): ?>

<div style="background:#f85149;color:#fff;padding:1rem;border-radius:8px;margin-bottom:1rem;">
    <?= htmlspecialchars($_SESSION['error']); ?>
</div>

<?php unset($_SESSION['error']); endif; ?>

<?php if (!empty($_SESSION['success'])): ?>

<div style="background:#238636;color:#fff;padding:1rem;border-radius:8px;margin-bottom:1rem;">
    <?= htmlspecialchars($_SESSION['success']); ?>
</div>

<?php unset($_SESSION['success']); endif; ?>

<form method="POST"
      style="background:var(--card);padding:2rem;border-radius:10px;border:1px solid var(--border);">

    <label>Country</label>

    <input
        type="text"
        name="country"
        required
        value="<?= htmlspecialchars($region['country']) ?>"
        style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>Currency (3 Letters)</label>

    <input
        type="text"
        name="currency"
        maxlength="3"
        required
        value="<?= htmlspecialchars($region['currency']) ?>"
        style="width:100%;padding:.7rem;margin-bottom:1rem;text-transform:uppercase;">

    <label>Exchange Rate</label>

    <input
        type="number"
        step="0.0001"
        name="exchange_rates"
        required
        value="<?= htmlspecialchars($region['exchange_rates']) ?>"
        style="width:100%;padding:.7rem;margin-bottom:2rem;">

    <button type="submit"
            class="btn"
            style="width:100%;">

        <i class="fas fa-save"></i>
        Save Changes

    </button>

</form>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
