<?php
// register.php - User Account Registration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in users away from registration
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';

$pdo = null;
try {
    $db = new Database();
    $pdo = $db->connect();
} catch (Exception $e) {
    die($e->getMessage());
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($fullname) || !$email || empty($password)) {
        $error_message = "Please fill in all required fields with a valid email address.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        // Check if email already exists
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            $error_message = "An account with this email address already exists.";
        } else {
            // Hash password and insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmtInsert = $pdo->prepare("
                INSERT INTO users (fullname, email, phone, password, real_balance, demo_balance, status)
                VALUES (?, ?, ?, ?, 0.00000000, 10000.00000000, 'active')
            ");

            if ($stmtInsert->execute([$fullname, $email, $phone, $hashed_password])) {
                $new_user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $new_user_id;
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "An error occurred during registration. Please try again.";
            }
        }
    }
}

// Support Contacts
$support = ['phone' => '+1 (800) 123-4567', 'telegram' => '@ArbitrageSupport', 'email' => 'support@yourdomain.com'];
$stmtSup = $pdo->query("SELECT * FROM support_info LIMIT 1");
if ($dbSup = $stmtSup->fetch()) {
    $support = $dbSup;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | ArbitragePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0b0e11;
            --bg-card: #181a20;
            --bg-hover: #2b313a;
            --text-main: #eaecef;
            --text-muted: #848e9c;
            --green: #0ecb81;
            --red: #f6465d;
            --accent-blue: #2b6cb0;
            --border-color: #2b313a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Segoe UI', Roboto, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .auth-container {
            width: min(450px, calc(100% - 32px));
            margin: 40px auto;
        }

        .auth-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .auth-header h2 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .auth-header p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            background: rgba(246, 70, 93, 0.15);
            border: 1px solid var(--red);
            color: #ff8091;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .form-control {
            width: 100%;
            background: #1e2329;
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--accent-blue);
        }

        .btn-submit {
            width: 100%;
            background: var(--green);
            color: #000;
            border: none;
            padding: 14px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            transition: opacity 0.2s;
        }

        .btn-submit:hover { opacity: 0.9; }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .auth-footer a {
            color: var(--green);
            text-decoration: none;
            font-weight: 600;
        }

        /* Support Floating Widget */
        .floating-support { position: fixed; bottom: 20px; right: 20px; z-index: 99; display: flex; flex-direction: column; gap: 10px; }
        .support-btn { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.4); }
        .sup-telegram { background: #0088cc; } .sup-phone { background: #25d366; } .sup-email { background: var(--accent-blue); }
    </style>
</head>
<body>

    <?php include 'inc/navbar.php'; ?>

    <main class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Create Your Account</h2>
                <p>Start trading CoinMarketCap arbitrage opportunities</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert-box">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= e($error_message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="fullname">Full Name *</label>
                    <input type="text" name="fullname" id="fullname" class="form-control" placeholder="John Doe" required value="<?= e($_POST['fullname'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number (Optional)</label>
                    <input type="text" name="phone" id="phone" class="form-control" placeholder="+1234567890" value="<?= e($_POST['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="At least 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter password" required>
                </div>

                <button type="submit" name="register" class="btn-submit">
                    <i class="fa-solid fa-user-plus"></i> Register & Get $10k Demo Account
                </button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Log In</a>
            </div>
        </div>
    </main>

    <!-- Support Floating Buttons -->
    <div class="floating-support">
        <a href="https://t.me/<?= e(ltrim($support['telegram'], '@')) ?>" target="_blank" class="support-btn sup-telegram" title="Telegram Support">
            <i class="fa-brands fa-telegram"></i>
        </a>
        <a href="tel:<?= e($support['phone']) ?>" class="support-btn sup-phone" title="Phone Support">
            <i class="fa-solid fa-phone"></i>
        </a>
        <a href="mailto:<?= e($support['email']) ?>" class="support-btn sup-email" title="Email Support">
            <i class="fa-solid fa-envelope"></i>
        </a>
    </div>

</body>
</html>
