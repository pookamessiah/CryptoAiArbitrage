<?php
session_start();

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/inc/header.php';

/*----------------------------------------------------
GET CONCERT
----------------------------------------------------*/

$concert_id = isset($_GET['concert_id']) ? (int)$_GET['concert_id'] : 0;
$concert = null;

if($concert_id){

    $stmt=$pdo->prepare("
    SELECT
        concerts.*,
        artists.artist_name,
        artists.artist_image
    FROM concerts
    JOIN artists
        ON concerts.artist_id=artists.artist_id
    WHERE concerts.concert_id=?
    ");

    $stmt->execute([$concert_id]);
    $concert=$stmt->fetch(PDO::FETCH_ASSOC);
}

$sections = [];

if($concert_id){
    $stmt = $pdo->prepare("
        SELECT DISTINCT section_name
        FROM tickets
        WHERE concert_id=?
        ORDER BY section_name
    ");
    $stmt->execute([$concert_id]);
    $sections = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/*----------------------------------------------------
HANDLE POST
----------------------------------------------------*/

if($_SERVER['REQUEST_METHOD']=="POST"){

    try{

        $action=$_POST['action'];
        $concert_id=(int)$_POST['concert_id'];

        /*---------------- ADD ----------------*/
        if($action=="add"){

            $ticket_name=trim($_POST['ticket_name']);
            $section_name=trim($_POST['section_name']);
            $row_name=trim($_POST['row_name']);
            $seat_name=trim($_POST['seat_name']);
            $price=(float)$_POST['price'];

            if(empty($ticket_name)||empty($section_name)||empty($row_name)||empty($seat_name)||$price<=0){
                throw new Exception("All fields are required.");
            }

            $section_view=null;

            if(isset($_FILES['section_view']) && $_FILES['section_view']['error']==UPLOAD_ERR_OK){

                $uploadDir="../uploads/tickets/";
                if(!is_dir($uploadDir)) mkdir($uploadDir,0755,true);

                $ext=strtolower(pathinfo($_FILES['section_view']['name'],PATHINFO_EXTENSION));
                $filename=uniqid('seat_').".".$ext;

                move_uploaded_file($_FILES['section_view']['tmp_name'],$uploadDir.$filename);

                $section_view=$filename;
            }

            $stmt=$pdo->prepare("
                INSERT INTO tickets
                (concert_id,ticket_name,section_name,row_name,seat_name,price,section_view)
                VALUES (?,?,?,?,?,?,?)
            ");

            $stmt->execute([
                $concert_id,$ticket_name,$section_name,$row_name,$seat_name,$price,$section_view
            ]);

            $_SESSION['success']="Ticket added.";
        }


        /*---------------- BULK UPLOAD ----------------*/
        if($action=="bulk_upload"){
        
            $bulk = trim($_POST['bulk_data']);
        
            if(empty($bulk)){
                throw new Exception("No ticket data supplied.");
            }
        
            // Split into ticket blocks separated by blank lines
            $blocks = preg_split("/\R\s*\R/", $bulk);
        
            $stmt = $pdo->prepare("
                INSERT INTO tickets
                (
                    concert_id,
                    ticket_name,
                    section_name,
                    row_name,
                    seat_name,
                    price,
                    section_view
                )
                VALUES
                (?,?,?,?,?,?,NULL)
            ");
        
            $count = 0;
        
            foreach($blocks as $block){
        
                $lines = array_values(array_filter(array_map('trim', preg_split("/\R/", trim($block)))));
        
                if(count($lines) < 5){
                    continue;
                }
        
                $ticket_name = $lines[0];
                $section_name = $lines[1];
                $row_name = $lines[2];
                $seat_name = $lines[3];
        
                $price = str_replace(
                    ["$", ","],
                    "",
                    $lines[4]
                );
        
                $price = (float)$price;
        
                if($price <= 0){
                    continue;
                }
        
                $stmt->execute([
                    $concert_id,
                    $ticket_name,
                    $section_name,
                    $row_name,
                    $seat_name,
                    $price
                ]);
        
                $count++;
            }
        
            $_SESSION['success'] = $count . " tickets uploaded successfully.";
        }


        /*---------------- BULK DELETE ----------------*/
        if($action=="bulk_delete"){
        
            $section_name = trim($_POST['section_name']);
        
            if(empty($section_name)){
                throw new Exception("Please select a section.");
            }
        
            $stmt = $pdo->prepare("
                DELETE FROM tickets
                WHERE concert_id=?
                AND section_name=?
            ");
        
            $stmt->execute([$concert_id, $section_name]);
        
            $_SESSION['success'] =
                $stmt->rowCount() . " tickets deleted from " . $section_name;
        }


        /*---------------- BULK SECTION VIEW ----------------*/
        if($action=="bulk_section_view"){
        
            $section_name = trim($_POST['section_name']);
        
            if(empty($section_name)){
                throw new Exception("Please select a section.");
            }
        
            if(
                !isset($_FILES['section_view']) ||
                $_FILES['section_view']['error'] != UPLOAD_ERR_OK
            ){
                throw new Exception("Please choose an image.");
            }
        
            $uploadDir = "../uploads/tickets/";
        
            if(!is_dir($uploadDir)){
                mkdir($uploadDir,0755,true);
            }
        
            $ext = strtolower(pathinfo($_FILES['section_view']['name'],PATHINFO_EXTENSION));
        
            $filename = uniqid("section_").".".$ext;
        
            move_uploaded_file(
                $_FILES['section_view']['tmp_name'],
                $uploadDir.$filename
            );
        
            $stmt = $pdo->prepare("
                UPDATE tickets
                SET section_view=?
                WHERE concert_id=?
                AND section_name=?
            ");
        
            $stmt->execute([
                $filename,
                $concert_id,
                $section_name
            ]);
        
            $_SESSION['success'] =
                "Section image applied to ".$stmt->rowCount()." tickets.";
        }
        


        /*---------------- DELETE ----------------*/
        if($action=="delete"){

            $ticket_id=(int)$_POST['ticket_id'];

            $stmt=$pdo->prepare("DELETE FROM tickets WHERE ticket_id=?");
            $stmt->execute([$ticket_id]);

            $_SESSION['success']="Ticket deleted.";
        }

        header("Location: manage-tickets.php?concert_id=".$concert_id);
        exit;

    }catch(Exception $e){
        $_SESSION['error']=$e->getMessage();
        header("Location: manage-tickets.php?concert_id=".$concert_id);
        exit;
    }
}

/*----------------------------------------------------
DATA
----------------------------------------------------*/

$stmt=$pdo->query("
SELECT concerts.concert_id, concerts.title, concerts.concert_date, artists.artist_name
FROM concerts
JOIN artists ON concerts.artist_id=artists.artist_id
ORDER BY concerts.concert_date DESC
");
$concerts=$stmt->fetchAll(PDO::FETCH_ASSOC);

$tickets=[];

if($concert_id){
$stmt=$pdo->prepare("SELECT * FROM tickets WHERE concert_id=? ORDER BY price");
$stmt->execute([$concert_id]);
$tickets=$stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<main style="max-width:1100px;margin:auto;">

<h1 style="margin-bottom:25px;">Manage Tickets</h1>

<?php if(isset($_SESSION['success'])){ ?>
<div style="background:#238636;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;">
<?= $_SESSION['success']; unset($_SESSION['success']);?>
</div>
<?php } ?>

<?php if(isset($_SESSION['error'])){ ?>
<div style="background:#f85149;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;">
<?= $_SESSION['error']; unset($_SESSION['error']);?>
</div>
<?php } ?>

<?php if(!$concert_id){ ?>

<div style="background:var(--card);padding:25px;border-radius:10px;max-width:700px;margin:auto;">

<h2>Select Concert</h2>

<form method="GET">
<select name="concert_id" required onchange="this.form.submit()" style="width:100%;padding:12px;">
<option value="">Choose Concert</option>

<?php foreach($concerts as $c){ ?>
<option value="<?= $c['concert_id'] ?>">
<?= htmlspecialchars($c['artist_name']) ?> - <?= htmlspecialchars($c['title']) ?> (<?= $c['concert_date'] ?>)
</option>
<?php } ?>

</select>
</form>

</div>

<?php return; } ?>

<!-- CONCERT HEADER -->
<div style="display:flex;align-items:center;gap:15px;background:var(--card);padding:20px;border-radius:10px;margin-bottom:25px;">

<?php if($concert['artist_image']){ ?>
<img src="../uploads/artists/<?= htmlspecialchars($concert['artist_image']) ?>"
style="width:70px;height:70px;border-radius:10px;object-fit:cover;">
<?php } ?>

<div>
<h2><?= htmlspecialchars($concert['artist_name']) ?></h2>
<small><?= htmlspecialchars($concert['title']) ?><br><?= htmlspecialchars($concert['concert_date']) ?></small>
</div>

</div>

<div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;margin-bottom:20px;">

<button class="btn" onclick="openSectionModal()">
    <i class="fas fa-image"></i>
    Bulk Section View
</button>

<button class="btn" onclick="openBulkModal()">
    <i class="fas fa-upload"></i>
    Bulk Upload
</button>

<button class="btn" onclick="openAddModal()">
    <i class="fas fa-plus"></i>
    Add Ticket
</button>

<button class="btn red" onclick="openDeleteModal()">
    <i class="fas fa-trash"></i>
    Bulk Delete Section
</button>

</div>


<!-- TABLE -->
<div style="overflow:auto;">
<table style="width:100%;border-collapse:collapse;">
<thead>
<tr style="background:#111827;">
<th>Ticket</th>
<th>Section</th>
<th>Row</th>
<th>Seat</th>
<th>Price</th>
<th>Image</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php if(empty($tickets)){ ?>
<tr><td colspan="7" style="padding:20px;text-align:center;">No tickets found.</td></tr>
<?php } ?>

<?php foreach($tickets as $ticket){ ?>

<tr style="border-bottom:1px solid var(--border);">

<td style="padding:12px;"><?= htmlspecialchars($ticket['ticket_name']) ?></td>
<td style="padding:12px;"><?= htmlspecialchars($ticket['section_name']) ?></td>
<td style="padding:12px;"><?= htmlspecialchars($ticket['row_name']) ?></td>
<td style="padding:12px;"><?= htmlspecialchars($ticket['seat_name']) ?></td>
<td style="padding:12px;">$<?= number_format($ticket['price'],2) ?></td>

<td style="padding:12px;">
<?php if(!empty($ticket['section_view'])){ ?>
<img src="../uploads/tickets/<?= htmlspecialchars($ticket['section_view']) ?>"
style="width:55px;height:55px;object-fit:cover;border-radius:8px;">
<?php } else { ?>
<span style="color:#888;">N/A</span>
<?php } ?>
</td>

<td style="padding:12px;display:flex;gap:8px;">

<!-- EDIT -->
<a
    href="edit-tickets.php?ticket_id=<?= $ticket['ticket_id'] ?>"
    class="btn"
    style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
    Edit
</a>
<!-- DELETE -->
<form method="POST" onsubmit="return confirm('Delete ticket?');">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="concert_id" value="<?= $concert_id ?>">
<input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
<button class="btn red">Delete</i></button>
</form>

</td>

</tr>

<?php } ?>

</tbody>
</table>
</div>

<!-- ADD MODAL -->
<div id="addModal" style="display:none;position:fixed;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.7);">

<div style="background:#111827;max-width:600px;margin:5% auto;padding:25px;border-radius:10px;">

<h2>Add Ticket</h2>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="action" value="add">
<input type="hidden" name="concert_id" value="<?= $concert_id ?>">

<input placeholder="Ticket Name" name="ticket_name" required style="width:100%;padding:10px;margin:10px 0;">
<input placeholder="Section" name="section_name" required style="width:100%;padding:10px;margin:10px 0;">
<input placeholder="Row" name="row_name" required style="width:100%;padding:10px;margin:10px 0;">
<input placeholder="Seat" name="seat_name" required style="width:100%;padding:10px;margin:10px 0;">
<input type="number" step="0.01" placeholder="Price" name="price" required style="width:100%;padding:10px;margin:10px 0;">

<input type="file" name="section_view" required style="width:100%;padding:10px;margin:10px 0;">

<button class="btn" style="width:100%;">Save</button>
</form>

<br>
<button class="btn red" onclick="closeAddModal()" style="width:100%;">Close</button>

</div>
</div>

<div id="bulkModal"
style="display:none;position:fixed;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.7);">

<div style="background:#111827;max-width:700px;margin:5% auto;padding:25px;border-radius:10px;">

<h2>Bulk Ticket Upload</h2>

<p>
Paste tickets in this format:
</p>

<pre style="background:#000;padding:15px;color:#0f0;border-radius:6px;">
Standard Ticket
Sec 222
Row 7
Seat 13
$75.55

Premium Ticket
Sec 203
Row 2
Seat 14
$112.50
</pre>

<form method="POST">

<input type="hidden" name="action" value="bulk_upload">
<input type="hidden" name="concert_id" value="<?= $concert_id ?>">

<textarea
name="bulk_data"
required
style="width:100%;height:320px;padding:12px;"></textarea>

<br><br>

<button class="btn" style="width:100%;">
Upload Tickets
</button>

</form>

<br>

<button class="btn red"
onclick="closeBulkModal()"
style="width:100%;">
Close
</button>

</div>
</div>

<div id="sectionModal"
style="
display:none;
position:fixed;
left:0;
top:0;
width:100%;
height:100%;
background:rgba(0,0,0,.7);
overflow-y:auto;
padding:40px 20px;
z-index:9999;
">

<div
style="
background:#111827;
max-width:600px;
margin:auto;
padding:25px;
border-radius:10px;
max-height:90vh;
overflow-y:auto;
">

<h2>Bulk Section View Upload</h2>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="action" value="bulk_section_view">
<input type="hidden" name="concert_id" value="<?= $concert_id ?>">

<label>Select Section</label>

<select
name="section_name"
required
style="width:100%;padding:12px;margin:10px 0;">

<option value="">Choose Section</option>

<?php foreach($sections as $section){ ?>

<option value="<?= htmlspecialchars($section) ?>">
<?= htmlspecialchars($section) ?>
</option>

<?php } ?>

</select>

<label>Section Image</label>

<input
type="file"
name="section_view"
accept="image/*"
required
style="width:100%;padding:10px;margin:10px 0;">

<button class="btn" style="width:100%;">
Upload & Apply
</button>

</form>

<br>

<button
class="btn red"
onclick="closeSectionModal()"
style="width:100%;">
Close
</button>

</div>

</div>

<div id="deleteModal"
style="
display:none;
position:fixed;
left:0;
top:0;
width:100%;
height:100%;
background:rgba(0,0,0,.7);
overflow-y:auto;
padding:40px 20px;
z-index:9999;
">

<div style="
background:#111827;
max-width:600px;
margin:auto;
padding:25px;
border-radius:10px;
max-height:90vh;
overflow-y:auto;
">

<h2>Bulk Delete Tickets</h2>

<p style="color:#fca5a5;">
This will permanently delete all tickets in the selected section.
</p>

<form method="POST">

<input type="hidden" name="action" value="bulk_delete">
<input type="hidden" name="concert_id" value="<?= $concert_id ?>">

<label>Select Section</label>

<select
name="section_name"
required
style="width:100%;padding:12px;margin:10px 0;">

<option value="">Choose Section</option>

<?php foreach($sections as $section){ ?>
<option value="<?= htmlspecialchars($section) ?>">
<?= htmlspecialchars($section) ?>
</option>
<?php } ?>

</select>

<button class="btn red" style="width:100%;">
Delete All Tickets
</button>

</form>

<br>

<button class="btn" onclick="closeDeleteModal()" style="width:100%;">
Cancel
</button>

</div>
</div>


<script>

function openAddModal(){
document.getElementById("addModal").style.display="block";
}

function closeAddModal(){
document.getElementById("addModal").style.display="none";
}

function openBulkModal(){
    document.getElementById("bulkModal").style.display = "block";
}

function closeBulkModal(){
    document.getElementById("bulkModal").style.display = "none";
}

function openSectionModal(){
    document.getElementById("sectionModal").style.display="block";
}

function closeSectionModal(){
    document.getElementById("sectionModal").style.display="none";
}

function openDeleteModal(){
    document.getElementById("deleteModal").style.display="block";
}

function closeDeleteModal(){
    document.getElementById("deleteModal").style.display="none";
}

</script>

</main>

<?php require_once __DIR__.'/inc/footer.php'; ?>
