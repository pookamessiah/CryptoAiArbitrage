<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

/*--------------------------------------------------
GET ARTIST
---------------------------------------------------*/
$artist_id = (int)($_GET['artist_id'] ?? 0);

if ($artist_id <= 0) {
    $_SESSION['error'] = "Invalid artist.";
    header("Location: manage-artists.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM artists WHERE artist_id=?");
$stmt->execute([$artist_id]);
$artist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artist) {
    $_SESSION['error'] = "Artist not found.";
    header("Location: manage-artists.php");
    exit;
}

/*--------------------------------------------------
ADD / DELETE
---------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    try {

        $action = $_POST['action'] ?? '';

        /*---------------- ADD ----------------*/
        if ($action == 'add') {

            $name = trim($_POST['v_artist_name']);
            $link = trim($_POST['v_artist_link']);

            if ($name == '') {
                throw new Exception("Artist name is required.");
            }

            $imageName = '';

            if (!empty($_FILES['v_artist_image']['name'])) {

                $uploadDir = __DIR__ . "/../uploads/fans-also-viewed/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $imageName = time() . "_" . basename($_FILES['v_artist_image']['name']);

                move_uploaded_file(
                    $_FILES['v_artist_image']['tmp_name'],
                    $uploadDir . $imageName
                );
            }

            $stmt = $pdo->prepare("
                INSERT INTO fans_also_viewed
                (artist_id,v_artist_name,v_artist_image,v_artist_link)
                VALUES (?,?,?,?)
            ");

            $stmt->execute([
                $artist_id,
                $name,
                $imageName,
                $link
            ]);

            $_SESSION['success'] = "Artist added successfully.";
        }

        /*---------------- DELETE ----------------*/
        if ($action == 'delete') {

            $fav_id = (int)$_POST['fav_id'];

            $stmt = $pdo->prepare("
                SELECT v_artist_image
                FROM fans_also_viewed
                WHERE fav_id=?
            ");

            $stmt->execute([$fav_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['v_artist_image']) {
                $file = __DIR__ . "/../uploads/fans-also-viewed/" . $row['v_artist_image'];
                if (file_exists($file)) unlink($file);
            }

            $stmt = $pdo->prepare("
                DELETE FROM fans_also_viewed
                WHERE fav_id=?
            ");

            $stmt->execute([$fav_id]);

            $_SESSION['success'] = "Artist removed.";
        }

        header("Location: manage-fans-also-viewed.php?artist_id=".$artist_id);
        exit;

    } catch(Exception $e){
        $_SESSION['error']=$e->getMessage();
        header("Location: manage-fans-also-viewed.php?artist_id=".$artist_id);
        exit;
    }
}

/*--------------------------------------------------
FETCH RECORDS
---------------------------------------------------*/
$stmt = $pdo->prepare("
SELECT *
FROM fans_also_viewed
WHERE artist_id=?
ORDER BY fav_id DESC
");

$stmt->execute([$artist_id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main style="max-width:1100px;margin:auto;">

<h1 style="margin-bottom:25px;">Fans Also Viewed</h1>

<!-- Artist -->
<div style="display:flex;align-items:center;gap:15px;background:var(--card);padding:20px;border-radius:10px;margin-bottom:30px;">

<?php if($artist['artist_image']){ ?>
<img src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
style="width:70px;height:70px;border-radius:10px;object-fit:cover;">
<?php } ?>

<div>
<h2 style="margin:0;"><?= htmlspecialchars($artist['artist_name']) ?></h2>
<small>Manage Related Artists</small>
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
<i class="fas fa-plus"></i> Add Artist
</button>
</div>

<div style="overflow:auto;">

<table style="width:100%;border-collapse:collapse;">

<thead>
<tr style="background:#111827;">
<th style="padding:12px;">Image</th>
<th style="padding:12px;">Artist</th>
<th style="padding:12px;">Link</th>
<th style="padding:12px;">Action</th>
</tr>
</thead>

<tbody>

<?php if(empty($records)){ ?>
<tr>
<td colspan="4" style="padding:20px;text-align:center;">No records found.</td>
</tr>
<?php } ?>

<?php foreach($records as $row){ ?>
<tr style="border-bottom:1px solid var(--border);">

<td style="padding:12px;">
<?php if($row['v_artist_image']){ ?>
<img src="../uploads/fans-also-viewed/<?= htmlspecialchars($row['v_artist_image']) ?>"
style="width:60px;height:60px;border-radius:8px;object-fit:cover;">
<?php } ?>
</td>

<td style="padding:12px;">
<?= htmlspecialchars($row['v_artist_name']) ?>
</td>

<td style="padding:12px;max-width:300px;word-break:break-all;">
<?= htmlspecialchars($row['v_artist_link']) ?>
</td>

<!-- ACTIONS FIXED -->
<td style="padding:12px;white-space:nowrap;">

<a href="edit-fans-also-viewed.php?id=<?= $row['fav_id'] ?>"
   class="btn green"
   style="padding:6px 10px;font-size:13px;">
    Edit
</a>

<form method="POST"
      onsubmit="return confirm('Delete this artist?');"
      style="display:inline-block;margin-left:5px;">

    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="fav_id" value="<?= $row['fav_id'] ?>">

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
<div id="modal"
style="display:none;position:fixed;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.7);overflow-y:auto;padding:20px;box-sizing:border-box;z-index:9999;">

<div style="background:#111827;max-width:550px;margin:40px auto;padding:25px;border-radius:10px;max-height:calc(100vh - 80px);overflow-y:auto;">

<h2>Add Related Artist</h2>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="action" value="add">

<label>Artist Name</label>
<input type="text" name="v_artist_name" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Artist Image</label>
<input type="file" name="v_artist_image" required style="width:100%;margin:10px 0 20px;">

<label>Artist Link</label>
<input type="text" name="v_artist_link" style="width:100%;padding:10px;margin:10px 0 20px;">

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
    document.body.style.overflow='hidden';
}

function closeModal(){
    document.getElementById('modal').style.display='none';
    document.body.style.overflow='auto';
}

document.getElementById('modal').addEventListener('click', function(e){
    if(e.target === this) closeModal();
});
</script>

</main>

<?php require_once __DIR__.'/inc/footer.php'; ?>
