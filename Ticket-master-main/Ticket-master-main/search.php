<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// FIXED: Corrected path traversal reference from '../config/db.php' to 'config/db.php'
require_once 'config/db.php';

$pdo = null;
try {
    if (class_exists('Database')) {
        $dbInstance = new Database();
        $pdo = $dbInstance->connect(); 
    }
} catch (Exception $e) {
    // Graceful containment fallback
}

// Combine keyword and location inputs into a single smart search context
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$location_query = isset($_GET['location']) ? trim($_GET['location']) : '';

// If keyword is blank but location is filled, use location as the primary keyword reference
if (empty($search_query) && !empty($location_query)) {
    $search_query = $location_query;
}

$matched_artists = [];
$matched_events = [];

if (!empty($search_query) && $pdo !== null) {
    // Case-insensitivity conversion setup
    $lowercase_query = mb_strtolower($search_query, 'UTF-8');
    $wildcard_param = "%" . $lowercase_query . "%";
    
    // Phonetic breakdown calculation to ignore typos (e.g., "Teylor" -> "Taylor")
    $soundex_query = soundex($search_query);

    try {
        // Query 1: Smart Artist Matching (Matches substring or similar phonetic vocal structures)
        $artist_sql = "SELECT *
                       FROM artists
                       WHERE LOWER(artist_name) LIKE ?
                          OR SOUNDEX(artist_name) = SOUNDEX(?)
                       ORDER BY artist_name
                       LIMIT 10";
        $artist_stmt = $pdo->prepare($artist_sql);
        $artist_stmt->execute([$wildcard_param, $search_query]);
        $matched_artists = $artist_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Query 2: FIXED Smart Event Matching across Titles, Venues, Locations, and Dates
        $event_sql = "
        SELECT
            c.*,
            a.artist_name,
            a.artist_image
        FROM concerts c
        INNER JOIN artists a
            ON c.artist_id = a.artist_id
        WHERE (
             LOWER(a.artist_name) LIKE ?
          OR LOWER(c.title) LIKE ?
          OR LOWER(c.venue) LIKE ?
          OR LOWER(c.location) LIKE ?
          OR SOUNDEX(a.artist_name) = SOUNDEX(?)
          OR SOUNDEX(c.title) = SOUNDEX(?)
          OR SOUNDEX(c.venue) = SOUNDEX(?)
          OR SOUNDEX(c.location) = SOUNDEX(?)
        )
        ORDER BY c.concert_date DESC
        LIMIT 20
        ";
                     
        $event_stmt = $pdo->prepare($event_sql);
        
        $event_stmt->execute([
            $wildcard_param,   // artist_name
            $wildcard_param,   // title
            $wildcard_param,   // venue
            $wildcard_param,   // location
            $search_query,     // soundex artist_name
            $search_query,     // soundex title
            $search_query,     // soundex venue
            $search_query      // soundex location
        ]);

        $matched_events = $event_stmt->fetchAll(PDO::FETCH_ASSOC);

        /* --------------------------------------------
           SAVE SEARCH HISTORY
        ---------------------------------------------*/
        try {
            $user_id = $_SESSION['user_id'] ?? null;
            $result_count = count($matched_artists) + count($matched_events);

            $save = $pdo->prepare("
                INSERT INTO user_searches
                (user_id, search, result)
                VALUES (?, ?, ?)
            ");

            $save->execute([
                $user_id,
                $search_query,
                $result_count
            ]);
        } catch (Exception $e) {
            // Never stop the search page if logging fails
        }
        
    } catch (Exception $e) {
        // Query fallback container protection
    }
} else if ($pdo !== null) {
    // Default system presentation state on page initialization
    try {
        $default_stmt = $pdo->prepare("
        SELECT
            c.*,
            a.artist_name,
            a.artist_image
        FROM concerts c
        INNER JOIN artists a
            ON c.artist_id = a.artist_id
        ORDER BY c.concert_date DESC
        LIMIT 20
        ");
        $default_stmt->execute();
        $matched_events = $default_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// High-frequency hotkeys
$top_searches_list = [
    "BTS", "Taylor Swift", "Coldplay", "MetLife Stadium", "Los Angeles", 
    "New York", "World Tour", "Blackpink", "Ariana Grande", "Drake"
];
?>
<!DOCTYPE html>
<html lang="en">

<?php include "inc/head.php"; ?>
<?php include "inc/navbar.php"; ?> 
 
<body class="bg-white text-gray-900 font-sans antialiased">
    <div id="__next">
        <?php include "inc/header.php"; ?>

        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white py-10 px-4 md:px-8 shadow-md">
            <div class="max-w-7xl mx-auto">
                <span class="text-xs font-black uppercase tracking-widest text-blue-400 bg-blue-950/60 px-3 py-1 rounded-full">Intelligent Search Engine Loop</span>
                <h1 class="text-3xl md:text-5xl font-black tracking-tight mt-3">
                    <?php if (!empty($search_query)): ?>
                        Matches for "<span class="text-blue-400 font-extrabold"><?php echo htmlspecialchars($search_query); ?></span>"
                    <?php else: ?>
                        Explore Live Presentations & Events
                    <?php endif; ?>
                </h1>
                <p class="text-xs md:text-sm text-gray-300 mt-2 max-w-xl leading-relaxed font-medium">
                    Typo correction algorithms and phonetic analyzers are online. Case casing and structural spell mistakes are resolved automatically in real-time.
                </p>
            </div>
        </div>

        <div class="border-b border-gray-200 bg-gray-50/50 shadow-sm overflow-x-auto select-none sticky top-0 z-40 backdrop-blur-md">
            <div class="max-w-7xl mx-auto flex items-center px-4 md:px-8 space-x-2 whitespace-nowrap h-14">
                <span class="text-xs font-black uppercase tracking-wider text-gray-400 shrink-0 mr-2 flex items-center gap-1">
                    <i class="fas fa-fire text-amber-500"></i> Popular Terms:
                </span>
                <?php foreach ($top_searches_list as $trending_node): ?>
                    <a href="search.php?q=<?php echo urlencode($trending_node); ?>" 
                       class="px-4 py-1.5 text-xs font-bold bg-white text-gray-700 hover:text-[#024DDF] hover:bg-blue-50 hover:border-blue-300 border border-gray-200 rounded-full shadow-sm transition-all inline-block">
                        <?php echo htmlspecialchars($trending_node); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <main id="main-content" class="max-w-7xl mx-auto px-4 md:px-8 py-10 space-y-12">

            <?php if (!empty($matched_artists)): ?>
                <div class="space-y-4">
                    <div class="border-b border-gray-200 pb-3">
                        <h3 class="text-lg font-black tracking-tight text-gray-900 uppercase flex items-center gap-2">
                            <i class="fas fa-user-music text-[#024DDF]"></i> Nearest Artist Profiles
                        </h3>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <?php foreach ($matched_artists as $artist): 
                            $artist_pic = !empty($artist['artist_image']) ? "uploads/artists/" . $artist['artist_image'] : "https://picsum.photos/id/64/400/400";
                        ?>
                            <div class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm hover:shadow-md transition-all flex flex-col items-center">
                                <img src="<?php echo htmlspecialchars($artist_pic); ?>" 
                                     onerror="this.src='https://picsum.photos/id/64/400/400';" 
                                     alt="Profile Visual" 
                                     class="w-20 h-20 rounded-full object-cover border border-gray-100 shadow-sm mb-3">
                                <h4 class="text-sm font-black text-gray-900 tracking-tight truncate w-full"><?php echo htmlspecialchars($artist['artist_name']); ?></h4>
                                
                                <a href="events.php?artist_id=<?php echo $artist['artist_id']; ?>" 
                                   class="mt-3 text-[11px] font-bold text-white bg-[#024DDF] hover:bg-blue-800 px-3 py-1.5 rounded-md transition-all uppercase tracking-wider w-full text-center block shadow-sm">
                                     View Artist
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="space-y-4">
                <div class="border-b border-gray-200 pb-3 flex justify-between items-center">
                    <h3 class="text-lg font-black tracking-tight text-gray-900 uppercase flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-[#024DDF]"></i> Matched Production Concert Events (Max 20)
                    </h3>
                    <span class="text-xs font-bold text-gray-400 bg-gray-100 px-2.5 py-1 rounded-md border border-gray-200">
                        <?php echo count($matched_events); ?> Results Displayed
                    </span>
                </div>

                <?php if (!empty($matched_events)): ?>
                    <div class="space-y-4">
                        <?php foreach ($matched_events as $event): 
                            $timestamp = strtotime($event['concert_date']);
                            
                            $month_val = date('M', $timestamp);
                            $day_num_val = date('d', $timestamp);
                            $day_name_val = date('D', $timestamp);
                            
                            $time_val = $event['day_time'] ?? '00:00';
                        ?>
                            <div class="bg-white border border-gray-200 rounded-xl p-4 md:p-6 shadow-sm hover:shadow-md hover:border-gray-300 transition-all flex flex-col md:flex-row md:items-center justify-between gap-6">
                                
                                <div class="flex items-center gap-4 border-b md:border-b-0 border-gray-100 pb-3 md:pb-0 shrink-0 min-w-[130px]">
                                    <div class="text-center bg-gray-50 rounded-lg p-2 min-w-[64px] border border-gray-100 shadow-inner">
                                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-500"><?php echo htmlspecialchars($month_val); ?></span>
                                        <span class="block text-2xl font-black text-gray-900 leading-none my-0.5"><?php echo htmlspecialchars($day_num_val); ?></span>
                                        <span class="block text-xs font-bold text-gray-400 uppercase"><?php echo htmlspecialchars($day_name_val); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 block"><i class="far fa-clock text-blue-600 mr-1"></i> <?php echo htmlspecialchars($time_val); ?></span>
                                        <span class="text-[11px] font-bold text-green-600 bg-green-50 px-1.5 py-0.5 rounded uppercase mt-1 inline-block">Verified Seat</span>
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-tight flex items-center gap-1.5 truncate">
                                        <i class="fas fa-map-marker-alt text-[#024DDF]"></i> 
                                        <?php echo htmlspecialchars($event['location']); ?> — <?php echo htmlspecialchars($event['venue']); ?>
                                    </p>
                                    <h4 class="text-base md:text-lg font-extrabold text-gray-900 tracking-tight mt-1 truncate" title="<?php echo htmlspecialchars($event['title']); ?>">
                                        <span class="text-[#024DDF] font-black"><?php echo htmlspecialchars($event['artist_name']); ?></span>: <?php echo htmlspecialchars($event['title']); ?>
                                    </h4>
                                </div>

                                <div class="shrink-0 text-right">
                                    <a href="bookings.php?concert_id=<?php echo $event['concert_id']; ?>"
                                       class="block text-center w-full md:w-auto bg-[#024DDF] hover:bg-blue-800 text-white font-bold text-xs uppercase tracking-wider py-3 px-6 rounded-lg transition-colors shadow focus:outline-none">
                                        Find Tickets
                                    </a>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-12 text-center bg-gray-50/50">
                        <i class="fas fa-search-minus text-4xl text-gray-300 mb-3 block"></i>
                        <h3 class="text-lg font-black text-gray-900 tracking-tight">No Direct Ledger Coordinates Found</h3>
                        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto leading-relaxed">
                            We couldn't locate data items matching your explicit parameter entries right now. Try checking alternative spellings, city codes, or arena names.
                        </p>
                        <a href="search.php" class="mt-4 inline-block text-xs font-bold text-white bg-[#024DDF] hover:bg-blue-800 px-4 py-2 rounded-lg transition-all uppercase tracking-wider">
                            Clear Search Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </main>

        <?php include "inc/footer.php"; ?>
    </div>
    
    <style>
        html { scroll-behavior: smooth; }
        body { overflow-x: hidden; }
        .overflow-x-auto::-webkit-scrollbar { display: none; }
    </style>
</body>
</html>
