<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

$message = '';
$error   = '';

/* --------------------------------------------------
   FETCH ALL USERS
-------------------------------------------------- */
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $users = [];
}

/* --------------------------------------------------
   HANDLE ACTIONS
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {

    $action = $_POST['action'] ?? '';

    try {

        /* ---------------- ADD USER ---------------- */
        if ($action === 'add') {

            $full_name    = trim($_POST['full_name'] ?? '');
            $email        = trim($_POST['email'] ?? '');
            $country      = trim($_POST['country'] ?? '');
            $country_code = trim($_POST['country_code'] ?? '');
            $phone        = trim($_POST['phone'] ?? '');
            $balance      = trim($_POST['balance'] ?? '0.00');
            $password     = $_POST['password'] ?? '';

            if ($full_name === '' || $email === '' || $password === '') {
                throw new Exception("Full name, email and password are required.");
            }

            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                INSERT INTO users 
                (full_name, email, balance, country, country_code, phone, password_hash)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $full_name,
                $email,
                $balance,
                $country,
                $country_code,
                $phone,
                $password_hash
            ]);

            $_SESSION['success'] = "User added successfully.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        /* ---------------- DELETE USER ---------------- */
        if ($action === 'delete') {

            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception("Invalid user ID.");
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['success'] = "User deleted successfully.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

         /* ---------------- NOTIFY USER ---------------- */
         if ($action === 'notify') {
         
             $id = (int)($_POST['id'] ?? 0);
             $messageText = trim($_POST['message'] ?? '');
         
             if ($id <= 0) {
                 throw new Exception("Invalid user ID.");
             }
         
             if ($messageText === '') {
                 throw new Exception("Message cannot be empty.");
             }
         
             $stmt = $pdo->prepare("
                 UPDATE users
                 SET message = ?
                 WHERE id = ?
             ");
         
             $stmt->execute([
                 $messageText,
                 $id
             ]);
         
             $_SESSION['success'] = "Notification saved successfully.";
             header("Location: " . $_SERVER['PHP_SELF']);
             exit;
         }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>

<main>

<h1 style="text-align:center; margin:2rem 0;">Manage Users</h1>

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

<!-- ADD BUTTON -->
<div style="text-align:center;margin-bottom:2rem;">
    <button onclick="openModal()" class="btn">
        + Add User
    </button>
</div>

<!-- TABLE -->
<div style="max-width:1200px;margin:0 auto;overflow-x:auto;padding:0 10px;">

<table style="width:100%;border-collapse:collapse;background:var(--card);border:1px solid var(--border);border-radius:10px;min-width:900px;">

<thead>
<tr style="text-align:left;background:#111827;">
    <th style="padding:12px;">Name</th>
    <th style="padding:12px;">Email</th>
    <th style="padding:12px;">Balance</th>
    <th style="padding:12px;">Country</th>
    <th style="padding:12px;">Phone</th>
    <th style="padding:12px;">Created</th>
    <th style="padding:12px;">Actions</th>
</tr>
</thead>

<tbody>

<?php foreach ($users as $user): ?>
<tr style="border-top:1px solid var(--border);">

    <td style="padding:12px;font-weight:600;">
        <?= htmlspecialchars($user['full_name']) ?>
    </td>

    <td style="padding:12px;">
        <?= htmlspecialchars($user['email']) ?>
    </td>

    <td style="padding:12px;">
        $<?= number_format((float)$user['balance'], 2) ?>
    </td>

    <td style="padding:12px;">
        <?= htmlspecialchars($user['country']) ?> (<?= htmlspecialchars($user['country_code']) ?>)
    </td>

    <td style="padding:12px;">
        <?= htmlspecialchars($user['phone']) ?>
    </td>

    <td style="padding:12px;">
        <?= htmlspecialchars($user['created_at']) ?>
    </td>

    <td style="padding:12px;white-space:nowrap;">

        <a href="edit-user.php?id=<?= $user['id'] ?>"
           class="btn green"
           style="padding:6px 10px;font-size:13px;">
            Edit
        </a>
       
        <button
            type="button"
            class="btn"
            style="padding:6px 10px;font-size:13px;margin-left:5px;background:#2563eb;"
            data-id="<?= $user['id'] ?>"
            data-message="<?= htmlspecialchars($user['message'] ?? '', ENT_QUOTES) ?>"
            onclick="notifyUser(this)">
            Notify
        </button>

        <form method="POST"
              onsubmit="return confirm('Delete this user?');"
              style="display:inline-block;margin-left:5px;">

            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $user['id'] ?>">

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

<!-- MODAL -->
<div id="userModal"
     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
     background:rgba(0,0,0,.7);overflow-y:auto;padding:20px;">

<div style="
    background:#0d1117;
    max-width:500px;
    margin:40px auto;
    padding:2rem;
    border-radius:10px;
    max-height:90vh;
    overflow-y:auto;
">

<h2>Add User</h2>

<form method="POST">

<input type="hidden" name="action" value="add">

<label>Full Name</label>
<input type="text" name="full_name" required style="width:100%;padding:.7rem;margin-bottom:1rem;">

<label>Email</label>
<input type="email" name="email" required style="width:100%;padding:.7rem;margin-bottom:1rem;">

<label>Password</label>
<input type="password" name="password" required style="width:100%;padding:.7rem;margin-bottom:1rem;">

<label>Balance</label>
<input type="number" step="0.01" name="balance" value="0.00" style="width:100%;padding:.7rem;margin-bottom:1rem;">

<label>Country</label>
<input type="text" name="country" style="width:100%;padding:.7rem;margin-bottom:1rem;">

<label>Country Code</label>
<input type="text" name="country_code" style="width:100%;padding:.7rem;margin-bottom:1rem;">

<label>Phone</label>
<input type="text" name="phone" style="width:100%;padding:.7rem;margin-bottom:1rem;">

<button type="submit" class="btn" style="width:100%;">Save</button>

</form>

<br>

<button onclick="closeModal()" class="btn red" style="width:100%;">Close</button>

</div>
</div>

<!-- Notify Modal -->
<div id="notifyModal"
     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
     background:rgba(0,0,0,.7);">

<div style="
    background:#0d1117;
    max-width:500px;
    margin:80px auto;
    padding:2rem;
    border-radius:10px;
">

<h2>Notify User</h2>

<form method="POST">

    <input type="hidden" name="action" value="notify">
    <input type="hidden" name="id" id="notifyUserId">

    <label>Message</label>

    <textarea
        id="notifyMessage"
        name="message"
        rows="6"
        required
        style="width:100%;padding:.8rem;margin:1rem 0;"></textarea>

    <button class="btn" style="width:100%;">
        Save Notification
    </button>

</form>

<br>

<button onclick="closeNotifyModal()"
        class="btn red"
        style="width:100%;">
    Cancel
</button>

</div>
</div>

<script>
function openModal(){
    document.getElementById('userModal').style.display='block';
}
function closeModal(){
    document.getElementById('userModal').style.display='none';
}

function notifyUser(button) {

    document.getElementById('notifyUserId').value =
        button.dataset.id;

    document.getElementById('notifyMessage').value =
        button.dataset.message || '';

    document.getElementById('notifyModal').style.display = 'block';
}

function closeNotifyModal() {
    document.getElementById('notifyModal').style.display = 'none';
}
   
</script>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
