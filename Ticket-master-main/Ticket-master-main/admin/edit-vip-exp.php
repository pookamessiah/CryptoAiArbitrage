<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

/* --------------------------------------------------
   GET VIP ID
-------------------------------------------------- */
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "Invalid VIP ID.";
    header("Location: manage-artists.php");
    exit;
}

/* --------------------------------------------------
   FETCH VIP RECORD
-------------------------------------------------- */
try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM vip_exp
        WHERE vip_exp_id = ?
    ");
    $stmt->execute([$id]);
    $vip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vip) {
        $_SESSION['error'] = "VIP Experience not found.";
        header("Location: manage-artists.php");
        exit;
    }

    /* fetch artist for header */
    $stmt = $pdo->prepare("SELECT * FROM artists WHERE artist_id = ?");
    $stmt->execute([$vip['artist_id']]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: manage-artists.php");
    exit;
}

/* --------------------------------------------------
   HANDLE UPDATE
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title === '') {
            throw new Exception("Title is required.");
        }

        /* keep old image */
        $imageName = $vip['image'];

        /* upload new image (optional) */
        if (!empty($_FILES['image']['name'])) {

            $uploadDir = __DIR__ . "/../uploads/vip/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $imageName = uniqid("vip_") . "." . $extension;

            move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $uploadDir . $imageName
            );
        }

        /* UPDATE */
        $stmt = $pdo->prepare("
            UPDATE vip_exp
            SET title = ?, description = ?, image = ?
            WHERE vip_exp_id = ?
        ");

        $stmt->execute([
            $title,
            $description,
            $imageName,
            $id
        ]);

        $_SESSION['success'] = "VIP Experience updated successfully.";

        header("Location: manage-vip-exp.php?artist_id=" . $vip['artist_id']);
        exit;

    } catch (Exception $e) {

        $_SESSION['error'] = $e->getMessage();
        header("Location: edit-vip-exp.php?id=" . $id);
        exit;
    }
}
?>

<main style="max-width:700px;margin:2rem auto;">

<h1 style="text-align:center;margin-bottom:2rem;">
    Edit VIP Experience
</h1>

<!-- ARTIST HEADER -->
<div style="display:flex;align-items:center;gap:15px;background:var(--card);padding:15px;border-radius:10px;border:1px solid var(--border);margin-bottom:2rem;">

    <?php if (!empty($artist['artist_image'])): ?>
        <img src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
             style="width:60px;height:60px;object-fit:cover;border-radius:8px;">
    <?php endif; ?>

    <div>
        <h3 style="margin:0;">
            <?= htmlspecialchars($artist['artist_name']) ?>
        </h3>
        <small style="color:#888;">Edit VIP Experience</small>
    </div>

</div>

<!-- FORM -->
<form method="POST" enctype="multipart/form-data"
      style="background:var(--card);padding:2rem;border-radius:10px;border:1px solid var(--border);">

    <!-- TITLE -->
    <label>Title</label>
    <input type="text"
           name="title"
           value="<?= htmlspecialchars($vip['title']) ?>"
           required
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <!-- DESCRIPTION -->
    <label>Description</label>
    <textarea name="description"
              style="width:100%;padding:.7rem;margin-bottom:1rem;height:120px;">
        <?= htmlspecialchars($vip['description']) ?>
    </textarea>

    <!-- CURRENT IMAGE -->
    <label>Current Image</label><br>

    <?php if (!empty($vip['image'])): ?>
        <img src="../uploads/vip/<?= htmlspecialchars($vip['image']) ?>"
             style="width:120px;height:120px;object-fit:cover;border-radius:8px;margin-bottom:1rem;">
    <?php else: ?>
        <p style="color:#888;">No image</p>
    <?php endif; ?>

    <!-- NEW IMAGE -->
    <label>Change Image (optional)</label>
    <input type="file"
           name="image"
           style="width:100%;margin-bottom:1.5rem;">

    <!-- BUTTON -->
    <button type="submit" class="btn" style="width:100%;">
        <i class="fas fa-save"></i> Save Changes
    </button>

</form>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
