<?php
// api/live-data.php - Live Arbitrage Engine & 5-Minute Price Synchronizer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

class LiveArbitrageEngine {
    private $pdo;

    public function __construct($pdoConnection) {
        $pdo = $pdoConnection;
    }

    // Ensure database table exists
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

    // Check if 5 minutes have passed and refresh prices using live data
    public function refreshPricesIfNeeded($pdo) {
        self::initTable($pdo);

        // Check last update time
        $stmtCheck = $pdo->query("SELECT MAX(updated_at) as last_update FROM arbitrage_deals");
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $lastUpdate = $row['last_update'] ?? null;

        $currentTime = time();
        $fiveMinutesAgo = $currentTime - 300; // 300 seconds = 5 minutes

        if (!$lastUpdate || strtotime($lastUpdate) < $fiveMinutesAgo) {
            $this->fetchAndUpsertLiveDeals($pdo);
        }
    }

    // Fetch live prices from Binance API for 28+ assets and generate 25+ arbitrage opportunities
    private function fetchAndUpsertLiveDeals($pdo) {
        $coins = [
            'BTC' => 'Bitcoin', 'ETH' => 'Ethereum', 'SOL' => 'Solana', 'XRP' => 'XRP',
            'ADA' => 'Cardano', 'AVAX' => 'Avalanche', 'DOGE' => 'Dogecoin', 'DOT' => 'Polkadot',
            'LINK' => 'Chainlink', 'MATIC' => 'Polygon', 'UNI' => 'Uniswap', 'LTC' => 'Litecoin',
            'NEAR' => 'Near Protocol', 'ATOM' => 'Cosmos', 'XLM' => 'Stellar', 'APT' => 'Aptos',
            'SUI' => 'Sui', 'ARB' => 'Arbitrum', 'OP' => 'Optimism', 'ICP' => 'Internet Computer',
            'RENDER' => 'Render', 'INJ' => 'Injective', 'TIA' => 'Celestia', 'FIL' => 'Filecoin',
            'STX' => 'Stacks', 'ETC' => 'Ethereum Classic', 'ALGO' => 'Algorand', 'NEAR' => 'Near'
        ];

        // Call Binance Public Price API
        $ch = curl_init('https://api.binance.com/api/v3/ticker/price');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);

        $binancePrices = [];
        if ($response) {
            $data = json_decode($response, true);
            if (is_array($data)) {
                foreach ($data as item) {
                    if (str_ends_with(item['symbol'], 'USDT')) {
                        $symbol = str_replace('USDT', '', item['symbol']);
                        $binancePrices[$symbol] = (float)item['price'];
                    }
                }
            }
        }

        $exchanges = ['Binance', 'OKX', 'Bybit', 'KuCoin', 'Kraken'];
        $pdo->exec("TRUNCATE TABLE arbitrage_deals");

        $stmtInsert = $pdo->prepare("
            INSERT INTO arbitrage_deals (symbol, coin_name, buy_exchange, sell_exchange, buy_price, sell_price, profit_percentage, volume_24h)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $counter = 0;
        foreach ($coins as $symbol => $name) {
            // Base price from live API or fallback mock value if API fails
            $basePrice = $binancePrices[$symbol] ?? ($symbol === 'BTC' ? 68000 : ($symbol === 'ETH' ? 3500 : 100));

            // Select two different exchanges for cross-exchange arbitrage
            $buyEx = $exchanges[array_rand($exchanges)];
            do {
                $sellEx = $exchanges[array_rand($exchanges)];
            } while ($sellEx === $buyEx);

            // Generate realistic profit percentage spread (mix of low <=3% and high >3% opportunities)
            // Ensure we generate over 25 unique items
            $profitSpread = round((rand(80, 450) / 100), 2); // 0.80% to 4.50%

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
                rand(15, 98) . 'M USDT'
            ]);

            $counter++;
        }
    }

    // Fetch all active deals with 3% gating rules applied
    public static function getOpportunities($pdo, $userRealBalance = 0) {
        $stmt = $pdo->query("SELECT * FROM arbitrage_deals ORDER BY profit_percentage DESC");
        $deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($deals as &$deal) {
            $deal['is_locked'] = ((float)$deal['profit_percentage'] > 3.00 && (float)$userRealBalance < 3000);
        }
        return $deals;
    }
}
?>
