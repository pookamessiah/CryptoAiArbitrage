<?php
// all-trades.php - Full Arbitrage Marketplace with Live Refreshes & 3% Gating
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';

$pdo = null;
$user_real_balance = 0.00;
$is_logged_in = isset($_SESSION['user_id']);
$user_id =$_SESSION['user_id'] ?? null;

try {
    $db = new Database();
    $pdo =$db->connect();
    
    // Fetch logged-in user's real balance for gating check
    if ($is_logged_in &&$user_id) {
        $stmtUser =$pdo->prepare("SELECT real_balance FROM users WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $uData =$stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($uData) {
            $user_real_balance = (float)$uData['real_balance'];
        }
    }
} catch (Exception $e) {
    // Graceful fallback if database connection is pending configuration
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Fetch support details
$support = ['phone' => '+18001234567', 'telegram' => '@ArbitrageSupport', 'email' => 'support@yourdomain.com'];
if ($pdo) {
    try {
        $stmtSup =$pdo->query("SELECT * FROM support_info LIMIT 1");
        if ($dbSup =$stmtSup->fetch(PDO::FETCH_ASSOC)) {
            $support =$dbSup;
        }
    } catch (Exception $e) {}
}
$whatsapp_number = preg_replace('/[^0-9]/', '',$support['phone']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Arbitrage Deals | Live Opportunities Marketplace</title>
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
            --warning: #ffc107;
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
            position: relative;
        }

        .route-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .route-row:last-child { margin-bottom: 0; }

        /* Blur & Lock Overlay Styles */
        .blur-target {
            filter: blur(6px);
            user-select: none;
            pointer-events: none;
        }

        .lock-overlay {
            position: absolute;
            inset: 0;
            background: rgba(11, 14, 17, 0.88);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            text-align: center;
            padding: 6px;
            z-index: 2;
        }

        .lock-overlay span {
            color: var(--warning);
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .btn-unlock {
            background: var(--warning);
            color: #000;
            font-weight: 700;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 4px;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn-unlock:hover { opacity: 0.9; }

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
            width: 100%;
            text-align: center;
            background: var(--green);
            color: #000;
            font-weight: 700;
            padding: 10px 0;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }
        .btn-trade:hover { opacity: 0.9; }

        .btn-locked {
            background: #2b313a;
            color: var(--text-muted);
            cursor: pointer;
        }

        /* Footer */
        .site-footer {
            background: #14161c;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 40px 0 20px;
            margin-top: 60px;
            font-size: 14px;
            text-align: center;
        }

        /* Floating Support */
        .floating-support { position: fixed; bottom: 20px; right: 20px; z-index: 99; display: flex; flex-direction: column; gap: 10px; }
        .support-btn { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.4); transition: transform 0.2s; }
        .support-btn:hover { transform: scale(1.1); }
        .sup-telegram { background: #0088cc; } .sup-whatsapp { background: #25d366; } .sup-email { background: var(--accent-blue); }
    </style>
</head>
<body>

    <?php if (file_exists('inc/navbar.php')) include 'inc/navbar.php'; ?>

    <main class="container">
        <div class="header-title">
            <h1><i class="fa-solid fa-list-check" style="color: var(--green);"></i> All Arbitrage Opportunities (50+ Markets)</h1>
            <span style="color: var(--text-muted);" id="dealCount">Loading live deals...</span>
        </div>

        <?php if ($is_logged_in): ?>
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: gap; font-size: 14px;">
                <span><i class="fa-solid fa-wallet" style="color: var(--green);"></i> Real Account Balance: <strong>$<?= number_format($user_real_balance, 2) ?> USDT</strong></span>
                <?php if ($user_real_balance < 3000): ?>
                    <span style="color: var(--warning);"><i class="fa-solid fa-triangle-exclamation"></i> Deposit $3,000+ to unlock high-yield &gt;3% arbitrage routes</span>
                <?php else: ?>
                    <span style="color: var(--green);"><i class="fa-solid fa-circle-check"></i> VIP High-Yield Access Active</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Toolbar -->
        <div class="filter-card">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search by coin name, symbol, or exchange..." oninput="filterAndRenderDeals()">
            </div>
            <div class="filter-select">
                <select id="sortSelect" onchange="filterAndRenderDeals()">
                    <option value="profit_desc">Highest Profit Margin</option>
                    <option value="profit_asc">Lowest Profit Margin</option>
                    <option value="name_asc">Coin Name (A-Z)</option>
                </select>
            </div>
        </div>

        <!-- Deals Grid -->
        <div class="deals-grid" id="allDealsGrid">
            <!-- Dynamically populated via live JS feed -->
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> ArbitragePro. All rights reserved. Live CEX &amp; DEX Arbitrage Infrastructure.</p>
        </div>
    </footer>

    <!-- Floating Support -->
    <div class="floating-support">
        <a href="https://t.me/<?= e(ltrim($support['telegram'], '@')) ?>" target="_blank" class="support-btn sup-telegram" title="Telegram Support">
            <i class="fa-brands fa-telegram"></i>
        </a>
        <a href="https://wa.me/<?= e($whatsapp_number) ?>?text=Hello%20ArbitragePro%20Support" target="_blank" class="support-btn sup-whatsapp" title="WhatsApp Support">
            <i class="fa-brands fa-whatsapp"></i>
        </a>
        <a href="mailto:<?= e($support['email']) ?>" class="support-btn sup-email" title="Email Support">
            <i class="fa-solid fa-envelope"></i>
        </a>
    </div>

    <script>
        const userRealBalance = <?= json_encode($user_real_balance) ?>;
        let allDealsData = [];

        async function fetchAllLiveDeals() {
            try {
                const response = await fetch('https://api.binance.com/api/v3/ticker/price');
                const data = await response.json();
                const prices = {};
                data.forEach(item => { prices[item.symbol] = parseFloat(item.price); });

                // Fallback base prices if offline
                const getPrice = (sym, fallback) => prices[sym + 'USDT'] || fallback;

                const makeDeal = (id, symbol, name, buyEx, buyP, sellEx, sellP, spread, time, vol) => {
                    return { id, symbol, name, buyEx, buyPrice: buyP, sellEx, sellPrice: sellP, profit: spread, time, vol };
                };

                // Expanded 50+ marketplace list synced with live market pricing
                allDealsData = [
                    makeDeal(1, 'BTC', 'Bitcoin', 'Binance (Spot)', getPrice('BTC', 64200) * 0.998, 'Hyperliquid (Perp)', getPrice('BTC', 64200) * 1.021, 2.30, '15 - 30 mins', '45M USDT'),
                    makeDeal(2, 'ETH', 'Ethereum', 'Uniswap v3 (DEX)', getPrice('ETH', 3410) * 0.997, 'Bybit (Futures)', getPrice('ETH', 3410) * 1.038, 4.10, '20 - 40 mins', '32M USDT'),
                    makeDeal(3, 'SOL', 'Solana', 'Raydium (DEX)', getPrice('SOL', 141) * 0.996, 'OKX (Perp)', getPrice('SOL', 141) * 1.025, 2.90, '10 - 25 mins', '28M USDT'),
                    makeDeal(4, 'XRP', 'Ripple', 'Gate.io (Spot)', getPrice('XRP', 0.58) * 0.995, 'Bitget (Futures)', getPrice('XRP', 0.58) * 1.045, 5.02, '15 - 30 mins', '19M USDT'),
                    makeDeal(5, 'SUI', 'Sui Network', 'Cetus (DEX)', getPrice('SUI', 1.82) * 0.994, 'Binance (Perp)', getPrice('SUI', 1.82) * 1.054, 6.03, '10 - 20 mins', '24M USDT'),
                    makeDeal(6, 'PEPE', 'Pepe Coin', 'Uniswap v3 (DEX)', getPrice('PEPE', 0.0000092) * 0.99, 'MECX (Spot)', getPrice('PEPE', 0.0000092) * 1.034, 4.44, '10 - 15 mins', '18M USDT'),
                    makeDeal(7, 'AVAX', 'Avalanche', 'TraderJoe (DEX)', getPrice('AVAX', 26.40) * 0.997, 'Deribit (Futures)', getPrice('AVAX', 26.40) * 1.022, 2.50, '20 - 35 mins', '14M USDT'),
                    makeDeal(8, 'LINK', 'Chainlink', 'KuCoin (Spot)', getPrice('LINK', 11.20) * 0.996, 'dYdX (DEX Perp)', getPrice('LINK', 11.20) * 1.039, 4.31, '15 - 30 mins', '11M USDT'),
                    makeDeal(9, 'ADA', 'Cardano', 'Binance (Spot)', getPrice('ADA', 0.38) * 0.998, 'Kraken (Futures)', getPrice('ADA', 0.38) * 1.021, 2.30, '15 - 30 mins', '16M USDT'),
                    makeDeal(10, 'MATIC', 'Polygon', 'Uniswap (DEX)', getPrice('MATIC', 0.52) * 0.995, 'OKX (Spot)', getPrice('MATIC', 0.52) * 1.028, 3.31, '10 - 20 mins', '12M USDT'),
                    makeDeal(11, 'DOT', 'Polkadot', 'KuCoin (Spot)', getPrice('DOT', 6.10) * 0.997, 'Bybit (Perp)', getPrice('DOT', 6.10) * 1.032, 3.51, '20 - 30 mins', '15M USDT'),
                    makeDeal(12, 'APT', 'Aptos', 'Panora (DEX)', getPrice('APT', 8.40) * 0.994, 'Binance (Perp)', getPrice('APT', 8.40) * 1.042, 4.83, '15 - 25 mins', '13M USDT'),
                    makeDeal(13, 'NEAR', 'Near Protocol', 'Ref Finance (DEX)', getPrice('NEAR', 5.15) * 0.995, 'Gate.io (Spot)', getPrice('NEAR', 5.15) * 1.024, 2.91, '10 - 20 mins', '17M USDT'),
                    makeDeal(14, 'ARB', 'Arbitrum', 'Camelot (DEX)', getPrice('ARB', 0.65) * 0.993, 'Binance (Perp)', getPrice('ARB', 0.65) * 1.036, 4.33, '15 - 30 mins', '21M USDT'),
                    makeDeal(15, 'OP', 'Optimism', 'Velodrome (DEX)', getPrice('OP', 1.70) * 0.995, 'Bybit (Perp)', getPrice('OP', 1.70) * 1.022, 2.71, '15 - 30 mins', '10M USDT'),
                    makeDeal(16, 'DOGE', 'Dogecoin', 'Binance (Spot)', getPrice('DOGE', 0.12) * 0.996, 'OKX (Futures)', getPrice('DOGE', 0.12) * 1.052, 5.62, '10 - 25 mins', '38M USDT'),
                    makeDeal(17, 'ICP', 'Internet Computer', 'Coinbase (Spot)', getPrice('ICP', 8.20) * 0.997, 'Binance (Perp)', getPrice('ICP', 8.20) * 1.023, 2.61, '15 - 30 mins', '9M USDT'),
                    makeDeal(18, 'RENDER', 'Render', 'Uniswap v3 (DEX)', getPrice('RENDER', 5.40) * 0.994, 'Bybit (Perp)', getPrice('RENDER', 5.40) * 1.049, 5.53, '10 - 20 mins', '22M USDT'),
                    makeDeal(19, 'INJ', 'Injective', 'Helix (DEX)', getPrice('INJ', 18.50) * 0.995, 'OKX (Futures)', getPrice('INJ', 18.50) * 1.031, 3.63, '20 - 30 mins', '12M USDT'),
                    makeDeal(20, 'TIA', 'Celestia', 'Osmosis (DEX)', getPrice('TIA', 4.80) * 0.993, 'Binance (Perp)', getPrice('TIA', 4.80) * 1.055, 6.24, '10 - 20 mins', '16M USDT'),
                    makeDeal(21, 'FIL', 'Filecoin', 'Gate.io (Spot)', getPrice('FIL', 3.90) * 0.996, 'KuCoin (Perp)', getPrice('FIL', 3.90) * 1.021, 2.51, '20 - 40 mins', '8M USDT'),
                    makeDeal(22, 'STX', 'Stacks', 'Bitfinex (Spot)', getPrice('STX', 1.70) * 0.995, 'Binance (Perp)', getPrice('STX', 1.70) * 1.042, 4.72, '15 - 30 mins', '11M USDT'),
                    makeDeal(23, 'ETC', 'Ethereum Classic', 'Kraken (Spot)', getPrice('ETC', 22.00) * 0.997, 'OKX (Futures)', getPrice('ETC', 22.00) * 1.024, 2.71, '15 - 30 mins', '7M USDT'),
                    makeDeal(24, 'ALGO', 'Algorand', 'Pact (DEX)', getPrice('ALGO', 0.18) * 0.994, 'Binance (Spot)', getPrice('ALGO', 0.18) * 1.038, 4.43, '10 - 20 mins', '9M USDT'),
                    makeDeal(25, 'UNI', 'Uniswap', 'Uniswap v3 (DEX)', getPrice('UNI', 7.80) * 0.997, 'Bybit (Perp)', getPrice('UNI', 7.80) * 1.025, 2.81, '15 - 30 mins', '14M USDT'),
                    makeDeal(26, 'ATOM', 'Cosmos', 'Osmosis (DEX)', getPrice('ATOM', 4.20) * 0.995, 'Binance (Spot)', getPrice('ATOM', 4.20) * 1.033, 3.82, '20 - 30 mins', '10M USDT'),
                    makeDeal(27, 'XLM', 'Stellar', 'Kraken (Spot)', getPrice('XLM', 0.09) * 0.996, 'OKX (Perp)', getPrice('XLM', 0.09) * 1.041, 4.52, '10 - 25 mins', '12M USDT'),
                    makeDeal(28, 'SHIB', 'Shiba Inu', 'Shibaswap (DEX)', getPrice('SHIB', 0.000018) * 0.99, 'Binance (Spot)', getPrice('SHIB', 0.000018) * 1.061, 7.17, '10 - 15 mins', '35M USDT'),
                    makeDeal(29, 'KAS', 'Kaspa', 'Gate.io (Spot)', getPrice('KAS', 0.14) * 0.995, 'KuCoin (Perp)', getPrice('KAS', 0.14) * 1.028, 3.32, '15 - 30 mins', '15M USDT'),
                    makeDeal(30, 'TON', 'Toncoin', 'DeDust (DEX)', getPrice('TON', 5.20) * 0.994, 'Bybit (Perp)', getPrice('TON', 5.20) * 1.037, 4.33, '10 - 20 mins', '26M USDT'),
                    makeDeal(31, 'FLOKI', 'Floki', 'Uniswap v3 (DEX)', getPrice('FLOKI', 0.00015) * 0.99, 'Gate.io (Spot)', getPrice('FLOKI', 0.00015) * 1.058, 6.87, '10 - 15 mins', '18M USDT'),
                    makeDeal(32, 'BONK', 'Bonk', 'Raydium (DEX)', getPrice('BONK', 0.000022) * 0.99, 'Binance (Perp)', getPrice('BONK', 0.000022) * 1.065, 7.58, '10 - 15 mins', '20M USDT'),
                    makeDeal(33, 'JUP', 'Jupiter', 'Meteora (DEX)', getPrice('JUP', 0.95) * 0.995, 'OKX (Perp)', getPrice('JUP', 0.95) * 1.042, 4.72, '10 - 20 mins', '16M USDT'),
                    makeDeal(34, 'WIF', 'dogwifhat', 'Raydium (DEX)', getPrice('WIF', 2.10) * 0.992, 'Binance (Perp)', getPrice('WIF', 2.10) * 1.071, 7.96, '10 - 20 mins', '29M USDT'),
                    makeDeal(35, 'RUNE', 'Thorchain', 'THORSwap (DEX)', getPrice('RUNE', 4.80) * 0.994, 'KuCoin (Spot)', getPrice('RUNE', 4.80) * 1.036, 4.23, '15 - 30 mins', '11M USDT'),
                    makeDeal(36, 'SEI', 'Sei', 'Astroport (DEX)', getPrice('SEI', 0.38) * 0.995, 'Bybit (Perp)', getPrice('SEI', 0.38) * 1.044, 4.92, '10 - 20 mins', '13M USDT'),
                    makeDeal(37, 'ENA', 'Ethena', 'Uniswap v3 (DEX)', getPrice('ENA', 0.35) * 0.993, 'Binance (Perp)', getPrice('ENA', 0.35) * 1.062, 6.95, '10 - 20 mins', '22M USDT'),
                    makeDeal(38, 'ONDO', 'Ondo', 'Uniswap v3 (DEX)', getPrice('ONDO', 0.85) * 0.996, 'OKX (Perp)', getPrice('ONDO', 0.85) * 1.032, 3.61, '15 - 30 mins', '17M USDT'),
                    makeDeal(39, 'PENDLE', 'Pendle', 'TraderJoe (DEX)', getPrice('PENDLE', 3.80) * 0.994, 'Binance (Spot)', getPrice('PENDLE', 3.80) * 1.051, 5.74, '15 - 25 mins', '9M USDT'),
                    makeDeal(40, 'STRK', 'Starknet', 'Ekubo (DEX)', getPrice('STRK', 0.42) * 0.992, 'OKX (Perp)', getPrice('STRK', 0.42) * 1.048, 5.64, '10 - 20 mins', '10M USDT'),
                    makeDeal(41, 'MANTA', 'Manta Network', 'Airstack (DEX)', getPrice('MANTA', 0.75) * 0.995, 'Bybit (Perp)', getPrice('MANTA', 0.75) * 1.034, 3.92, '15 - 30 mins', '8M USDT'),
                    makeDeal(42, 'AERO', 'Aerodrome', 'Aerodrome (DEX)', getPrice('AERO', 1.20) * 0.993, 'Coinbase (Spot)', getPrice('AERO', 1.20) * 1.068, 7.55, '10 - 20 mins', '12M USDT'),
                    makeDeal(43, 'AXS', 'Axie Infinity', 'Binance (Spot)', getPrice('AXS', 5.10) * 0.996, 'OKX (Futures)', getPrice('AXS', 5.10) * 1.025, 2.91, '20 - 30 mins', '7M USDT'),
                    makeDeal(44, 'SAND', 'The Sandbox', 'Uniswap (DEX)', getPrice('SAND', 0.31) * 0.995, 'Bybit (Perp)', getPrice('SAND', 0.31) * 1.031, 3.62, '15 - 30 mins', '9M USDT'),
                    makeDeal(45, 'MANA', 'Decentraland', 'Gate.io (Spot)', getPrice('MANA', 0.33) * 0.994, 'Binance (Spot)', getPrice('MANA', 0.33) * 1.029, 3.52, '15 - 30 mins', '8M USDT'),
                    makeDeal(46, 'GMT', 'STEPN', 'Raydium (DEX)', getPrice('GMT', 0.14) * 0.992, 'OKX (Perp)', getPrice('GMT', 0.14) * 1.042, 5.04, '10 - 20 mins', '11M USDT'),
                    makeDeal(47, 'CHZ', 'Chiliz', 'Binance (Spot)', getPrice('CHZ', 0.07) * 0.996, 'KuCoin (Perp)', getPrice('CHZ', 0.07) * 1.024, 2.81, '15 - 30 mins', '10M USDT'),
                    makeDeal(48, 'ENJ', 'Enjin Coin', 'Crypto.com', getPrice('ENJ', 0.19) * 0.995, 'Binance (Spot)', getPrice('ENJ', 0.19) * 1.033, 3.82, '15 - 30 mins', '6M USDT'),
                    makeDeal(49, 'CRV', 'Curve DAO', 'Curve (DEX)', getPrice('CRV', 0.32) * 0.991, 'Bybit (Perp)', getPrice('CRV', 0.32) * 1.061, 7.06, '10 - 20 mins', '14M USDT'),
                    makeDeal(50, 'GALA', 'Gala', 'Uniswap v3 (DEX)', getPrice('GALA', 0.022) * 0.99, 'Binance (Perp)', getPrice('GALA', 0.022) * 1.055, 6.57, '10 - 20 mins', '19M USDT')
                ];

                filterAndRenderDeals();
            } catch (err) {
                console.error("Error fetching live deals:", err);
            }
        }

        function filterAndRenderDeals() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const sortBy = document.getElementById('sortSelect').value;

            let filtered = allDealsData.filter(d => 
                d.name.toLowerCase().includes(query) || 
                d.symbol.toLowerCase().includes(query) || 
                d.buyEx.toLowerCase().includes(query) || 
                d.sellEx.toLowerCase().includes(query)
            );

            if (sortBy === 'profit_desc') {
                filtered.sort((a, b) => b.profit - a.profit);
            } else if (sortBy === 'profit_asc') {
                filtered.sort((a, b) => a.profit - b.profit);
            } else if (sortBy === 'name_asc') {
                filtered.sort((a, b) => a.name.localeCompare(b.name));
            }

            document.getElementById('dealCount').innerText = filtered.length + ' Deals Available';

            let html = '';
            if (filtered.length > 0) {
                filtered.forEach(d => {
                    const isLocked = (d.profit > 3.00 && userRealBalance < 3000.00);

                    html += `
                        <div class="trade-card">
                            <div>
                                <div class="card-top">
                                    <div class="coin-info">
                                        <div class="coin-icon">${d.symbol.substring(0, 3)}</div>
                                        <div>
                                            <h4 style="font-size: 15px;">${d.name}</h4>
                                            <span style="font-size: 12px; color: var(--text-muted);">${d.symbol}/USDT</span>
                                        </div>
                                    </div>
                                    <div class="profit-tag">+${d.profit.toFixed(2)}%</div>
                                </div>
                                <div class="exchange-route">
                                    ${isLocked ? `
                                        <div class="blur-target">
                                            <div class="route-row">
                                                <span style="color: var(--text-muted);">Buy On:</span>
                                                <strong>${d.buyEx} ($${d.buyPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4})})</strong>
                                            </div>
                                            <div class="route-row">
                                                <span style="color: var(--text-muted);">Sell On:</span>
                                                <strong style="color: var(--green);">${d.sellEx} ($${d.sellPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4})})</strong>
                                            </div>
                                        </div>
                                        <div class="lock-overlay">
                                            <span><i class="fa-solid fa-lock"></i> &gt;3% Yield (&ge;$3k Bal Required)</span>
                                            <a href="deposit.php" class="btn-unlock"><i class="fa-solid fa-unlock"></i> Unlock Route</a>
                                        </div>
                                    ` : `
                                        <div class="route-row">
                                            <span style="color: var(--text-muted);">Buy On:</span>
                                            <strong>${d.buyEx} ($${d.buyPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4})})</strong>
                                        </div>
                                        <div class="route-row">
                                            <span style="color: var(--text-muted);">Sell On:</span>
                                            <strong style="color: var(--green);">${d.sellEx} ($${d.sellPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4})})</strong>
                                        </div>
                                    `}
                                </div>
                            </div>
                            <div>
                                <div class="card-meta">
                                    <span><i class="fa-regular fa-clock"></i> ${d.time}</span>
                                    <span><i class="fa-solid fa-shield-halved"></i> Vol: ${d.vol}</span>
                                </div>
                                ${isLocked ? `
                                    <a href="deposit.php" class="btn-trade btn-locked"><i class="fa-solid fa-lock"></i> Deposit to Unlock</a>
                                ` : `
                                    <form action="trade.php" method="POST">
                                        <input type="hidden" name="symbol" value="${d.symbol}">
                                        <input type="hidden" name="coin_name" value="${d.name}">
                                        <input type="hidden" name="buy_exchange" value="${d.buyEx}">
                                        <input type="hidden" name="sell_exchange" value="${d.sellEx}">
                                        <input type="hidden" name="buy_price" value="${d.buyPrice}">
                                        <input type="hidden" name="sell_price" value="${d.sellPrice}">
                                        <input type="hidden" name="profit_percentage" value="${d.profit}">
                                        <input type="hidden" name="estimated_time" value="${d.time}">
                                        <button type="submit" class="btn-trade"><i class="fa-solid fa-bolt"></i> Trade Now</button>
                                    </form>
                                `}
                            </div>
                        </div>
                    `;
                });
            } else {
                html = `<div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-muted); background: var(--bg-card); border-radius: 10px;">
                    <i class="fa-solid fa-filter-circle-xmark" style="font-size: 40px; margin-bottom: 12px;"></i>
                    <p>No arbitrage opportunities matched your search criteria.</p>
                </div>`;
            }

            document.getElementById('allDealsGrid').innerHTML = html;
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchAllLiveDeals();
            setInterval(fetchAllLiveDeals, 15 * 60 * 1000); // Auto-refresh every 15 mins
        });
    </script>
</body>
</html>
