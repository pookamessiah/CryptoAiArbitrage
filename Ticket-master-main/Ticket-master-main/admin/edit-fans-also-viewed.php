<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

/* --------------------------------------------------
   GET ID
-------------------------------------------------- */
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "Invalid record ID.";
    header("Location: manage-fans-also-viewed.php");
    exit;
}

/* --------------------------------------------------
   FETCH RECORD
-------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM fans_also_viewed WHERE fav_id=?");
$stmt->execute([$id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    $_SESSION['error'] = "Record not found.";
    header("Location: manage-fans-also-viewed.php");
    exit;
}

/* --------------------------------------------------
   UPDATE HANDLER
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $name = trim($_POST['v_artist_name'] ?? '');
        $link = trim($_POST['v_artist_link'] ?? '');

        if ($name === '') {
            throw new Exception("Artist name is required.");
        }

        $imageName = $record['v_artist_image'];

        /* ---------------- IMAGE UPLOAD ---------------- */
        if (!empty($_FILES['v_artist_image']['name'])) {

            $uploadDir = __DIR__ . "/../uploads/fans-also-viewed/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            /* delete old image */
            if (!empty($imageName)) {
                $oldFile = $uploadDir . $imageName;
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            $imageName = time() . "_" . basename($_FILES['v_artist_image']['name']);

            move_uploaded_file(
                $_FILES['v_artist_image']['tmp_name'],
                $uploadDir . $imageName
            );
        }

        /* ---------------- UPDATE ---------------- */
        $stmt = $pdo->prepare("
            UPDATE fans_also_viewed
            SET v_artist_name = ?,
                v_artist_image = ?,
                v_artist_link = ?
            WHERE fav_id = ?
        ");

        $stmt->execute([
            $name,
            $imageName,
            $link,
            $id
        ]);

        $_SESSION['success'] = "Record updated successfully.";

        header("Location: manage-fans-also-viewed.php?artist_id=".$record['artist_id']);
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: edit-fans-also-viewed.php?id=".$id);
        exit;
    }
}
?>

<main style="max-width:700px;margin:2rem auto;">

<h1 style="text-align:center;margin-bottom:2rem;">Edit Related Artist</h1>

<form method="POST" enctype="multipart/form-data"
      style="background:var(--card);padding:2rem;border-radius:10px;border:1px solid var(--border);">

    <!-- NAME -->
    <label>Artist Name</label>
    <input type="text"
           name="v_artist_name"
           value="<?= htmlspecialchars($record['v_artist_name']) ?>"
           required
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <!-- LINK -->
    <label>Artist Link</label>
    <input type="text"
           name="v_artist_link"
           value="<?= htmlspecialchars($record['v_artist_link']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <!-- CURRENT IMAGE -->
    <label>Current Image</label><br>

    <?php if (!empty($record['v_artist_image'])): ?>
        <img src="../uploads/fans-also-viewed/<?= htmlspecialchars($record['v_artist_image']) ?>"
             style="width:120px;height:120px;object-fit:cover;border-radius:8px;margin-bottom:1rem;">
    <?php else: ?>
        <p style="color:#888;">No image</p>
    <?php endif; ?>

    <!-- NEW IMAGE -->
    <label>Change Image (optional)</label>
    <input type="file"
           name="v_artist_image"
           style="width:100%;margin-bottom:1.5rem;">

    <!-- BUTTON -->
    <button type="submit" class="btn" style="width:100%;">
        <i class="fas fa-save"></i> Save Changes
    </button>

</form>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
