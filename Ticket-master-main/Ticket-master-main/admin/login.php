<?php
// Enable error displaying so we can pinpoint issues if database structural details are missing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// If already logged in → redirect to dashboard
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_logged_in'])) {
    header("Location: dashboard.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';
// Assuming it defines $pdo as PDO object

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Both fields are required.";
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT id, username, password_hash, full_name, role, is_active 
                FROM admins 
                WHERE (username = :user OR email = :user)
                LIMIT 1
            ");
            $stmt->execute([':user' => $username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && $admin['is_active'] == 1 && password_verify($password, $admin['password_hash'])) {
                // Success
                session_regenerate_id(true);

                $_SESSION['admin_id']         = $admin['id'];
                $_SESSION['admin_username']   = $admin['username'];
                $_SESSION['admin_fullname']   = $admin['full_name'];
                $_SESSION['admin_role']       = $admin['role'];
                $_SESSION['admin_logged_in']  = true;
                $_SESSION['last_activity']    = time();

                // Update last login
                $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")
                    ->execute([$admin['id']]);

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username/email or password.";
            }
        } catch (PDOException $e) {
            // In production: log error, don't show to user
            $error = "A system error occurred. Please try again later.";
            // error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login – TICKET MASTER</title>
  
  <!-- Font Awesome 6 Free (CDN) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" 
        crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    :root {
      --primary: #1e90ff;
      --primary-dark: #1565c0;
      --bg: #0d1117;
      --card: #161b22;
      --text: #e6edf3;
      --text-muted: #8b949e;
      --danger: #f85149;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .login-container {
      background: var(--card);
      border: 1px solid #30363d;
      border-radius: 12px;
      width: 100%;
      max-width: 420px;
      padding: 2.5rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .logo {
      text-align: center;
      margin-bottom: 2rem;
    }
    .logo img {
      width: 110px;
      height: auto;
      border-radius: 12px;
    }
    h1 {
      text-align: center;
      font-size: 1.7rem;
      margin-bottom: 0.5rem;
      color: #fff;
    }
    .subtitle {
      text-align: center;
      color: var(--text-muted);
      margin-bottom: 2rem;
      font-size: 0.95rem;
    }
    .form-group {
      margin-bottom: 1.4rem;
    }
    label {
      display: block;
      margin-bottom: 0.5rem;
      font-size: 0.95rem;
      color: #c9d1d9;
    }
    .input-wrapper {
      position: relative;
    }
    .input-wrapper i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
    }
    input {
      width: 100%;
      padding: 0.75rem 1rem 0.75rem 2.8rem;
      border: 1px solid #30363d;
      border-radius: 6px;
      background: #0d1117;
      color: var(--text);
      font-size: 1rem;
      transition: border-color 0.2s;
    }
    input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(30,144,255,0.15);
    }
    button {
      width: 100%;
      padding: 0.9rem;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 1.05rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    button:hover {
      background: var(--primary-dark);
    }
    .error {
      background: rgba(248,81,73,0.15);
      border: 1px solid rgba(248,81,73,0.4);
      color: #f85149;
      padding: 0.9rem;
      border-radius: 6px;
      margin-bottom: 1.5rem;
      text-align: center;
      font-size: 0.95rem;
    }
    .footer {
      text-align: center;
      margin-top: 2rem;
      font-size: 0.85rem;
      color: var(--text-muted);
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="logo">
    <img src="../assets/images/vip.jpg" alt="TICKET MASTER Logo">
  </div>
  
  <h1>Admin Panel</h1>
  <div class="subtitle">TICKET MASTER</div>

  <?php if ($error): ?>
    <div class="error">
      <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="form-group">
      <label for="username">Username or Email</label>
      <div class="input-wrapper">
        <i class="fas fa-user"></i>
        <input type="text" id="username" name="username" 
               value="<?= htmlspecialchars($username) ?>" 
               placeholder="admin or admin@example.com" required autofocus>
      </div>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <div class="input-wrapper">
        <i class="fas fa-lock"></i>
        <input type="password" id="password" name="password" 
               placeholder="••••••••" required>
      </div>
    </div>

    <button type="submit">
      <i class="fas fa-sign-in-alt"></i> Sign In
    </button>
  </form>

  <div class="footer">
    © <?= date("Y") ?> TICKET MASTER • Admin Access Only
  </div>
</div>

</body>
</html>
