<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/header.php';

$error = '';

/* --------------------------------------------------
   FETCH REGIONS
-------------------------------------------------- */
try {

    $stmt = $pdo->query("
        SELECT *
        FROM region_settings
        ORDER BY country ASC
    ");

    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e){

    $regions = [];
    $error = $e->getMessage();

}

/* --------------------------------------------------
   HANDLE ACTIONS
-------------------------------------------------- */

if($_SERVER['REQUEST_METHOD']=='POST' && !$error){

    $action = $_POST['action'] ?? '';

    try{

        /* ---------------- ADD ---------------- */

        if($action=='add'){

            $country = trim($_POST['country']);
            $currency = strtoupper(trim($_POST['currency']));
            $rate = trim($_POST['exchange_rates']);

            if($country==''){
                throw new Exception("Country is required.");
            }

            if(strlen($currency)!=3){
                throw new Exception("Currency must be exactly 3 characters.");
            }

            if(!is_numeric($rate)){
                throw new Exception("Invalid exchange rate.");
            }

            $stmt=$pdo->prepare("
                INSERT INTO region_settings
                (country,currency,exchange_rates)
                VALUES(?,?,?)
            ");

            $stmt->execute([
                $country,
                $currency,
                $rate
            ]);

            $_SESSION['success']="Region added successfully.";

            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }

        /* ---------------- DELETE ---------------- */

        if($action=='delete'){

            $id=(int)($_POST['id'] ?? 0);

            if($id<=0){
                throw new Exception("Invalid region.");
            }

            $stmt=$pdo->prepare("
                DELETE FROM region_settings
                WHERE id=?
            ");

            $stmt->execute([$id]);

            $_SESSION['success']="Region deleted successfully.";

            header("Location: ".$_SERVER['PHP_SELF']);
            exit;

        }

    }catch(Exception $e){

        $_SESSION['error']=$e->getMessage();

        header("Location: ".$_SERVER['PHP_SELF']);
        exit;

    }

}

?>

<main>

<h1 style="text-align:center;margin:2rem 0;">
Manage Region Settings
</h1>

<?php if(!empty($_SESSION['success'])): ?>

<div style="background:#238636;color:#fff;padding:1rem;border-radius:8px;text-align:center;max-width:900px;margin:1rem auto;">
<?= htmlspecialchars($_SESSION['success']) ?>
</div>

<?php unset($_SESSION['success']); endif; ?>

<?php if(!empty($_SESSION['error'])): ?>

<div style="background:#f85149;color:#fff;padding:1rem;border-radius:8px;text-align:center;max-width:900px;margin:1rem auto;">
<?= htmlspecialchars($_SESSION['error']) ?>
</div>

<?php unset($_SESSION['error']); endif; ?>


<div style="text-align:center;margin-bottom:2rem;">

<button class="btn" onclick="openModal()">
+ Add Region
</button>

</div>


<div style="max-width:1000px;margin:auto;overflow-x:auto;padding:0 10px;">

<table style="width:100%;border-collapse:collapse;background:var(--card);border:1px solid var(--border);">

<thead>

<tr style="background:#111827;text-align:left;">

<th style="padding:12px;">Country</th>
<th style="padding:12px;">Currency</th>
<th style="padding:12px;">Exchange Rate</th>
<th style="padding:12px;">Updated</th>
<th style="padding:12px;">Actions</th>

</tr>

</thead>

<tbody>

<?php foreach($regions as $row): ?>

<tr style="border-top:1px solid var(--border);">

<td style="padding:12px;font-weight:600;">
<?= htmlspecialchars($row['country']) ?>
</td>

<td style="padding:12px;">
<?= htmlspecialchars($row['currency']) ?>
</td>

<td style="padding:12px;">
<?= number_format($row['exchange_rates'],4) ?>
</td>

<td style="padding:12px;">
<?= htmlspecialchars($row['updated_at']) ?>
</td>

<td style="padding:12px;white-space:nowrap;">

<a href="edit-region-setting.php?id=<?= $row['id'] ?>"
class="btn green"
style="padding:6px 10px;font-size:13px;">
Edit
</a>

<form method="POST"
style="display:inline-block;margin-left:5px;"
onsubmit="return confirm('Delete this region?');">

<input type="hidden" name="action" value="delete">

<input type="hidden" name="id"
value="<?= $row['id'] ?>">

<button class="btn red"
style="padding:6px 10px;font-size:13px;">
Delete
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>



<!-- MODAL -->

<div id="regionModal"
style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.7);overflow-y:auto;padding:20px;">

<div style="
background:#0d1117;
max-width:500px;
margin:40px auto;
padding:2rem;
border-radius:10px;
max-height:90vh;
overflow-y:auto;
">

<h2>Add Region</h2>

<form method="POST">

<input type="hidden"
name="action"
value="add">

<label>Country</label>

<input
type="text"
name="country"
required
style="width:100%;padding:.7rem;margin-bottom:1rem;">

<label>Currency (3 Letters)</label>

<input
type="text"
name="currency"
maxlength="3"
required
style="width:100%;padding:.7rem;margin-bottom:1rem;text-transform:uppercase;">

<label>Exchange Rate</label>

<input
type="number"
step="0.0001"
name="exchange_rates"
required
style="width:100%;padding:.7rem;margin-bottom:1.5rem;">

<button class="btn"
style="width:100%;">
Save
</button>

</form>

<br>

<button
onclick="closeModal()"
class="btn red"
style="width:100%;">
Close
</button>

</div>

</div>

<script>

function openModal(){

document.getElementById('regionModal').style.display='block';

}

function closeModal(){

document.getElementById('regionModal').style.display='none';

}

</script>

</main>

<?php require_once __DIR__.'/inc/footer.php'; ?>
