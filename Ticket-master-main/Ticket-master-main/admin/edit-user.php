<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

$user = null;

/* --------------------------------------------------
   GET USER ID
-------------------------------------------------- */
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: manage-users.php");
    exit;
}

/* --------------------------------------------------
   FETCH USER
-------------------------------------------------- */
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: manage-users.php");
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: manage-users.php");
    exit;
}

/* --------------------------------------------------
   HANDLE UPDATE
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $full_name    = trim($_POST['full_name'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $country      = trim($_POST['country'] ?? '');
        $country_code = trim($_POST['country_code'] ?? '');
        $phone        = trim($_POST['phone'] ?? '');
        $balance      = trim($_POST['balance'] ?? '0.00');
        $password     = $_POST['password'] ?? '';

        if ($full_name === '' || $email === '') {
            throw new Exception("Full name and email are required.");
        }

        /* Keep existing password unless new one is provided */
        $password_hash = $user['password_hash'];

        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
        }

        /* UPDATE USER */
        $stmt = $pdo->prepare("
            UPDATE users 
            SET full_name = ?, 
                email = ?, 
                balance = ?, 
                country = ?, 
                country_code = ?, 
                phone = ?, 
                password_hash = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $full_name,
            $email,
            $balance,
            $country,
            $country_code,
            $phone,
            $password_hash,
            $id
        ]);

        $_SESSION['success'] = "User updated successfully.";
        header("Location: manage-users.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: manage-users.php");
        exit;
    }
}
?>

<main style="max-width:700px;margin:2rem auto;padding:0 15px;">

<h1 style="text-align:center;margin-bottom:2rem;">Edit User</h1>

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

<form method="POST"
      style="background:var(--card);padding:2rem;border-radius:10px;border:1px solid var(--border);">

    <label>Full Name</label>
    <input type="text"
           name="full_name"
           value="<?= htmlspecialchars($user['full_name']) ?>"
           required
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>Email</label>
    <input type="email"
           name="email"
           value="<?= htmlspecialchars($user['email']) ?>"
           required
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>Balance</label>
    <input type="number"
           step="0.01"
           name="balance"
           value="<?= htmlspecialchars($user['balance']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>Country</label>
    <input type="text"
           name="country"
           value="<?= htmlspecialchars($user['country']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>Country Code</label>
    <input type="text"
           name="country_code"
           value="<?= htmlspecialchars($user['country_code']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>Phone</label>
    <input type="text"
           name="phone"
           value="<?= htmlspecialchars($user['phone']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>New Password (optional)</label>
    <input type="password"
           name="password"
           placeholder="Leave blank to keep current password"
           style="width:100%;padding:.7rem;margin-bottom:1.5rem;">

    <button type="submit" class="btn" style="width:100%;">
        <i class="fas fa-save"></i> Save Changes
    </button>

</form>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
