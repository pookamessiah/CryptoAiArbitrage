<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

$artist = null;

/* --------------------------------------------------
   GET ARTIST ID
-------------------------------------------------- */
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "Invalid artist ID.";
    header("Location: manage-artists.php");
    exit;
}

/* --------------------------------------------------
   FETCH ARTIST
-------------------------------------------------- */
try {
    $stmt = $pdo->prepare("SELECT * FROM artists WHERE artist_id = ?");
    $stmt->execute([$id]);
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
   HANDLE UPDATE
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $artist_name = trim($_POST['artist_name'] ?? '');
        $genre       = trim($_POST['genre'] ?? '');
        $rating      = trim($_POST['rating'] ?? '');
        $about       = trim($_POST['about'] ?? '');

        if ($artist_name === '') {
            throw new Exception("Artist name is required.");
        }

        $imageName = $artist['artist_image'];

        /* IMAGE UPLOAD */
        if (!empty($_FILES['artist_image']['name'])) {

            $uploadDir = __DIR__ . "/../uploads/artists/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $imageName = time() . '_' . basename($_FILES['artist_image']['name']);
            $target = $uploadDir . $imageName;

            move_uploaded_file($_FILES['artist_image']['tmp_name'], $target);
        }

        /* UPDATE */
        $stmt = $pdo->prepare("
            UPDATE artists 
            SET artist_name = ?, artist_image = ?, genre = ?, rating = ?, about = ?
            WHERE artist_id = ?
        ");

        $stmt->execute([
            $artist_name,
            $imageName,
            $genre,
            $rating,
            $about,
            $id
        ]);

        $_SESSION['success'] = "Artist updated successfully.";
        header("Location: manage-artists.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: manage-artists.php");
        exit;
    }
}
?>

<main style="max-width:700px;margin:2rem auto;padding:0 15px;">

<h1 style="text-align:center;margin-bottom:2rem;">Edit Artist</h1>

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
      enctype="multipart/form-data"
      style="background:var(--card);padding:2rem;border-radius:10px;border:1px solid var(--border);">

    <label>Artist Name</label>
    <input type="text"
           name="artist_name"
           value="<?= htmlspecialchars($artist['artist_name']) ?>"
           required
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>Genre</label>
    <input type="text"
           name="genre"
           value="<?= htmlspecialchars($artist['genre']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>Rating</label>
    <input type="number"
           step="0.1"
           name="rating"
           value="<?= htmlspecialchars($artist['rating']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>About</label>
    <textarea name="about"
              style="width:100%;padding:.7rem;margin-bottom:1rem;height:140px;resize:vertical;"><?= 
        htmlspecialchars($artist['about']) 
    ?></textarea>

    <label>Current Image</label>
    <div style="margin:10px 0 15px;">
        <?php if (!empty($artist['artist_image'])): ?>
            <img src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
                 style="width:120px;height:120px;object-fit:cover;border-radius:8px;">
        <?php else: ?>
            <span style="color:#888;">No image</span>
        <?php endif; ?>
    </div>

    <label>Change Image (optional)</label>
    <input type="file"
           name="artist_image"
           style="width:100%;margin-bottom:1.5rem;">

    <button type="submit" class="btn" style="width:100%;">
        <i class="fas fa-save"></i> Save Changes
    </button>

</form>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
