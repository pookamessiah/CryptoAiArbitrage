<?php
// all-trades.php - Full Arbitrage Marketplace with Live 15-Min Auto-Refresh Prices
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
    $pdo =$db->connect();
} catch (Exception $e) {
    die($e->getMessage());
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Support details
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
        .support-btn { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.4); }
        .sup-telegram { background: #0088cc; } .sup-whatsapp { background: #25d366; } .sup-email { background: var(--accent-blue); }
    </style>
</head>
<body>

    <?php if (file_exists('inc/navbar.php')) include 'inc/navbar.php'; ?>

    <main class="container">
        <div class="header-title">
            <h1><i class="fa-solid fa-list-check" style="color: var(--green);"></i> All Arbitrage Opportunities</h1>
            <span style="color: var(--text-muted);" id="dealCount">Loading live deals...</span>
        </div>

        <!-- Filter Toolbar -->
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
        let allDealsData = [];

        async function fetchAllLiveDeals() {
            try {
                const response = await fetch('https://api.binance.com/api/v3/ticker/price');
                const data = await response.json();
                const prices = {};
                data.forEach(item => { prices[item.symbol] = parseFloat(item.price); });

                // Expanded dataset of 16+ coins
                const btc = prices['BTCUSDT'] || 64200;
                const eth = prices['ETHUSDT'] || 3410;
                const sol = prices['SOLUSDT'] || 141.5;
                const xrp = prices['XRPUSDT'] || 0.58;
                const sui = prices['SUIUSDT'] || 1.82;
                const pepe = prices['PEPEUSDT'] || 0.0000092;
                const avax = prices['AVAXUSDT'] || 26.40;
                const link = prices['LINKUSDT'] || 11.20;
                const ada = prices['ADAUSDT'] || 0.38;
                const matic = prices['MATICUSDT'] || 0.52;
                const dot = prices['DOTUSDT'] || 6.10;
                const apt = prices['APTUSDT'] || 8.40;
                const near = prices['NEARUSDT'] || 5.15;
                const arb = prices['ARBUSDT'] || 0.65;
                const op = prices['OPUSDT'] || 1.70;
                const doge = prices['DOGEUSDT'] || 0.12;

                const makeDeal = (id, symbol, name, buyEx, buyP, sellEx, sellP, time) => {
                    const profit = ((sellP - buyP) / buyP) * 100;
                    return { id, symbol, name, buyEx, buyPrice: buyP, sellEx, sellPrice: sellP, profit, time };
                };

                allDealsData = [
                    makeDeal(1, 'BTC', 'Bitcoin', 'Binance (Spot)', btc, 'Hyperliquid (Perp)', btc * 1.046, '15 - 30 mins'),
                    makeDeal(2, 'ETH', 'Ethereum', 'Uniswap v3 (DEX)', eth * 0.998, 'Bybit (Futures)', eth * 1.062, '20 - 40 mins'),
                    makeDeal(3, 'SOL', 'Solana', 'Raydium (DEX)', sol * 0.997, 'OKX (Perp)', sol * 1.078, '10 - 25 mins'),
                    makeDeal(4, 'XRP', 'Ripple', 'Gate.io (Spot)', xrp * 0.995, 'Bitget (Futures)', xrp * 1.082, '15 - 30 mins'),
                    makeDeal(5, 'SUI', 'Sui Network', 'Cetus (DEX)', sui * 0.996, 'Binance (Perp)', sui * 1.0934, '10 - 20 mins'),
                    makeDeal(6, 'PEPE', 'Pepe Coin', 'Uniswap v3 (DEX)', pepe * 0.99, 'MECX (Spot)', pepe * 1.1087, '10 - 15 mins'),
                    makeDeal(7, 'AVAX', 'Avalanche', 'TraderJoe (DEX)', avax * 0.998, 'Deribit (Futures)', avax * 1.075, '20 - 35 mins'),
                    makeDeal(8, 'LINK', 'Chainlink', 'KuCoin (Spot)', link * 0.995, 'dYdX (DEX Perp)', link * 1.0848, '15 - 30 mins'),
                    makeDeal(9, 'ADA', 'Cardano', 'Binance (Spot)', ada, 'Kraken (Futures)', ada * 1.065, '15 - 30 mins'),
                    makeDeal(10, 'MATIC', 'Polygon', 'Uniswap (DEX)', matic * 0.99, 'OKX (Spot)', matic * 1.072, '10 - 20 mins'),
                    makeDeal(11, 'DOT', 'Polkadot', 'KuCoin (Spot)', dot, 'Bybit (Perp)', dot * 1.068, '20 - 30 mins'),
                    makeDeal(12, 'APT', 'Aptos', 'Panora (DEX)', apt * 0.995, 'Binance (Perp)', apt * 1.085, '15 - 25 mins'),
                    makeDeal(13, 'NEAR', 'Near Protocol', 'Ref Finance (DEX)', near * 0.992, 'Gate.io (Spot)', near * 1.079, '10 - 20 mins'),
                    makeDeal(14, 'ARB', 'Arbitrum', 'Camelot (DEX)', arb * 0.994, 'Binance (Perp)', arb * 1.091, '15 - 30 mins'),
                    makeDeal(15, 'OP', 'Optimism', 'Velodrome (DEX)', op * 0.993, 'Bybit (Perp)', op * 1.088, '15 - 30 mins'),
                    makeDeal(16, 'DOGE', 'Dogecoin', 'Binance (Spot)', doge, 'OKX (Futures)', doge * 1.095, '10 - 25 mins')
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
                                    <div class="route-row">
                                        <span style="color: var(--text-muted);">Buy On:</span>
                                        <strong>${d.buyEx} ($${d.buyPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4})})</strong>
                                    </div>
                                    <div class="route-row">
                                        <span style="color: var(--text-muted);">Sell On:</span>
                                        <strong style="color: var(--green);">${d.sellEx} ($${d.sellPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4})})</strong>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="card-meta">
                                    <span><i class="fa-regular fa-clock"></i> ${d.time}</span>
                                    <span><i class="fa-solid fa-shield-halved"></i> Managed</span>
                                </div>
                                <a href="trade.php?id=${d.symbol}" class="btn-trade">Trade Now</a>
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
            setInterval(fetchAllLiveDeals, 15 * 60 * 1000); // 15 mins
        });
    </script>
</body>
</html>
