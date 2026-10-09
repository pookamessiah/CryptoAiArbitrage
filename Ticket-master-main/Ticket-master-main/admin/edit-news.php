<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

/* --------------------------------------------------
   GET IDS
-------------------------------------------------- */
$artist_id = (int)($_GET['artist_id'] ?? 0);
$news_id   = (int)($_GET['news_id'] ?? 0);

if ($artist_id <= 0 || $news_id <= 0) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: manage-artists.php");
    exit;
}

/* --------------------------------------------------
   FETCH ARTIST
-------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM artists WHERE artist_id=?");
$stmt->execute([$artist_id]);
$artist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artist) {
    $_SESSION['error'] = "Artist not found.";
    header("Location: manage-artists.php");
    exit;
}

/* --------------------------------------------------
   FETCH NEWS ITEM
-------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM news WHERE news_id=? AND artist_id=?");
$stmt->execute([$news_id, $artist_id]);
$news = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$news) {
    $_SESSION['error'] = "News item not found.";
    header("Location: manage-news.php?artist_id=".$artist_id);
    exit;
}

/* --------------------------------------------------
   HANDLE UPDATE
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title === '' || $description === '') {
            throw new Exception("Title and description are required.");
        }

        $imageName = $news['image'];

        /* ---------------- IMAGE UPLOAD ---------------- */
        if (!empty($_FILES['image']['name'])) {

            $uploadDir = __DIR__ . "/../uploads/news/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newImage = time() . '_' . basename($_FILES['image']['name']);

            move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $uploadDir . $newImage
            );

            /* delete old image */
            if (!empty($news['image'])) {
                $oldFile = $uploadDir . $news['image'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            $imageName = $newImage;
        }

        /* ---------------- UPDATE DB ---------------- */
        $stmt = $pdo->prepare("
            UPDATE news
            SET title=?, description=?, image=?
            WHERE news_id=? AND artist_id=?
        ");

        $stmt->execute([
            $title,
            $description,
            $imageName,
            $news_id,
            $artist_id
        ]);

        $_SESSION['success'] = "News updated successfully.";
        header("Location: manage-news.php?artist_id=".$artist_id);
        exit;

    } catch (Exception $e) {

        $_SESSION['error'] = $e->getMessage();
        header("Location: edit-news.php?artist_id=".$artist_id."&news_id=".$news_id);
        exit;
    }
}
?>

<main style="max-width:900px;margin:2rem auto;">

<h1 style="text-align:center;margin-bottom:20px;">Edit News</h1>

<!-- ARTIST HEADER -->
<div style="display:flex;align-items:center;gap:15px;background:var(--card);padding:20px;border-radius:10px;margin-bottom:25px;border:1px solid var(--border);">

    <?php if($artist['artist_image']){ ?>
        <img src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
             style="width:70px;height:70px;border-radius:10px;object-fit:cover;">
    <?php } ?>

    <div>
        <h2 style="margin:0;"><?= htmlspecialchars($artist['artist_name']) ?></h2>
        <small style="color:#888;">Edit News Article</small>
    </div>

</div>

<!-- FORM -->
<div style="background:#111827;padding:25px;border-radius:10px;border:1px solid var(--border);">

<form method="POST" enctype="multipart/form-data">

    <label>Title</label>
    <input type="text"
           name="title"
           value="<?= htmlspecialchars($news['title']) ?>"
           required
           style="width:100%;padding:10px;margin:10px 0 20px;">

    <label>Description</label>
    <textarea name="description"
              rows="6"
              required
              style="width:100%;padding:10px;margin:10px 0 20px;"><?= htmlspecialchars($news['description']) ?></textarea>

    <label>Current Image</label><br>

    <?php if($news['image']){ ?>
        <img src="../uploads/news/<?= htmlspecialchars($news['image']) ?>"
             style="width:120px;height:120px;object-fit:cover;border-radius:10px;margin:10px 0;">
    <?php } else { ?>
        <p style="color:#888;">No image uploaded</p>
    <?php } ?>

    <label>Replace Image (optional)</label>
    <input type="file"
           name="image"
           style="width:100%;margin:10px 0 20px;">

    <button class="btn green" style="width:100%;padding:10px;">
        <i class="fas fa-save"></i> Save Changes
    </button>

</form>

<br>

<a href="manage-news.php?artist_id=<?= $artist_id ?>"
   class="btn red"
   style="display:block;text-align:center;padding:10px;text-decoration:none;">
    Cancel
</a>

</div>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
