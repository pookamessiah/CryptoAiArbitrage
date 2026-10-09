<?php
session_start();

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/inc/header.php';

/*----------------------------------------------------
GET TICKET
----------------------------------------------------*/

$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;

if(!$ticket_id){
    $_SESSION['error'] = "Ticket not found.";
    header("Location: manage-tickets.php");
    exit;
}

$stmt = $pdo->prepare("
SELECT *
FROM tickets
WHERE ticket_id=?
");
$stmt->execute([$ticket_id]);

$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$ticket){
    $_SESSION['error'] = "Ticket not found.";
    header("Location: manage-tickets.php");
    exit;
}

/*----------------------------------------------------
GET CONCERT (for redirect context)
----------------------------------------------------*/

$stmt = $pdo->prepare("
SELECT concert_id, artist_id
FROM concerts
WHERE concert_id=?
");
$stmt->execute([$ticket['concert_id']]);
$concert = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$concert){
    $_SESSION['error'] = "Concert not found.";
    header("Location: manage-tickets.php");
    exit;
}

/*----------------------------------------------------
HANDLE UPDATE
----------------------------------------------------*/

if($_SERVER['REQUEST_METHOD'] === "POST"){

    try{

        $ticket_name = trim($_POST['ticket_name']);
        $section_name = trim($_POST['section_name']);
        $row_name = trim($_POST['row_name']);
        $seat_name = trim($_POST['seat_name']);
        $price = (float)$_POST['price'];

        if(
            empty($ticket_name) ||
            empty($section_name) ||
            empty($row_name) ||
            empty($seat_name) ||
            $price <= 0
        ){
            throw new Exception("All fields are required.");
        }

        /*----------------------------------------------------
        HANDLE IMAGE UPDATE (OPTIONAL)
        ----------------------------------------------------*/

        $section_view = $ticket['section_view'];

        if(
            isset($_FILES['section_view']) &&
            $_FILES['section_view']['error'] === UPLOAD_ERR_OK
        ){

            $uploadDir = "../uploads/tickets/";

            if(!is_dir($uploadDir)){
                mkdir($uploadDir,0755,true);
            }

            $ext = strtolower(pathinfo($_FILES['section_view']['name'], PATHINFO_EXTENSION));

            $section_view = uniqid("seat_").".".$ext;

            move_uploaded_file(
                $_FILES['section_view']['tmp_name'],
                $uploadDir.$section_view
            );
        }

        /*----------------------------------------------------
        UPDATE QUERY
        ----------------------------------------------------*/

        $stmt = $pdo->prepare("
        UPDATE tickets
        SET
            ticket_name=?,
            section_name=?,
            row_name=?,
            seat_name=?,
            price=?,
            section_view=?
        WHERE ticket_id=?
        ");

        $stmt->execute([
            $ticket_name,
            $section_name,
            $row_name,
            $seat_name,
            $price,
            $section_view,
            $ticket_id
        ]);

        $_SESSION['success'] = "Ticket updated.";

        header("Location: manage-tickets.php?concert_id=".$ticket['concert_id']);
        exit;

    }catch(Exception $e){
        $_SESSION['error'] = $e->getMessage();
    }
}

?>

<main style="max-width:900px;margin:auto;">

<h1 style="margin-bottom:20px;">Edit Ticket</h1>

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

<label>Ticket Name</label>
<input type="text" name="ticket_name"
value="<?= htmlspecialchars($ticket['ticket_name']) ?>"
required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Section</label>
<input type="text" name="section_name"
value="<?= htmlspecialchars($ticket['section_name']) ?>"
required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Row</label>
<input type="text" name="row_name"
value="<?= htmlspecialchars($ticket['row_name']) ?>"
required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Seat</label>
<input type="text" name="seat_name"
value="<?= htmlspecialchars($ticket['seat_name']) ?>"
required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Price</label>
<input type="number" step="0.01" name="price"
value="<?= htmlspecialchars($ticket['price']) ?>"
required style="width:100%;padding:10px;margin:10px 0 20px;">

<label>Section View (optional)</label>

<?php if(!empty($ticket['section_view'])){ ?>
    <div style="margin-bottom:10px;">
        <img src="../uploads/tickets/<?= htmlspecialchars($ticket['section_view']) ?>"
             style="width:120px;height:120px;object-fit:cover;border-radius:8px;">
    </div>
<?php } ?>

<input type="file" name="section_view" accept="image/*"
style="width:100%;padding:10px;margin:10px 0 20px;">

<button class="btn" style="width:100%;">
    <i class="fas fa-save"></i> Save Changes
</button>

</form>

</main>

<?php require_once __DIR__.'/inc/footer.php'; ?>
