<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

/* --------------------------------------------------
   GET IDS
-------------------------------------------------- */
$id = (int)($_GET['id'] ?? 0);
$artist_id = (int)($_GET['artist_id'] ?? 0);

if ($id <= 0 || $artist_id <= 0) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: manage-gallery.php?artist_id=" . $artist_id);
    exit;
}

/* --------------------------------------------------
   FETCH ARTIST
-------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM artists WHERE artist_id = ?");
$stmt->execute([$artist_id]);
$artist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artist) {
    $_SESSION['error'] = "Artist not found.";
    header("Location: manage-artists.php");
    exit;
}

/* --------------------------------------------------
   FETCH GALLERY ITEM
-------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM gallery WHERE gallery_id = ? AND artist_id = ?");
$stmt->execute([$id, $artist_id]);
$media = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$media) {
    $_SESSION['error'] = "Media not found.";
    header("Location: manage-gallery.php?artist_id=" . $artist_id);
    exit;
}

/* --------------------------------------------------
   HANDLE UPDATE
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $title = trim($_POST['media_title'] ?? '');
        $link  = trim($_POST['youtube_media_link'] ?? '');

        if ($link === '') {
            throw new Exception("YouTube link is required.");
        }

        $stmt = $pdo->prepare("
            UPDATE gallery
            SET media_title = ?, youtube_media_link = ?
            WHERE gallery_id = ? AND artist_id = ?
        ");

        $stmt->execute([
            $title,
            $link,
            $id,
            $artist_id
        ]);

        $_SESSION['success'] = "Video updated successfully.";
        header("Location: manage-gallery.php?artist_id=" . $artist_id);
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: edit-gallery.php?id=" . $id . "&artist_id=" . $artist_id);
        exit;
    }
}
?>

<main style="max-width:700px;margin:2rem auto;">

<h1 style="text-align:center;margin-bottom:2rem;">Edit Gallery Video</h1>

<!-- ARTIST HEADER -->
<div style="display:flex;align-items:center;gap:15px;background:var(--card);padding:15px;border-radius:10px;border:1px solid var(--border);margin-bottom:2rem;">

    <img src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
         style="width:60px;height:60px;object-fit:cover;border-radius:8px;">

    <div>
        <h3 style="margin:0;"><?= htmlspecialchars($artist['artist_name']) ?></h3>
        <small style="color:#888;">Edit YouTube Video</small>
    </div>

</div>

<!-- FORM -->
<form method="POST"
      style="background:#111827;padding:20px;border-radius:10px;border:1px solid var(--border);">

    <label style="display:block;margin-bottom:10px;">Video Title</label>
    <input type="text"
           name="media_title"
           value="<?= htmlspecialchars($media['media_title']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:15px;">

    <label style="display:block;margin-bottom:10px;">YouTube Link</label>
    <input type="text"
           name="youtube_media_link"
           value="<?= htmlspecialchars($media['youtube_media_link']) ?>"
           required
           style="width:100%;padding:.7rem;margin-bottom:20px;">

    <button type="submit" class="btn" style="width:100%;">
        <i class="fas fa-save"></i> Save Changes
    </button>

</form>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
