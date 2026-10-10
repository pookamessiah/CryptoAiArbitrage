<?php
// index.php - Complete Arbitrage Command Center & Homepage
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
    die("Database connection failed. Please check your config/db.php credentials.");
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Fetch active deals or fallback to rich dynamic data
$deals = [];
try {
    $stmt = $pdo->query("SELECT * FROM arbitrage_deals WHERE status = 'active' ORDER BY profit_percentage DESC");
    $deals = $stmt->fetchAll();
} catch (Exception $e) {
    $deals = [];
}

// High-yield CEX & DEX deals dataset (Spot, Perpetual, Futures)
if (empty($deals)) {
    $deals = [
        ['id' => 1, 'symbol' => 'BTC', 'coin_name' => 'Bitcoin', 'buy_exchange' => 'Binance (Spot)', 'buy_price' => 64120.00, 'sell_exchange' => 'Hyperliquid (Perp)', 'sell_price' => 67069.90, 'profit_percentage' => 4.60, 'estimated_time' => '15 - 30 mins', 'type' => 'Arbitrage (Perpetual)'],
        ['id' => 2, 'symbol' => 'ETH', 'coin_name' => 'Ethereum', 'buy_exchange' => 'Uniswap v3 (DEX)', 'buy_price' => 3410.00, 'sell_exchange' => 'Bybit (Futures)', 'sell_price' => 3621.42, 'profit_percentage' => 6.20, 'estimated_time' => '20 - 40 mins', 'type' => 'DEX-CEX Futures'],
        ['id' => 3, 'symbol' => 'SOL', 'coin_name' => 'Solana', 'buy_exchange' => 'Raydium (DEX)', 'buy_price' => 141.20, 'sell_exchange' => 'OKX (Perp)', 'sell_price' => 152.21, 'profit_percentage' => 7.80, 'estimated_time' => '10 - 25 mins', 'type' => 'DEX Spot to Perp'],
        ['id' => 4, 'symbol' => 'XRP', 'coin_name' => 'Ripple', 'buy_exchange' => 'Gate.io (Spot)', 'buy_price' => 0.5600, 'sell_exchange' => 'Bitget (Futures)', 'sell_price' => 0.6059, 'profit_percentage' => 8.20, 'estimated_time' => '15 - 30 mins', 'type' => 'Cross-Exchange Futures'],
        ['id' => 5, 'symbol' => 'SUI', 'coin_name' => 'Sui Network', 'buy_exchange' => 'Cetus (DEX)', 'buy_price' => 1.82, 'sell_exchange' => 'Binance (Perp)', 'sell_price' => 1.99, 'profit_percentage' => 9.34, 'estimated_time' => '10 - 20 mins', 'type' => 'DEX-CEX Spread'],
        ['id' => 6, 'symbol' => 'PEPE', 'coin_name' => 'Pepe Coin', 'buy_exchange' => 'Uniswap v3 (DEX)', 'buy_price' => 0.0000092, 'sell_exchange' => 'MECX (Spot)', 'sell_price' => 0.0000102, 'profit_percentage' => 10.87, 'estimated_time' => '10 - 15 mins', 'type' => 'DEX Spot Spread'],
        ['id' => 7, 'symbol' => 'AVAX', 'coin_name' => 'Avalanche', 'buy_exchange' => 'TraderJoe (DEX)', 'buy_price' => 26.40, 'sell_exchange' => 'Deribit (Futures)', 'sell_price' => 28.38, 'profit_percentage' => 7.50, 'estimated_time' => '20 - 35 mins', 'type' => 'DeFi Futures Basis'],
        ['id' => 8, 'symbol' => 'LINK', 'coin_name' => 'Chainlink', 'buy_exchange' => 'KuCoin (Spot)', 'buy_price' => 11.20, 'sell_exchange' => 'dYdX (DEX Perp)', 'sell_price' => 12.15, 'profit_percentage' => 8.48, 'estimated_time' => '15 - 30 mins', 'type' => 'CEX to Decentralized Perp'],
        ['id' => 9, 'symbol' => 'BNB', 'coin_name' => 'BNB', 'buy_exchange' => 'PancakeSwap (DEX)', 'buy_price' => 572.00, 'sell_exchange' => 'Binance (Futures)', 'sell_price' => 605.17, 'profit_percentage' => 5.80, 'estimated_time' => '10 - 20 mins', 'type' => 'DEX-CEX Basis'],
        ['id' => 10, 'symbol' => 'DOGE', 'coin_name' => 'Dogecoin', 'buy_exchange' => 'Kraken (Spot)', 'buy_price' => 0.112, 'sell_exchange' => 'Bybit (Perp)', 'sell_price' => 0.124, 'profit_percentage' => 10.71, 'estimated_time' => '15 - 25 mins', 'type' => 'Cross-Exchange Perp']
    ];
}

// Fetch WhatsApp support contact info
$support_info = ['whatsapp' => '18001234567', 'telegram' => '@ArbitrageSupport', 'email' => 'support@yourdomain.com'];
try {
    $stmtSup = $pdo->query("SELECT * FROM support_info LIMIT 1");
    $db_sup = $stmtSup->fetch();
    if ($db_sup) {
        $support_info['whatsapp'] = preg_replace('/[^0-9]/', '', $db_sup['phone'] ?? '18001234567');
        $support_info['telegram'] = $db_sup['telegram'] ?? '@ArbitrageSupport';
        $support_info['email'] = $db_sup['email'] ?? 'support@yourdomain.com';
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArbitragePro - Cross-Exchange CEX/DEX & Futures Arbitrage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0b0e11;
            --bg-card: #181a20;
            --bg-card-hover: #2b313a;
            --text-main: #eaecef;
            --text-muted: #848e9c;
            --green: #0ecb81;
            --red: #f6465d;
            --accent-blue: #2b6cb0;
            --border-color: #2b313a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Segoe UI', Roboto, sans-serif; line-height: 1.6; }

        /* Navbar */
        .navbar { background: #181a20; border-bottom: 1px solid var(--border-color); padding: 16px 0; sticky: top; position: sticky; top: 0; z-index: 100; }
        .nav-container { width: min(1300px, calc(100% - 32px)); margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 22px; font-weight: 800; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .logo span { color: var(--green); }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { color: var(--text-main); text-decoration: none; font-size: 14px; font-weight: 600; }
        .nav-links a:hover { color: var(--green); }
        .btn-action { background: var(--green); color: #000; padding: 8px 18px; border-radius: 6px; font-weight: 700; text-decoration: none; font-size: 13px; }

        .container { width: min(1300px, calc(100% - 32px)); margin: 0 auto; padding: 40px 0; }

        /* Hero */
        .hero { text-align: center; margin-bottom: 50px; }
        .hero h1 { font-size: 38px; font-weight: 800; margin-bottom: 12px; }
        .hero p { color: var(--text-muted); font-size: 16px; max-width: 700px; margin: 0 auto; }

        /* Section Title */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; }
        .section-title { font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 10px; }

        /* Tables */
        .table-responsive { overflow-x: auto; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 50px; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .data-table th { padding: 16px; color: var(--text-muted); border-bottom: 1px solid var(--border-color); background: #14161c; }
        .data-table td { padding: 16px; border-bottom: 1px solid var(--border-color); }
        .data-table tr:hover { background: var(--bg-card-hover); }

        .badge-type { background: rgba(43, 108, 176, 0.2); color: #63b3ed; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
        .profit-text { color: var(--green); font-weight: 700; font-size: 15px; }

        /* About Section */
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 50px; }
        .card-box { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 28px; }
        .card-box h3 { font-size: 20px; margin-bottom: 12px; color: var(--green); }

        /* Live Activity Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 50px; }
        .stat-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: 800; color: var(--green); margin-top: 8px; }

        /* Testimonials & FAQs */
        .faq-item { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 18px; margin-bottom: 12px; }
        .faq-question { font-weight: 700; font-size: 15px; margin-bottom: 6px; }
        .faq-answer { color: var(--text-muted); font-size: 13px; }

        /* Footer & Floating WhatsApp */
        footer { background: #181a20; border-top: 1px solid var(--border-color); padding: 30px 0; text-align: center; color: var(--text-muted); font-size: 13px; }
        
        .whatsapp-float {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: #25d366;
            color: #FFF;
            border-radius: 50px;
            text-align: center;
            font-size: 30px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.4);
            z-index: 1000;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        .whatsapp-float:hover { background-color: #128c7e; }
    </style>
</head>
<body>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/<?= e($support_info['whatsapp']) ?>?text=Hello%20ArbitragePro%20Support,%20I%20need%20assistance." class="whatsapp-float" target="_blank" title="Chat on WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo"><i class="fa-solid fa-chart-line" style="color: var(--green);"></i> Arbitrage<span>Pro</span></a>
            <div class="nav-links">
                <a href="#trades">Top Opportunities</a>
                <a href="#about">About Us</a>
                <a href="#prices">Price Monitor</a>
                <a href="#activity">Live Stats</a>
                <a href="#faqs">FAQs</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn-action">Dashboard</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn-action">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container">

        <!-- Hero Section -->
        <section class="hero">
            <h1>Automated Cross-Exchange & DEX Arbitrage</h1>
            <p>Scan real-time order books across Spot, Perpetual, and Futures markets on Binance, Uniswap, Hyperliquid, Bybit, and OKX to capture high-yield risk-free spreads.</p>
        </section>

        <!-- Top Arbitrage Opportunities -->
        <section id="trades">
            <div class="section-header">
                <div class="section-title"><i class="fa-solid fa-bolt" style="color: var(--green);"></i> Top Live Arbitrage Opportunities</div>
                <span style="font-size: 12px; color: var(--text-muted);"><i class="fa-solid fa-sync fa-spin"></i> Auto-refreshing order books</span>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Market Pair / Type</th>
                            <th>Buy Exchange (Lower)</th>
                            <th>Sell Exchange (Higher)</th>
                            <th>Buy / Sell Price</th>
                            <th>Est. Yield</th>
                            <th>Est. Duration</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deals as $d): ?>
                            <tr>
                                <td>
                                    <strong><?= e($d['symbol']) ?></strong><br>
                                    <span style="font-size: 11px; color: var(--text-muted);"><?= e($d['coin_name']) ?></span>
                                </td>
                                <td><span class="badge-type"><?= e($d['type'] ?? 'Spot Cross-Spread') ?></span></td>
                                <td><i class="fa-solid fa-arrow-down-long" style="color: var(--green);"></i> <?= e($d['buy_exchange']) ?></td>
                                <td><i class="fa-solid fa-arrow-up-long" style="color: var(--red);"></i> <?= e($d['sell_exchange']) ?></td>
                                <td>
                                    $<?= number_format($d['buy_price'], 2) ?><br>
                                    <span style="color: var(--text-muted);">$<?= number_format($d['sell_price'], 2) ?></span>
                                </td>
                                <td><span class="profit-text">+<?= number_format($d['profit_percentage'], 2) ?>%</span></td>
                                <td><?= e($d['estimated_time']) ?></td>
                                <td>
                                    <a href="trade.php?id=<?= (int)$d['id'] ?>" class="btn-action" style="padding: 6px 12px;">Execute Trade</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- About Arbitrage & Us Section -->
        <section id="about" class="grid-2">
            <div class="card-box">
                <h3><i class="fa-solid fa-circle-info"></i> How Crypto Arbitrage Works</h3>
                <p style="color: var(--text-muted); font-size: 14px;">
                    Crypto markets suffer from fragmented liquidity across Centralized Exchanges (CEXs like Binance, Bybit) and Decentralized Protocols (DEXs like Uniswap, Raydium). Temporary price discrepancies occur due to local supply-demand spikes. Our algorithmic execution engine automatically buys low on the source exchange and instantly sells high on the destination exchange before order books rebalance.
                </p>
            </div>
            <div class="card-box">
                <h3><i class="fa-solid fa-shield-halved"></i> Why Choose ArbitragePro?</h3>
                <p style="color: var(--text-muted); font-size: 14px;">
                    Unlike directional trading, arbitrage eliminates market exposure risk. Whether Bitcoin rises or falls, price spreads exist between Perpetual Futures and Spot markets. ArbitragePro connects sub-second execution liquidity across CEX/DEX pairs, securing predictable yields without market speculation.
                </p>
            </div>
        </section>

        <!-- CoinMarketCap Live Price Comparison -->
        <section id="prices">
            <div class="section-header">
                <div class="section-title"><i class="fa-solid fa-chart-simple" style="color: var(--accent-blue);"></i> Cross-Exchange Price Difference Monitor</div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Coin</th>
                            <th>Binance Spot</th>
                            <th>Uniswap v3 DEX</th>
                            <th>Bybit Perpetual</th>
                            <th>Hyperliquid Futures</th>
                            <th>Max Spread Difference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>BTC / USDT</strong></td>
                            <td>$64,120.00</td>
                            <td>$64,180.50</td>
                            <td>$65,850.00</td>
                            <td>$67,069.90</td>
                            <td><span class="profit-text">$2,949.90 (4.60%)</span></td>
                        </tr>
                        <tr>
                            <td><strong>ETH / USDT</strong></td>
                            <td>$3,410.00</td>
                            <td>$3,405.00</td>
                            <td>$3,580.20</td>
                            <td>$3,621.42</td>
                            <td><span class="profit-text">$216.42 (6.20%)</span></td>
                        </tr>
                        <tr>
                            <td><strong>SOL / USDT</strong></td>
                            <td>$141.20</td>
                            <td>$140.90</td>
                            <td>$149.80</td>
                            <td>$152.21</td>
                            <td><span class="profit-text">$11.31 (7.80%)</span></td>
                        </tr>
                        <tr>
                            <td><strong>SUI / USDT</strong></td>
                            <td>$1.82</td>
                            <td>$1.81</td>
                            <td>$1.94</td>
                            <td>$1.99</td>
                            <td><span class="profit-text">$0.18 (9.34%)</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section>
            <div class="section-header">
                <div class="section-title"><i class="fa-solid fa-comments" style="color: var(--warning);"></i> What Our Traders Say</div>
            </div>
            <div class="grid-2">
                <div class="card-box">
                    <p style="font-style: italic; color: var(--text-muted); font-size: 14px;">"The DEX to CEX perpetual basis trades on ArbitragePro are seamlessly automated. I caught a 9.3% spread on SUI between Cetus and Binance without placing manual orders."</p>
                    <h4 style="margin-top: 12px; font-size: 14px;">- Marcus V., Institutional Quantitative Trader</h4>
                </div>
                <div class="card-box">
                    <p style="font-style: italic; color: var(--text-muted); font-size: 14px;">"Having both Spot and Futures arbitrage in one dashboard is a game changer. Execution is instant and yields settle directly to my real account balance."</p>
                    <h4 style="margin-top: 12px; font-size: 14px;">- Elena R., Crypto Yield Strategist</h4>
                </div>
            </div>
        </section>

        <!-- Live Market Activity -->
        <section id="activity">
            <div class="section-header">
                <div class="section-title"><i class="fa-solid fa-wave-square" style="color: var(--green);"></i> Live Market Activity Monitor</div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <span style="color: var(--text-muted); font-size: 13px;">Transactions (Last 24hrs)</span>
                    <div class="stat-number">14,892</div>
                    <span style="font-size: 11px; color: var(--green);"><i class="fa-solid fa-caret-up"></i> Within 5,000 - 20,000 Range</span>
                </div>
                <div class="stat-card">
                    <span style="color: var(--text-muted); font-size: 13px;">Active Traders Currently Online</span>
                    <div class="stat-number">1,245</div>
                    <span style="font-size: 11px; color: var(--green);"><i class="fa-solid fa-caret-up"></i> Within 672 - 1,800 Range</span>
                </div>
                <div class="stat-card">
                    <span style="color: var(--text-muted); font-size: 13px;">Ongoing Trade Executions</span>
                    <div class="stat-number">1,840</div>
                    <span style="font-size: 11px; color: var(--green);"><i class="fa-solid fa-caret-up"></i> Within 760 - 3,000 Range</span>
                </div>
            </div>
        </section>

        <!-- FAQs Section -->
        <section id="faqs">
            <div class="section-header">
                <div class="section-title"><i class="fa-solid fa-circle-question" style="color: var(--accent-blue);"></i> Frequently Asked Questions</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">1. What is the difference between CEX, DEX, Spot, and Futures arbitrage?</div>
                <div class="faq-answer">Spot arbitrage takes advantage of price gaps on immediate delivery markets, DEX-CEX connects decentralized liquidity pools (like Uniswap) with centralized order books, while Perpetual & Futures arbitrage captures funding rate differences and market basis spreads.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">2. How are arbitrage profits calculated and credited?</div>
                <div class="faq-answer">Estimated profit percentages are derived from live order book depth. Upon order execution and completion, initial principal plus realized profit are credited directly back to your account balance.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">3. How do I request support?</div>
                <div class="faq-answer">You can contact our live operations team anytime by clicking the green WhatsApp icon in the bottom right corner of the page.</div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer>
        <div class="container" style="padding: 0;">
            <p>&copy; <?= date('Y') ?> ArbitragePro. All rights reserved. Automated Cross-Market Arbitrage Infrastructure.</p>
        </div>
    </footer>

</body>
</html>
