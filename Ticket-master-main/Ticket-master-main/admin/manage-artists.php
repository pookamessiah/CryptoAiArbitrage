<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

$message = '';
$error   = '';

/* --------------------------------------------------
   FETCH ALL ARTISTS
-------------------------------------------------- */
try {
    $stmt = $pdo->query("SELECT * FROM artists ORDER BY artist_id DESC");
    $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $artists = [];
}

/* --------------------------------------------------
   HANDLE ACTIONS
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {

    $action = $_POST['action'] ?? '';

    try {

        /* ---------------- ADD ARTIST ---------------- */
        if ($action === 'add') {

            $artist_name = trim($_POST['artist_name'] ?? '');
            $genre       = trim($_POST['genre'] ?? '');
            $rating      = trim($_POST['rating'] ?? '');
            $about       = trim($_POST['about'] ?? '');

            if ($artist_name === '') {
                throw new Exception("Artist name is required.");
            }

            $imageName = '';
            if (!empty($_FILES['artist_image']['name'])) {

                $uploadDir = __DIR__ . "/../uploads/artists/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $imageName = time() . '_' . basename($_FILES['artist_image']['name']);
                $target = $uploadDir . $imageName;

                move_uploaded_file($_FILES['artist_image']['tmp_name'], $target);
            }

            $stmt = $pdo->prepare("
                INSERT INTO artists (artist_name, artist_image, genre, rating, about)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $artist_name,
                $imageName,
                $genre,
                $rating,
                $about
            ]);

            $_SESSION['success'] = "Artist added successfully.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        /* ---------------- DELETE ARTIST ---------------- */
        if ($action === 'delete') {

            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception("Invalid artist ID.");
            }

            $stmt = $pdo->prepare("DELETE FROM artists WHERE artist_id = ?");
            $stmt->execute([$id]);

            $_SESSION['success'] = "Artist deleted successfully.";
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

<h1 style="text-align:center; margin:2rem 0;">Manage Artists</h1>

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
        + Add Artist
    </button>
</div>

<!-- TABLE -->
<div style="max-width:1100px;margin:0 auto;overflow-x:auto;padding:0 10px;">

<table style="width:100%;border-collapse:collapse;background:var(--card);border:1px solid var(--border);border-radius:10px;min-width:800px;">

<thead>
<tr style="text-align:left;background:#111827;">
    <th style="padding:12px;">Image</th>
    <th style="padding:12px;">Name</th>
    <th style="padding:12px;">Genre</th>
    <th style="padding:12px;">Rating</th>
    <th style="padding:12px;">About</th>
    <th style="padding:12px;">Actions</th>
</tr>
</thead>

<tbody>

<?php foreach ($artists as $artist): ?>
<tr style="border-top:1px solid var(--border);">

    <td style="padding:12px;">
        <?php if (!empty($artist['artist_image'])): ?>
            <img src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
                 style="width:55px;height:55px;object-fit:cover;border-radius:8px;">
        <?php else: ?>
            <span style="color:#888;">N/A</span>
        <?php endif; ?>
    </td>

    <td style="padding:12px;font-weight:600;">
        <?= htmlspecialchars($artist['artist_name']) ?>
    </td>

    <td style="padding:12px;">
        <?= htmlspecialchars($artist['genre']) ?>
    </td>

    <td style="padding:12px;">
        <i class="fas fa-star" style="color:#facc15;"></i>
        <?= htmlspecialchars($artist['rating']) ?>
    </td>

    <td style="padding:12px;max-width:250px;">
        <?php
            $about = mb_strimwidth($artist['about'], 0, 60, "...");
        ?>
        <span title="<?= htmlspecialchars($artist['about']) ?>">
            <?= htmlspecialchars($about) ?>
        </span>
    </td>

    <!-- ACTIONS FIXED -->
    <td style="padding:12px;white-space:nowrap;">

        <a href="edit-artist.php?id=<?= $artist['artist_id'] ?>"
           class="btn green"
           style="padding:6px 10px;font-size:13px;">
            Edit
        </a>

        <a href="extras.php?artist_id=<?= $artist['artist_id'] ?>"
           class="btn"
           style="padding:6px 10px;font-size:13px;background:#6f42c1;color:#fff;margin-left:5px;">
            Extras
        </a>

        <form method="POST"
              onsubmit="return confirm('Delete this artist?');"
              style="display:inline-block;margin-left:5px;">

            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $artist['artist_id'] ?>">

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

<!-- SCROLLABLE MODAL FIX -->
<div id="artistModal"
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

<h2>Add Artist</h2>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="action" value="add">

<label>Name</label>
<input type="text" name="artist_name" required style="width:100%;padding:.7rem;margin-bottom:1rem;">

<label>Genre</label>
<input type="text" name="genre" style="width:100%;padding:.7rem;margin-bottom:1rem;">

<label>Rating</label>
<input type="number" step="0.1" name="rating" style="width:100%;padding:.7rem;margin-bottom:1rem;">

<label>About</label>
<textarea name="about" style="width:100%;padding:.7rem;margin-bottom:1rem;"></textarea>

<label>Image</label>
<input type="file" name="artist_image" style="margin-bottom:1rem;">

<button type="submit" class="btn" style="width:100%;">Save</button>

</form>

<br>

<button onclick="closeModal()" class="btn red" style="width:100%;">Close</button>

</div>
</div>

<script>
function openModal(){
    document.getElementById('artistModal').style.display='block';
}
function closeModal(){
    document.getElementById('artistModal').style.display='none';
}
</script>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
