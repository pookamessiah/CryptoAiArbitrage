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
try {

    $stmt = $pdo->prepare("SELECT * FROM artists WHERE artist_id = ?");
    $stmt->execute([$artist_id]);

    $artist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artist) {
        $_SESSION['error'] = "Artist not found.";
        header("Location: manage-artists.php");
        exit;
    }

} catch(PDOException $e){

    $_SESSION['error'] = $e->getMessage();
    header("Location: manage-artists.php");
    exit;

}

/* --------------------------------------------------
   HANDLE ACTIONS
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $action = $_POST['action'] ?? '';

        /* ---------------- ADD FAQ ---------------- */

        if ($action === 'add') {

            $question = trim($_POST['question'] ?? '');
            $answer   = trim($_POST['answer'] ?? '');

            if ($question == '' || $answer == '') {
                throw new Exception("Question and Answer are required.");
            }

            $stmt = $pdo->prepare("
                INSERT INTO faqs
                (artist_id, question, answer)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $artist_id,
                $question,
                $answer
            ]);

            $_SESSION['success'] = "FAQ added successfully.";

            header("Location: manage-faqs.php?artist_id=".$artist_id);
            exit;
        }

        /* ---------------- DELETE FAQ ---------------- */

        if ($action == 'delete') {

            $faq_id = (int)$_POST['faq_id'];

            $stmt = $pdo->prepare("
                DELETE FROM faqs
                WHERE faq_id=?
            ");

            $stmt->execute([$faq_id]);

            $_SESSION['success']="FAQ deleted successfully.";

            header("Location: manage-faqs.php?artist_id=".$artist_id);
            exit;
        }

    } catch(Exception $e){

        $_SESSION['error']=$e->getMessage();

        header("Location: manage-faqs.php?artist_id=".$artist_id);
        exit;
    }
}

/* --------------------------------------------------
   FETCH FAQS
-------------------------------------------------- */

$stmt = $pdo->prepare("
SELECT *
FROM faqs
WHERE artist_id=?
ORDER BY faq_id DESC
");

$stmt->execute([$artist_id]);

$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<main style="max-width:1100px;margin:auto;">

<h1 style="margin-bottom:20px;">Manage FAQs</h1>

<div style="display:flex;align-items:center;gap:15px;background:var(--card);padding:20px;border-radius:10px;margin-bottom:30px;">

    <?php if($artist['artist_image']){ ?>

        <img src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
             style="width:70px;height:70px;border-radius:10px;object-fit:cover;">

    <?php } ?>

    <div>

        <h2 style="margin:0;">
            <?= htmlspecialchars($artist['artist_name']) ?>
        </h2>

        <small>Manage Artist FAQs</small>

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
        <i class="fas fa-plus"></i> Add FAQ
    </button>
</div>

<div style="overflow:auto;">

<table style="width:100%;border-collapse:collapse;">

<thead>

<tr style="background:#111827;">

<th style="padding:12px;">Question</th>
<th style="padding:12px;">Answer</th>
<th style="padding:12px;width:160px;">Action</th>

</tr>

</thead>

<tbody>

<?php if(empty($faqs)){ ?>
<tr>
<td colspan="3" style="padding:20px;text-align:center;">
No FAQs added.
</td>
</tr>
<?php } ?>

<?php foreach($faqs as $faq){ ?>

<tr style="border-bottom:1px solid var(--border);">

<td style="padding:15px;">
<?= htmlspecialchars($faq['question']) ?>
</td>

<td style="padding:15px;">
<?= htmlspecialchars(mb_strimwidth($faq['answer'],0,120,'...')) ?>
</td>

<!-- ACTIONS FIXED -->
<td style="padding:15px;white-space:nowrap;">

    <!-- EDIT -->
    <a href="edit-faq.php?faq_id=<?= $faq['faq_id'] ?>&artist_id=<?= $artist_id ?>"
       class="btn green"
       style="padding:6px 10px;font-size:13px;">
        Edit
    </a>

    <!-- DELETE -->
    <form method="POST"
          onsubmit="return confirm('Delete this FAQ?');"
          style="display:inline-block;margin-left:5px;">

        <input type="hidden" name="action" value="delete">

        <input type="hidden"
               name="faq_id"
               value="<?= $faq['faq_id'] ?>">

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

<!-- MODAL (SCROLL SAFE) -->
<div id="faqModal"
style="
display:none;
position:fixed;
left:0;
top:0;
width:100%;
height:100%;
background:rgba(0,0,0,.7);
overflow-y:auto;
padding:20px;
box-sizing:border-box;
z-index:9999;
">

<div style="
background:#111827;
max-width:600px;
margin:40px auto;
padding:25px;
border-radius:10px;
max-height:calc(100vh - 80px);
overflow-y:auto;
box-sizing:border-box;
">

<h2>Add FAQ</h2>

<form method="POST">

<input type="hidden" name="action" value="add">

<label>Question</label>
<input type="text"
name="question"
required
style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Answer</label>
<textarea
name="answer"
required
rows="6"
style="width:100%;padding:10px;margin:10px 0 20px;"></textarea>

<button class="btn" style="width:100%;">
<i class="fas fa-save"></i>
Save FAQ
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
    document.getElementById('faqModal').style.display='block';
    document.body.style.overflow='hidden';
}

function closeModal(){
    document.getElementById('faqModal').style.display='none';
    document.body.style.overflow='auto';
}

document.getElementById('faqModal').addEventListener('click', function(e){
    if(e.target === this){
        closeModal();
    }
});

</script>

</main>

<?php require_once __DIR__.'/inc/footer.php'; ?>
