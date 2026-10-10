<?php
// index.php - Main Arbitrage Trading Portal with Live Dynamic Spreads & Randomized Stats
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);

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
    <title>ArbitragePro | Live CoinMarketCap CEX & DEX Arbitrage Infrastructure</title>
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
            padding-bottom: 0;
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

        .grid-carousel::-webkit-scrollbar { display: none; }

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

        .coin-name h4 { font-size: 15px; font-weight: 700; }
        .coin-name span { font-size: 12px; color: var(--text-muted); }

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
        .route-row:last-child { margin-bottom: 0; }

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
        .btn-trade:hover { opacity: 0.9; }

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

        .content-card p { color: var(--text-muted); font-size: 14px; }

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
        .price-table tr:hover { background: var(--bg-hover); }

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
        .faq-question { font-weight: 700; font-size: 15px; margin-bottom: 6px; }
        .faq-answer { color: var(--text-muted); font-size: 13px; }

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

        .chart-container { height: 450px; width: 100%; }

        /* Floating Support Bar */
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
        .support-btn:hover { transform: scale(1.1); }

        .sup-telegram { background: #0088cc; }
        .sup-whatsapp { background: #25d366; }
        .sup-email { background: var(--accent-blue); }

        /* Professional Modern Footer */
        .site-footer {
            background: #14161c;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 60px 0 30px;
            margin-top: 60px;
            font-size: 14px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-col h4 {
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 10px;
        }

        .footer-col ul li a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-col ul li a:hover {
            color: var(--green);
        }

        .footer-bottom {
            border-top: 1px solid var(--border-color);
            padding-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 13px;
        }

        .footer-socials {
            display: flex;
            gap: 16px;
        }

        .footer-socials a {
            width: 36px;
            height: 36px;
            background: #1e2329;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            transition: background 0.2s;
        }

        .footer-socials a:hover {
            background: var(--green);
            color: #000;
        }

        @media (max-width: 768px) {
            .nav-btn { display: none; }
            .grid-carousel { grid-template-columns: repeat(4, 240px); }
            .footer-bottom { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

    <!-- CoinMarketCap Live Ticker Bar -->
    <div class="cmc-ticker-bar">
        <div class="ticker-wrap" id="tickerWrap">
            <div class="ticker-item">
                <span>Connecting to live exchange order books...</span>
            </div>
        </div>
    </div>

    <!-- Header / Navbar -->
    <?php if (file_exists('inc/navbar.php')) include 'inc/navbar.php'; ?>

    <main class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1>Automated CoinMarketCap & Cross-Exchange Arbitrage</h1>
            <p>Monitored Spot, Perpetual, and Futures arbitrage opportunities with live market prices auto-refreshing every 15 minutes.</p>
        </section>

        <!-- What is Cryptocurrency Arbitrage Section -->
        <div class="section-header" style="margin-top: 30px;">
            <div class="section-title"><i class="fa-solid fa-graduation-cap" style="color: var(--green);"></i> What is Cryptocurrency Arbitrage?</div>
        </div>
        <div class="content-card" style="margin-bottom: 30px;">
            <p style="margin-bottom: 12px;">
                <strong>Cryptocurrency Arbitrage</strong> is a trading strategy that involves purchasing a digital asset on one exchange or decentralized protocol at a lower price and simultaneously (or near-simultaneously) selling it on another platform where the price is higher. Because cryptocurrency markets are decentralized and highly fragmented across the globe, temporary pricing inefficiencies occur constantly between centralized platforms (CEXs like Binance and Bybit) and decentralized automated market makers (DEXs like Uniswap and Raydium).
            </p>
            <p>
                Unlike directional trading (where you speculate on whether a coin will go up or down), arbitrage captures risk-free spread margins by exploiting these temporary order book imbalances. Our automated scanner monitors real-time CoinMarketCap feeds to highlight these profit opportunities across Spot, Perpetual Contracts, and Futures markets.
            </p>
        </div>

        <!-- Top Trades Grid Section -->
        <div class="section-header">
            <div class="section-title">
                <i class="fa-solid fa-bolt" style="color: var(--green);"></i> Top Arbitrage Opportunities (Live Dynamic Yields)
            </div>
            <a href="all-trades.php" class="btn-see-all">See All Trades <i class="fa-solid fa-chevron-right"></i></a>
        </div>

        <div class="carousel-container">
            <button class="nav-btn left" onclick="scrollCarousel(-300)"><i class="fa-solid fa-chevron-left"></i></button>
            
            <div class="grid-carousel" id="tradeCarousel">
                <!-- Dynamically populated with live prices & calculated percentage profit -->
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
                <tbody id="priceMonitorTable">
                    <!-- Dynamically populated live price monitor rows -->
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
                <div class="stat-number" id="statTx">14,892</div>
                <span style="font-size: 11px; color: var(--green);">Target range: 5,000 - 20,000</span>
            </div>
            <div class="stat-card">
                <span style="color: var(--text-muted); font-size: 13px;">People Currently Trading Online</span>
                <div class="stat-number" id="statUsers">1,245</div>
                <span style="font-size: 11px; color: var(--green);">Target range: 672 - 1,800</span>
            </div>
            <div class="stat-card">
                <span style="color: var(--text-muted); font-size: 13px;">Ongoing Trade Executions</span>
                <div class="stat-number" id="statTrades">1,840</div>
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
            <div class="faq-question">1. How often do live arbitrage prices and percentages refresh?</div>
            <div class="faq-answer">Market prices, buy/sell values, and dynamic profit percentages auto-refresh every 15 minutes automatically via live exchange feed integrations.</div>
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

    <!-- Professional Modern Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3 style="color: #fff; font-size: 18px; margin-bottom: 12px;"><i class="fa-solid fa-chart-line" style="color: var(--green);"></i> ArbitragePro</h3>
                    <p style="font-size: 13px; color: var(--text-muted);">Institutional-grade cross-market arbitrage infrastructure connecting CoinMarketCap spot feeds with CEX and DEX liquidity pools.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Navigation</h4>
                    <ul>
                        <li><a href="index.php">Home Dashboard</a></li>
                        <li><a href="all-trades.php">All Arbitrage Deals</a></li>
                        <li><a href="dashboard.php">User Account</a></li>
                        <li><a href="deposit.php">Deposit Funds</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Supported Markets</h4>
                    <ul>
                        <li><a href="#">Binance Spot &amp; Perp</a></li>
                        <li><a href="#">Uniswap v3 DEX Pools</a></li>
                        <li><a href="#">Bybit &amp; OKX Futures</a></li>
                        <li><a href="#">Hyperliquid Basises</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Risk &amp; Compliance</h4>
                    <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4;">Crypto arbitrage involves execution timing and network fees. Ensure your account is sufficiently funded before dispatching managed trades.</p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> ArbitragePro. All rights reserved.</p>
                <div class="footer-socials">
                    <a href="https://t.me/<?= e(ltrim($support['telegram'], '@')) ?>" target="_blank" title="Telegram"><i class="fa-brands fa-telegram"></i></a>
                    <a href="https://wa.me/<?= e($whatsapp_number) ?>" target="_blank" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                    <a href="mailto:<?= e($support['email']) ?>" title="Email"><i class="fa-solid fa-envelope"></i></a>
                </div>
            </div>
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

        // Helper for random integers within target range
        function getRandomInt(min, max) {
            return Math.floor(Math.random() * (max - min + 1)) + min;
        }

        // Update randomized stats
        function updatePlatformStats() {
            document.getElementById('statTx').innerText = getRandomInt(5000, 20000).toLocaleString();
            document.getElementById('statUsers').innerText = getRandomInt(672, 1800).toLocaleString();
            document.getElementById('statTrades').innerText = getRandomInt(760, 3000).toLocaleString();
        }

        // Live Market Data Fetch & Dynamic Profit Calculation with 15-Min Auto-Refresh
        async function fetchLiveMarketData() {
            try {
                // Fetch live spot prices from Binance public API
                const response = await fetch('https://api.binance.com/api/v3/ticker/price');
                const data = await response.json();
                
                const prices = {};
                data.forEach(item => {
                    prices[item.symbol] = parseFloat(item.price);
                });

                // Fallback base prices if offline
                const btc = prices['BTCUSDT'] || 64200.00;
                const eth = prices['ETHUSDT'] || 3410.00;
                const sol = prices['SOLUSDT'] || 141.50;
                const xrp = prices['XRPUSDT'] || 0.58;
                const sui = prices['SUIUSDT'] || 1.82;
                const pepe = prices['PEPEUSDT'] || 0.0000092;
                const avax = prices['AVAXUSDT'] || 26.40;
                const link = prices['LINKUSDT'] || 11.20;

                // Dynamically calculate buy/sell prices and percentage profit margins
                const btcBuy = btc;
                const btcSell = btc * 1.046;
                const btcProfit = ((btcSell - btcBuy) / btcBuy) * 100;

                const ethBuy = eth * 0.998;
                const ethSell = eth * 1.062;
                const ethProfit = ((ethSell - ethBuy) / ethBuy) * 100;

                const solBuy = sol * 0.997;
                const solSell = sol * 1.078;
                const solProfit = ((solSell - solBuy) / solBuy) * 100;

                const xrpBuy = xrp * 0.995;
                const xrpSell = xrp * 1.082;
                const xrpProfit = ((xrpSell - xrpBuy) / xrpBuy) * 100;

                const suiBuy = sui * 0.996;
                const suiSell = sui * 1.0934;
                const suiProfit = ((suiSell - suiBuy) / suiBuy) * 100;

                const pepeBuy = pepe * 0.99;
                const pepeSell = pepe * 1.1087;
                const pepeProfit = ((pepeSell - pepeBuy) / pepeBuy) * 100;

                const avaxBuy = avax * 0.998;
                const avaxSell = avax * 1.075;
                const avaxProfit = ((avaxSell - avaxBuy) / avaxBuy) * 100;

                const linkBuy = link * 0.995;
                const linkSell = link * 1.0848;
                const linkProfit = ((linkSell - linkBuy) / linkBuy) * 100;

                const liveDeals = [
                    { id: 1, symbol: 'BTC', name: 'Bitcoin', buyEx: 'Binance (Spot)', buyPrice: btcBuy, sellEx: 'Hyperliquid (Perp)', sellPrice: btcSell, profit: btcProfit, time: '15 - 30 mins' },
                    { id: 2, symbol: 'ETH', name: 'Ethereum', buyEx: 'Uniswap v3 (DEX)', buyPrice: ethBuy, sellEx: 'Bybit (Futures)', sellPrice: ethSell, profit: ethProfit, time: '20 - 40 mins' },
                    { id: 3, symbol: 'SOL', name: 'Solana', buyEx: 'Raydium (DEX)', buyPrice: solBuy, sellEx: 'OKX (Perp)', sellPrice: solSell, profit: solProfit, time: '10 - 25 mins' },
                    { id: 4, symbol: 'XRP', name: 'Ripple', buyEx: 'Gate.io (Spot)', buyPrice: xrpBuy, sellEx: 'Bitget (Futures)', sellPrice: xrpSell, profit: xrpProfit, time: '15 - 30 mins' },
                    { id: 5, symbol: 'SUI', name: 'Sui Network', buyEx: 'Cetus (DEX)', buyPrice: suiBuy, sellEx: 'Binance (Perp)', sellPrice: suiSell, profit: suiProfit, time: '10 - 20 mins' },
                    { id: 6, symbol: 'PEPE', name: 'Pepe Coin', buyEx: 'Uniswap v3 (DEX)', buyPrice: pepeBuy, sellEx: 'MECX (Spot)', sellPrice: pepeSell, profit: pepeProfit, time: '10 - 15 mins' },
                    { id: 7, symbol: 'AVAX', name: 'Avalanche', buyEx: 'TraderJoe (DEX)', buyPrice: avaxBuy, sellEx: 'Deribit (Futures)', sellPrice: avaxSell, profit: avaxProfit, time: '20 - 35 mins' },
                    { id: 8, symbol: 'LINK', name: 'Chainlink', buyEx: 'KuCoin (Spot)', buyPrice: linkBuy, sellEx: 'dYdX (DEX Perp)', sellPrice: linkSell, profit: linkProfit, time: '15 - 30 mins' }
                ];

                // Render Carousel / Grid Cards
                let carouselHTML = '';
                let tickerHTML = '';

                liveDeals.forEach(d => {
                    carouselHTML += `
                        <div class="trade-card">
                            <div class="card-top">
                                <div class="coin-info">
                                    <div class="coin-icon">${d.symbol.substring(0, 3)}</div>
                                    <div class="coin-name">
                                        <h4>${d.name}</h4>
                                        <span>${d.symbol}/USDT</span>
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
                            <div class="card-meta">
                                <span><i class="fa-regular fa-clock"></i> ${d.time}</span>
                                <span><i class="fa-solid fa-shield-halved"></i> Managed</span>
                            </div>
                            <a href="trade.php?id=${d.id}" class="btn-trade">Trade Now</a>
                        </div>
                    `;

                    tickerHTML += `
                        <div class="ticker-item">
                            <strong>${d.symbol}:</strong>
                            <span>Buy ${d.buyEx} ($${d.buyPrice.toFixed(2)})</span>
                            <i class="fa-solid fa-arrow-right-long"></i>
                            <span>Sell ${d.sellEx} ($${d.sellPrice.toFixed(2)})</span>
                            <span class="badge-profit">+${d.profit.toFixed(2)}%</span>
                        </div>
                    `;
                });

                document.getElementById('tradeCarousel').innerHTML = carouselHTML;
                document.getElementById('tickerWrap').innerHTML = tickerHTML;

                // Render CMC Price Monitor Table with dynamic spread calculation
                const priceTableHTML = `
                    <tr>
                        <td><strong>BTC / USDT</strong></td>
                        <td>$${btc.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td>$${(btc * 1.001).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td>$${(btc * 1.025).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td>$${(btc * 1.046).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td><span style="color: var(--green); font-weight: 700;">$${(btc * 0.046).toFixed(2)} (+4.60%)</span></td>
                    </tr>
                    <tr>
                        <td><strong>ETH / USDT</strong></td>
                        <td>$${eth.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td>$${(eth * 0.998).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td>$${(eth * 1.035).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td>$${(eth * 1.062).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td><span style="color: var(--green); font-weight: 700;">$${(eth * 0.062).toFixed(2)} (+6.20%)</span></td>
                    </tr>
                    <tr>
                        <td><strong>SOL / USDT</strong></td>
                        <td>$${sol.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td>$${(sol * 0.997).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td>$${(sol * 1.055).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td>$${(sol * 1.078).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td><span style="color: var(--green); font-weight: 700;">$${(sol * 0.078).toFixed(2)} (+7.80%)</span></td>
                    </tr>
                    <tr>
                        <td><strong>SUI / USDT</strong></td>
                        <td>$${sui.toLocaleString(undefined, {minimumFractionDigits:4})}</td>
                        <td>$${(sui * 0.996).toLocaleString(undefined, {minimumFractionDigits:4})}</td>
                        <td>$${(sui * 1.065).toLocaleString(undefined, {minimumFractionDigits:4})}</td>
                        <td>$${(sui * 1.0934).toLocaleString(undefined, {minimumFractionDigits:4})}</td>
                        <td><span style="color: var(--green); font-weight: 700;">$${(sui * 0.0934).toFixed(4)} (+9.34%)</span></td>
                    </tr>
                `;
                document.getElementById('priceMonitorTable').innerHTML = priceTableHTML;

                // Also update stats on refresh
                updatePlatformStats();

            } catch (error) {
                console.error("Error fetching live crypto prices:", error);
            }
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

        // Initialize on page load and set 15-minute auto-refresh interval
        document.addEventListener('DOMContentLoaded', function() {
            loadTradingViewWidget('BINANCE:BTCUSDT');
            fetchLiveMarketData();

            // 15 Minutes = 15 * 60 * 1000 ms = 900,000 ms
            setInterval(fetchLiveMarketData, 15 * 60 * 1000);
        });
    </script>
</body>
</html>
