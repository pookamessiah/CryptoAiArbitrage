<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error displaying so we can pinpoint issues if database structural details are missing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';

// 2. Instantiate your "Database" class and invoke the connect() function to expose $pdo safely
$pdo = null;
try {
    if (class_exists('Database')) {
        $dbInstance = new Database();
        $pdo = $dbInstance->connect(); 
    }
} catch (Exception $e) {
    // Catch initial structural connection issues down below gracefully
}

// Default values
$artist_name = "";
$event_banner_image = "https://picsum.photos/id/625/2000/1000";
$genre = "";
$rating = "";

if (isset($_GET['artist_id'])) {

    $artist_id = (int)$_GET['artist_id'];

    try {

        if ($pdo) {

            $stmt = $pdo->prepare("
                SELECT
                    artist_name,
                    artist_image,
                    genre,
                    rating
                FROM artists
                WHERE artist_id = ?
                LIMIT 1
            ");

            $stmt->execute([$artist_id]);

            $artist = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($artist) {

                $artist_name = $artist['artist_name'];
                $genre = $artist['genre'];
                $rating = $artist['rating'];

                if (!empty($artist['artist_image'])) {
                    $event_banner_image = "uploads/artists/" . $artist['artist_image'];
                }

            }

        }

    } catch (PDOException $e) {

        die($e->getMessage());

    }

}

$concerts_results = [];

if (!empty($artist_id) && $pdo) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM concerts
        WHERE artist_id = ?
        ORDER BY concert_date ASC
    ");

    $stmt->execute([$artist_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // Month and Day Number from concert_date
        $timestamp = strtotime($row['concert_date']);

        // Split "Sat • 8:00 PM"
        $parts = explode('•', $row['day_time']);

        $day_name = trim($parts[0] ?? '');
        $time = trim($parts[1] ?? '');

        $concerts_results[] = [

            'id' => $row['concert_id'],

            'month' => date('M', $timestamp),

            'day_num' => date('d', $timestamp),

            'day_name' => $day_name,

            'time' => $time,

            'location' => $row['location'],

            'venue' => $row['venue'],

            'title' => $row['title']

        ];

    }

}

$total_concerts_count = count($concerts_results);

$vip_packages = [];

if (!empty($artist_id) && $pdo) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM vip_exp
        WHERE artist_id = ?
        ORDER BY vip_exp_id ASC
    ");

    $stmt->execute([$artist_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $vip_packages[] = $row;

    }

}

$gallery_items = [];

if (!empty($artist_id) && $pdo) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM gallery
        WHERE artist_id = ?
        ORDER BY gallery_id ASC
    ");

    $stmt->execute([$artist_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // Extract YouTube Video ID
        preg_match(
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/',
            $row['youtube_media_link'],
            $matches
        );

        $row['youtube_id'] = $matches[1] ?? '';

        $gallery_items[] = $row;
    }
}
    
?>
<!DOCTYPE html>
<html lang="en">

<?php include "inc/head.php"; ?>
<?php include "inc/navbar.php"; ?> 
 
<body class="bg-white text-gray-900 font-sans antialiased">
    
    <?php include "inc/header.php"; ?> 

    <div id="__next">
        <div class="sc-d727d306-0 llCpYZ">
         Your browser is not supported. For the best experience, use any of these supported browsers:
         <a class="Link__StyledLink-sc-pudy0l-0 bfasNL" href="https://www.google.com/chrome/">Chrome</a>, 
         <a class="Link__StyledLink-sc-pudy0l-0 bfasNL" href="https://www.mozilla.org/firefox/new/">Firefox</a>, 
         <a class="Link__StyledLink-sc-pudy0l-0 bfasNL" href="https://support.apple.com/downloads/safari">Safari</a>, 
         <a class="Link__StyledLink-sc-pudy0l-0 bfasNL" href="https://www.microsoft.com/edge">Edge</a>.
        </div>
        <section class="sc-7f6df46b-0 frSDhw">
         <a class="Link__StyledLink-sc-pudy0l-0 eYZQRC sc-7f6df46b-1 firzHb" href="#main-content">
          Skip to main content
         </a>
        </section>    
        
        <!-- 2. HERO HEADER BANNER RENDERS DIRECTLY UNDERNEATH NAVBAR -->
        <div class="relative w-full h-[420px] md:h-[500px] overflow-hidden bg-black">
        
            <!-- Banner -->
            <img
                src="<?= htmlspecialchars($event_banner_image); ?>"
                onerror="this.src='https://picsum.photos/2000/900';"
                class="absolute inset-0 w-full h-full object-cover"
                alt="<?= htmlspecialchars($artist_name); ?>"
            >
        
            <!-- Dark Overlay -->
            <div class="absolute inset-0 bg-black/45"></div>
        
            <!-- Bottom Gradient -->
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent"></div>
        
            <!-- Breadcrumb -->
            <div class="absolute top-8 left-8 md:left-12 text-white text-sm">
        
                <span class="text-white/80">Home</span>
        
                <span class="mx-1">/</span>
        
                <span class="text-white/80">Concerts</span>
        
                <span class="mx-1">/</span>
        
                <span class="text-white/80">
                    <?= htmlspecialchars($genre); ?>
                </span>
        
                <span class="mx-1">/</span>
        
                <span class="font-medium">
                    <?= htmlspecialchars($artist_name); ?> Tickets
                </span>
        
            </div>
        
            <!-- Content -->
            <div class="absolute bottom-12 left-8 md:left-12 text-white">
        
                <!-- Genre -->
                <p class="text-xl font-semibold mb-2">
                    <?= htmlspecialchars($genre); ?>
                </p>
        
                <!-- Artist -->
                <h1 class="text-4xl md:text-4xl font-black leading-none">
                    <?= htmlspecialchars($artist_name); ?> Tickets
                </h1>
        
                <!-- Rating -->
                <div class="flex items-center gap-3 mt-6">
        
                    <div class="w-11 h-11 rounded-full border border-white flex items-center justify-center cursor-pointer hover:bg-white/10 transition">
        
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="w-5 h-5"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">
        
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        
                        </svg>
        
                    </div>
        
                    <div class="px-4 py-2 rounded-full border border-white/70 bg-transparent backdrop-blur-sm text-white font-bold flex items-center gap-2">
        
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewBox="0 0 20 20">
        
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.538 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.783.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.363-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81H7.03a1 1 0 00.95-.69z"/>
        
                        </svg>
        
                        <span><?= htmlspecialchars($rating); ?></span>
        
                    </div>
        
                </div>
        
            </div>
        
        </div>

        <div class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm overflow-x-auto select-none">
            <div class="max-w-7xl mx-auto flex items-center px-4 md:px-8 space-x-1 whitespace-nowrap min-w-max h-14">
                <a href="#concerts-section" class="px-5 py-2 text-sm font-bold text-[#024DDF] border-b-2 border-[#024DDF] hover:text-blue-800 transition-colors">CONCERTS</a>
                <a href="#vip-section" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:text-blue-600 border-b-2 border-transparent transition-colors">VIP EXPERIENCE</a>
                <a href="#fan-card-section" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:text-blue-600 border-b-2 border-transparent transition-colors">FAN CARD</a>
                <a href="#gallery-section" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:text-blue-600 border-b-2 border-transparent transition-colors">GALLERY</a>
                <a href="#about-section" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:text-blue-600 border-b-2 border-transparent transition-colors">ABOUT</a>
                <a href="#setlists-section" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:text-blue-600 border-b-2 border-transparent transition-colors">SETLISTS</a>
                <a href="#news-section" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:text-blue-600 border-b-2 border-transparent transition-colors">NEWS</a>
                <a href="#faqs-section" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:text-blue-600 border-b-2 border-transparent transition-colors">FAQS</a>
                <a href="#reviews-section" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:text-blue-600 border-b-2 border-transparent transition-colors">REVIEWS</a>
                <a href="#fans-also-viewed-section" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:text-blue-600 border-b-2 border-transparent transition-colors">FANS ALSO VIEWED</a>
            </div>
        </div>

        <main id="main-content" class="max-w-7xl mx-auto px-4 md:px-8 py-10 space-y-16">
            
            <div id="concerts-section" class="scroll-mt-16">
                <div class="border-b border-gray-200 pb-4 mb-6">
                    <h3 class="text-3xl font-black tracking-tight text-gray-900">
                        "<strong>CONCERTS</strong> • <?php echo $total_concerts_count; ?> RESULTS"
                    </h3>
                </div>

                <div class="space-y-4">
                    <?php foreach ($concerts_results as $concert): ?>
                        <div class="bg-white border border-gray-200 rounded-xl p-4 md:p-6 shadow-sm hover:shadow-md hover:border-gray-300 transition-all flex flex-col md:flex-row md:items-center justify-between gap-6">
                            
                            <div class="flex items-center gap-4 border-b md:border-b-0 border-gray-100 pb-3 md:pb-0 shrink-0 min-w-[130px]">
                                <div class="text-center bg-gray-50 rounded-lg p-2 min-w-[64px]">
                                    <span class="block text-xs font-bold uppercase tracking-wider text-gray-500"><?php echo htmlspecialchars($concert['month']); ?></span>
                                    <span class="block text-2xl font-black text-gray-900 leading-none my-0.5"><?php echo htmlspecialchars($concert['day_num']); ?></span>
                                    <span class="block text-xs font-bold text-gray-400 uppercase"><?php echo htmlspecialchars($concert['day_name']); ?></span>
                                </div>
                                <div>
                                    <span class="text-sm font-bold text-gray-900 block"><i class="far fa-clock text-blue-600 mr-1"></i> <?php echo htmlspecialchars($concert['time']); ?></span>
                                    <span class="text-[11px] font-bold text-green-600 bg-green-50 px-1.5 py-0.5 rounded uppercase mt-1 inline-block">Verified Option</span>
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-tight flex items-center gap-1.5 truncate">
                                    <i class="fas fa-map-marker-alt text-[#024DDF]"></i> 
                                    <?php echo htmlspecialchars($concert['location']); ?> - <?php echo htmlspecialchars($concert['venue']); ?>
                                </p>
                                <h4 class="text-base md:text-lg font-extrabold text-gray-900 tracking-tight mt-1 truncate" title="<?php echo htmlspecialchars($concert['title']); ?>">
                                    <?php echo htmlspecialchars($concert['title']); ?>
                                </h4>
                            </div>

                            <div class="shrink-0 text-right">
                                <a href="bookings.php?concert_id=<?php echo $concert['id']; ?>"
                                   class="block text-center w-full md:w-auto bg-[#024DDF] hover:bg-blue-800 text-white font-bold text-xs uppercase tracking-wider py-3 px-6 rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Find Tickets
                                </a>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($vip_packages)): ?>
            
            <div id="vip-section" class="scroll-mt-16 bg-[#121212] py-12 relative">
            
                <div class="max-w-7xl mx-auto px-4 md:px-8">
            
                    <div class="mb-8">
                        <div class="w-8 h-[3px] bg-white mb-3"></div>
                        <h2 class="text-white font-bold text-3xl uppercase tracking-wide">
                            Experience
                        </h2>
                    </div>
            
                    <!-- Left Arrow -->
                    <button
                        id="vipPrev"
                        class="absolute left-5 top-1/2 -translate-y-1/2 z-20 bg-white text-gray-700 w-10 h-10 rounded flex items-center justify-center shadow hover:bg-gray-200">
            
                        <i class="fas fa-chevron-left"></i>
            
                    </button>
            
                    <!-- Right Arrow -->
                    <button
                        id="vipNext"
                        class="absolute right-5 top-1/2 -translate-y-1/2 z-20 bg-[#0256ff] text-white w-10 h-10 rounded flex items-center justify-center shadow hover:bg-blue-700">
            
                        <i class="fas fa-chevron-right"></i>
            
                    </button>
            
                    <div
                        id="vipSlider"
                        class="flex gap-5 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-4">
            
                        <?php foreach($vip_packages as $vip): ?>
            
                            <div class="min-w-[430px] max-w-[430px] snap-start bg-white shadow-xl">
            
                                <div class="bg-black h-60 flex items-center justify-center">
            
                                    <img
                                        src="uploads/vip/<?= htmlspecialchars($vip['image']); ?>"
                                        class="max-h-full max-w-full object-contain"
                                        alt="<?= htmlspecialchars($vip['title']); ?>">
            
                                </div>
            
                                <div class="p-8">
            
                                    <h3 class="text-3xl font-semibold mb-5">
            
                                        <?= htmlspecialchars($vip['title']); ?>
            
                                    </h3>
            
                                    <div class="text-gray-700 leading-7 whitespace-pre-line">
            
                                        <?= nl2br(htmlspecialchars($vip['description'])); ?>
            
                                    </div>
            
                                </div>
            
                            </div>
            
                        <?php endforeach; ?>
            
                    </div>
            
                </div>
            
            </div>
            
            <?php endif; ?>

            <?php if(!empty($gallery_items)): ?>
            
            <div id="gallery-section" class="scroll-mt-16 py-14">
            
                <div class="flex items-center mb-8">
            
                    <div class="w-8 h-[3px] bg-black mr-3"></div>
            
                    <h2 class="text-3xl font-black uppercase tracking-wide">
                        Gallery
                    </h2>
            
                </div>
            
                <div class="relative">
            
                    <!-- Left -->
                    <button id="galleryPrev"
                        class="absolute left-0 top-1/2 -translate-y-1/2 z-20 bg-white shadow w-10 h-10 rounded flex items-center justify-center">
            
                        <i class="fas fa-chevron-left"></i>
            
                    </button>
            
                    <!-- Right -->
                    <button id="galleryNext"
                        class="absolute right-0 top-1/2 -translate-y-1/2 z-20 bg-[#0256ff] text-white shadow w-10 h-10 rounded flex items-center justify-center">
            
                        <i class="fas fa-chevron-right"></i>
            
                    </button>
            
                    <div
                        id="gallerySlider"
                        class="flex overflow-x-auto scroll-smooth snap-x snap-mandatory">
            
                        <?php foreach($gallery_items as $media): ?>
            
                            <a
                                href="<?= htmlspecialchars($media['youtube_media_link']); ?>"
                                target="_blank"
                                class="relative min-w-[250px] md:min-w-[320px] h-[190px] snap-start overflow-hidden group">
            
                                <img
                                    src="https://img.youtube.com/vi/<?= $media['youtube_id']; ?>/hqdefault.jpg"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
            
                                <div class="absolute inset-0 bg-black/40"></div>
            
                                <!-- Play Icon -->
                                <div class="absolute inset-0 flex items-center justify-center">
            
                                    <div class="w-14 h-14 rounded-full border-2 border-white flex items-center justify-center">
            
                                        <i class="fas fa-play text-white ml-1"></i>
            
                                    </div>
            
                                </div>
            
                                <!-- Title -->
                                <div class="absolute bottom-4 left-4 right-4">
            
                                    <p class="text-white font-bold leading-6 text-lg drop-shadow">
            
                                        <?= htmlspecialchars($media['media_title']); ?>
            
                                    </p>
            
                                </div>
            
                            </a>
            
                        <?php endforeach; ?>
            
                    </div>
            
                </div>
            
            </div>
            
            <?php endif; ?>

            <div id="reviews-section" class="scroll-mt-16 bg-gray-50 rounded-2xl p-8 border border-gray-100">
                <span class="text-xs font-bold uppercase tracking-widest text-gray-400 block mb-2">Category Box C</span>
                <h3 class="text-2xl font-black text-gray-900 tracking-tight mb-4">Verified Fan Reviews</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex items-center text-yellow-400 text-xs mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="text-xs text-gray-600 font-medium italic">"An absolute phenomenal audio arrangement execution framework. Clean tracking updates from start to finish."</p>
                        <span class="text-[10px] font-bold text-gray-400 block mt-3">— Ticketmaster Verified Fan</span>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex items-center text-yellow-400 text-xs mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="text-xs text-gray-600 font-medium italic">"The stadium configuration matches perfectly. Seamless order confirmation response pipeline."</p>
                        <span class="text-[10px] font-bold text-gray-400 block mt-3">— Secondary Market Ticket Holder</span>
                    </div>
                </div>
            </div>

            <div id="about-section" class="scroll-mt-16">
                <span class="text-xs font-bold uppercase tracking-widest text-gray-400 block mb-1">Category Box D</span>
                <h3 class="text-2xl font-black text-gray-900 tracking-tight">About the Production Program</h3>
                <p class="text-sm text-gray-600 mt-2 leading-relaxed max-w-4xl">
                    Experience this generation's masterclass musical performance running globally. This show brings advanced synchronized visual arts choreography directly to high-capacity arenas. Book verified tickets securely through our real-time seating coordinate ledger system.
                </p>
            </div>

            <div id="faqs-section" class="scroll-mt-16 bg-gray-50 rounded-2xl p-8 border border-gray-100 space-y-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-400 block mb-1">Category Box E</span>
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight mb-4">Frequently Asked Questions</h3>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-sm font-bold text-gray-900">Are the ticket allocations verified in real time?</h4>
                    <p class="text-xs text-gray-500 mt-1">Yes. All sections, row listings, and concrete seat configurations are synced instantly with our administration inventory ledger validation system loops.</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-sm font-bold text-gray-900">Can I transfer my tickets on the secondary market after purchase?</h4>
                    <p class="text-xs text-gray-500 mt-1">Yes, purchases are linked directly to your user account dashboard, enabling you to manage verification uploads cleanly.</p>
                </div>
            </div>

            <div id="gifts-section" class="scroll-mt-16 border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center bg-gray-50/50">
                <span class="text-xs font-bold uppercase tracking-widest text-gray-400 block mb-1">Category Box F</span>
                <i class="fas fa-gift text-3xl text-blue-600 mb-2 block"></i>
                <h3 class="text-xl font-black text-gray-900 tracking-tight">Souvenirs & Collectors Commemorative Items</h3>
                <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto">Digital tokens, unique visual prints, and memorabilia tokens are accessible inside your confirmation wallet framework upon finalizing checkouts.</p>
            </div>

        </main>

        <?php include "inc/footer.php"; ?>
    </div>

    <div id="modals" data-testid="modals"></div>
    
    <style>
        html { scroll-behavior: smooth; }
        body { overflow-x: hidden; }
        .sticky::-webkit-scrollbar { display: none; }

        #vipSlider::-webkit-scrollbar{
            display:none;
        }
        
        #vipSlider{
            -ms-overflow-style:none;
            scrollbar-width:none;
        }

        #gallerySlider::-webkit-scrollbar{
            display:none;
        }
        
        #gallerySlider{
            scrollbar-width:none;
            -ms-overflow-style:none;
        }
    </style>

<script>

const slider = document.getElementById('vipSlider');

if(slider){

    document.getElementById('vipNext').onclick=function(){

        slider.scrollBy({
            left:450,
            behavior:'smooth'
        });

    };

    document.getElementById('vipPrev').onclick=function(){

        slider.scrollBy({
            left:-450,
            behavior:'smooth'
        });

    };

}

const gallerySlider = document.getElementById('gallerySlider');

if(gallerySlider){

    document.getElementById('galleryNext').onclick=function(){

        gallerySlider.scrollBy({
            left:320,
            behavior:'smooth'
        });

    };

    document.getElementById('galleryPrev').onclick=function(){

        gallerySlider.scrollBy({
            left:-320,
            behavior:'smooth'
        });

    };

}

</script>
    
</body>
</html>
