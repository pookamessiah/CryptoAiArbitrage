<?php
// inc/header.php - Global Navigation Component with Session-Safe Initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if a user is actively authenticated to toggle view state layouts
$is_logged_in = isset($_SESSION['user_id']);
?>
<nav class="bg-[#024DDF] text-white border-b border-blue-800 relative z-40 w-full">
  <div class="max-w-7xl mx-auto px-6">
    <div class="flex items-center justify-between h-14 lg:h-[88px]">

      <div class="flex items-center gap-4 lg:gap-5">
        <button id="mobileMenuTrigger" class="block lg:hidden hover:bg-[#013ba8] p-1 rounded-md transition-colors focus:outline-none" aria-label="Toggle Navigation Menu">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
          </svg>
        </button>

        <a href="/index.php" class="flex items-center">
          <img src="/assets/images/logo.png" alt="Ticketmaster" class="h-6 lg:h-[26px] w-auto" onerror="this.src='https://uk.tmconst.com/rc-b3c71b5a/images/logo/ticketmaster_black.svg';">
        </a>

        <ul class="hidden lg:flex items-center gap-6 text-base font-bold">
          <li><a href="/search.php?q=Concerts" class="nav-link">Concerts</a></li>
          <li><a href="/search.php?q=Sports" class="nav-link">Sports</a></li>
          <li><a href="/search.php?q=Art" class="nav-link">Arts, Theater &amp; Comedy</a></li>
          <li><a href="/search.php?q=Family" class="nav-link">Family</a></li>
          <li><a href="/search.php?q=Cities" class="nav-link">Cities</a></li>
        </ul>
      </div>

      <div class="flex items-center gap-4">
        <?php if ($is_logged_in): ?>
          <a href="/auth/dashboard.php" class="flex items-center gap-2 lg:gap-3 text-white hover:text-gray-200 transition-colors duration-200" title="Access Dashboard Portfolio">
            <i class="fa-regular fa-user text-xl"></i>
            <span class="hidden md:inline font-bold text-sm lg:text-base">Dashboard</span>
          </a>
          <a href="/logout.php" class="hidden md:inline-block border border-blue-400 hover:bg-blue-800 text-white font-bold text-xs uppercase tracking-wider px-3 py-1.5 rounded transition">
            Sign Out
          </a>
        <?php else: ?>
          <a href="/oauth.php" class="flex items-center gap-2 lg:gap-3 text-white hover:text-gray-200 transition-colors duration-200">
            <i class="fa-regular fa-user text-xl"></i>
            <span class="hidden md:inline font-bold text-sm lg:text-base">Sign In / Register</span>
          </a>
        <?php endif; ?>
      </div>

    </div>
  </div>
</nav>

<div id="mobileNavigationDrawer" class="fixed inset-0 z-50 invisible transition-all duration-300">
  <div id="drawerBackdrop" class="absolute inset-0 bg-black/60 opacity-0 transition-opacity duration-300"></div>
  <div id="drawerContent" class="absolute inset-y-0 left-0 w-72 max-w-xs bg-slate-900 text-slate-100 shadow-2xl transform -translate-x-full transition-transform duration-300 flex flex-col border-r border-slate-800">
    <div class="bg-slate-950 text-white px-5 py-4 flex items-center justify-between border-b border-slate-800">
      <span class="font-black text-xs uppercase tracking-widest text-blue-400">Navigation Menu</span>
      <button id="mobileMenuClose" class="text-slate-400 hover:text-white text-xl p-1 focus:outline-none">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <ul class="flex-1 overflow-y-auto font-bold text-sm divide-y divide-slate-800/60 uppercase tracking-tight">
      <li><a href="/search.php?q=Concerts" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-800/50 text-slate-200 transition-colors"><i class="fas fa-music text-base w-5 text-center text-slate-400"></i> Concerts</a></li>
      <li><a href="/search.php?q=Sports" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-800/50 text-slate-200 transition-colors"><i class="fas fa-football-ball w-5 text-center text-slate-400"></i> Sports</a></li>
      <li><a href="/search.php?q=Art" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-800/50 text-slate-200 transition-colors"><i class="fas fa-theater-masks w-5 text-center text-slate-400"></i> Arts &amp; Theater</a></li>
      <li><a href="/search.php?q=Family" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-800/50 text-slate-200 transition-colors"><i class="fas fa-child w-5 text-center text-slate-400"></i> Family</a></li>
      <li><a href="/search.php?q=Cities" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-800/50 text-slate-200 transition-colors"><i class="fas fa-city w-5 text-center text-slate-400"></i> Cities</a></li>
       
      <?php if ($is_logged_in): ?>
        <li class="border-t border-slate-700/50"><a href="/auth/dashboard.php" class="flex items-center gap-3 px-5 py-4 bg-slate-950 text-blue-400 hover:bg-slate-800 transition-colors"><i class="fas fa-columns text-base w-5 text-center"></i> User Dashboard</a></li>
        <li><a href="/logout.php" class="flex items-center gap-3 px-5 py-4 bg-red-950/40 text-red-400 hover:bg-red-900/30 transition-colors"><i class="fas fa-sign-out-alt text-base w-5 text-center"></i> Sign Out</a></li>
      <?php else: ?>
        <li class="border-t border-slate-700/50"><a href="/oauth.php" class="flex items-center gap-3 px-5 py-4 bg-slate-950 text-emerald-400 hover:bg-slate-800 transition-colors"><i class="fas fa-sign-in-alt text-base w-5 text-center"></i> Sign In / Register</a></li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<div class="bg-[#024DDF] py-6 md:py-11 w-full">
  <div class="max-w-5xl mx-auto px-4 md:px-6">
    <div class="bg-white text-gray-900 rounded shadow-md p-1.5 max-w-full mx-auto relative">
      <form action="/search.php" method="GET" class="flex flex-col md:flex-row items-stretch md:items-center">
        <div class="flex flex-col md:flex-row w-full flex-1 divide-y md:divide-y-0 md:divide-x divide-gray-200">
          <div class="flex items-center gap-3.5 px-4 py-2 flex-1">
            <i class="fa-solid fa-location-dot text-[#024DDF] text-lg"></i>
            <div class="w-full">
              <label class="text-[11px] uppercase font-bold tracking-wider text-black block leading-none mb-0.5">Location</label>
              <input type="text" name="location" value="<?php echo isset($_GET['location']) ? htmlspecialchars($_GET['location']) : ''; ?>" placeholder="City or Zip Code" class="bg-transparent outline-none w-full text-sm font-medium text-gray-600 placeholder-gray-400 py-0">
            </div>
          </div>
          <div class="flex items-center gap-3.5 px-4 py-2 flex-1 cursor-pointer hover:bg-gray-50/50 transition-colors">
            <i class="fa-regular fa-calendar-days text-[#024DDF] text-lg"></i>
            <div class="w-full">
              <label class="text-[11px] uppercase font-bold tracking-wider text-black block leading-none mb-0.5">Dates</label>
              <span class="text-sm block text-gray-500 font-medium leading-none mt-0.5">All Dates</span>
            </div>
            <i class="fas fa-chevron-down text-gray-400 text-xs ml-auto"></i>
          </div>
        </div>
        <div class="flex items-center gap-3.5 px-4 py-2.5 flex-1 md:flex-[1.8] border-t md:border-t-0 border-gray-200">
          <i class="fa-solid fa-magnifying-glass text-[#024DDF] text-lg"></i>
          <div class="w-full">
            <label class="text-[11px] uppercase font-bold tracking-wider text-black block leading-none mb-0.5">Search</label>
            <input type="text" name="q" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" placeholder="Artist, Event or Venue" class="bg-transparent outline-none w-full text-sm font-medium text-gray-600 placeholder-gray-400 py-0" required>
          </div>
        </div>
        <div class="p-1 md:p-0 shrink-0">
          <button type="submit" class="w-full md:w-auto bg-[#024DDF] hover:bg-[#013ba8] text-white px-8 py-3 rounded font-bold text-sm flex items-center justify-center gap-2 transition-colors">Search</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Setup Mobile Drawer event visibility toggle bindings safely
  (function() {
    const menuTrigger = document.getElementById('mobileMenuTrigger');
    const menuCloseBtn = document.getElementById('mobileMenuClose');
    const drawerBackdrop = document.getElementById('drawerBackdrop');
    const navDrawer = document.getElementById('mobileNavigationDrawer');
    const drawerContent = document.getElementById('drawerContent');

    if(menuTrigger && menuCloseBtn && drawerBackdrop) {
        menuTrigger.addEventListener('click', () => {
            navDrawer.classList.remove('invisible');
            setTimeout(() => {
                drawerBackdrop.classList.add('opacity-100');
                drawerContent.classList.remove('-translate-x-full');
            }, 10);
        });

        const closeMenu = () => {
            drawerBackdrop.classList.remove('opacity-100');
            drawerContent.classList.add('-translate-x-full');
            setTimeout(() => { navDrawer.classList.add('invisible'); }, 300);
        };

        menuCloseBtn.addEventListener('click', closeMenu);
        drawerBackdrop.addEventListener('click', closeMenu);
    }
  })();
</script>
