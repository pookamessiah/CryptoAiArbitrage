<?php
// all-trades.php - Full Arbitrage Marketplace with Search & Filters
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

// Search & Filter Parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'profit_desc';

// Construct Dynamic Query
$sql = "SELECT * FROM arbitrage_deals WHERE status = 'active'";
$params = [];

if (!empty($search)) {
    $sql .= " AND (coin_name LIKE ? OR symbol LIKE ? OR buy_exchange LIKE ? OR sell_exchange LIKE ?)";
    $term = "%{$search}%";
    $params = [$term, $term, $term, $term];
}

switch ($sort) {
    case 'profit_asc':
        $sql .= " ORDER BY profit_percentage ASC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY coin_name ASC";
        break;
    case 'profit_desc':
    default:
        $sql .= " ORDER BY profit_percentage DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Support details
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
    <title>All Arbitrage Deals | Live Opportunities</title>
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
            line-height: 1.5;
            padding-bottom: 60px;
        }

        .container {
            width: min(1280px, calc(100% - 32px));
            margin: 32px auto;
        }

        .header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-title h1 {
            font-size: 28px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Search & Filter Toolbar */
        .filter-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 18px 24px;
            margin-bottom: 28px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-card form {
            display: flex;
            gap: 16px;
            width: 100%;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 240px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            background: #1e2329;
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 10px 16px 10px 40px;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .filter-select select {
            background: #1e2329;
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }

        .btn-filter {
            background: var(--accent-blue);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Grid Display */
        .deals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .trade-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .trade-card:hover {
            transform: translateY(-3px);
            border-color: #474d57;
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }

        .coin-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .coin-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #2b313a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 13px;
            color: var(--green);
        }

        .profit-tag {
            color: var(--green);
            font-size: 18px;
            font-weight: 800;
        }

        .exchange-route {
            background: #1e2329;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .route-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .route-row:last-child { margin-bottom: 0; }

        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        .btn-trade {
            display: block;
            text-align: center;
            background: var(--green);
            color: #000;
            font-weight: 700;
            padding: 10px 0;
            border-radius: 6px;
            text-decoration: none;
            transition: opacity 0.2s ease;
        }

        .btn-trade:hover { opacity: 0.9; }

        /* Floating Support Bar */
        .floating-support { position: fixed; bottom: 20px; right: 20px; z-index: 99; display: flex; flex-direction: column; gap: 10px; }
        .support-btn { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.4); }
        .sup-telegram { background: #0088cc; } .sup-phone { background: #25d366; } .sup-email { background: var(--accent-blue); }
    </style>
</head>
<body>

    <?php include 'inc/navbar.php'; ?>

    <main class="container">
        <div class="header-title">
            <h1><i class="fa-solid fa-list-check" style="color: var(--green);"></i> Arbitrage Opportunities</h1>
            <span style="color: var(--text-muted);"><?= count($deals) ?> Deals Available</span>
        </div>

        <!-- Filter Toolbar -->
        <div class="filter-card">
            <form method="GET" action="all-trades.php">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Search by coin name, symbol, or exchange..." value="<?= e($search) ?>">
                </div>

                <div class="filter-select">
                    <select name="sort" onchange="this.form.submit()">
                        <option value="profit_desc" <?= $sort === 'profit_desc' ? 'selected' : '' ?>>Highest Profit Margin</option>
                        <option value="profit_asc" <?= $sort === 'profit_asc' ? 'selected' : '' ?>>Lowest Profit Margin</option>
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Coin Name (A-Z)</option>
                    </select>
                </div>

                <button type="submit" class="btn-filter">Apply Filter</button>
            </form>
        </div>

        <!-- Deals Grid -->
        <div class="deals-grid">
            <?php if (!empty($deals)): ?>
                <?php foreach ($deals as $deal): ?>
                    <div class="trade-card">
                        <div>
                            <div class="card-top">
                                <div class="coin-info">
                                    <div class="coin-icon"><?= e(substr($deal['symbol'], 0, 3)) ?></div>
                                    <div>
                                        <h4 style="font-size: 15px;"><?= e($deal['coin_name']) ?></h4>
                                        <span style="font-size: 12px; color: var(--text-muted);"><?= e($deal['symbol']) ?>/USDT</span>
                                    </div>
                                </div>
                                <div class="profit-tag">+<?= e($deal['profit_percentage']) ?>%</div>
                            </div>

                            <div class="exchange-route">
                                <div class="route-row">
                                    <span style="color: var(--text-muted);">Buy On:</span>
                                    <strong><?= e($deal['buy_exchange']) ?> ($<?= number_format($deal['buy_price'], 2) ?>)</strong>
                                </div>
                                <div class="route-row">
                                    <span style="color: var(--text-muted);">Sell On:</span>
                                    <strong style="color: var(--green);"><?= e($deal['sell_exchange']) ?> ($<?= number_format($deal['sell_price'], 2) ?>)</strong>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="card-meta">
                                <span><i class="fa-regular fa-clock"></i> <?= e($deal['estimated_time']) ?></span>
                                <span><i class="fa-solid fa-shield-halved"></i> Managed</span>
                            </div>
                            <a href="trade.php?id=<?= (int)$deal['id'] ?>" class="btn-trade">Trade Now</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-muted); background: var(--bg-card); border-radius: 10px;">
                    <i class="fa-solid fa-filter-circle-xmark" style="font-size: 40px; margin-bottom: 12px;"></i>
                    <p>No arbitrage opportunities matched your search criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Floating Support -->
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
