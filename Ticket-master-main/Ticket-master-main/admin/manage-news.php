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
$stmt = $pdo->prepare("SELECT * FROM artists WHERE artist_id=?");
$stmt->execute([$artist_id]);
$artist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artist) {
    $_SESSION['error'] = "Artist not found.";
    header("Location: manage-artists.php");
    exit;
}

/* --------------------------------------------------
   HANDLE ACTIONS
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $action = $_POST['action'] ?? '';

        /* ---------------- ADD NEWS ---------------- */
        if ($action === 'add') {

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($title === '' || $description === '') {
                throw new Exception("Title and Description are required.");
            }

            /* IMAGE UPLOAD */
            $imageName = '';

            if (!empty($_FILES['image']['name'])) {

                $uploadDir = __DIR__ . "/../uploads/news/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $imageName = time() . '_' . basename($_FILES['image']['name']);

                move_uploaded_file(
                    $_FILES['image']['tmp_name'],
                    $uploadDir . $imageName
                );
            }

            $stmt = $pdo->prepare("
                INSERT INTO news
                (artist_id, image, title, description)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $artist_id,
                $imageName,
                $title,
                $description
            ]);

            $_SESSION['success'] = "News added successfully.";

            header("Location: manage-news.php?artist_id=".$artist_id);
            exit;
        }

        /* ---------------- DELETE NEWS ---------------- */
        if ($action === 'delete') {

            $news_id = (int)$_POST['news_id'];

            $stmt = $pdo->prepare("
                SELECT image FROM news WHERE news_id=?
            ");

            $stmt->execute([$news_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['image']) {

                $file = __DIR__ . "/../uploads/news/" . $row['image'];

                if (file_exists($file)) {
                    unlink($file);
                }
            }

            $stmt = $pdo->prepare("
                DELETE FROM news WHERE news_id=?
            ");

            $stmt->execute([$news_id]);

            $_SESSION['success'] = "News deleted.";

            header("Location: manage-news.php?artist_id=".$artist_id);
            exit;
        }

    } catch (Exception $e) {

        $_SESSION['error'] = $e->getMessage();

        header("Location: manage-news.php?artist_id=".$artist_id);
        exit;
    }
}

/* --------------------------------------------------
   FETCH NEWS
-------------------------------------------------- */
$stmt = $pdo->prepare("
SELECT * FROM news
WHERE artist_id=?
ORDER BY news_id DESC
");

$stmt->execute([$artist_id]);

$news = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<main style="max-width:1100px;margin:auto;">

<h1 style="margin-bottom:20px;">Manage News</h1>

<!-- ARTIST HEADER -->
<div style="display:flex;align-items:center;gap:15px;background:var(--card);padding:20px;border-radius:10px;margin-bottom:30px;">

<?php if($artist['artist_image']){ ?>

<img src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
style="width:70px;height:70px;border-radius:10px;object-fit:cover;">

<?php } ?>

<div>

<h2 style="margin:0;">
<?= htmlspecialchars($artist['artist_name']) ?>
</h2>

<small>Manage Artist News</small>

</div>

</div>

<?php if(isset($_SESSION['success'])){ ?>

<div style="background:#238636;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;">
<?= $_SESSION['success']; unset($_SESSION['success']); ?>
</div>

<?php } ?>

<?php if(isset($_SESSION['error'])){ ?>

<div style="background:#f85149;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;">
<?= $_SESSION['error']; unset($_SESSION['error']); ?>
</div>

<?php } ?>

<div style="text-align:right;margin-bottom:20px;">

<button class="btn" onclick="openModal()">
<i class="fas fa-plus"></i> Add News
</button>

</div>

<div style="overflow:auto;">

<table style="width:100%;border-collapse:collapse;">

<thead>

<tr style="background:#111827;">

<th style="padding:12px;">Image</th>
<th style="padding:12px;">Title</th>
<th style="padding:12px;">Description</th>
<th style="padding:12px;">Action</th>

</tr>

</thead>

<tbody>

<?php if(empty($news)){ ?>

<tr>
<td colspan="4" style="padding:20px;text-align:center;">
No news found.
</td>
</tr>

<?php } ?>

<?php foreach($news as $item){ ?>

<tr style="border-bottom:1px solid var(--border);">

<td style="padding:12px;">

<?php if($item['image']){ ?>

<img src="../uploads/news/<?= htmlspecialchars($item['image']) ?>"
style="width:60px;height:60px;border-radius:8px;object-fit:cover;">

<?php } ?>

</td>

<td style="padding:12px;">
<?= htmlspecialchars($item['title']) ?>
</td>

<td style="padding:12px;max-width:250px;">
<?= htmlspecialchars(mb_strimwidth($item['description'],0,80,'...')) ?>
</td>

<td style="padding:12px;white-space:nowrap;">

    <!-- EDIT -->
    <a href="edit-news.php?news_id=<?= $item['news_id'] ?>&artist_id=<?= $artist_id ?>"
       class="btn green"
       style="padding:6px 10px;font-size:13px;">
        Edit
    </a>

    <!-- VIEW (optional extras-style button) -->
    <a href="view-news.php?news_id=<?= $item['news_id'] ?>"
       class="btn"
       style="padding:6px 10px;font-size:13px;background:#6f42c1;color:#fff;margin-left:5px;">
        View
    </a>

    <!-- DELETE -->
    <form method="POST"
          onsubmit="return confirm('Delete this news?');"
          style="display:inline-block;margin-left:5px;">

        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="news_id" value="<?= $item['news_id'] ?>">

        <button class="btn red"
                style="padding:6px 10px;font-size:13px;">
            Delete
        </button>

    </form>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<!-- MODAL -->
<div id="modal"
style="display:none;position:fixed;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.7);">

<div style="background:#111827;max-width:600px;margin:5% auto;padding:25px;border-radius:10px;">

<h2>Add News</h2>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="action" value="add">

<label>Title</label>

<input type="text"
name="title"
required
style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Description</label>

<textarea name="description"
rows="6"
required
style="width:100%;padding:10px;margin:10px 0 20px;"></textarea>

<label>Image</label>

<input type="file"
name="image"
style="width:100%;margin:10px 0 20px;">

<button class="btn" style="width:100%;">
<i class="fas fa-save"></i> Save
</button>

</form>

<br>

<button class="btn red" style="width:100%;" onclick="closeModal()">
Close
</button>

</div>

</div>

<script>

function openModal(){
    document.getElementById('modal').style.display='block';
}

function closeModal(){
    document.getElementById('modal').style.display='none';
}

</script>

</main>

<?php require_once __DIR__.'/inc/footer.php'; ?>
