<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

/* --------------------------------------------------
   GET ARTIST ID
-------------------------------------------------- */
$artist_id = (int)($_GET['artist_id'] ?? 0);

if ($artist_id <= 0) {
    $_SESSION['error'] = "Invalid artist ID.";
    header("Location: manage-artists.php");
    exit;
}

/* --------------------------------------------------
   FETCH ARTIST
-------------------------------------------------- */
try {
    $stmt = $pdo->prepare("SELECT * FROM artists WHERE artist_id = ?");
    $stmt->execute([$artist_id]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artist) {
        $_SESSION['error'] = "Artist not found.";
        header("Location: manage-artists.php");
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: manage-artists.php");
    exit;
}

/* --------------------------------------------------
   FETCH VIP EXP
-------------------------------------------------- */
try {
    $stmt = $pdo->prepare("SELECT * FROM vip_exp WHERE artist_id = ? ORDER BY vip_exp_id DESC");
    $stmt->execute([$artist_id]);
    $vipList = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $vipList = [];
    $_SESSION['error'] = $e->getMessage();
}

/* --------------------------------------------------
   HANDLE ADD + DELETE
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $action = $_POST['action'] ?? '';

        /* ---------------- ADD ---------------- */
        if ($action === 'add') {

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($title === '') {
                throw new Exception("Title is required.");
            }

            /* IMAGE UPLOAD */
            $imageName = '';

            if (!empty($_FILES['image']['name'])) {

                $uploadDir = __DIR__ . "/../uploads/vip/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $imageName = time() . '_' . basename($_FILES['image']['name']);
                $target = $uploadDir . $imageName;

                move_uploaded_file($_FILES['image']['tmp_name'], $target);
            }

            $stmt = $pdo->prepare("
                INSERT INTO vip_exp (artist_id, image, title, description)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $artist_id,
                $imageName,
                $title,
                $description
            ]);

            $_SESSION['success'] = "VIP Experience added successfully.";
            header("Location: manage-vip-exp.php?artist_id=" . $artist_id);
            exit;
        }

        /* ---------------- DELETE ---------------- */
        if ($action === 'delete') {

            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception("Invalid ID.");
            }

            $stmt = $pdo->prepare("DELETE FROM vip_exp WHERE vip_exp_id = ?");
            $stmt->execute([$id]);

            $_SESSION['success'] = "VIP Experience deleted.";
            header("Location: manage-vip-exp.php?artist_id=" . $artist_id);
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: manage-vip-exp.php?artist_id=" . $artist_id);
        exit;
    }
}
?>

<main style="max-width:1000px;margin:2rem auto;">

<h1 style="text-align:center;margin-bottom:1rem;">
    VIP & Experience
</h1>

<!-- ARTIST HEADER -->
<div style="display:flex;align-items:center;gap:15px;background:var(--card);padding:15px;border-radius:10px;border:1px solid var(--border);margin-bottom:2rem;">

    <img src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
         style="width:60px;height:60px;object-fit:cover;border-radius:8px;">

    <div>
        <h3 style="margin:0;"><?= htmlspecialchars($artist['artist_name']) ?></h3>
        <small style="color:#888;">Manage VIP Experiences</small>
    </div>

</div>

<!-- ADD BUTTON -->
<div style="text-align:center;margin-bottom:2rem;">
    <button onclick="openModal()" class="btn">+ Add VIP Experience</button>
</div>

<!-- LIST -->
<?php if (empty($vipList)): ?>
<p style="text-align:center;color:#888;">No VIP experiences yet.</p>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:15px;">

<?php foreach ($vipList as $vip): ?>

<div style="background:#111827;padding:15px;border-radius:10px;border:1px solid var(--border);">

    <?php if ($vip['image']): ?>
        <img src="../uploads/vip/<?= htmlspecialchars($vip['image']) ?>"
             style="width:100%;height:150px;object-fit:cover;border-radius:8px;margin-bottom:10px;">
    <?php endif; ?>

    <h3 style="margin:0 0 10px;">
        <?= htmlspecialchars($vip['title']) ?>
    </h3>

    <p style="color:#aaa;font-size:14px;">
        <?= nl2br(htmlspecialchars($vip['description'])) ?>
    </p>

    <!-- ACTIONS FIXED -->
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;">

        <a href="edit-vip-exp.php?id=<?= $vip['vip_exp_id'] ?>"
           class="btn green"
            .btn-sm {
                padding:6px 10px;
                font-size:13px;
            }>
            Edit
        </a>

        <form method="POST"
              onsubmit="return confirm('Delete this VIP experience?');"
              style="display:inline-block;margin:0;">

            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $vip['vip_exp_id'] ?>">

            <button class="btn red"
               .btn-sm {
                   padding:6px 10px;
                   font-size:13px;
               }>
                Delete
            </button>

        </form>

    </div>

</div>

<?php endforeach; ?>

</div>

<!-- MODAL -->
<div id="vipModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.7);">

<div style="background:#0d1117;max-width:500px;margin:5% auto;padding:2rem;border-radius:10px;">

<h2>Add VIP Experience</h2>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="action" value="add">

<label>Title</label>
<input type="text" name="title" style="width:100%;padding:.7rem;margin-bottom:1rem;" required>

<label>Description</label>
<textarea name="description" style="width:100%;padding:.7rem;margin-bottom:1rem;"></textarea>

<label>Image</label>
<input type="file" name="image" style="margin-bottom:1rem;">

<button type="submit" class="btn" style="width:100%;">Save</button>

</form>

<br>
<button onclick="closeModal()" class="btn red" style="width:100%;">Close</button>

</div>
</div>

<script>
function openModal(){
    document.getElementById('vipModal').style.display='block';
}
function closeModal(){
    document.getElementById('vipModal').style.display='none';
}
</script>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
