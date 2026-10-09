<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Top Navigation</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  
  <style>
    .nav-link {
      transition: color 0.2s ease;
    }
    .nav-link:hover {
      color: #fff;
    }

    .contact-float-wrapper{
        position: fixed;
        right: 25px;
        bottom: 50%;
        transform: translateY(50%);
        z-index: 9999;
    }
    
    .contact-main{
        width:65px;
        height:65px;
        border-radius:50%;
        border:none;
        background:#28a745;
        color:#fff;
        font-size:28px;
        cursor:pointer;
        box-shadow:0 10px 30px rgba(0,0,0,.25);
        animation:pulse 2s infinite;
        transition:.3s;
    }
    
    .contact-main:hover{
        transform:scale(1.08);
    }
    
    .contact-options{
        position:absolute;
        bottom:80px;
        right:8px;
        display:flex;
        flex-direction:column;
        gap:12px;
        opacity:0;
        pointer-events:none;
        transform:translateY(20px);
        transition:.35s ease;
    }
    
    .contact-options.show{
        opacity:1;
        pointer-events:auto;
        transform:translateY(0);
    }
    
    .contact-btn{
        width:55px;
        height:55px;
        border-radius:50%;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        font-size:24px;
        text-decoration:none;
        box-shadow:0 8px 20px rgba(0,0,0,.2);
        animation:pop .4s ease;
    }
    
    .whatsapp{
        background:#25D366;
    }
    
    .telegram{
        background:#229ED9;
    }
    
    .email{
        background:#EA4335;
    }
    
    .contact-btn:hover{
        transform:scale(1.1);
    }
    
    @keyframes pulse{
        0%{
            box-shadow:0 0 0 0 rgba(40,167,69,.6);
        }
        70%{
            box-shadow:0 0 0 18px rgba(40,167,69,0);
        }
        100%{
            box-shadow:0 0 0 0 rgba(40,167,69,0);
        }
    }
    
    @keyframes pop{
        from{
            transform:scale(.5);
            opacity:0;
        }
        to{
            transform:scale(1);
            opacity:1;
        }
    }

    .contact-main i{
        pointer-events: none;
    }

    
  </style>
</head>
<body class="bg-white text-gray-900">

  <!-- Navbar1 - Dark Background -->
  <div class="relative bg-[#121212] text-white border-b border-gray-800 h-11 shadow-sm">
  
    <!-- Main centered content -->
    <div class="max-w-7xl mx-auto h-full px-0 lg:px-6 flex items-center justify-between text-sm">
  
      <!-- Country Selector -->
      <div class="pl-[30px] lg:pl-0">
        <a href="/inc/language.php" class="flex items-center gap-2">
      
          <!-- Circular border around flag only -->
          <span class="flex items-center justify-center w-6 h-6 rounded-full border border-gray-600 hover:border-gray-500 hover:bg-gray-800 transition-all">
            <svg class="w-5 h-5" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M503.2 322.8c5.7-21.3 8.8-43.7 8.8-66.8l-8.8-66.8a254.6 254.6 0 0 0-28.8-66.8l-59-66.7A255 255 0 0 0 256 0h-.2A255 255 0 0 0 96.6 55.7l-59 66.7a254.6 254.6 0 0 0-28.8 66.8L0 256v.1c0 23 3 45.4 8.8 66.7l28.8 66.8a257.3 257.3 0 0 0 59 66.7L256 512l159.4-55.7a257.3 257.3 0 0 0 59-66.7z" fill="#FFF"/>
              <path d="M503.2 189.2c5.7 21.3 8.8 43.7 8.8 66.8H0c0-23.1 3-45.5 8.8-66.8zM415.4 55.7a257.3 257.3 0 0 1 59 66.7H37.6a257.3 257.3 0 0 1 59-66.7zm59 333.9c12.6-20.6 22.4-43 28.8-66.8H8.8a254.6 254.6 0 0 0 28.8 66.8zm-59 66.7H96.6A255 255 0 0 0 255.8 512h.4a255 255 0 0 0 159.2-55.7" fill="#D80027"/>
              <path d="M0 245.6A256 256 0 0 1 256 0v256H0z" fill="#0052B4"/>
            </svg>
          </span>
      
          <span class="font-semibold w-auto h-5 text-white">US</span>
      
        </a>
      </div>
  
  
    </div>
  
    <!-- PayPal pinned to top-right edge -->
    <div class="hidden md:flex absolute top-0 right-0 h-full items-center pl-2">

      <nav class="flex items-center gap-7 font-medium">
        <a href="#" class="nav-link flex items-center gap-1.5">

          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 01-2-2H7a2 2 0 01-2 2v16m14 0h2m-2 0h-5m-4 0H3" />
          </svg>

          Hotels
        </a>

        <a href="#" class="nav-link">Sell</a>

        <a href="/giftcard.php" class="nav-link flex items-center gap-1.5">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-1a2 2 0 01-2-2H9a2 2 0 01-2-2v-1a2 2 0 012-2m0 0V9a2 2 0 012-2" />
          </svg>
          Gift Cards
        </a>

        <a href="#" class="nav-link">Help</a>
        <a href="#" class="nav-link">VIP</a>
      </nav>
      
      <a href="#" class="h-full flex items-center ml-6">
        <img
          src="assets/paypal_small.svg"
          alt="PayPal"
          class="block h-full w-auto"
        >
      </a>
    </div>
  
  </div>

</body>
</html>
