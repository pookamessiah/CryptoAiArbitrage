<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

/* --------------------------------------------------
   GET REVIEW ID
-------------------------------------------------- */
$review_id = (int)($_GET['id'] ?? 0);

if ($review_id <= 0) {
    $_SESSION['error'] = "Invalid review ID.";
    header("Location: manage-artists.php");
    exit;
}

/* --------------------------------------------------
   FETCH REVIEW
-------------------------------------------------- */
try {

    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        $_SESSION['error'] = "Review not found.";
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

        $rating       = trim($_POST['rating'] ?? '');
        $title        = trim($_POST['title'] ?? '');
        $uploaded_by  = trim($_POST['uploaded_by'] ?? '');
        $review_date  = trim($_POST['review_date'] ?? '');
        $description  = trim($_POST['description'] ?? '');

        if (
            $rating === '' ||
            $title === '' ||
            $uploaded_by === '' ||
            $review_date === '' ||
            $description === ''
        ) {
            throw new Exception("All fields are required.");
        }

        $stmt = $pdo->prepare("
            UPDATE reviews
            SET rating = ?,
                title = ?,
                uploaded_by = ?,
                review_date = ?,
                description = ?
            WHERE review_id = ?
        ");

        $stmt->execute([
            $rating,
            $title,
            $uploaded_by,
            $review_date,
            $description,
            $review_id
        ]);

        $_SESSION['success'] = "Review updated successfully.";

        header("Location: manage-reviews.php?artist_id=" . $review['artist_id']);
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: edit-review.php?id=" . $review_id);
        exit;
    }
}
?>

<main style="max-width:700px;margin:2rem auto;">

<h1 style="text-align:center;margin-bottom:2rem;">Edit Review</h1>

<div style="background:var(--card);padding:2rem;border-radius:10px;border:1px solid var(--border);">

<form method="POST">

    <!-- RATING -->
    <label>Rating</label>
    <input type="number"
           name="rating"
           step="0.1"
           min="0"
           max="5"
           required
           value="<?= htmlspecialchars($review['rating']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <!-- TITLE -->
    <label>Title</label>
    <input type="text"
           name="title"
           required
           value="<?= htmlspecialchars($review['title']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <!-- UPLOADED BY -->
    <label>Uploaded By</label>
    <input type="text"
           name="uploaded_by"
           required
           value="<?= htmlspecialchars($review['uploaded_by']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <!-- DATE -->
    <label>Review Date</label>
    <input type="date"
           name="review_date"
           required
           value="<?= htmlspecialchars($review['review_date']) ?>"
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <!-- DESCRIPTION -->
    <label>Description</label>
    <textarea name="description"
              rows="6"
              required
              style="width:100%;padding:.7rem;margin-bottom:1.5rem;"><?= htmlspecialchars($review['description']) ?></textarea>

    <!-- BUTTON -->
    <button type="submit" class="btn" style="width:100%;">
        <i class="fas fa-save"></i> Save Changes
    </button>

</form>

</div>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
