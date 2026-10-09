<?php
session_start();

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/inc/header.php';

$concert_id = isset($_GET['concert_id']) ? (int)$_GET['concert_id'] : 0;

if(!$concert_id){
    $_SESSION['error'] = "Concert not found.";
    header("Location: manage-concerts.php");
    exit;
}

/*----------------------------------------------------
FETCH CONCERT
----------------------------------------------------*/
$stmt = $pdo->prepare("
SELECT *
FROM concerts
WHERE concert_id=?
");
$stmt->execute([$concert_id]);
$concert = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$concert){
    $_SESSION['error'] = "Concert not found.";
    header("Location: manage-concerts.php");
    exit;
}

/*----------------------------------------------------
HANDLE UPDATE
----------------------------------------------------*/
if($_SERVER['REQUEST_METHOD'] === "POST"){

    try{

        $date = $_POST['concert_date'];
        $day_time = trim($_POST['day_time']);
        $venue = trim($_POST['venue']);
        $location = trim($_POST['location']);
        $title = trim($_POST['title']);
        $index_type = $_POST['index_type'];

        if(
            empty($date) ||
            empty($day_time) ||
            empty($venue) ||
            empty($location) ||
            empty($title)
        ){
            throw new Exception("All fields are required.");
        }

        /*----------------------------------------------------
        HANDLE MAP UPLOAD
        ----------------------------------------------------*/
        $map_view = $concert['map_view'];

        if(
            isset($_FILES['map_view']) &&
            $_FILES['map_view']['error'] === UPLOAD_ERR_OK
        ){

            $uploadDir = "../uploads/concerts/";

            if(!is_dir($uploadDir)){
                mkdir($uploadDir,0755,true);
            }

            $ext = strtolower(pathinfo($_FILES['map_view']['name'], PATHINFO_EXTENSION));

            $map_view = uniqid("map_").".".$ext;

            move_uploaded_file(
                $_FILES['map_view']['tmp_name'],
                $uploadDir.$map_view
            );
        }

        /*----------------------------------------------------
        UPDATE QUERY
        ----------------------------------------------------*/
        $stmt = $pdo->prepare("
        UPDATE concerts
        SET
            concert_date=?,
            day_time=?,
            venue=?,
            location=?,
            title=?,
            index_type=?,
            map_view=?
        WHERE concert_id=?
        ");

        $stmt->execute([
            $date,
            $day_time,
            $venue,
            $location,
            $title,
            $index_type,
            $map_view,
            $concert_id
        ]);

        $_SESSION['success'] = "Concert updated.";

        header("Location: manage-concerts.php?artist_id=".$concert['artist_id']);
        exit;

    }catch(Exception $e){

        $_SESSION['error'] = $e->getMessage();

    }
}

?>

<main style="max-width:900px;margin:auto;">

<h1 style="margin-bottom:20px;">Edit Concert</h1>

<?php if(isset($_SESSION['error'])){ ?>
<div style="background:#f85149;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;">
<?= $_SESSION['error']; unset($_SESSION['error']); ?>
</div>
<?php } ?>

<form method="POST" enctype="multipart/form-data" style="
background:var(--card);
padding:25px;
border-radius:10px;
">

<label>Date</label>
<input type="date" name="concert_date" value="<?= htmlspecialchars($concert['concert_date']) ?>" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Day / Time</label>
<input type="text" name="day_time" value="<?= htmlspecialchars($concert['day_time']) ?>" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Venue</label>
<input type="text" name="venue" value="<?= htmlspecialchars($concert['venue']) ?>" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Location</label>
<input type="text" name="location" value="<?= htmlspecialchars($concert['location']) ?>" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Title</label>
<input type="text" name="title" value="<?= htmlspecialchars($concert['title']) ?>" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Display Section</label>
<select name="index_type" required style="width:100%;padding:10px;margin:10px 0 20px;">
    <option value="upcoming" <?= $concert['index_type']=="upcoming"?"selected":"" ?>>Upcoming</option>
    <option value="trending" <?= $concert['index_type']=="trending"?"selected":"" ?>>Trending Searches</option>
    <option value="sponsored" <?= $concert['index_type']=="sponsored"?"selected":"" ?>>Sponsored Presales & Offers</option>
</select>

<label>Venue Map (optional)</label>

<?php if(!empty($concert['map_view'])){ ?>
    <div style="margin-bottom:10px;">
        <img src="../uploads/concerts/<?= htmlspecialchars($concert['map_view']) ?>"
             style="width:120px;height:120px;object-fit:cover;border-radius:8px;">
    </div>
<?php } ?>

<input type="file" name="map_view" accept="image/*" style="width:100%;padding:10px;margin:10px 0 20px;">

<button class="btn" style="width:100%;">
    <i class="fas fa-save"></i> Save Changes
</button>

</form>

</main>

<?php require_once __DIR__.'/inc/footer.php'; ?>
