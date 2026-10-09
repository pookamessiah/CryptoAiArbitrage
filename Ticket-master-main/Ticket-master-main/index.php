<?php 
// inc/header.php - Global Navigation Component with Session-Safe Initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if a user is actively authenticated to toggle view state layouts
$is_logged_in = isset($_SESSION['user_id']);
?>


<!DOCTYPE html>
<html lang="en">

<?php include "inc/head.php"; ?>
<?php include "inc/navbar.php"; ?> 
 
<body>
        
<?php include "inc/header.php"; ?>
<?php

// Enable error displaying so we can pinpoint issues if database structural details are missing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';

$pdo = null;

try {
    $db = new Database();
    $pdo = $db->connect();
} catch (Exception $e) {
    die($e->getMessage());
}

if (!$pdo) {
    die("Database connection failed.");
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function image_url($filename, $fallback = 'assets/images/bts.jpg')
{
    $filename = trim((string)$filename);

    if ($filename === '') {
        return $fallback;
    }

    // Already a full URL
    if (preg_match('/^https?:\/\//i', $filename)) {
        return $filename;
    }

    // If the database already contains uploads/artists/filename.png
    if (str_starts_with($filename, 'uploads/')) {
        return $filename;
    }

    // Otherwise prepend the upload folder
    return 'uploads/artists/' . ltrim($filename, '/');
}

function fetch_all_rows(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_one_row(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    function date_badge($date)
    {
        $time = strtotime($date);
    
        if (!$time) {
            return '';
        }
    
        return '<span>' .
            e(strtoupper(date('M', $time))) .
            '</span><strong>' .
            e(date('d', $time)) .
            '</strong>';
    }

$eventFields = "
    c.concert_id,
    c.artist_id,
    c.concert_date,
    c.day_time,
    c.venue,
    c.location,
    c.title,
    a.artist_name,
    a.artist_image,
    a.genre
";

// Keep track of how many times each artist has been displayed across the whole page
$artist_global_count = [];

// Helper function to process sections safely according to business rules
function process_section_events(array $rawEvents, array &$globalCounts, int $limit) {
    $processed = [];
    $sectionArtists = [];

    foreach ($rawEvents as $event) {
        $artistId = $event['artist_id'];

        // 1. Only once per specific section
        if (in_array($artistId, $sectionArtists)) {
            continue;
        }

        // 2. Only twice at most across all page sections
        $currentGlobalCount = $globalCounts[$artistId] ?? 0;
        if ($currentGlobalCount >= 2) {
            continue;
        }

        // Add to processed section array
        $processed[] = $event;
        $sectionArtists[] = $artistId;
        
        // Track globally
        $globalCounts[$artistId] = $currentGlobalCount + 1;

        if (count($processed) >= $limit) {
            break;
        }
    }
    return $processed;
}

// 1. Fetch random hero event
$heroEvent = fetch_one_row(
    $pdo,
    "SELECT $eventFields
     FROM concerts c
     INNER JOIN artists a ON a.artist_id = c.artist_id
     WHERE c.index_type = 'upcoming'
     ORDER BY RAND()
     LIMIT 1"
);

if ($heroEvent) {
    $artist_global_count[$heroEvent['artist_id']] = 1;
}

// 2. Upcoming Section (Fetch larger raw pool randomly, then slice according to strict artist limits)
$rawUpcomingSql = "
SELECT $eventFields
FROM concerts c
INNER JOIN artists a ON a.artist_id = c.artist_id
WHERE c.index_type = 'upcoming'
";
$params = [];
if ($heroEvent) {
    $rawUpcomingSql .= " AND c.concert_id <> ?";
    $params[] = $heroEvent['concert_id'];
}
$rawUpcomingSql .= " ORDER BY RAND()";
$rawUpcoming = fetch_all_rows($pdo, $rawUpcomingSql, $params);
$upcomingEvents = process_section_events($rawUpcoming, $artist_global_count, 4);

// 3. Trending Section
$rawTrending = fetch_all_rows(
    $pdo,
    "SELECT $eventFields
     FROM concerts c
     INNER JOIN artists a ON a.artist_id = c.artist_id
     WHERE c.index_type = 'trending'
     ORDER BY RAND()"
);
$trendingEvents = process_section_events($rawTrending, $artist_global_count, 10);

// 4. Sponsored Section
$rawSponsored = fetch_all_rows(
    $pdo,
    "SELECT $eventFields
     FROM concerts c
     INNER JOIN artists a ON a.artist_id = c.artist_id
     WHERE c.index_type = 'sponsored'
     ORDER BY RAND()"
);
$sponsoredEvents = process_section_events($rawSponsored, $artist_global_count, 10);

// 5. Popular Near You Section (Fetch distinctive components organically mapped via raw query)
$rawPopular = fetch_all_rows(
    $pdo,
    "SELECT 
        c.artist_id,
        c.concert_date,
        a.artist_name,
        a.artist_image,
        a.genre
     FROM concerts c
     INNER JOIN artists a ON a.artist_id = c.artist_id
     WHERE c.index_type = 'upcoming'
     ORDER BY RAND()"
);
$popularNearYou = process_section_events($rawPopular, $artist_global_count, 10);

$recentSearches = [];
if (isset($_SESSION['user_id'])) {
    $recentSearches = fetch_all_rows(
        $pdo,
        "SELECT search, result, searched_at
         FROM user_searches
         WHERE user_id=?
         ORDER BY searched_at DESC
         LIMIT 5",
        [$_SESSION['user_id']]
    );
} else {
    $recentSearches = fetch_all_rows(
        $pdo,
        "SELECT search, result, searched_at
         FROM user_searches
         WHERE user_id=0
         ORDER BY searched_at DESC
         LIMIT 5"
    );
}

$redirectEvent = [
    'image' => 'assets/images/summer-lawn.jpeg',
    'eyebrow' => '4 LAWN TICKETS FOR $99',
    'title' => 'Make it a Summer of Live Music',
    'url' => '#'
];
?>

<style>
* {
    box-sizing: border-box;
}

:root {
    --tm-blue: #024ddf;
    --tm-blue-dark: #0138a7;
    --tm-red: #d71920;
    --tm-ink: #121212;
    --tm-muted: #5f6670;
    --tm-line: #dfe4ea;
    --tm-surface: #ffffff;
    --tm-page: #f6f8fb;
}

body {
    margin: 0;
    background: var(--tm-page);
    color: var(--tm-ink);
    font-family: Arial, Helvetica, sans-serif;
}

.tm-container {
    width: min(1180px, calc(100% - 32px));
    margin: 0 auto;
}

.hero {
    position: relative;
    min-height: 430px;
    display: flex;
    align-items: flex-end;
    overflow: hidden;
    background: #101820;
}

.hero img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center top;
}

.hero::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(0,0,0,.78), rgba(0,0,0,.34) 48%, rgba(0,0,0,.08)),
                linear-gradient(0deg, rgba(0,0,0,.78), transparent 55%);
}

.hero-content {
    position: relative;
    z-index: 1;
    width: min(1180px, calc(100% - 32px));
    margin: 0 auto;
    padding: 0 0 54px;
    color: #fff;
}

.hero-kicker,
.eyebrow {
    margin: 0 0 8px;
    color: var(--tm-blue);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.hero-content h1 {
    max-width: 720px;
    margin: 0 0 10px;
    font-size: clamp(34px, 6vw, 68px);
    line-height: 1;
    letter-spacing: 0;
}

.hero-content p {
    margin: 0 0 22px;
    max-width: 620px;
    font-size: 18px;
    line-height: 1.45;
    color: rgba(255,255,255,.9);
}

.btn-find,
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 20px;
    border-radius: 4px;
    border: 1px solid transparent;
    font-size: 15px;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
}

.btn-find,
.btn-primary {
    background: var(--tm-blue);
    color: #fff;
}

.btn-find:hover,
.btn-primary:hover {
    background: var(--tm-blue-dark);
}

.btn-outline {
    background: #fff;
    border-color: var(--tm-blue);
    color: var(--tm-blue);
}

.section-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin: 42px 0 18px;
}

.section-title {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--tm-ink);
    font-size: 24px;
    line-height: 1.2;
    font-weight: 800;
}

.icon-red {
    color: var(--tm-red);
}

.grid-4 {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

.event-card,
.search-card {
    display: block;
    min-width: 0;
    overflow: hidden;
    background: var(--tm-surface);
    border: 1px solid var(--tm-line);
    border-radius: 6px;
    color: inherit;
    text-decoration: none;
    transition: box-shadow .18s ease, transform .18s ease, border-color .18s ease;
}

.event-card:hover,
.search-card:hover {
    transform: translateY(-2px);
    border-color: #b9c6d6;
    box-shadow: 0 10px 26px rgba(10,22,41,.12);
}

.event-media {
    position: relative;
    aspect-ratio: 16 / 10;
    background: #dfe4ea;
}

.event-media img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}

.event-info {
    padding: 14px 14px 16px;
}

.event-info h3 {
    margin: 0 0 8px;
    font-size: 16px;
    line-height: 1.25;
    font-weight: 800;
}

.event-info p {
    margin: 0 0 6px;
    color: var(--tm-muted);
    font-size: 14px;
    line-height: 1.35;
}

.date-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    min-width: 48px;
    padding: 6px 7px;
    background: #fff;
    border-radius: 4px;
    color: var(--tm-ink);
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,.16);
}

.date-badge span,
.date-badge strong {
    display: block;
    line-height: 1;
}

.date-badge span {
    color: var(--tm-blue);
    font-size: 11px;
    font-weight: 800;
}

.date-badge strong {
    margin-top: 3px;
    font-size: 18px;
}

.promo-card {
    grid-column: span 2;
}

.promo-card .event-media {
    aspect-ratio: 16 / 7;
}

.carousel-wrapper {
    position: relative;
}

.carousel {
    display: flex;
    gap: 18px;
    overflow-x: auto;
    padding: 2px 2px 18px;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.carousel::-webkit-scrollbar {
    display: none;
}

.carousel .event-card,
.carousel .search-card {
    width: 270px;
    flex: 0 0 270px;
}

.carousel-btn {
    position: absolute;
    top: 42%;
    z-index: 2;
    width: 42px;
    height: 42px;
    border: 1px solid var(--tm-line);
    border-radius: 50%;
    background: #fff;
    color: var(--tm-ink);
    font-size: 22px;
    box-shadow: 0 6px 18px rgba(10,22,41,.16);
    cursor: pointer;
}

.carousel-btn.left {
    left: -21px;
}

.carousel-btn.right {
    right: -21px;
}

.near-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pill {
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    padding: 0 14px;
    border: 1px solid var(--tm-blue);
    border-radius: 999px;
    background: #fff;
    color: var(--tm-blue);
    font-size: 14px;
    font-weight: 800;
}

.search-card {
    padding: 16px;
}

.search-card h3 {
    margin: 0 0 8px;
    font-size: 16px;
}

.search-card p {
    margin: 0;
    color: var(--tm-muted);
    font-size: 14px;
}

.empty-section {
    padding: 24px;
    border: 1px dashed var(--tm-line);
    border-radius: 6px;
    background: #fff;
    color: var(--tm-muted);
}

@media (max-width: 980px) {
    .grid-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .promo-card {
        grid-column: span 2;
    }
}

@media (max-width: 680px) {
    .tm-container {
        width: min(100% - 24px, 1180px);
    }

    .hero {
        min-height: 390px;
    }

    .hero-content {
        width: min(100% - 24px, 1180px);
        padding-bottom: 36px;
    }

    .hero-content p {
        font-size: 15px;
    }

    .section-bar {
        align-items: flex-start;
        flex-direction: column;
    }

    .grid-4 {
        grid-template-columns: 1fr;
    }

    .promo-card {
        grid-column: auto;
    }

    .carousel-btn {
        display: none;
    }

    .carousel .event-card,
    .carousel .search-card {
        width: 240px;
        flex-basis: 240px;
    }
}
</style>

<?php if ($heroEvent): ?>
    <section class="hero">
        <img src="<?= e(image_url($heroEvent['artist_image'])) ?>" alt="<?= e($heroEvent['artist_name']) ?>">
        <div class="hero-content">
            <p class="hero-kicker"><?= e($heroEvent['day_time']) ?> &bull; <?= e($heroEvent['venue']) ?></p>
            <h1><?= e($heroEvent['title']) ?></h1>
            <p><?= e($heroEvent['location']) ?></p>
            <a href="events.php?artist_id=<?= (int) $heroEvent['artist_id'] ?>" class="btn-find">Find Tickets</a>
        </div>
    </section>
<?php endif; ?>

<main class="tm-container">
    <div class="section-bar">
        <h2 class="section-title">Featured Events</h2>
    </div>

    <section class="grid-4">
        <a href="<?= e($redirectEvent['url']) ?>" class="event-card promo-card">
            <div class="event-media">
                <img src="<?= e($redirectEvent['image']) ?>" alt="<?= e($redirectEvent['title']) ?>">
            </div>
            <div class="event-info">
                <p class="eyebrow"><?= e($redirectEvent['eyebrow']) ?></p>
                <h3><?= e($redirectEvent['title']) ?></h3>
            </div>
        </a>

        <?php foreach ($upcomingEvents as $event): ?>
            <a href="events.php?artist_id=<?= (int) $event['artist_id'] ?>" class="event-card">
                <div class="event-media">
                    <img src="<?= e(image_url($event['artist_image'])) ?>" alt="<?= e($event['artist_name']) ?>">
                    <div class="date-badge"><?= date_badge($event['concert_date']) ?></div>
                </div>
                <div class="event-info">
                    <p><?= e($event['venue']) ?> &bull; <?= e($event['location']) ?></p>
                    <h3><?= e($event['title']) ?></h3>
                </div>
            </a>
        <?php endforeach; ?>
    </section>

    <div class="section-bar">
        <h2 class="section-title"><i class="fa-solid fa-fire icon-red"></i> Trending Searches</h2>
    </div>

    <?php if ($trendingEvents): ?>
        <section class="carousel-wrapper">
            <button class="carousel-btn left" type="button" onclick="scrollCarousel(this.parentElement.querySelector('.carousel'), -288)" aria-label="Previous trending events">&#8249;</button>
            <div class="carousel">
                <?php foreach ($trendingEvents as $event): ?>
                    <a href="events.php?artist_id=<?= (int) $event['artist_id'] ?>" class="event-card">
                        <div class="event-media">
                            <img src="<?= e(image_url($event['artist_image'])) ?>" alt="<?= e($event['artist_name']) ?>">
                            <div class="date-badge"><?= date_badge($event['concert_date']) ?></div>
                        </div>
                        <div class="event-info">
                            <h3><?= e($event['artist_name']) ?></h3>
                            <p><?= e($event['title']) ?> &bull; <?= e($event['venue']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <button class="carousel-btn right" type="button" onclick="scrollCarousel(this.parentElement.querySelector('.carousel'), 288)" aria-label="Next trending events">&#8250;</button>
        </section>
    <?php else: ?>
        <div class="empty-section">No trending events are available right now.</div>
    <?php endif; ?>

    <?php if ($recentSearches): ?>
        <div class="section-bar">
            <div class="section-title">Recently Viewed</div>
        </div>

        <section class="carousel-wrapper">
            <button class="carousel-btn left" type="button" onclick="scrollCarousel(this.parentElement.querySelector('.carousel'), -288)" aria-label="Previous recent searches">&#8249;</button>
            <div class="carousel">
                <?php foreach ($recentSearches as $search): ?>
                    <a href="search.php?q=<?= urlencode($search['search']) ?>" class="search-card">
                        <h3><?= e($search['search']) ?></h3>
                        <p><?= (int) $search['result'] ?> results &bull; <?= e(date('M d, Y', strtotime($search['searched_at']))) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
            <button class="carousel-btn right" type="button" onclick="scrollCarousel(this.parentElement.querySelector('.carousel'), 288)" aria-label="Next recent searches">&#8250;</button>
        </section>
    <?php endif; ?>

    <div class="section-bar">
        <h2 class="section-title"><i class="fa-solid fa-star icon-red"></i> Sponsored Presales &amp; Offers</h2>
    </div>

    <?php if ($sponsoredEvents): ?>
        <section class="carousel-wrapper">
            <button class="carousel-btn left" type="button" onclick="scrollCarousel(this.parentElement.querySelector('.carousel'), -288)" aria-label="Previous sponsored events">&#8249;</button>
            <div class="carousel">
                <?php foreach ($sponsoredEvents as $event): ?>
                    <a href="events.php?artist_id=<?= (int) $event['artist_id'] ?>" class="event-card">
                        <div class="event-media">
                            <img src="<?= e(image_url($event['artist_image'])) ?>" alt="<?= e($event['artist_name']) ?>">
                            <div class="date-badge"><?= date_badge($event['concert_date']) ?></div>
                        </div>
                        <div class="event-info">
                            <p><?= e($event['day_time']) ?></p>
                            <h3><?= e($event['title']) ?></h3>
                            <p><?= e($event['venue']) ?> &bull; <?= e($event['location']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <button class="carousel-btn right" type="button" onclick="scrollCarousel(this.parentElement.querySelector('.carousel'), 288)" aria-label="Next sponsored events">&#8250;</button>
        </section>
    <?php else: ?>
        <div class="empty-section">No sponsored offers are available right now.</div>
    <?php endif; ?>

    <div class="section-bar">
        <div class="near-header">
            <h2 class="section-title"><i class="fa-solid fa-location-dot icon-red"></i> Popular Near You</h2>
            <span class="pill">Concerts</span>
        </div>
        <a href="events.php" class="btn btn-outline">See All Events</a>
    </div>

    <?php if ($popularNearYou): ?>
        <section class="carousel-wrapper">
            <button class="carousel-btn left" type="button" onclick="scrollCarousel(this.parentElement.querySelector('.carousel'), -288)" aria-label="Previous popular events">&#8249;</button>
            <div class="carousel">
                <?php foreach ($popularNearYou as $event): ?>
                    <a href="events.php?artist_id=<?= (int) $event['artist_id'] ?>" class="event-card">
                        <div class="event-media">
                            <img src="<?= e(image_url($event['artist_image'])) ?>" alt="<?= e($event['artist_name']) ?>">
                            <div class="date-badge"><?= date_badge($event['concert_date']) ?></div>
                        </div>
                        <div class="event-info">
                            <p><?= e($event['genre']) ?></p>
                            <h3><?= e($event['artist_name']) ?></h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <button class="carousel-btn right" type="button" onclick="scrollCarousel(this.parentElement.querySelector('.carousel'), 288)" aria-label="Next popular events">&#8250;</button>
        </section>
    <?php else: ?>
        <div class="empty-section">No nearby popular events are available right now.</div>
    <?php endif; ?>
</main>

<script>
function scrollCarousel(carousel, offset) {
    if (!carousel) return;
    carousel.scrollBy({ left: offset, behavior: 'smooth' });
}
</script>
      
<?php include "inc/footer.php"; ?>

</div>

<div id="modals" data-testid="modals">
</div>
</body>
</html>
