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
    header("Location: manage-artists.php");
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
   FETCH SETLIST
-------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM setlists WHERE setlist_id = ? AND artist_id = ?");
$stmt->execute([$id, $artist_id]);
$setlist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$setlist) {
    $_SESSION['error'] = "Setlist not found.";
    header("Location: manage-setlists.php?artist_id=" . $artist_id);
    exit;
}

/* --------------------------------------------------
   HANDLE UPDATE
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $title = trim($_POST['title'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $event_date = $_POST['event_date'] ?? null;
        $description = trim($_POST['description'] ?? '');

        if ($title === '') {
            throw new Exception("Title is required.");
        }

        $stmt = $pdo->prepare("
            UPDATE setlists 
            SET title = ?, location = ?, event_date = ?, description = ?
            WHERE setlist_id = ? AND artist_id = ?
        ");

        $stmt->execute([
            $title,
            $location,
            $event_date,
            $description,
            $id,
            $artist_id
        ]);

        $_SESSION['success'] = "Setlist updated successfully.";
        header("Location: manage-setlists.php?artist_id=" . $artist_id);
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: edit-setlist.php?id=" . $id . "&artist_id=" . $artist_id);
        exit;
    }
}
?>

<main style="max-width:700px;margin:2rem auto;">

<h1 style="text-align:center;margin-bottom:2rem;">Edit Setlist</h1>

<!-- ARTIST HEADER -->
<div style="display:flex;align-items:center;gap:15px;background:var(--card);padding:15px;border-radius:10px;border:1px solid var(--border);margin-bottom:2rem;">

    <img src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
         style="width:60px;height:60px;object-fit:cover;border-radius:8px;">

    <div>
        <h3 style="margin:0;"><?= htmlspecialchars($artist['artist_name']) ?></h3>
        <small style="color:#888;">Edit Setlist</small>
    </div>

</div>

<!-- FORM -->
<form method="POST"
      style="background:var(--card);padding:2rem;border-radius:10px;border:1px solid var(--border);">

    <label>Title</label>
    <input type="text"
           name="title"
           value="<?= htmlspecialchars($setlist['title']) ?>"
           required
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>Location</label>
    <input type="text"
           name="location"
           value="<?= htmlspecialchars($setlist['location']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>Date</label>
    <input type="date"
           name="event_date"
           value="<?= htmlspecialchars($setlist['event_date']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <label>Description</label>
    <textarea name="description"
              style="width:100%;padding:.7rem;margin-bottom:1rem;height:120px;"><?= htmlspecialchars($setlist['description']) ?></textarea>

    <button type="submit" class="btn" style="width:100%;">
        <i class="fas fa-save"></i> Save Changes
    </button>

</form>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
