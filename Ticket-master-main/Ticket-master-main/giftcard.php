<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Define the Catalog of Major Gift Cards using digital delivery platforms
$giftcards_catalog = [
    'apple' => [
        'name' => 'Apple Gift Card',
        'icon' => 'fab fa-apple',
        'color' => 'from-slate-700 to-slate-900',
        'text_color' => 'text-slate-800',
        'bg_light' => 'bg-slate-50',
        'border_color' => 'border-slate-200',
        'url' => 'https://www.carddelivery.com/apple-gift-card',
        'youtube_id' => 'dQw4w9WgXcQ', // Replace with a CardDelivery Apple tutorial video ID
        'instructions' => 'Select the Apple/iTunes card value, pay using secure methods like PayPal or credit card, and check your inbox for the immediate digital code delivery.'
    ],
    'steam' => [
        'name' => 'Steam Wallet Code',
        'icon' => 'fab fa-steam',
        'color' => 'from-slate-800 to-indigo-950',
        'text_color' => 'text-indigo-950',
        'bg_light' => 'bg-slate-100',
        'border_color' => 'border-slate-300',
        'url' => 'https://www.carddelivery.com/steam',
        'youtube_id' => 'dQw4w9WgXcQ', // Replace with a CardDelivery Steam tutorial video ID
        'instructions' => 'Choose your Steam Wallet code amount, check out using your preferred payment gateway, and get your digital key emailed instantly to top up your Steam account.'
    ],
    'razer' => [
        'name' => 'Razer Gold Pin',
        'icon' => 'fas fa-gem',
        'color' => 'from-green-500 to-emerald-600',
        'text_color' => 'text-green-600',
        'bg_light' => 'bg-green-50',
        'border_color' => 'border-green-200',
        'url' => 'https://www.carddelivery.com/razer-gold',
        'youtube_id' => 'dQw4w9WgXcQ', // Replace with a Dundle Razer Gold tutorial video ID
        'instructions' => 'Select the Razer Gold PIN amount on Dundle, choose from over 70 payment methods, and your PIN will appear instantly on-screen and in your email.'
    ]
];

// 2. Identify the active selected card or default to Apple if empty/invalid
$selected_key = isset($_GET['type']) ? trim($_GET['type']) : 'apple';
if (!array_key_exists($selected_key, $giftcards_catalog)) {
    $selected_key = 'apple';
}

$active_card = $giftcards_catalog[$selected_key];
?>
<!DOCTYPE html>
<html lang="en">

<?php 
if (file_exists("inc/head.php")) { include "inc/head.php"; } else {
    echo '<head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Buy Gift Cards — Fast Digital Delivery Guide</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
          </head>';
}
?>

<body class="bg-gray-50 text-gray-900 font-sans antialiased">
    
    <?php if (file_exists("inc/navbar.php")) { include "inc/navbar.php"; } ?> 

    <div id="__next">
        <div class="bg-gradient-to-r <?php echo $active_card['color']; ?> text-white py-12 px-4 md:px-8 shadow-md transition-all duration-300">
            <div class="max-w-6xl mx-auto text-center md:text-left flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <span class="text-xs font-black uppercase tracking-widest bg-black/30 px-3 py-1 rounded-full">Instant Digital Delivery</span>
                    <h1 class="text-3xl md:text-5xl font-black tracking-tight mt-3 flex items-center justify-center md:justify-start gap-3">
                        <i class="<?php echo $active_card['icon']; ?>"></i> How to Buy <?php echo htmlspecialchars($active_card['name']); ?>
                    </h1>
                    <p class="text-xs md:text-sm text-white/80 mt-2 max-w-xl leading-relaxed font-medium mx-auto md:mx-0">
                        Follow our verified digital onboarding blueprint below to safely secure and claim valid credit codes instantly through trusted third-party delivery platforms.
                    </p>
                </div>
            </div>
        </div>

        <main class="max-w-6xl mx-auto px-4 md:px-8 py-10 grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <div class="lg:col-span-3 space-y-3">
                <h3 class="text-xs font-black text-gray-400 uppercase tracking-wider mb-2 pl-1"><i class="fas fa-layer-group"></i> Select Card Type</h3>
                <div class="flex lg:flex-col gap-2 overflow-x-auto pb-2 lg:pb-0 scrollbar-none whitespace-nowrap">
                    <?php foreach ($giftcards_catalog as $key => $card): 
                        $is_active = ($key === $selected_key);
                        $btn_classes = $is_active 
                            ? "bg-slate-900 text-white shadow" 
                            : "bg-white text-gray-700 hover:bg-gray-100 border border-gray-200";
                    ?>
                        <a href="giftcard?type=<?php echo $key; ?>" 
                           class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-200 shrink-0 <?php echo $btn_classes; ?>">
                            <i class="<?php echo $card['icon']; ?> text-sm <?php echo !$is_active ? $card['text_color'] : ''; ?>"></i>
                            <?php echo htmlspecialchars(explode(' ', $card['name'])[0]); ?> Card
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-base font-black text-gray-900 tracking-tight uppercase border-b border-gray-100 pb-3 mb-6 flex items-center gap-2">
                        <i class="fas fa-list-check <?php echo $active_card['text_color']; ?>"></i> Purchase Steps
                    </h2>

                    <div class="flex gap-4 mb-6 relative">
                        <div class="absolute left-4 top-10 bottom-0 w-0.5 bg-gray-100 hidden sm:block"></div>
                        <div class="w-9 h-9 <?php echo $active_card['bg_light']; ?> border <?php echo $active_card['border_color']; ?> <?php echo $active_card['text_color']; ?> font-black rounded-full flex items-center justify-center shrink-0 shadow-sm">
                            1
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">Access Delivery Platform</h4>
                            <p class="text-xs text-gray-500 leading-relaxed mt-1">
                                Click the primary <span class="font-bold text-gray-700">"Proceed to Purchase Website"</span> button located on the right sidebar module to launch the trusted retail portal securely.
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-4 mb-6 relative">
                        <div class="absolute left-4 top-10 bottom-0 w-0.5 bg-gray-100 hidden sm:block"></div>
                        <div class="w-9 h-9 <?php echo $active_card['bg_light']; ?> border <?php echo $active_card['border_color']; ?> <?php echo $active_card['text_color']; ?> font-black rounded-full flex items-center justify-center shrink-0 shadow-sm">
                            2
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">Configure & Checkout</h4>
                            <p class="text-xs text-gray-500 leading-relaxed mt-1">
                                <?php echo htmlspecialchars($active_card['instructions']); ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="w-9 h-9 <?php echo $active_card['bg_light']; ?> border <?php echo $active_card['border_color']; ?> <?php echo $active_card['text_color']; ?> font-black rounded-full flex items-center justify-center shrink-0 shadow-sm">
                            3
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">Check Email & Validate</h4>
                            <p class="text-xs text-gray-500 leading-relaxed mt-1">
                                Check your email inbox (and spam folder) for the delivery confirmation containing your alphanumeric code. Keep your receipt file safe and redeem the pin on the official platform.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
                    <i class="fas fa-circle-exclamation text-amber-500 text-lg mt-0.5 shrink-0"></i>
                    <div>
                        <h5 class="text-xs font-extrabold text-amber-900 uppercase tracking-wider">Security Notice</h5>
                        <p class="text-[11px] text-amber-700 leading-relaxed mt-0.5">
                            Verify the external domain browser navigation strings match secure SSL structures. Never disclose active alphanumeric keys to unverified support entities or third parties.
                        </p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 space-y-6">
                
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm text-center">
                    <div class="w-12 h-12 rounded-full mx-auto flex items-center justify-center mb-3 <?php echo $active_card['bg_light']; ?>">
                        <i class="<?php echo $active_card['icon']; ?> text-xl <?php echo $active_card['text_color']; ?>"></i>
                    </div>
                    <h3 class="text-sm font-black text-gray-900 tracking-tight uppercase">Ready to Purchase?</h3>
                    <p class="text-xs text-gray-500 mt-1 mb-6 leading-relaxed">
                        Redirect instantly to our certified digital delivery merchant system to securely purchase your card code.
                    </p>
                    
                    <a href="<?php echo htmlspecialchars($active_card['url']); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="block text-center w-full bg-slate-900 hover:bg-slate-800 text-white font-black text-xs uppercase tracking-wider py-4 px-6 rounded-xl transition-all shadow focus:outline-none flex items-center justify-center gap-2">
                        Proceed to Purchase Website <i class="fas fa-external-link-alt text-[10px]"></i>
                    </a>
                </div>

                <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
                    <h3 class="text-xs font-black text-gray-900 uppercase tracking-tight mb-3 flex items-center gap-2">
                        <i class="fab fa-youtube text-red-600 text-base"></i> Video Walkthrough Guide
                    </h3>
                    
                    <div class="relative w-full aspect-video rounded-xl overflow-hidden shadow-inner bg-slate-900 border border-gray-100">
                        <iframe 
                            class="absolute top-0 left-0 w-full h-full"
                            src="https://www.youtube.com/embed/<?php echo htmlspecialchars($active_card['youtube_id']); ?>?rel=0" 
                            title="YouTube video player" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                            allowfullscreen>
                        </iframe>
                    </div>
                    
                    <p class="text-[10px] text-gray-400 mt-3 text-center italic leading-relaxed">
                        Having issues processing your transaction window? Watch this visual execution demo layout module.
                    </p>
                </div>

            </div>
        </main>

        <?php if (file_exists("inc/footer.php")) { include "inc/footer.php"; } ?>
    </div>

    <style>
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</body>
</html>
