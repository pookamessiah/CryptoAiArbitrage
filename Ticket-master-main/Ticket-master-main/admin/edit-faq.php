<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

/* --------------------------------------------------
   GET FAQ ID
-------------------------------------------------- */
$faq_id = (int)($_GET['faq_id'] ?? 0);

if ($faq_id <= 0) {
    $_SESSION['error'] = "Invalid FAQ ID.";
    header("Location: manage-faqs.php");
    exit;
}

/* --------------------------------------------------
   FETCH FAQ
-------------------------------------------------- */
try {

   $stmt = $pdo->prepare("SELECT * FROM faqs WHERE faq_id = ?");
   $stmt->execute([$faq_id]);
   $faq = $stmt->fetch(PDO::FETCH_ASSOC);
   
   if (!$faq) {
       $_SESSION['error'] = "FAQ not found.";
       header("Location: manage-artists.php");
       exit;
   }

} catch (PDOException $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: manage-faqs.php");
    exit;
}

/* --------------------------------------------------
   HANDLE UPDATE
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $question = trim($_POST['question'] ?? '');
        $answer   = trim($_POST['answer'] ?? '');

        if ($question === '' || $answer === '') {
            throw new Exception("Question and Answer are required.");
        }

        $stmt = $pdo->prepare("
            UPDATE faqs
            SET question = ?, answer = ?
            WHERE faq_id = ?
        ");

        $stmt->execute([
            $question,
            $answer,
            $faq_id
        ]);

        $_SESSION['success'] = "FAQ updated successfully.";
        header("Location: manage-faqs.php?artist_id=" . $faq['artist_id']);
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: edit-faq.php?faq_id=" . $faq_id);
        exit;
    }
}
?>

<main style="max-width:700px;margin:2rem auto;">

<h1 style="text-align:center;margin-bottom:2rem;">Edit FAQ</h1>

<form method="POST"
      style="background:var(--card);padding:2rem;border-radius:10px;border:1px solid var(--border);">

    <!-- QUESTION -->
    <label>Question</label>
    <input type="text"
           name="question"
           value="<?= htmlspecialchars($faq['question']) ?>"
           required
           style="width:100%;padding:.7rem;margin-bottom:1rem;">

    <!-- ANSWER -->
    <label>Answer</label>
    <textarea name="answer"
              rows="6"
              required
              style="width:100%;padding:.7rem;margin-bottom:1.5rem;"><?= htmlspecialchars($faq['answer']) ?></textarea>

    <!-- BUTTONS -->
    <div style="display:flex;gap:10px;">

        <button type="submit" class="btn" style="flex:1;">
            <i class="fas fa-save"></i> Save Changes
        </button>

        <a href="manage-faqs.php?artist_id=<?= $faq['artist_id'] ?>"
           class="btn red"
           style="flex:1;text-align:center;display:inline-block;">
            Cancel
        </a>

    </div>

</form>

</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
