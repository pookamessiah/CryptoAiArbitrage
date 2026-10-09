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
$stmt = $pdo->prepare("SELECT * FROM artists WHERE artist_id = ?");
$stmt->execute([$artist_id]);
$artist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artist) {
    $_SESSION['error'] = "Artist not found.";
    header("Location: manage-artists.php");
    exit;
}

/* --------------------------------------------------
   FETCH SETLISTS
-------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM setlists WHERE artist_id = ? ORDER BY setlist_id DESC");
$stmt->execute([$artist_id]);
$setlists = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------
   HANDLE ADD / DELETE
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $action = $_POST['action'] ?? '';

        /* ---------------- ADD ---------------- */
        if ($action === 'add') {

            $title = trim($_POST['title'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $event_date = $_POST['event_date'] ?? null;
            $description = trim($_POST['description'] ?? '');

            if ($title === '') {
                throw new Exception("Title is required.");
            }

            $stmt = $pdo->prepare("
                INSERT INTO setlists (artist_id, title, location, event_date, description)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $artist_id,
                $title,
                $location,
                $event_date,
                $description
            ]);

            $_SESSION['success'] = "Setlist added successfully.";
            header("Location: manage-setlists.php?artist_id=" . $artist_id);
            exit;
        }

        /* ---------------- DELETE ---------------- */
        if ($action === 'delete') {

            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception("Invalid ID.");
            }

            $stmt = $pdo->prepare("DELETE FROM setlists WHERE setlist_id = ?");
            $stmt->execute([$id]);

            $_SESSION['success'] = "Setlist deleted.";
            header("Location: manage-setlists.php?artist_id=" . $artist_id);
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: manage-setlists.php?artist_id=" . $artist_id);
        exit;
    }
}
?>

<main style="max-width:1000px;margin:2rem auto;">

<h1 style="text-align:center;margin-bottom:1rem;">Setlists</h1>

<!-- ARTIST HEADER -->
<div style="display:flex;align-items:center;gap:15px;background:var(--card);padding:15px;border-radius:10px;border:1px solid var(--border);margin-bottom:2rem;">

    <img src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
         style="width:60px;height:60px;object-fit:cover;border-radius:8px;">

    <div>
        <h3 style="margin:0;"><?= htmlspecialchars($artist['artist_name']) ?></h3>
        <small style="color:#888;">Manage Setlists</small>
    </div>

</div>

<!-- ADD FORM -->
<div style="background:#111827;padding:20px;border-radius:10px;margin-bottom:2rem;">

<form method="POST">

    <input type="hidden" name="action" value="add">

    <label>Title</label>
    <input type="text" name="title"
           style="width:100%;padding:.7rem;margin-bottom:10px;" required>

    <label>Location</label>
    <input type="text" name="location"
           style="width:100%;padding:.7rem;margin-bottom:10px;">

    <label>Date</label>
    <input type="date" name="event_date"
           style="width:100%;padding:.7rem;margin-bottom:10px;">

    <label>Description</label>
    <textarea name="description"
              style="width:100%;padding:.7rem;margin-bottom:10px;"></textarea>

    <button class="btn" style="width:100%;">Add Setlist</button>

</form>

</div>

<!-- LIST -->
<?php if (empty($setlists)): ?>
<p style="text-align:center;color:#888;">No setlists found.</p>
<?php endif; ?>

<div style="
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:15px;
">

<?php foreach ($setlists as $set): ?>

<div style="background:#111827;border:1px solid var(--border);border-radius:10px;padding:15px;">

    <h3 style="margin:0 0 8px;">
        <?= htmlspecialchars($set['title']) ?>
    </h3>

    <p style="margin:0;color:#aaa;font-size:14px;">
        📍 <?= htmlspecialchars($set['location']) ?>
    </p>

    <p style="margin:5px 0;color:#aaa;font-size:14px;">
        📅 <?= htmlspecialchars($set['event_date']) ?>
    </p>

    <p style="margin-top:10px;color:#ddd;font-size:14px;">
        <?= nl2br(htmlspecialchars($set['description'])) ?>
    </p>

    <!-- ACTIONS FIXED -->
    <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:10px;">

        <!-- EDIT -->
        <a href="edit-setlist.php?id=<?= $set['setlist_id'] ?>&artist_id=<?= $artist_id ?>"
           class="btn green"
           style="padding:6px 10px;font-size:13px;">
            Edit
        </a>

        <!-- DELETE -->
        <form method="POST"
              onsubmit="return confirm('Delete this setlist?');"
              style="margin:0;">

            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $set['setlist_id'] ?>">

            <button class="btn red"
                    style="padding:6px 10px;font-size:13px;">
                Delete
            </button>

        </form>

    </div>

</div>

<?php endforeach; ?>

</div>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
