<?php
// index.php - Main Arbitrage Trading Portal with Live Dynamic Spreads & Server-Side Gating
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';
require_once 'api/live-data.php';

$pdo = null;
$user_real_balance = 0.00;

try {
    $db = new Database();
    $pdo = $db->connect();
    
    // Fetch logged-in user's real balance for 3% gating check
    if ($is_logged_in && $user_id) {
        $stmtUser = $pdo->prepare("SELECT real_balance FROM users WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $uData = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($uData) {
            $user_real_balance = (float)$uData['real_balance'];
        }
    }
} catch (Exception $e) {
    // Graceful fallback if database connection is pending configuration
}

// Fetch live arbitrage opportunities via engine (Auto-refreshes every 5 mins)
$deals = [];
if ($pdo) {
    try {
        $deals = LiveArbitrageEngine::getOpportunities($pdo, $user_real_balance);
    } catch (Exception $e) {
        $deals = [];
    }
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

        /* Grid Carousel */
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
            position: relative;
        }

        .route-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .route-row:last-child { margin-bottom: 0; }

        /* Gating & Blur Styles */
        .blurred-content {
            filter: blur(6px);
            user-select: none;
            pointer-events: none;
        }

        .lock-notice {
            position: absolute;
            inset: 0;
            background: rgba(11, 14, 17, 0.85);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            text-align: center;
            padding: 4px;
            font-size: 11px;
            color: var(--warning);
            font-weight: 600;
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
            cursor: not-allowed;
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

        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 10px; }
        .footer-col ul li a { color: var(--text-muted); text-decoration: none; transition: color 0.2s; }
        .footer-col ul li a:hover { color: var(--green); }

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

        .footer-socials { display: flex; gap: 16px; }
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
        .footer-socials a:hover { background: var(--green); color: #000; }

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
            <?php foreach ($deals as $d): ?>
                <div class="ticker-item">
                    <span><strong><?= e($d['symbol']) ?></strong>: $<?= number_format((float)$d['buy_price'], 2) ?></span>
                    <span class="badge-profit">+<?= number_format((float)$d['profit_percentage'], 2) ?>%</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Header / Navbar -->
    <?php if (file_exists('inc/navbar.php')) include 'inc/navbar.php'; ?>

    <main class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1>Automated AI-Powered Cross-Exchange Arbitrage System</h1>
            <p>Monitored Spot, Perpetual, and Futures arbitrage opportunities with live market prices auto-refreshing every 5 minutes.</p>
            <?php if ($is_logged_in): ?>
                <p style="margin-top: 10px; font-size: 14px; color: var(--green);">
                    <i class="fa-solid fa-wallet"></i> Real Account Balance: <strong>$<?= number_format($user_real_balance, 2) ?> USDT</strong>
                    <?php if ($user_real_balance < 3000): ?>
                        <span style="color: var(--warning); margin-left: 8px;">(Deposit $3,000+ to unlock high-yield >3% arbitrage routes)</span>
                    <?php else: ?>
                        <span style="color: var(--green); margin-left: 8px;">(VIP High-Yield Access Active)</span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </section>

        <!-- What is Cryptocurrency Arbitrage Section -->
        <div class="section-header" style="margin-top: 30px;">
            <div class="section-title"><i class="fa-solid fa-graduation-cap" style="color: var(--green);"></i> What is Cryptocurrency Arbitrage?</div>
        </div>
        <div class="content-card" style="margin-bottom: 30px;">
            <p style="margin-bottom: 12px;">
                <strong>Cryptocurrency Arbitrage</strong> is a trading strategy that involves buying a cryptocurrency at a lower price on one exchange and selling it on another exchange where the price is higher, allowing traders to profit from the price difference.
            </p>
            <p>
                Our automated arbitrage scanner monitors real-time cryptocurrency market data across 25+ assets to identify profit opportunities. Opportunities with 3% and below are fully visible for all users, while high-yield opportunities above 3% require a real balance of $3,000 and above.
            </p>
        </div>

        <!-- Top Trades Grid Section -->
        <div class="section-header">
            <div class="section-title">
                <i class="fa-solid fa-bolt" style="color: var(--green);"></i> Top Arbitrage Opportunities (Live Dynamic Yields - <?= count($deals) ?> Found)
            </div>
            <a href="all-trades.php" class="btn-see-all">See All Trades <i class="fa-solid fa-chevron-right"></i></a>
        </div>

        <div class="carousel-container">
            <button class="nav-btn left" onclick="scrollCarousel(-300)"><i class="fa-solid fa-chevron-left"></i></button>
            
            <div class="grid-carousel" id="tradeCarousel">
                <?php foreach ($deals as $deal): ?>
                    <div class="trade-card">
                        <div>
                            <div class="card-top">
                                <div class="coin-info">
                                    <div class="coin-icon"><?= e(substr($deal['symbol'], 0, 3)) ?></div>
                                    <div class="coin-name">
                                        <h4><?= e($deal['symbol']) ?> / USDT</h4>
                                        <span><?= e($deal['coin_name']) ?></span>
                                    </div>
                                </div>
                                <div class="profit-tag">+<?= number_format((float)$deal['profit_percentage'], 2) ?>%</div>
                            </div>

                            <div class="exchange-route">
                                <?php if ($deal['is_locked']): ?>
                                    <div class="blurred-content">
                                        <div class="route-row">
                                            <span>Buy:</span> <strong><?= e($deal['buy_exchange']) ?> ($<?= number_format((float)$deal['buy_price'], 2) ?>)</strong>
                                        </div>
                                        <div class="route-row">
                                            <span>Sell:</span> <strong><?= e($deal['sell_exchange']) ?> ($<?= number_format((float)$deal['sell_price'], 2) ?>)</strong>
                                        </div>
                                    </div>
                                    <div class="lock-notice">
                                        <i class="fa-solid fa-lock"></i> Locked (&gt;3% Yield)<br>Requires $3,000+ Balance
                                    </div>
                                <?php else: ?>
                                    <div class="route-row">
                                        <span>Buy:</span> <strong><?= e($deal['buy_exchange']) ?> ($<?= number_format((float)$deal['buy_price'], 2) ?>)</strong>
                                    </div>
                                    <div class="route-row">
                                        <span>Sell:</span> <strong><?= e($deal['sell_exchange']) ?> ($<?= number_format((float)$deal['sell_price'], 2) ?>)</strong>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-meta">
                                <span>Vol: <?= e($deal['volume_24h']) ?></span>
                                <span>Est. 15-30m</span>
                            </div>
                        </div>

                        <div>
                            <?php if ($deal['is_locked']): ?>
                                <a href="deposit.php" class="btn-trade btn-locked"><i class="fa-solid fa-lock"></i> Deposit to Unlock</a>
                            <?php else: ?>
                                <a href="trade-execution.php?symbol=<?= e($deal['symbol']) ?>&id=<?= (int)$deal['id'] ?>" class="btn-trade"><i class="fa-solid fa-bolt"></i> Execute Trade</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="nav-btn right" onclick="scrollCarousel(300)"><i class="fa-solid fa-chevron-right"></i></button>
        </div>

        <!-- CoinMarketCap Prices Display Monitor -->
        <div class="section-header" style="margin-top: 50px;">
            <div class="section-title"><i class="fa-solid fa-chart-simple" style="color: var(--green);"></i> CoinMarketCap Live Price Difference Monitor (25+ Assets)</div>
        </div>
        <div class="table-responsive">
            <table class="price-table">
                <thead>
                    <tr>
                        <th>Asset Pair</th>
                        <th>Buy Exchange</th>
                        <th>Buy Price</th>
                        <th>Sell Exchange</th>
                        <th>Sell Price</th>
                        <th>Profit Yield</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($deals, 0, 15) as $d): ?>
                        <tr>
                            <td><strong><?= e($d['symbol']) ?> / USDT</strong></td>
                            <td><?= e($d['buy_exchange']) ?></td>
                            <td>$<?= number_format((float)$d['buy_price'], 4) ?></td>
                            <td>
                                <?php if ($d['is_locked']): ?>
                                    <span style="color: var(--warning); filter: blur(4px);">[Restricted Exchange]</span>
                                <?php else: ?>
                                    <?= e($d['sell_exchange']) ?>
                                <?php endif; ?>
                            </td>
                            <td>$<?= number_format((float)$d['sell_price'], 4) ?></td>
                            <td><strong style="color: var(--green);">+<?= number_format((float)$d['profit_percentage'], 2) ?>%</strong></td>
                            <td>
                                <?php if ($d['is_locked']): ?>
                                    <a href="deposit.php" class="btn-see-all" style="padding: 4px 10px; font-size: 12px; color: var(--warning);">Unlock</a>
                                <?php else: ?>
                                    <a href="trade-execution.php?symbol=<?= e($d['symbol']) ?>&id=<?= (int)$d['id'] ?>" class="btn-see-all" style="padding: 4px 10px; font-size: 12px; color: var(--green);">Trade</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
            </div>
            <div class="stat-card">
                <span style="color: var(--text-muted); font-size: 13px;">People Currently Trading Online</span>
                <div class="stat-number" id="statUsers">1,245</div>
            </div>
            <div class="stat-card">
                <span style="color: var(--text-muted); font-size: 13px;">Ongoing Trade Executions</span>
                <div class="stat-number" id="statTrades">1,840</div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
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

    <!-- Floating Support -->
    <div class="floating-support">
        <a href="https://t.me/<?= e(ltrim($support['telegram'], '@')) ?>" target="_blank" class="support-btn sup-telegram" title="Telegram Support"><i class="fa-brands fa-telegram"></i></a>
        <a href="https://wa.me/<?= e($whatsapp_number) ?>" target="_blank" class="support-btn sup-whatsapp" title="WhatsApp Support"><i class="fa-brands fa-whatsapp"></i></a>
        <a href="mailto:<?= e($support['email']) ?>" class="support-btn sup-email" title="Email Support"><i class="fa-solid fa-envelope"></i></a>
    </div>

    <!-- Embedded Scripts -->
    <script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
    <script>
        function scrollCarousel(offset) {
            const carousel = document.getElementById('tradeCarousel');
            carousel.scrollBy({ left: offset, behavior: 'smooth' });
        }

        // Initialize TradingView Widget
        new TradingView.widget({
            "width": "100%",
            "height": "450",
            "symbol": "BINANCE:BTCUSDT",
            "interval": "15",
            "timezone": "Etc/UTC",
            "theme": "dark",
            "style": "1",
            "locale": "en",
            "toolbar_bg": "#181a20",
            "enable_publishing": false,
            "hide_top_toolbar": false,
            "container_id": "tradingview_widget"
        });

        function changeChartSymbol(symbol) {
            document.getElementById('tradingview_widget').innerHTML = "";
            new TradingView.widget({
                "width": "100%",
                "height": "450",
                "symbol": symbol,
                "interval": "15",
                "timezone": "Etc/UTC",
                "theme": "dark",
                "style": "1",
                "locale": "en",
                "toolbar_bg": "#181a20",
                "enable_publishing": false,
                "hide_top_toolbar": false,
                "container_id": "tradingview_widget"
            });
        }
    </script>
</body>
</html>
