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

        /* ---------------- ADD ---------------- */
        if ($action === 'add') {

            $rating       = trim($_POST['rating']);
            $title        = trim($_POST['title']);
            $uploaded_by  = trim($_POST['uploaded_by']);
            $review_date  = trim($_POST['review_date']);
            $description  = trim($_POST['description']);

            if ($rating == '' || $title == '' || $uploaded_by == '' || $review_date == '' || $description == '') {
                throw new Exception("Please complete all fields.");
            }

            $stmt = $pdo->prepare("
                INSERT INTO reviews
                (artist_id,rating,title,uploaded_by,review_date,description)
                VALUES (?,?,?,?,?,?)
            ");

            $stmt->execute([
                $artist_id,
                $rating,
                $title,
                $uploaded_by,
                $review_date,
                $description
            ]);

            $_SESSION['success'] = "Review added successfully.";
        }

        /* ---------------- DELETE ---------------- */
        if ($action == 'delete') {

            $review_id = (int)$_POST['review_id'];

            $stmt = $pdo->prepare("DELETE FROM reviews WHERE review_id=?");
            $stmt->execute([$review_id]);

            $_SESSION['success'] = "Review deleted.";
        }

        header("Location: manage-reviews.php?artist_id=".$artist_id);
        exit;

    } catch(Exception $e){
        $_SESSION['error']=$e->getMessage();
        header("Location: manage-reviews.php?artist_id=".$artist_id);
        exit;
    }
}

/* --------------------------------------------------
   FETCH REVIEWS
-------------------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM reviews WHERE artist_id=? ORDER BY review_id DESC");
$stmt->execute([$artist_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main style="max-width:1100px;margin:auto;">

<h1 style="margin-bottom:20px;">Manage Reviews</h1>

<div style="display:flex;align-items:center;gap:15px;background:var(--card);padding:20px;border-radius:10px;margin-bottom:30px;">

<?php if($artist['artist_image']){ ?>
<img src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
style="width:70px;height:70px;border-radius:10px;object-fit:cover;">
<?php } ?>

<div>
<h2 style="margin:0;"><?= htmlspecialchars($artist['artist_name']) ?></h2>
<small>Manage Artist Reviews</small>
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
<i class="fas fa-plus"></i> Add Review
</button>
</div>

<div style="overflow:auto;">

<table style="width:100%;border-collapse:collapse;">

<thead>
<tr style="background:#111827;">
<th style="padding:12px;">Rating</th>
<th style="padding:12px;">Title</th>
<th style="padding:12px;">Uploaded By</th>
<th style="padding:12px;">Date</th>
<th style="padding:12px;">Description</th>
<th style="padding:12px;">Action</th>
</tr>
</thead>

<tbody>

<?php if(empty($reviews)){ ?>
<tr>
<td colspan="6" style="padding:20px;text-align:center;">No reviews added.</td>
</tr>
<?php } ?>

<?php foreach($reviews as $review){ ?>
<tr style="border-bottom:1px solid var(--border);">

<td style="padding:12px;">
<i class="fas fa-star" style="color:#facc15;"></i>
<?= htmlspecialchars($review['rating']) ?>
</td>

<td style="padding:12px;"><?= htmlspecialchars($review['title']) ?></td>
<td style="padding:12px;"><?= htmlspecialchars($review['uploaded_by']) ?></td>
<td style="padding:12px;"><?= htmlspecialchars($review['review_date']) ?></td>

<td style="padding:12px;max-width:250px;">
<?= htmlspecialchars(mb_strimwidth($review['description'],0,70,"...")) ?>
</td>

<!-- ACTIONS FIXED -->
<td style="padding:12px;white-space:nowrap;">

<a href="edit-review.php?id=<?= $review['review_id'] ?>"
   class="btn green"
   style="padding:6px 10px;font-size:13px;">
    Edit
</a>

<form method="POST"
      onsubmit="return confirm('Delete this review?');"
      style="display:inline-block;margin-left:5px;">

    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">

    <button class="btn red" style="padding:6px 10px;font-size:13px;">
        Delete
    </button>

</form>

</td>

</tr>
<?php } ?>

</tbody>
</table>
</div>

<!-- MODAL (SCROLLABLE FIX) -->
<div id="reviewModal"
style="display:none;position:fixed;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.7);overflow-y:auto;padding:20px;box-sizing:border-box;z-index:9999;">

<div style="background:#111827;max-width:600px;margin:40px auto;padding:25px;border-radius:10px;max-height:calc(100vh - 80px);overflow-y:auto;">

<h2>Add Review</h2>

<form method="POST">

<input type="hidden" name="action" value="add">

<label>Rating</label>
<input type="number" name="rating" step="0.1" min="0" max="5" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Title</label>
<input type="text" name="title" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Uploaded By</label>
<input type="text" name="uploaded_by" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Date</label>
<input type="date" name="review_date" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Description</label>
<textarea name="description" rows="6" required style="width:100%;padding:10px;margin:10px 0 20px;"></textarea>

<button class="btn" style="width:100%;">
<i class="fas fa-save"></i> Save Review
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
    document.getElementById('reviewModal').style.display='block';
    document.body.style.overflow='hidden';
}

function closeModal(){
    document.getElementById('reviewModal').style.display='none';
    document.body.style.overflow='auto';
}

document.getElementById('reviewModal').addEventListener('click', function(e){
    if(e.target === this){
        closeModal();
    }
});
</script>

</main>

<?php require_once __DIR__.'/inc/footer.php'; ?>
