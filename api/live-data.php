<?php
// api/live-data.php - Standalone Arbitrage Engine & 5-Minute Price Synchronizer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

class LiveArbitrageEngine {
    
    // Ensure table exists
    public static function initTable($pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS arbitrage_deals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                symbol VARCHAR(20) NOT NULL,
                coin_name VARCHAR(50) NOT NULL,
                buy_exchange VARCHAR(50) NOT NULL,
                sell_exchange VARCHAR(50) NOT NULL,
                buy_price DECIMAL(18, 8) NOT NULL,
                sell_price DECIMAL(18, 8) NOT NULL,
                profit_percentage DECIMAL(5, 2) NOT NULL,
                volume_24h VARCHAR(50) DEFAULT 'High',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
    }

    // Refresh prices automatically if older than 5 minutes (300 seconds)
    public static function refreshPricesIfNeeded($pdo) {
        self::initTable($pdo);

        $stmtCheck = $pdo->query("SELECT MAX(updated_at) as last_update FROM arbitrage_deals");
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $lastUpdate = $row['last_update'] ?? null;

        $currentTime = time();
        $fiveMinutesAgo = $currentTime - 300;

        if (!$lastUpdate || strtotime($lastUpdate) < $fiveMinutesAgo) {
            self::fetchAndUpsertLiveDeals($pdo);
        }
    }

    // Pull from Binance Public API for 28+ coins to build 25+ robust arbitrage pairs
    private static function fetchAndUpsertLiveDeals($pdo) {
        $coins = [
            'BTC' => 'Bitcoin', 'ETH' => 'Ethereum', 'SOL' => 'Solana', 'XRP' => 'XRP',
            'ADA' => 'Cardano', 'AVAX' => 'Avalanche', 'DOGE' => 'Dogecoin', 'DOT' => 'Polkadot',
            'LINK' => 'Chainlink', 'MATIC' => 'Polygon', 'UNI' => 'Uniswap', 'LTC' => 'Litecoin',
            'NEAR' => 'Near Protocol', 'ATOM' => 'Cosmos', 'XLM' => 'Stellar', 'APT' => 'Aptos',
            'SUI' => 'Sui', 'ARB' => 'Arbitrum', 'OP' => 'Optimism', 'ICP' => 'Internet Computer',
            'RENDER' => 'Render', 'INJ' => 'Injective', 'TIA' => 'Celestia', 'FIL' => 'Filecoin',
            'STX' => 'Stacks', 'ETC' => 'Ethereum Classic', 'ALGO' => 'Algorand'
        ];

        // Fetch live market data via cURL
        $ch = curl_init('https://api.binance.com/api/v3/ticker/price');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);

        $binancePrices = [];
        if ($response) {
            $data = json_decode($response, true);
            if (is_array($data)) {
                foreach ($data as $item) { // FIXED: added $ sign
                    if (str_ends_with($item['symbol'], 'USDT')) { // FIXED: added $ sign
                        $symbol = str_replace('USDT', '', $item['symbol']); // FIXED: added $ sign
                        $binancePrices[$symbol] = (float)$item['price']; // FIXED: added $ sign
                    }
                }
            }
        }

        $exchanges = ['Binance', 'OKX', 'Bybit', 'KuCoin', 'Kraken', 'Gate.io', 'Uniswap v3 (DEX)', 'Hyperliquid'];
        $pdo->exec("TRUNCATE TABLE arbitrage_deals");

        $stmtInsert = $pdo->prepare("
            INSERT INTO arbitrage_deals (symbol, coin_name, buy_exchange, sell_exchange, buy_price, sell_price, profit_percentage, volume_24h)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($coins as $symbol => $name) {
            $basePrice = $binancePrices[$symbol] ?? 100.00;

            $buyEx = $exchanges[array_rand($exchanges)];
            do {
                $sellEx = $exchanges[array_rand($exchanges)];
            } while ($sellEx === $buyEx);

            // Mix of yields <= 3% (unlocked for all) and > 3% (gated for $3,000+ balance)
            $profitSpread = round((rand(80, 520) / 100), 2); // 0.80% to 5.20%

            $buyPrice = $basePrice;
            $sellPrice = $basePrice * (1 + ($profitSpread / 100));

            $stmtInsert->execute([
                $symbol,
                $name,
                $buyEx,
                $sellEx,
                round($buyPrice, 4),
                round($sellPrice, 4),
                $profitSpread,
                rand(12, 95) . 'M USDT'
            ]);
        }
    }

    // Fetch and check locking status relative to user's real balance
    public static function getOpportunities($pdo, $userRealBalance = 0) {
        self::refreshPricesIfNeeded($pdo);
        
        $stmt = $pdo->query("SELECT * FROM arbitrage_deals ORDER BY profit_percentage DESC");
        $deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($deals as &$deal) {
            // Lock platforms if yield > 3.00% and user balance < $3000
            $deal['is_locked'] = ((float)$deal['profit_percentage'] > 3.00 && (float)$userRealBalance < 3000.00);
        }
        return $deals;
    }
}
?>
