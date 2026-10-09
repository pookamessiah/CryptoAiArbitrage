<?php
session_start();

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/inc/header.php';

/*----------------------------------------------------
GET ARTIST
----------------------------------------------------*/

$artist_id = isset($_GET['artist_id']) ? (int)$_GET['artist_id'] : 0;
$artist = null;

/*----------------------------------------------------
FETCH ARTISTS FOR DROPDOWN
----------------------------------------------------*/

$stmt = $pdo->query("
SELECT artist_id,artist_name
FROM artists
ORDER BY artist_name
");

$artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*----------------------------------------------------
IF ARTIST SELECTED
----------------------------------------------------*/

if($artist_id){

    $stmt=$pdo->prepare("
    SELECT *
    FROM artists
    WHERE artist_id=?
    ");

    $stmt->execute([$artist_id]);

    $artist=$stmt->fetch(PDO::FETCH_ASSOC);

    if(!$artist){
        $_SESSION['error']="Artist not found.";
        header("Location: manage-concerts.php");
        exit;
    }
}

/*----------------------------------------------------
HANDLE POST
----------------------------------------------------*/

if($_SERVER['REQUEST_METHOD']=="POST"){

    try{

        $action=$_POST['action'];

        $artist_id=(int)$_POST['artist_id'];

        if($action=="add"){

            $date=$_POST['concert_date'];
            $day_time=trim($_POST['day_time']);
            $venue=trim($_POST['venue']);
            $location=trim($_POST['location']);
            $title=trim($_POST['title']);
            $index_type = $_POST['index_type'] ?? '';

            if(
                empty($date) ||
                empty($day_time) ||
                empty($venue) ||
                empty($location) ||
                empty($title)
            ){
                throw new Exception("All fields are required.");
            }
            
            if(!in_array($index_type, ['upcoming','trending','sponsored'])){
                throw new Exception("Invalid event type.");
            }

            $map_view = null;
            
            if(
                isset($_FILES['map_view']) &&
                $_FILES['map_view']['error'] === UPLOAD_ERR_OK
            ){
            
                $uploadDir = "../uploads/concerts/";
            
                if(!is_dir($uploadDir)){
                    mkdir($uploadDir,0755,true);
                }
            
                $extension = strtolower(
                    pathinfo(
                        $_FILES['map_view']['name'],
                        PATHINFO_EXTENSION
                    )
                );
            
                $map_view = uniqid("map_").".".$extension;
            
                move_uploaded_file(
                    $_FILES['map_view']['tmp_name'],
                    $uploadDir.$map_view
                );
            }

            $stmt=$pdo->prepare("
            INSERT INTO concerts
            (
                artist_id,
                concert_date,
                day_time,
                venue,
                location,
                title,
                index_type,
                map_view
            )
            VALUES
            (?,?,?,?,?,?,?,?)
            ");

            $stmt->execute([
                $artist_id,
                $date,
                $day_time,
                $venue,
                $location,
                $title,
                $index_type,
                $map_view
            ]);

            $_SESSION['success']="Concert added.";

        }

        if ($action == "universal_bulk_upload") {
            $bulk = trim($_POST['universal_bulk_data']);
            $artist_id = (int)$_POST['artist_id'];
        
            if (empty($bulk)) {
                throw new Exception("No upload data supplied.");
            }
        
            // 1. Split the data into Concerts Section and Tickets Section
            $sections = explode("&&&", $bulk);
            $concerts_raw = trim($sections[0] ?? '');
            $tickets_raw = trim($sections[1] ?? '');
        
            if (empty($concerts_raw)) {
                throw new Exception("Concert data section is missing.");
            }
        
            // 2. Isolate blocks by double spacing
            $concert_blocks = array_filter(array_map('trim', preg_split("/\R\s*\R/", $concerts_raw)));
            $ticket_blocks  = array_filter(array_map('trim', preg_split("/\R\s*\R/", $tickets_raw)));
        
            // Track relational mapping [User_Provided_Number => Real_Database_Concert_ID]
            $concert_id_mapping = [];
            $concert_count = 0;
            $ticket_count = 0;
        
            try {
                // START TRANSACTION: Keeps operations atomic and safe
                $pdo->beginTransaction();
        
                /*----------------------------------------------------
                PROCESS CONCERTS
                ----------------------------------------------------*/
                $stmt_concert = $pdo->prepare("
                    INSERT INTO concerts (artist_id, concert_date, day_time, venue, location, title, index_type, map_view)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NULL)
                ");
        
                foreach ($concert_blocks as $block) {
                    $lines = array_values(array_filter(array_map('trim', preg_split("/\R/", $block))));
                    
                    // Expected: Line 0 = Identifier Number, Lines 1 to 6 = Data fields (Total 7 lines)
                    if (count($lines) < 7) {
                        continue; 
                    }
        
                    $user_index = $lines[0]; // e.g. "1", "2"
                    $date       = $lines[1];
                    $day_time   = $lines[2];
                    $venue      = $lines[3];
                    $location   = $lines[4];
                    $title      = $lines[5];
                    $index_type = $lines[6];
        
                    if (!in_array($index_type, ['upcoming', 'trending', 'sponsored'])) {
                        $index_type = 'upcoming';
                    }
        
                    $stmt_concert->execute([$artist_id, $date, $day_time, $venue, $location, $title, $index_type]);
                    
                    // Map the user identifier to the generated Auto-Increment Key
                    $concert_id_mapping[$user_index] = $pdo->lastInsertId();
                    $concert_count++;
                }
        
                /*----------------------------------------------------
                PROCESS TICKETS
                ----------------------------------------------------*/
                if (!empty($ticket_blocks) && !empty($concert_id_mapping)) {
                    $stmt_ticket = $pdo->prepare("
                        INSERT INTO tickets (concert_id, ticket_name, section_name, row_name, seat_name, price, section_view)
                        VALUES (?, ?, ?, ?, ?, ?, NULL)
                    ");
        
                    foreach ($ticket_blocks as $block) {
                        $lines = array_values(array_filter(array_map('trim', preg_split("/\R/", $block))));
                        
                        // Expected: Line 0 = Mapping Number, Lines 1 to 5 = Data fields (Total 6 lines)
                        if (count($lines) < 6) {
                            continue;
                        }
        
                        $mapped_index = $lines[0]; // e.g. "1", "2"
                        
                        // If this ticket points to a concert mapping that failed or doesn't exist, skip it
                        if (!isset($concert_id_mapping[$mapped_index])) {
                            continue;
                        }
        
                        $real_concert_id = $concert_id_mapping[$mapped_index];
                        $ticket_name     = $lines[1];
                        $section_name    = $lines[2];
                        $row_name        = $lines[3];
                        $seat_name       = $lines[4];
                        
                        // Price extraction normalization
                        $price = str_replace(["$", ","], "", $lines[5]);
                        $price = (float)$price;
        
                        if ($price <= 0) {
                            continue; 
                        }
        
                        $stmt_ticket->execute([$real_concert_id, $ticket_name, $section_name, $row_name, $seat_name, $price]);
                        $ticket_count++;
                    }
                }
        
                // Complete execution securely
                $pdo->commit();
                $_SESSION['success'] = "Successfully loaded $concert_count concerts and $ticket_count tickets seamlessly.";
        
            } catch (Exception $e) {
                // Something broke; undo everything to prevent broken matching associations
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }

        if ($action == "bulk_delete_tickets") {
            $target_concert_id = (int)$_POST['target_concert_id'];
        
            if ($target_concert_id <= 0) {
                throw new Exception("Invalid concert selected.");
            }
        
            $stmt = $pdo->prepare("
                DELETE FROM tickets 
                WHERE concert_id = ?
            ");
            $stmt->execute([$target_concert_id]);
        
            $_SESSION['success'] = $stmt->rowCount() . " tickets deleted successfully.";
        }

        /*---------------- BULK MAP VIEW UPLOAD ----------------*/
        if($action == "bulk_map_upload"){
            
            $selected_concerts = $_POST['concert_ids'] ?? [];

            if(empty($selected_concerts) || !is_array($selected_concerts)){
                throw new Exception("Please select at least one concert.");
            }

            if(!isset($_FILES['bulk_map_view']) || $_FILES['bulk_map_view']['error'] !== UPLOAD_ERR_OK){
                throw new Exception("Please choose a valid venue map image.");
            }

            // File verification & saving
            $uploadDir = "../uploads/concerts/";
            if(!is_dir($uploadDir)){
                mkdir($uploadDir, 0755, true);
            }

            $extension = strtolower(pathinfo($_FILES['bulk_map_view']['name'], PATHINFO_EXTENSION));
            
            // Basic secure validation for image extensions
            if(!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){
                throw new Exception("Invalid file format. Only images are allowed.");
            }

            $map_filename = uniqid("map_bulk_") . "." . $extension;

            if(!move_uploaded_file($_FILES['bulk_map_view']['tmp_name'], $uploadDir . $map_filename)){
                throw new Exception("Failed to save the uploaded image folder file structure.");
            }

            // Construct prepared placeholders dynamically string for safe binding: (?, ?, ?)
            $placeholders = implode(',', array_fill(0, count($selected_concerts), '?'));
            
            // Build safe positional binding array parameters
            $params = array_merge([$map_filename], array_map('intval', $selected_concerts));

            $stmt = $pdo->prepare("
                UPDATE concerts 
                SET map_view = ? 
                WHERE concert_id IN ($placeholders)
            ");

            $stmt->execute($params);

            $_SESSION['success'] = "Venue map successfully applied to " . $stmt->rowCount() . " concert(s).";
        }

        if ($action == "clone_tickets") {
            $source_id = (int)$_POST['source_concert_id'];
            $target_id = (int)$_POST['target_concert_id'];
        
            if ($source_id === $target_id) {
                throw new Exception("Source and target concert cannot be the same.");
            }
        
            $stmt = $pdo->prepare("
                INSERT INTO tickets (concert_id, ticket_name, section_name, row_name, seat_name, price, section_view)
                SELECT ?, ticket_name, section_name, row_name, seat_name, price, section_view
                FROM tickets
                WHERE concert_id = ?
            ");
            $stmt->execute([$target_id, $source_id]);
        
            $_SESSION['success'] = $stmt->rowCount() . " tickets cloned successfully.";
        }

        if($action=="bulk_add_concerts"){
        
            $bulk = trim($_POST['bulk_data']);
        
            if(empty($bulk)){
                throw new Exception("No data provided.");
            }
        
            $blocks = preg_split("/\R\s*\R/", $bulk);
        
            $stmt = $pdo->prepare("
                INSERT INTO concerts
                (
                    artist_id,
                    concert_date,
                    day_time,
                    venue,
                    location,
                    title,
                    index_type,
                    map_view
                )
                VALUES (?,?,?,?,?,?,?,NULL)
            ");
        
            $count = 0;
        
            foreach($blocks as $block){
        
                $lines = array_values(array_filter(array_map('trim', preg_split("/\R/", trim($block)))));
        
                if(count($lines) < 6){
                    continue;
                }
        
                $date = $lines[0];
                $day_time = $lines[1];
                $venue = $lines[2];
                $location = $lines[3];
                $title = $lines[4];
                $index_type = $lines[5];
        
                if(!in_array($index_type, ['upcoming','trending','sponsored'])){
                    $index_type = 'upcoming';
                }
        
                $stmt->execute([
                    $artist_id,
                    $date,
                    $day_time,
                    $venue,
                    $location,
                    $title,
                    $index_type
                ]);
        
                $count++;
            }
        
            $_SESSION['success'] = "$count concerts uploaded successfully.";
        }

        if($action=="bulk_delete_concerts"){
        
            $stmt = $pdo->prepare("
                DELETE FROM concerts
                WHERE artist_id=?
            ");
        
            $stmt->execute([$artist_id]);
        
            $_SESSION['success'] =
                $stmt->rowCount() . " concerts deleted for this artist.";
        }

        if($action=="delete"){

            $concert_id=(int)$_POST['concert_id'];

            $stmt=$pdo->prepare("
            DELETE FROM concerts
            WHERE concert_id=?
            ");

            $stmt->execute([$concert_id]);

            $_SESSION['success']="Concert deleted.";

        }

        header("Location: manage-concerts.php?artist_id=".$artist_id);
        exit;

    }catch(Exception $e){

        $_SESSION['error']=$e->getMessage();

        header("Location: manage-concerts.php?artist_id=".$artist_id);
        exit;

    }

}

/*----------------------------------------------------
FETCH CONCERTS
----------------------------------------------------*/

$concerts=[];

if($artist_id){

$stmt=$pdo->prepare("
SELECT *
FROM concerts
WHERE artist_id=?
ORDER BY concert_date DESC
");

$stmt->execute([$artist_id]);

$concerts=$stmt->fetchAll(PDO::FETCH_ASSOC);

}

?>

<main style="max-width:1100px;margin:auto;">

<h1 style="margin-bottom:25px;">Manage Concerts</h1>

<?php
if(isset($_SESSION['success'])){
?>
<div style="background:#238636;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;">
<?= $_SESSION['success']; unset($_SESSION['success']);?>
</div>
<?php
}
?>

<?php
if(isset($_SESSION['error'])){
?>
<div style="background:#f85149;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;">
<?= $_SESSION['error']; unset($_SESSION['error']);?>
</div>
<?php
}
?>

<?php if(!$artist){ ?>

<div style="background:var(--card);padding:25px;border-radius:10px;max-width:600px;margin:auto;">

<h2>Select Artist</h2>

<form method="GET">

<select
name="artist_id"
required
style="width:100%;padding:12px;margin:20px 0;">

<option value="">Choose Artist</option>

<?php foreach($artists as $a){ ?>

<option value="<?= $a['artist_id'] ?>">

<?= htmlspecialchars($a['artist_name']) ?>

</option>

<?php } ?>

</select>

<button class="btn" style="width:100%;">

<i class="fas fa-arrow-right"></i>

Continue

</button>

</form>

</div>

<?php return; } ?>

<div style="display:flex;align-items:center;gap:15px;background:var(--card);padding:20px;border-radius:10px;margin-bottom:25px;">

<?php if($artist['artist_image']){ ?>

<img
src="../uploads/artists/<?= htmlspecialchars($artist['artist_image']) ?>"
style="width:70px;height:70px;border-radius:10px;object-fit:cover;">

<?php } ?>

<div>

<h2><?= htmlspecialchars($artist['artist_name']) ?></h2>

<small><?= htmlspecialchars($artist['genre']) ?></small>

</div>

</div>

<div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:20px;flex-wrap:wrap;">

<button class="btn" style="background:#0284c7; color:#fff;" onclick="openUniversalModal()">
    <i class="fas fa-layer-group"></i>
    Universal Tour Upload
</button>

<button class="btn" style="background:#eab308; color:#000;" onclick="openBulkMapModal()">
    <i class="fas fa-map-marked-alt"></i>
    Bulk Map View
</button>

<button class="btn" onclick="openBulkConcertUpload()">
    <i class="fas fa-upload"></i>
    Bulk Upload
</button>

<button class="btn red" onclick="openBulkConcertDelete()">
    <i class="fas fa-trash"></i>
    Bulk Delete
</button>

<button class="btn red" onclick="openBulkTicketDeleteModal()">
    <i class="fas fa-trash-alt"></i> Bulk Delete Tickets
</button>

<button class="btn" onclick="openModal()">
    <i class="fas fa-plus"></i>
    Add Concert
</button>

<button class="btn" style="background:#8b5cf6;" onclick="openCloneModal()">
    <i class="fas fa-copy"></i> Clone Tickets
</button>

</div>

<div style="overflow:auto;">

<table style="width:100%;border-collapse:collapse;">

<thead>

<tr style="background:#111827;">

<th>Date</th>
<th>Day/Time</th>
<th>Venue</th>
<th>Location</th>
<th>Title</th>
<th>Section</th>
<th>Map View</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php if(empty($concerts)){ ?>

<tr>

<td colspan="7" style="padding:20px;text-align:center;">

No concerts found.

</td>

</tr>

<?php } ?>

<?php foreach($concerts as $concert){ ?>

<tr style="border-bottom:1px solid var(--border);">

<td style="padding:12px;">
<?= htmlspecialchars($concert['concert_date']) ?>
</td>

<td style="padding:12px;">
<?= htmlspecialchars($concert['day_time']) ?>
</td>

<td style="padding:12px;">
<?= htmlspecialchars($concert['venue']) ?>
</td>

<td style="padding:12px;">
<?= htmlspecialchars($concert['location']) ?>
</td>

<td style="padding:12px;">
<?= htmlspecialchars($concert['title']) ?>
</td>

<td style="padding:12px;">
    <?=
        htmlspecialchars(
            ucfirst($concert['index_type'])
        )
    ?>
</td>

<td style="padding:12px;text-align:center;">

<?php if(!empty($concert['map_view'])){ ?>

<img
src="../uploads/concerts/<?= htmlspecialchars($concert['map_view']) ?>"
style="width:60px;height:60px;object-fit:cover;border-radius:8px;">

<?php }else{ ?>

<span style="color:#888;">No Map</span>

<?php } ?>

</td>

<!-- ACTIONS FIXED -->
<td style="padding:12px;white-space:nowrap;">

    <a href="edit-concert.php?concert_id=<?= $concert['concert_id'] ?>"
       class="btn green"
       style="padding:6px 10px;font-size:13px;">
        Edit
    </a>

    <a href="manage-tickets.php?concert_id=<?= $concert['concert_id'] ?>"
       class="btn"
       style="padding:6px 10px;font-size:13px;background:#6f42c1;color:#fff;margin-left:5px;">
        Tickets
    </a>

    <form method="POST"
          onsubmit="return confirm('Delete this concert?');"
          style="display:inline-block;margin-left:5px;">

        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="artist_id" value="<?= $artist_id ?>">
        <input type="hidden" name="concert_id" value="<?= $concert['concert_id'] ?>">

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

<div
id="modal"
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

<div
style="
background:#111827;
max-width:650px;
margin:40px auto;
padding:25px;
border-radius:10px;
max-height:calc(100vh - 80px);
overflow-y:auto;
box-sizing:border-box;
">

<h2>Add Concert</h2>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="action" value="add">
<input type="hidden" name="artist_id" value="<?= $artist_id ?>">

<label>Date</label>
<input type="date" name="concert_date" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Day / Time</label>
<input type="text" name="day_time" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Venue</label>
<input type="text" name="venue" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Location</label>
<input type="text" name="location" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Title</label>
<input type="text" name="title" required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Display Section</label>
<select name="index_type" required style="width:100%;padding:10px;margin:10px 0 20px;">
    <option value="upcoming">Upcoming</option>
    <option value="trending">Trending Searches</option>
    <option value="sponsored">Sponsored Presales & Offers</option>
</select>

<label>Venue Map</label>
<input type="file" name="map_view" accept="image/*"
       style="width:100%;padding:10px;margin:10px 0 20px;">

<button class="btn" style="width:100%;">
    <i class="fas fa-save"></i>
    Save Concert
</button>

</form>

<br>

<button class="btn red" style="width:100%;" onclick="closeModal()">
Close
</button>

</div>
</div>

<div id="bulkUploadModal"
style="display:none;position:fixed;left:0;top:0;width:100%;height:100%;
background:rgba(0,0,0,.7);overflow-y:auto;padding:20px;z-index:9999;">

<div style="background:#111827;max-width:700px;margin:40px auto;padding:25px;border-radius:10px;">

<h2>Bulk Upload Concerts</h2>

<p>Format (one concert per block):</p>

<pre style="background:#000;padding:15px;color:#0f0;border-radius:6px;">
2026-12-01
Saturday 8:00 PM
Eko Convention Centre
Lagos, Nigeria
New Year Tour
trending

2026-12-10
Friday 7:00 PM
National Stadium
Abuja, Nigeria
Live Experience
upcoming
</pre>

<form method="POST">

<input type="hidden" name="action" value="bulk_add_concerts">
<input type="hidden" name="artist_id" value="<?= $artist_id ?>">

<textarea name="bulk_data"
required
style="width:100%;height:300px;padding:12px;"></textarea>

<br><br>

<button class="btn" style="width:100%;">
Upload Concerts
</button>

</form>

<br>

<button class="btn red" onclick="closeBulkConcertUpload()" style="width:100%;">
Close
</button>

</div>
</div>

<div id="bulkDeleteModal"
style="display:none;position:fixed;left:0;top:0;width:100%;height:100%;
background:rgba(0,0,0,.7);overflow-y:auto;padding:20px;z-index:9999;">

<div style="background:#111827;max-width:600px;margin:40px auto;padding:25px;border-radius:10px;">

<h2>Bulk Delete Concerts</h2>

<p style="color:#fca5a5;">
This will delete ALL concerts for this artist.
</p>

<form method="POST">

<input type="hidden" name="action" value="bulk_delete_concerts">
<input type="hidden" name="artist_id" value="<?= $artist_id ?>">

<button class="btn red" style="width:100%;">
Delete All Concerts
</button>

</form>

<br>

<button class="btn" onclick="closeBulkConcertDelete()" style="width:100%;">
Cancel
</button>

</div>
</div>

<div id="universalModal" style="display:none; position:fixed; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,.7); overflow-y:auto; padding:20px; z-index:9999;">
    <div style="background:#111827; max-width:750px; margin:20px auto; padding:25px; border-radius:10px; color:#fff;">
        
        <h2>Universal Tour & Inventory Upload</h2>
        <p style="color:#9ca3af; font-size:14px; margin-bottom:15px;">
            Paste your complete manifest structured with a <code>&&&</code> section breakout. Use numbering lines to associate tickets directly to target show records.
        </p>

        <form method="POST">
            <input type="hidden" name="action" value="universal_bulk_upload">
            <input type="hidden" name="artist_id" value="<?= $artist_id ?>">

            <textarea name="universal_bulk_data" required 
                placeholder="1&#10;2026-12-01&#10;8:00 PM&#10;Arena&#10;City&#10;Tour Title&#10;upcoming&#10;&#10;&amp;&amp;&amp;&#10;&#10;1&#10;Standard Ticket&#10;Sec A&#10;Row 1&#10;Seat 5&#10;$95.00"
                style="width:100%; height:380px; padding:12px; font-family:monospace; background:#030712; color:#34d399; border:1px solid #374151; border-radius:6px; resize:vertical;"></textarea>

            <br><br>
            <button class="btn" style="width:100%; background:#10b981; color:#fff; font-weight:bold; padding:12px;">
                Execute Master Upload
            </button>
        </form>

        <br>
        <button class="btn red" onclick="closeUniversalModal()" style="width:100%;">
            Cancel
        </button>
    </div>
</div>

<div id="bulkMapModal" style="display:none; position:fixed; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,.7); overflow-y:auto; padding:20px; z-index:9999;">
    <div style="background:#111827; max-width:650px; margin:40px auto; padding:25px; border-radius:10px; color:#fff;">
        
        <h2>Bulk Map View Upload</h2>
        <p style="color:#9ca3af; font-size:14px; margin-bottom:20px;">
            Upload an image map profile and check off all corresponding concert listings you want to map it to.
        </p>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="bulk_map_upload">
            <input type="hidden" name="artist_id" value="<?= $artist_id ?>">

            <label style="font-weight:bold; display:block; margin-bottom:8px;">1. Select Target Concerts</label>
            
            <div style="margin-bottom:10px; background:#1f2937; padding:8px 12px; border-radius:6px;">
                <label style="cursor:pointer; display:flex; align-items:center; gap:10px;">
                    <input type="checkbox" id="selectAllConcerts" onchange="toggleSelectAllConcerts(this)"> 
                    <strong>Select / Unselect All</strong>
                </label>
            </div>

            <div style="max-height:220px; overflow-y:auto; border:1px solid #374151; background:#030712; padding:10px; border-radius:6px; margin-bottom:20px;">
                <?php if(empty($concerts)){ ?>
                    <p style="color:#6b7280; text-align:center; padding:10px;">No concert listings available.</p>
                <?php } else { ?>
                    <?php foreach($concerts as $c){ ?>
                        <div style="padding:8px; border-bottom:1px solid #1f2937; display:flex; align-items:flex-start; gap:10px;">
                            <input type="checkbox" name="concert_ids[]" value="<?= $c['concert_id'] ?>" class="concert-bulk-cb" style="margin-top:4px;">
                            <label style="cursor:pointer; font-size:14px;">
                                <strong><?= htmlspecialchars($c['title']) ?></strong> <br>
                                <small style="color:#9ca3af;"><?= htmlspecialchars($c['venue']) ?> (<?= htmlspecialchars($c['concert_date']) ?>)</small>
                            </label>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>

            <label style="font-weight:bold; display:block; margin-bottom:8px;">2. Upload Venue Map Image</label>
            <input type="file" name="bulk_map_view" accept="image/*" required style="width:100%; padding:10px; margin-bottom:25px; background:#1f2937; border:1px solid #374151; border-radius:6px; color:#fff;">

            <button class="btn" style="width:100%; background:#eab308; color:#000; font-weight:bold; padding:12px;">
                <i class="fas fa-file-upload"></i> Apply Map View to Selections
            </button>
        </form>

        <br>
        <button class="btn red" onclick="closeBulkMapModal()" style="width:100%;">
            Cancel
        </button>
    </div>
</div>

<div id="cloneModal" style="display:none; position:fixed; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,.7); z-index:9999; padding:20px;">
    <div style="background:#111827; max-width:500px; margin:40px auto; padding:25px; border-radius:10px; color:#fff;">
        <h2>Clone Tickets</h2>
        <form method="POST">
            <input type="hidden" name="action" value="clone_tickets">
            <input type="hidden" name="artist_id" value="<?= $artist_id ?>">
            
            <label>Source Concert (Copy from)</label>
            <select name="source_concert_id" required style="width:100%; padding:10px; margin-bottom:15px;">
                <?php foreach($concerts as $c){ ?>
                    <option value="<?= $c['concert_id'] ?>"><?= htmlspecialchars($c['title']) ?> (<?= $c['concert_date'] ?>)</option>
                <?php } ?>
            </select>

            <label>Target Concert (Paste to)</label>
            <select name="target_concert_id" required style="width:100%; padding:10px; margin-bottom:15px;">
                <?php foreach($concerts as $c){ ?>
                    <option value="<?= $c['concert_id'] ?>"><?= htmlspecialchars($c['title']) ?> (<?= $c['concert_date'] ?>)</option>
                <?php } ?>
            </select>

            <button class="btn" style="width:100%; background:#8b5cf6;">Execute Clone</button>
        </form>
        <button class="btn red" style="width:100%; margin-top:10px;" onclick="closeCloneModal()">Cancel</button>
    </div>
</div>

<div id="bulkTicketDeleteModal" style="display:none; position:fixed; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,.7); z-index:9999; padding:20px;">
    <div style="background:#111827; max-width:500px; margin:40px auto; padding:25px; border-radius:10px; color:#fff;">
        <h2>Bulk Delete Tickets</h2>
        <p style="color:#fca5a5;">Warning: This will permanently remove all tickets for the selected concert.</p>
        
        <form method="POST" onsubmit="return confirm('Are you absolutely sure? This cannot be undone.');">
            <input type="hidden" name="action" value="bulk_delete_tickets">
            <input type="hidden" name="artist_id" value="<?= $artist_id ?>">
            
            <label>Select Concert to Clear</label>
            <select name="target_concert_id" required style="width:100%; padding:10px; margin:15px 0;">
                <?php foreach($concerts as $c){ ?>
                    <option value="<?= $c['concert_id'] ?>"><?= htmlspecialchars($c['title']) ?> (<?= $c['concert_date'] ?>)</option>
                <?php } ?>
            </select>

            <button class="btn red" style="width:100%;">Delete All Tickets for Concert</button>
        </form>
        <button class="btn" style="width:100%; margin-top:10px;" onclick="closeBulkTicketDeleteModal()">Cancel</button>
    </div>
</div>
    
<script>

function openModal(){
    document.getElementById("modal").style.display="block";
    document.body.style.overflow="hidden";
}

function closeModal(){
    document.getElementById("modal").style.display="none";
    document.body.style.overflow="auto";
}

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modal");

    modal.addEventListener("click", function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });
});

function openBulkConcertUpload(){
    document.getElementById("bulkUploadModal").style.display="block";
    document.body.style.overflow="hidden";
}

function closeBulkConcertUpload(){
    document.getElementById("bulkUploadModal").style.display="none";
    document.body.style.overflow="auto";
}

function openBulkConcertDelete(){
    document.getElementById("bulkDeleteModal").style.display="block";
    document.body.style.overflow="hidden";
}

function closeBulkConcertDelete(){
    document.getElementById("bulkDeleteModal").style.display="none";
    document.body.style.overflow="auto";
}

function openUniversalModal() {
    document.getElementById("universalModal").style.display = "block";
    document.body.style.overflow = "hidden";
}

function closeUniversalModal() {
    document.getElementById("universalModal").style.display = "none";
    document.body.style.overflow = "auto";
}

// Global Document overlay close helper listener
document.addEventListener("DOMContentLoaded", function () {
    const uniModal = document.getElementById("universalModal");
    if (uniModal) {
        uniModal.addEventListener("click", function (e) {
            if (e.target === uniModal) {
                closeUniversalModal();
            }
        });
    }
});

function openBulkMapModal() {
    document.getElementById("bulkMapModal").style.display = "block";
    document.body.style.overflow = "hidden";
}

function closeBulkMapModal() {
    document.getElementById("bulkMapModal").style.display = "none";
    document.body.style.overflow = "auto";
}

// Checkbox helper logic to mass-select or clear selections
function toggleSelectAllConcerts(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.concert-bulk-cb');
    checkboxes.forEach(cb => {
        cb.checked = masterCheckbox.checked;
    });
}

// Background overlay dynamic close trigger registration setup
document.addEventListener("DOMContentLoaded", function () {
    const mapModal = document.getElementById("bulkMapModal");
    if (mapModal) {
        mapModal.addEventListener("click", function (e) {
            if (e.target === mapModal) {
                closeBulkMapModal();
            }
        });
    }
});

function openCloneModal() {
    document.getElementById("cloneModal").style.display = "block";
    document.body.style.overflow = "hidden";
}

function closeCloneModal() {
    document.getElementById("cloneModal").style.display = "none";
    document.body.style.overflow = "auto";
}

function openBulkTicketDeleteModal() {
    document.getElementById("bulkTicketDeleteModal").style.display = "block";
    document.body.style.overflow = "hidden";
}

function closeBulkTicketDeleteModal() {
    document.getElementById("bulkTicketDeleteModal").style.display = "none";
    document.body.style.overflow = "auto";
}

</script>

</main>

<?php require_once __DIR__.'/inc/footer.php'; ?>
