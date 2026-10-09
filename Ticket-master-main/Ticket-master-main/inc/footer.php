<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Footer Example</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <style>
    footer {
      background-color: #1a1a1a;
      color: #d1d5db;
    }
    .footer-link {
      color: #d1d5db;
      transition: color 0.2s;
    }
    .footer-link:hover {
      color: #fff;
    }
  </style>
</head>
<body>

<footer class="text-sm">
  <div class="max-w-7xl mx-auto px-6 py-12">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-10">
      
      <!-- Left Column: Logo + Social + Apps -->
      <div class="md:col-span-5 space-y-8">
        
        <!-- Logo -->
        <div class="flex items-center gap-3">
          <a href="#" class="block">
            <img src="assets/images/logo.png" 
                 alt="Ticketmaster" 
                 class="h-10 w-auto">
          </a>
        </div>

        <!-- Let's Connect -->
        <div>
          <h2 class="text-white text-lg font-semibold mb-4">Let's Connect</h2>
          <div class="flex gap-5">
            <a href="#" class="hover:scale-110 transition-transform"><i class="fab fa-facebook-f text-2xl"></i></a>
            <a href="#" class="hover:scale-110 transition-transform"><i class="fab fa-x-twitter text-2xl"></i></a>
            <a href="#" class="hover:scale-110 transition-transform"><i class="fab fa-instagram text-2xl"></i></a>
            <a href="#" class="hover:scale-110 transition-transform"><i class="fab fa-youtube text-2xl"></i></a>
          </div>
        </div>

        <!-- Download Apps -->
        <div>
          <h2 class="text-white text-lg font-semibold mb-4">Download Our Apps</h2>
          <div class="flex gap-4">
            <a href="#" class="block">
              <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store" class="h-10">
            </a>
            <a href="#" class="block">
              <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play" class="h-10">
            </a>
          </div>
        </div>

        <p class="text-xs text-gray-400">
          By continuing past this page, you agree to our 
          <a href="#" class="underline hover:text-white">Terms of Use</a>
        </p>
      </div>

      <!-- Right Columns: Links -->
      <div class="md:col-span-7 grid grid-cols-2 md:grid-cols-4 gap-8">
        
        <!-- Helpful Links -->
        <div>
          <h2 class="text-white font-semibold mb-4">Helpful Links</h2>
          <ul class="space-y-2 text-sm">
            <li><a href="#" class="footer-link">Help / FAQ</a></li>
            <li><a href="#" class="footer-link">Sell Tickets</a></li>
            <li><a href="#" class="footer-link">My Account</a></li>
            <li><a href="#" class="footer-link">Contact Us</a></li>
            <li><a href="#" class="footer-link">Gift Cards</a></li>
          </ul>
        </div>

        <!-- Our Network -->
        <div>
          <h2 class="text-white font-semibold mb-4">Our Network</h2>
          <ul class="space-y-2 text-sm">
            <li><a href="#" class="footer-link">Live Nation</a></li>
            <li><a href="#" class="footer-link">House of Blues</a></li>
            <li><a href="#" class="footer-link">Front Gate Tickets</a></li>
            <li><a href="#" class="footer-link">TicketWeb</a></li>
            <li><a href="#" class="footer-link">Universe</a></li>
          </ul>
        </div>

        <!-- About Us -->
        <div>
          <h2 class="text-white font-semibold mb-4">About Us</h2>
          <ul class="space-y-2 text-sm">
            <li><a href="#" class="footer-link">Blog</a></li>
            <li><a href="#" class="footer-link">Careers</a></li>
            <li><a href="#" class="footer-link">Ticketing Truths</a></li>
            <li><a href="#" class="footer-link">Ticket Your Event</a></li>
          </ul>
        </div>

        <!-- Friends & Partners -->
        <div>
          <h2 class="text-white font-semibold mb-4">Friends & Partners</h2>
          <ul class="space-y-2 text-sm">
            <li><a href="#" class="footer-link">PayPal</a></li>
            <li><a href="#" class="footer-link">Allianz</a></li>
            <li><a href="#" class="footer-link">AWS</a></li>
            <li><a href="#" class="footer-link">Affiliates</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Bottom Bar -->
  <div class="border-t border-gray-800 bg-black py-6">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex flex-col md:flex-row justify-between items-center gap-6 text-xs">
        <div class="flex flex-wrap gap-x-6 gap-y-2 justify-center md:justify-start">
          <a href="#" class="footer-link">Our Policies</a>
          <a href="#" class="footer-link">Privacy Policy</a>
          <a href="#" class="footer-link">Cookie Policy</a>
          <button onclick="alert('Cookie preferences would open here in a real site')" 
                  class="footer-link hover:text-white underline">
            Manage Cookies & Ad Choices
          </button>
        </div>
        
        <p class="text-gray-500 text-center md:text-right">
          © 1999-2026 YourCompany. All rights reserved.
        </p>
      </div>
    </div>
  </div>
</footer>

<?php
// Assuming these come from the admin table
$whatsapp = $admin['whatsapp'];
$telegram = $admin['telegram'];
$email = $admin['email'];
?>

<div class="contact-float-wrapper">
    <div class="contact-options">
        <?php if(!empty($email)): ?>
            <a href="mailto:<?= $email ?>" class="contact-btn email" title="Email">
                <i class="fas fa-envelope"></i>
            </a>
        <?php endif; ?>

        <?php if(!empty($telegram)): ?>
            <a href="https://t.me/<?= ltrim($telegram, '@') ?>" target="_blank" class="contact-btn telegram" title="Telegram">
                <i class="fab fa-telegram-plane"></i>
            </a>
        <?php endif; ?>

        <?php if(!empty($whatsapp)): ?>
            <a href="https://wa.me/<?= preg_replace('/\D/', '', $whatsapp) ?>" target="_blank" class="contact-btn whatsapp" title="WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
        <?php endif; ?>
    </div>

    <button class="contact-main" id="contactToggle">
        <i class="fas fa-headset"></i>
    </button>
</div>
</body>

</html>

<script>
const toggle = document.getElementById('contactToggle');
const options = document.querySelector('.contact-options');

toggle.addEventListener('click', function () {
    options.classList.toggle('show');

    this.innerHTML = options.classList.contains('show')
        ? '<i class="fas fa-times"></i>'
        : '<i class="fas fa-headset"></i>';
});

// Close when clicking outside
document.addEventListener('click', function(e){
    if(!e.target.closest('.contact-float-wrapper')){
        options.classList.remove('show');
        toggle.innerHTML = '<i class="fas fa-headset"></i>';
    }
});
</script>
