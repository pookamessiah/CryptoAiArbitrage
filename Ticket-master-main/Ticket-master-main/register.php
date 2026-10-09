<?php
session_start();
require_once "config/db.php";

$pdo = (new Database())->connect();

// Catch the simulated 'YES' login path from oauth.php if clicked
if (isset($_GET['action']) && $_GET['action'] === 'login_sim') {
    $sim_email = trim($_GET['email'] ?? '');
    
    // Look up the simulated user to get their actual ID
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->execute([$sim_email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION["user_id"] = $user['id'];
        $_SESSION["email"] = $user['email'];
        
        $redirect = !empty($_POST['redirect']) ? $_POST['redirect'] : ($_SESSION["redirect_after_auth"] ?? "auth/dashboard.php");
        unset($_SESSION["redirect_after_auth"]);
        header("Location: " . $redirect);
        exit;
    } else {
        $_SESSION['auth_error'] = "No existing profile found for that email address.";
        header("Location: oauth.php");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['auth_error'] = "Invalid request method.";
    header("Location: oauth.php");
    exit;
}

$full_name = trim($_POST["full_name"] ?? '');
$email = trim($_POST["email"] ?? '');
$country = trim($_POST["country"] ?? '');
$country_code = trim($_POST["country_code"] ?? '');
$phone = trim($_POST["phone"] ?? '');
$password = $_POST["password"] ?? '';
$confirm = $_POST["confirm_password"] ?? '';

/* -------------------------
   VALIDATION
--------------------------*/
if (!$full_name || !$email || !$country || !$phone || !$password) {
    $_SESSION['auth_error'] = "All profile fields are required.";
    header("Location: oauth.php");
    exit;
}

if ($password !== $confirm) {
    $_SESSION['auth_error'] = "Your chosen passwords do not match.";
    header("Location: oauth.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['auth_error'] = "Please enter a valid email address.";
    header("Location: oauth.php");
    exit;
}

/* -------------------------
   CHECK EXISTING USER
--------------------------*/
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    $_SESSION['auth_error'] = "This email address is already registered.";
    header("Location: oauth.php");
    exit;
}

/* -------------------------
   HASH PASSWORD
--------------------------*/
$hash = password_hash($password, PASSWORD_BCRYPT);

/* -------------------------
   INSERT USER
--------------------------*/
$stmt = $pdo->prepare("
    INSERT INTO users 
    (full_name, email, country, country_code, phone, password_hash)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $full_name,
    $email,
    $country,
    $country_code,
    $phone,
    $hash
]);

$user_id = $pdo->lastInsertId();

/* -------------------------
   AUTO LOGIN
--------------------------*/
$_SESSION["user_id"] = $user_id;
$_SESSION["email"] = $email;

/* -------------------------
   REDIRECT LOGIC (Hierarchical Safeguard)
--------------------------*/
if (!empty($_POST['redirect'])) {
    $redirect = $_POST['redirect'];
} else if (!empty($_SESSION["redirect_after_auth"])) {
    $redirect = $_SESSION["redirect_after_auth"];
} else {
    $redirect = "auth/dashboard.php"; // Fixed path to match subdirectory configuration
}

unset($_SESSION["redirect_after_auth"]);

header("Location: " . $redirect);
exit;
