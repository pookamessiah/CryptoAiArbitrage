<?php
// language.php - Dedicated Language Selection Hub (40 Languages)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$supported_languages = [
    'en' => ['name' => 'English', 'native' => 'English'],
    'es' => ['name' => 'Spanish', 'native' => 'Español'],
    'fr' => ['name' => 'French', 'native' => 'Français'],
    'de' => ['name' => 'German', 'native' => 'Deutsch'],
    'pt' => ['name' => 'Portuguese', 'native' => 'Português'],
    'it' => ['name' => 'Italian', 'native' => 'Italiano'],
    'ru' => ['name' => 'Russian', 'native' => 'Русский'],
    'zh' => ['name' => 'Chinese', 'native' => '中文'],
    'ja' => ['name' => 'Japanese', 'native' => '日本語'],
    'ko' => ['name' => 'Korean', 'native' => '한국어'],
    'ar' => ['name' => 'Arabic', 'native' => 'العربية'],
    'hi' => ['name' => 'Hindi', 'native' => 'हिन्दी'],
    'tr' => ['name' => 'Turkish', 'native' => 'Türkçe'],
    'nl' => ['name' => 'Dutch', 'native' => 'Nederlands'],
    'pl' => ['name' => 'Polish', 'native' => 'Polski'],
    'vi' => ['name' => 'Vietnamese', 'native' => 'Tiếng Việt'],
    'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia'],
    'tl' => ['name' => 'Filipino', 'native' => 'Tagalog'],
    'uk' => ['name' => 'Ukrainian', 'native' => 'Українська'],
    'el' => ['name' => 'Greek', 'native' => 'Ελληνικά'],
    'sv' => ['name' => 'Swedish', 'native' => 'Svenska'],
    'ro' => ['name' => 'Romanian', 'native' => 'Română'],
    'th' => ['name' => 'Thai', 'native' => 'ไทย'],
    'bn' => ['name' => 'Bengali', 'native' => 'বাংলা'],
    'fa' => ['name' => 'Persian', 'native' => 'فارسی'],
    'ur' => ['name' => 'Urdu', 'native' => 'اردو'],
    'he' => ['name' => 'Hebrew', 'native' => 'עברית'],
    'cs' => ['name' => 'Czech', 'native' => 'Čeština'],
    'hu' => ['name' => 'Hungarian', 'native' => 'Magyar'],
    'da' => ['name' => 'Danish', 'native' => 'Dansk'],
    'fi' => ['name' => 'Finnish', 'native' => 'Suomi'],
    'no' => ['name' => 'Norwegian', 'native' => 'Norsk'],
    'ms' => ['name' => 'Malay', 'native' => 'Bahasa Melayu'],
    'sw' => ['name' => 'Swahili', 'native' => 'Kiswahili'],
    'ta' => ['name' => 'Tamil', 'native' => 'தமிழ்'],
    'te' => ['name' => 'Telugu', 'native' => 'తెలుగు'],
    'mr' => ['name' => 'Marathi', 'native' => 'मराठी'],
    'gu' => ['name' => 'Gujarati', 'native' => 'ગુજરાતી'],
    'pa' => ['name' => 'Punjabi', 'native' => 'ਪੰਜਾਬੀ'],
    'ml' => ['name' => 'Malayalam', 'native' => 'മലയാളം']
];

// Handle language selection action
if (isset($_GET['set_lang']) && array_key_exists($_GET['set_lang'], $supported_languages)) {
    $lang_code = $_GET['set_lang'];
    $_SESSION['lang'] = $lang_code;
    setcookie('lang_pref', $lang_code, time() + (86400 * 30), '/');
    
    header("Location: index.php");
    exit();
}

$current_lang = $_SESSION['lang'] ?? ($_COOKIE['lang_pref'] ?? 'en');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Language | ArbitragePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0b0e11;
            --bg-card: #181a20;
            --bg-hover: #2b313a;
            --text-main: #eaecef;
            --text-muted: #848e9c;
            --green: #0ecb81;
            --border-color: #2b313a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Segoe UI', sans-serif; padding-bottom: 60px; }

        .container { width: min(1000px, calc(100% - 32px)); margin: 40px auto; }
        
        .header-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }
        .header-box h1 { font-size: 26px; margin-bottom: 10px; color: #fff; }
        .header-box p { color: var(--text-muted); font-size: 14px; }

        .lang-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 16px;
        }

        .lang-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px;
            text-decoration: none;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            gap: 4px;
            transition: all 0.2s ease;
        }

        .lang-card:hover {
            border-color: var(--green);
            background: var(--bg-hover);
            transform: translateY(-2px);
        }

        .lang-card.active {
            border-color: var(--green);
            background: rgba(14, 203, 129, 0.1);
        }

        .lang-native { font-size: 16px; font-weight: 700; color: #fff; }
        .lang-english { font-size: 12px; color: var(--text-muted); }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .back-btn:hover { color: #fff; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header-box">
            <h1><i class="fa-solid fa-globe" style="color: var(--green);"></i> Choose Your Preferred Language</h1>
            <p>Select from over 40 supported languages to customize your platform experience.</p>
        </div>

        <div class="lang-grid">
            <?php foreach ($supported_languages as $code => $lang): ?>
                <a href="language.php?set_lang=<?= $code ?>" class="lang-card <?= $current_lang === $code ? 'active' : '' ?>">
                    <span class="lang-native"><?= htmlspecialchars($lang['native']) ?></span>
                    <span class="lang-english"><?= htmlspecialchars($lang['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div>
            <a href="index.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Return to Platform</a>
        </div>
    </div>

</body>
</html>
