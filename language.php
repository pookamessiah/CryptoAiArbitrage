<?php
// language.php - Language Manager & Translator Handler
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Start output buffering to prevent "headers already sent" errors
if (!ob_get_level()) {
    ob_start();
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
    'ro' => ['name' => 'Romanian', 'native' => 'Română']
];

// Handle manual language switch request via URL parameter (?set_lang=es)
if (isset($_GET['set_lang']) && array_key_exists($_GET['set_lang'], $supported_languages)) {
    $lang_code = $_GET['set_lang'];
    $_SESSION['lang'] = $lang_code;
    setcookie('lang_pref', $lang_code, time() + (86400 * 30), '/'); // Save cookie for 30 days
    
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . ($redirect_url ? $redirect_url : 'index.php'));
    exit();
}

// Determine active language
if (!isset($_SESSION['lang'])) {
    if (isset($_COOKIE['lang_pref']) && array_key_exists($_COOKIE['lang_pref'], $supported_languages)) {
        $_SESSION['lang'] = $_COOKIE['lang_pref'];
    } else {
        $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);
        if (array_key_exists($browser_lang, $supported_languages)) {
            $_SESSION['lang'] = $browser_lang;
        } else {
            $_SESSION['lang'] = 'en';
        }
    }
}

$current_lang = $_SESSION['lang'];
?>
