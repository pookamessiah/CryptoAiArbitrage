<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Country & Language Selector</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <style>
    .goog-te-banner-frame, .goog-te-gadget, #google_translate_element, .skiptranslate {
      display: none !important;
    }
    body { top: 0px !important; }
  </style>
</head>
<body class="bg-gray-50">

<div id="google_translate_element"></div>

<div class="max-w-md mx-auto mt-20 p-6 bg-white rounded-2xl shadow-lg border border-gray-100">
  <h2 class="text-xl font-black mb-4 text-center tracking-tight text-gray-900">
    <i class="fas fa-globe text-[#024DDF] mr-1.5"></i> Select Your Country
  </h2>
  
  <div class="relative">
    <select id="countrySelector" 
            onchange="processGlobalTranslation()"
            class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm font-bold appearance-none bg-white focus:outline-none focus:border-[#024DDF] focus:ring-2 focus:ring-blue-100 cursor-pointer text-gray-800">
      <option value="">-- Select Country --</option>
    </select>
    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
  </div>

  <div class="mt-4 text-center">
    <a href="javascript:history.back()" class="text-xs font-bold text-gray-400 hover:text-gray-600 transition-colors">
      <i class="fas fa-arrow-left mr-1"></i> Cancel & Go Back
    </a>
  </div>
</div>

<script>
const countries = [
    { name: "United States", code: "US", langCode: "en" },
    { name: "United Kingdom", code: "GB", langCode: "en" },
    { name: "France", code: "FR", langCode: "fr" },
    { name: "Germany", code: "DE", langCode: "de" },
    { name: "Spain", code: "ES", langCode: "es" },
    { name: "Italy", code: "IT", langCode: "it" },
    { name: "Brazil", code: "BR", langCode: "pt" },
    { name: "Mexico", code: "MX", langCode: "es" },
    { name: "Japan", code: "JP", langCode: "ja" },
    { name: "China", code: "CN", langCode: "zh-CN" },
    { name: "India", code: "IN", langCode: "hi" },
    { name: "Russia", code: "RU", langCode: "ru" },
    { name: "South Korea", code: "KR", langCode: "ko" }
];

const select = document.getElementById('countrySelector');

countries.forEach(c => {
    const option = document.createElement('option');
    option.value = c.langCode;
    option.textContent = c.name;
    select.appendChild(option);
});

function processGlobalTranslation() {
    const selectedLang = select.value;
    if (!selectedLang) return;

    const cookieValue = "/en/" + selectedLang;
    
    // Extract base domains to clear wildcard paths (.domain.com)
    const host = window.location.hostname;
    const hostParts = host.split('.');
    const baseDomain = hostParts.length >= 2 ? "." + hostParts.slice(-2).join('.') : "";

    // Array of paths and domains to explicitly purge
    const paths = ["/", "/html", ""];
    const domains = [host, baseDomain, "." + host, ""];

    // 1. CLEAR LOOP: Wipe every trace of the old language cookie
    paths.forEach(p => {
        domains.forEach(d => {
            let domainStr = d ? "; domain=" + d : "";
            let pathStr = p ? "; path=" + p : "";
            document.cookie = "googtrans=" + pathStr + domainStr + "; expires=Thu, 01 Jan 1970 00:00:00 UTC;";
        });
    });

    // 2. WRITE LOOP: Set the new language values cleanly
    document.cookie = "googtrans=" + cookieValue + "; path=/; max-age=" + (365 * 24 * 60 * 60);
    if (baseDomain) {
        document.cookie = "googtrans=" + cookieValue + "; path=/; domain=" + baseDomain + "; max-age=" + (365 * 24 * 60 * 60);
    }

    // 3. REDIRECT: Go back to previous page
    if (document.referrer && document.referrer !== "" && !document.referrer.includes('countries.php')) {
        window.location.href = document.referrer;
    } else {
        window.location.href = 'index.php'; // fallback home
    }
}
</script>
</body>
</html>
