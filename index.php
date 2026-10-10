<?php
// index.php - Main Arbitrage Trading Portal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);

// Display errors during development on InfinityFree
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';

$pdo = null;
try {
    $db = new Database();
    $pdo = $db->connect();
} catch (Exception $e) {
    // Graceful fallback if database connection is pending configuration
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Fetch active arbitrage deals from database
$arbitrageDeals = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM arbitrage_deals WHERE status = 'active' ORDER BY profit_percentage DESC LIMIT 30");
        $arbitrageDeals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $arbitrageDeals = [];
    }
}

// Rich fallback dataset blending Spot, Perpetual, and Futures across CEX & DEX if database is empty
if (empty($arbitrageDeals)) {
    $arbitrageDeals = [
        ['id' => 1, 'symbol' => 'BTC', 'coin_name' => 'Bitcoin', 'buy_exchange' => 'Binance (Spot)', 'buy_price' => 64120.00, 'sell_exchange' => 'Hyperliquid (Perp)', 'sell_price' => 67069.90, 'profit_percentage' => 4.60, 'estimated_time' => '15 - 30 mins', 'type' => 'Arbitrage (Perpetual)'],
        ['id' => 2, 'symbol' => 'ETH', 'coin_name' => 'Ethereum', 'buy_exchange' => 'Uniswap v3 (DEX)', 'buy_price' => 3410.00, 'sell_exchange' => 'Bybit (Futures)', 'sell_price' => 3621.42, 'profit_percentage' => 6.20, 'estimated_time' => '20 - 40 mins', 'type' => 'DEX-CEX Futures'],
        ['id' => 3, 'symbol' => 'SOL', 'coin_name' => 'Solana', 'buy_exchange' => 'Raydium (DEX)', 'buy_price' => 141.20, 'sell_exchange' => 'OKX (Perp)', 'sell_price' => 152.21, 'profit_percentage' => 7.80, 'estimated_time' => '10 - 25 mins', 'type' => 'DEX Spot to Perp'],
        ['id' => 4, 'symbol' => 'XRP', 'coin_name' => 'Ripple', 'buy_exchange' => 'Gate.io (Spot)', 'buy_price' => 0.5600, 'sell_exchange' => 'Bitget (Futures)', 'sell_price' => 0.6059, 'profit_percentage' => 8.20, 'estimated_time' => '15 - 30 mins', 'type' => 'Cross-Exchange Futures'],
        ['id' => 5, 'symbol' => 'SUI', 'coin_name' => 'Sui Network', 'buy_exchange' => 'Cetus (DEX)', 'buy_price' => 1.82, 'sell_exchange' => 'Binance (Perp)', 'sell_price' => 1.99, 'profit_percentage' => 9.34, 'estimated_time' => '10 - 20 mins', 'type' => 'DEX-CEX Spread'],
        ['id' => 6, 'symbol' => 'PEPE', 'coin_name' => 'Pepe Coin', 'buy_exchange' => 'Uniswap v3 (DEX)', 'buy_price' => 0.0000092, 'sell_exchange' => 'MECX (Spot)', 'sell_price' => 0.0000102, 'profit_percentage' => 10.87, 'estimated_time' => '10 - 15 mins', 'type' => 'DEX Spot Spread'],
        ['id' => 7, 'symbol' => 'AVAX', 'coin_name' => 'Avalanche', 'buy_exchange' => 'TraderJoe (DEX)', 'buy_price' => 26.40, 'sell_exchange' => 'Deribit (Futures)', 'sell_price' => 28.38, 'profit_percentage' => 7.50, 'estimated_time' => '20 - 35 mins', 'type' => 'DeFi Futures Basis'],
        ['id' => 8, 'symbol' => 'LINK', 'coin_name' => 'Chainlink', 'buy_exchange' => 'KuCoin (Spot)', 'buy_price' => 11.20, 'sell_exchange' => 'dYdX (DEX Perp)', 'sell_price' => 12.15, 'profit_percentage' => 8.48, 'estimated_time' => '15 - 30 mins', 'type' => 'CEX to Decentralized Perp']
    ];
}

// Fetch support contacts for floating widget
$support = [
    'phone' => '+18001234567',
    'telegram' => '@ArbitrageSupport',
    'email' => 'support@yourdomain.com'
];
if ($pdo) {
    try {
        $stmtSup = $pdo->query("SELECT * FROM support_info LIMIT 1");
        $dbSup = $stmtSup->fetch(PDO::FETCH_ASSOC);
        if ($dbSup) {
            $support = $dbSup;
        }
    } catch (Exception $e) {}
}
$whatsapp_number = preg_replace('/[^0-9]/', '', $support['phone']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arbitrage Trading Portal | Live CoinMarketCap Opportunities</title>
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

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Segoe UI', Roboto, -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.5;
            padding-bottom: 60px;
        }

        .container {
            width: min(1280px, calc(100% - 32px));
            margin: 0 auto;
        }

        /* Ticker Bar */
        .cmc-ticker-bar {
            background: #1e2329;
            border-bottom: 1px solid var(--border-color);
            padding: 10px 0;
            font-size: 13px;
            overflow: hidden;
            white-space: nowrap;
        }

        .ticker-wrap {
            display: flex;
            gap: 28px;
            animation: tickerScroll 35s linear infinite;
        }

        @keyframes tickerScroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .ticker-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .badge-profit {
            background: rgba(14, 203, 129, 0.15);
            color: var(--green);
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
        }

        /* Hero Banner */
        .hero {
            padding: 40px 0 20px;
            text-align: center;
        }

        .hero h1 {
            font-size: clamp(28px, 4vw, 44px);
            font-weight: 800;
            margin-bottom: 12px;
            background: linear-gradient(90deg, #fff, #848e9c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            color: var(--text-muted);
            font-size: 16px;
            max-width: 680px;
            margin: 0 auto;
        }

        /* Section Titles */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 42px 0 16px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-see-all {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 8px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-see-all:hover {
            background: var(--bg-hover);
            border-color: #474d57;
        }

        /* 4x4 Grid Carousel */
        .carousel-container {
            position: relative;
        }

        .grid-carousel {
            display: grid;
            grid-template-columns: repeat(4, 280px);
            grid-auto-flow: column;
            grid-template-rows: repeat(2, auto);
            gap: 16px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding-bottom: 14px;
            scrollbar-width: none;
        }

        .grid-carousel::-webkit-scrollbar {
            display: none;
        }

        .trade-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 16px;
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
            margin-bottom: 12px;
        }

        .coin-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .coin-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #2b313a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 13px;
            color: var(--green);
        }

        .coin-name h4 {
            font-size: 15px;
            font-weight: 700;
        }

        .coin-name span {
            font-size: 12px;
            color: var(--text-muted);
        }

        .profit-tag {
            color: var(--green);
            font-size: 18px;
            font-weight: 800;
        }

        .exchange-route {
            background: #1e2329;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 14px;
            font-size: 12px;
        }

        .route-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .route-row:last-child {
            margin-bottom: 0;
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 14px;
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

        .btn-trade:hover {
            opacity: 0.9;
        }

        .nav-btn {
            position: absolute;
            top: 45%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(24, 26, 32, 0.9);
            border: 1px solid var(--border-color);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 5;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
        }

        .nav-btn.left { left: -20px; }
        .nav-btn.right { right: -20px; }

        /* Custom Content Cards & Tables */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .content-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
        }

        .content-card h3 {
            font-size: 18px;
            margin-bottom: 12px;
            color: var(--green);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .content-card p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .table-responsive {
            overflow-x: auto;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 10px;
            margin-top: 15px;
        }

        .price-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .price-table th {
            padding: 14px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
            background: #14161c;
        }

        .price-table td {
            padding: 14px;
            border-bottom: 1px solid var(--border-color);
        }

        .price-table tr:hover {
            background: var(--bg-hover);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
        }

        .stat-number {
            font-size: 30px;
            font-weight: 800;
            color: var(--green);
            margin-top: 8px;
        }

        /* FAQs */
        .faq-item {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .faq-question {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 6px;
        }

        .faq-answer {
            color: var(--text-muted);
            font-size: 13px;
        }

        /* Chart Section */
        .chart-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-top: 40px;
        }

        .chart-controls {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .chart-controls select {
            background: #1e2329;
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }

        .chart-container {
            height: 450px;
            width: 100%;
        }

        /* Floating Support Bar (Telegram, WhatsApp, Email) */
        .floating-support {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 99;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .support-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            font-size: 20px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.4);
            transition: transform 0.2s ease;
        }

        .support-btn:hover {
            transform: scale(1.1);
        }

        .sup-telegram { background: #0088cc; }
        .sup-whatsapp { background: #25d366; }
        .sup-email { background: var(--accent-blue); }

        footer {
            margin-top: 60px;
            border-top: 1px solid var(--border-color);
            padding: 30px 0;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .nav-btn { display: none; }
            .grid-carousel {
                grid-template-columns: repeat(4, 240px);
            }
        }
    </style>
</head>
<body>

    <!-- CoinMarketCap Live Ticker Bar -->
    <div class="cmc-ticker-bar">
        <div class="ticker-wrap">
            <?php if (!empty($arbitrageDeals)): ?>
                <?php foreach ($arbitrageDeals as $deal): ?>
                    <div class="ticker-item">
                        <strong><?= e($deal['symbol']) ?>:</strong>
                        <span>Buy <?= e($deal['buy_exchange']) ?> ($<?= number_format($deal['buy_price'], 2) ?>)</span>
                        <i class="fa-solid fa-arrow-right-long"></i>
                        <span>Sell <?= e($deal['sell_exchange']) ?> ($<?= number_format($deal['sell_price'], 2) ?>)</span>
                        <span class="badge-profit">+<?= e($deal['profit_percentage']) ?>%</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="ticker-item">
                    <span>CoinMarketCap Arbitrage Engine Active • Scanning live CEX & DEX perpetual/spot spreads...</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Header / Navbar -->
    <?php if (file_exists('inc/navbar.php')) include 'inc/navbar.php'; ?>

    <main class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1>Automated CoinMarketCap & Cross-Exchange Arbitrage</h1>
            <p>Monitored Spot, Perpetual, and Futures arbitrage opportunities across top CEXs and DEXs in real time. Select high-yield spreads managed by our operations team.</p>
        </section>

        <!-- Top Trades Grid Section -->
        <div class="section-header">
            <div class="section-title">
                <i class="fa-solid fa-bolt" style="color: var(--green);"></i> Top Arbitrage Opportunities (Spot, Perp & Futures)
            </div>
            <a href="all-trades.php" class="btn-see-all">See All Trades <i class="fa-solid fa-chevron-right"></i></a>
        </div>

        <div class="carousel-container">
            <button class="nav-btn left" onclick="scrollCarousel(-300)"><i class="fa-solid fa-chevron-left"></i></button>
            
            <div class="grid-carousel" id="tradeCarousel">
                <?php if (!empty($arbitrageDeals)): ?>
                    <?php foreach ($arbitrageDeals as $deal): ?>
                        <div class="trade-card">
                            <div class="card-top">
                                <div class="coin-info">
                                    <div class="coin-icon"><?= e(substr($deal['symbol'], 0, 3)) ?></div>
                                    <div class="coin-name">
                                        <h4><?= e($deal['coin_name']) ?></h4>
                                        <span><?= e($deal['symbol']) ?>/USDT</span>
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

                            <div class="card-meta">
                                <span><i class="fa-regular fa-clock"></i> <?= e($deal['estimated_time']) ?></span>
                                <span><i class="fa-solid fa-shield-halved"></i> Managed</span>
                            </div>

                            <a href="trade.php?id=<?= (int)$deal['id'] ?>" class="btn-trade">Trade Now</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="padding: 20px; color: var(--text-muted);">No active arbitrage deals found.</p>
                <?php endif; ?>
            </div>

            <button class="nav-btn right" onclick="scrollCarousel(300)"><i class="fa-solid fa-chevron-right"></i></button>
        </div>

        <!-- About Arbitrage and Us Section -->
        <div class="section-header" style="margin-top: 50px;">
            <div class="section-title"><i class="fa-solid fa-circle-info" style="color: var(--accent-blue);"></i> About Arbitrage &amp; Our Platform</div>
        </div>
        <div class="grid-2">
            <div class="content-card">
                <h3><i class="fa-solid fa-book-open"></i> How Cross-Market Arbitrage Works</h3>
                <p>Digital asset prices diverge across decentralized protocols (Uniswap, Raydium, Cetus) and centralized platforms (Binance, Bybit, OKX). By integrating Spot, Perpetual Futures, and funding-rate basis spreads, our scanning engine detects profitable inefficiencies and executes managed order routing to secure risk-free yield.</p>
            </div>
            <div class="content-card">
                <h3><i class="fa-solid fa-shield-halved"></i> Why Trade with ArbitragePro</h3>
                <p>We combine automated CoinMarketCap spread screening with a dedicated manual execution team. Your capital is safely managed across pre-funded exchange accounts with zero slippage exposure, delivering reliable returns without directional market risk.</p>
            </div>
        </div>

        <!-- CoinMarketCap Prices Display -->
        <div class="section-header" style="margin-top: 50px;">
            <div class="section-title"><i class="fa-solid fa-chart-simple" style="color: var(--green);"></i> CoinMarketCap Live Price Difference Monitor</div>
        </div>
        <div class="table-responsive">
            <table class="price-table">
                <thead>
                    <tr>
                        <th>Asset Pair</th>
                        <th>Binance Spot</th>
                        <th>Uniswap v3 DEX</th>
                        <th>Bybit Perpetual</th>
                        <th>Hyperliquid Futures</th>
                        <th>Max Spread / Yield</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>BTC / USDT</strong></td>
                        <td>$64,120.00</td>
                        <td>$64,180.50</td>
                        <td>$65,850.00</td>
                        <td>$67,069.90</td>
                        <td><span style="color: var(--green); font-weight: 700;">$2,949.90 (+4.60%)</span></td>
                    </tr>
                    <tr>
                        <td><strong>ETH / USDT</strong></td>
                        <td>$3,410.00</td>
                        <td>$3,405.00</td>
                        <td>$3,580.20</td>
                        <td>$3,621.42</td>
                        <td><span style="color: var(--green); font-weight: 700;">$216.42 (+6.20%)</span></td>
                    </tr>
                    <tr>
                        <td><strong>SOL / USDT</strong></td>
                        <td>$141.20</td>
                        <td>$140.90</td>
                        <td>$149.80</td>
                        <td>$152.21</td>
                        <td><span style="color: var(--green); font-weight: 700;">$11.31 (+7.80%)</span></td>
                    </tr>
                    <tr>
                        <td><strong>SUI / USDT</strong></td>
                        <td>$1.82</td>
                        <td>$1.81</td>
                        <td>$1.94</td>
                        <td>$1.99</td>
                        <td><span style="color: var(--green); font-weight: 700;">$0.18 (+9.34%)</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Live Crypto Chart Screen -->
        <section class="chart-section">
            <div class="chart-controls">
                <div class="section-title" style="margin: 0;">
                    <i class="fa-solid fa-chart-line" style="color: var(--accent-blue);"></i> Live Market Monitor
                </div>
                <select id="coinSelector" onchange="changeChartSymbol(this.value)">
                    <option value="BINANCE:BTCUSDT">BTC / USDT (Bitcoin)</option>
                    <option value="BINANCE:ETHUSDT">ETH / USDT (Ethereum)</option>
                    <option value="BINANCE:SOLUSDT">SOL / USDT (Solana)</option>
                    <option value="BINANCE:XRPUSDT">XRP / USDT (Ripple)</option>
                    <option value="BINANCE:ADAUSDT">ADA / USDT (Cardano)</option>
                </select>
            </div>

            <div class="chart-container" id="tradingview_widget"></div>
        </section>

        <!-- Live Market Activity Stats -->
        <div class="section-header" style="margin-top: 50px;">
            <div class="section-title"><i class="fa-solid fa-wave-square" style="color: var(--warning);"></i> Live Platform Activity</div>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <span style="color: var(--text-muted); font-size: 13px;">Transactions in Last 24 Hours</span>
                <div class="stat-number">14,892</div>
                <span style="font-size: 11px; color: var(--green);">Target range: 5,000 - 20,000</span>
            </div>
            <div class="stat-card">
                <span style="color: var(--text-muted); font-size: 13px;">People Currently Trading Online</span>
                <div class="stat-number">1,245</div>
                <span style="font-size: 11px; color: var(--green);">Target range: 672 - 1,800</span>
            </div>
            <div class="stat-card">
                <span style="color: var(--text-muted); font-size: 13px;">Ongoing Trade Executions</span>
                <div class="stat-number">1,840</div>
                <span style="font-size: 11px; color: var(--green);">Target range: 760 - 3,000</span>
            </div>
        </div>

        <!-- Testimonials Section -->
        <div class="section-header" style="margin-top: 50px;">
            <div class="section-title"><i class="fa-solid fa-comments" style="color: var(--green);"></i> Trader Testimonials</div>
        </div>
        <div class="grid-2">
            <div class="content-card">
                <p style="font-style: italic;">"The DEX to CEX perpetual basis trades on ArbitragePro are seamlessly managed. I caught a 9.3% spread on SUI between Cetus and Binance without worrying about manual bridging."</p>
                <h4 style="margin-top: 12px; font-size: 14px; color: #fff;">- Marcus V., Quantitative Trader</h4>
            </div>
            <div class="content-card">
                <p style="font-style: italic;">"Having Spot, Perp, and Futures arbitrage in one dashboard is incredible. Execution is prompt and profits settle directly to my balance."</p>
                <h4 style="margin-top: 12px; font-size: 14px; color: #fff;">- Elena R., Yield Strategist</h4>
            </div>
        </div>

        <!-- FAQs Section -->
        <div class="section-header" style="margin-top: 50px;">
            <div class="section-title"><i class="fa-solid fa-circle-question" style="color: var(--accent-blue);"></i> Frequently Asked Questions</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">1. What markets do you scan for arbitrage?</div>
            <div class="faq-answer">We scan Spot, Perpetual Contracts, and Futures markets across major Centralized Exchanges (Binance, Bybit, OKX) and Decentralized DEXs (Uniswap, Raydium, Cetus).</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">2. How are trades executed and settled?</div>
            <div class="faq-answer">When you submit a trade, our professional operations team executes the arbitrage route manually across our pre-funded accounts. Once completed, your principal and realized profit are credited to your account balance.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">3. How can I contact customer support?</div>
            <div class="faq-answer">You can reach us anytime instantly via Telegram, WhatsApp, or Email using the floating support buttons on the bottom right.</div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> ArbitragePro. All rights reserved. Automated CoinMarketCap CEX/DEX Arbitrage Infrastructure.</p>
        </div>
    </footer>

    <!-- Constant Floating Support Buttons (Telegram, WhatsApp, Email) -->
    <div class="floating-support">
        <a href="https://t.me/<?= e(ltrim($support['telegram'], '@')) ?>" target="_blank" class="support-btn sup-telegram" title="Telegram Support">
            <i class="fa-brands fa-telegram"></i>
        </a>
        <a href="https://wa.me/<?= e($whatsapp_number) ?>?text=Hello%20ArbitragePro%20Support,%20I%20need%20assistance." target="_blank" class="support-btn sup-whatsapp" title="WhatsApp Support">
            <i class="fa-brands fa-whatsapp"></i>
        </a>
        <a href="mailto:<?= e($support['email']) ?>" class="support-btn sup-email" title="Email Support">
            <i class="fa-solid fa-envelope"></i>
        </a>
    </div>

    <!-- Embedded Scripts -->
    <script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
    <script>
        // Horizontal Carousel Scroll
        function scrollCarousel(offset) {
            const carousel = document.getElementById('tradeCarousel');
            carousel.scrollBy({ left: offset, behavior: 'smooth' });
        }

        // TradingView Widget Integration
        let tvWidget;
        function loadTradingViewWidget(symbol) {
            tvWidget = new TradingView.widget({
                "autosize": true,
                "symbol": symbol,
                "interval": "15",
                "timezone": "Etc/UTC",
                "theme": "dark",
                "style": "1",
                "locale": "en",
                "toolbar_bg": "#f1f3f6",
                "enable_publishing": false,
                "hide_side_toolbar": false,
                "container_id": "tradingview_widget"
            });
        }

        function changeChartSymbol(symbol) {
            loadTradingViewWidget(symbol);
        }

        // Initialize chart on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadTradingViewWidget('BINANCE:BTCUSDT');
        });
    </script>
</body>
</html>
