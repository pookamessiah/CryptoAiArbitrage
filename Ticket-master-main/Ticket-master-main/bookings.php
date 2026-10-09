<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Enable error displaying so we can pinpoint issues if database structural details are missing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Load the connection file from the 'config' folder
require_once 'config/db.php';

// 2. Instantiate your "Database" class and invoke connect() to expose $pdo safely
$pdo = null;
try {
    if (class_exists('Database')) {
        $dbInstance = new Database();
        $pdo = $dbInstance->connect(); 
    }
} catch (Exception $e) {
    // Safe fallback handling if connection breaks down
}

// ---------------------------------------------
// GET CONCERT ID
// ---------------------------------------------
if (!isset($_GET['concert_id'])) {
    die("Concert not found.");
}

$concert_id = (int)$_GET['concert_id'];

$artist_name = "";
$concert_title = "";
$concert_details = "";
$stadium_map_image = "";
$ticket_sections = [];

try {

    // Fetch the concert
    $stmt = $pdo->prepare("
        SELECT *
        FROM concerts
        WHERE concert_id = ?
        LIMIT 1
    ");

    $stmt->execute([$concert_id]);
    $concert = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$concert) {
        die("Concert not found.");
    }

    // Get artist name
    $stmt = $pdo->prepare("
        SELECT artist_name
        FROM artists
        WHERE artist_id = ?
        LIMIT 1
    ");

    $stmt->execute([$concert['artist_id']]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);

    $artist_name = $artist['artist_name'] ?? '';

    // Concert details
    $concert_title = $concert['title'];

    $concert_details =
        $concert['concert_date'] .
        " • " .
        $concert['day_time'] .
        " • " .
        $concert['venue'] .
        " • " .
        $concert['location'];

    if (!empty($concert['map_view'])) {
        $stadium_map_image = "uploads/concerts/" . $concert['map_view'];
    } else {
        $stadium_map_image = "assets/images/theatre.jpg";
    }

    // Fetch tickets
    $stmt = $pdo->prepare("
        SELECT *
        FROM tickets
        WHERE concert_id = ?
        ORDER BY section_name,row_name,seat_name
    ");

    $stmt->execute([$concert_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $key = $row['section_name'].' • '.$row['row_name'];

        if (!isset($ticket_sections[$key])) {

            $ticket_sections[$key] = [
                'id'      => $key,
                'section' => $row['section_name'],
                'row'     => $row['row_name'],
                'type'    => $row['ticket_name'],
                'price'   => $row['price'],
                'entry'   => 'Mobile Entry',
                'view'    => !empty($row['section_view'])
                                ? "uploads/tickets/".$row['section_view']
                                : "",
                'seats'   => []
            ];
        }

        $ticket_sections[$key]['seats'][] = [
            'ticket_id' => $row['ticket_id'],
            'seat_name' => $row['seat_name']
        ];
    }

    $ticket_sections = array_values($ticket_sections);

} catch (PDOException $e) {
    die($e->getMessage());
}

    
?>
<!DOCTYPE html>
<html lang="en">

<?php include "inc/head.php"; ?>
<?php include "inc/navbar.php"; ?> 

<body class="bg-gray-50 text-gray-900 font-sans antialiased">
    <div id="__next">
        <?php include "inc/header.php"; ?>

        <div class="bg-white border-b border-gray-200 py-6 px-4 md:px-8 shadow-sm">
            <div class="max-w-7xl mx-auto">
                <span class="text-xs font-black uppercase tracking-wider text-[#024DDF] bg-blue-50 px-2.5 py-1 rounded">Selected Concert Ticket(s)</span>
                <h1 class="text-2xl md:text-4xl font-black text-gray-900 tracking-tight mt-2">
                    <?php echo htmlspecialchars($artist_name); ?> — <span class="font-bold text-gray-700"><?php echo htmlspecialchars($concert_title); ?></span>
                </h1>
                <p class="text-sm md:text-base font-medium text-gray-500 mt-1 flex items-center gap-2">
                    <i class="far fa-calendar-check text-blue-600"></i> <?php echo htmlspecialchars($concert_details); ?>
                </p>
            </div>
        </div>

        <div class="w-full bg-black relative h-[260px] md:h-[420px] overflow-hidden select-none shadow-inner">
            <img
                src="<?php echo htmlspecialchars($stadium_map_image); ?>"
                onclick="openImageModal('<?php echo htmlspecialchars($stadium_map_image); ?>','map')"
                class="w-full h-full object-cover opacity-90 object-center cursor-zoom-in"
                 onerror="this.src='https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=1600&q=80';" 
                 alt="Stadium Grid Mapping Layout" 
                 class="w-full h-full object-cover opacity-90 object-center">
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
            <div
                onclick="openImageModal('<?php echo htmlspecialchars($stadium_map_image, ENT_QUOTES); ?>','map')"
                class="absolute bottom-4 left-4 md:left-8 bg-black/70 backdrop-blur-md border border-gray-700 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest cursor-pointer hover:bg-black/80 transition">
                <i class="fas fa-map-marked-alt mr-1.5 text-blue-400"></i>
                Map View
            </div>
        </div>

        <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <div class="lg:col-span-7 space-y-4">
                    <h2 class="text-lg font-black uppercase tracking-wider text-gray-800 flex items-center gap-2">
                        <i class="fas fa-list-ol text-[#024DDF]"></i> Available Seating Entries
                    </h2>
                    
                    <?php foreach ($ticket_sections as $sec): ?>
                        <div class="bg-white border-2 border-gray-200 rounded-xl transition-all shadow-sm overflow-hidden" id="card-<?php echo $sec['id']; ?>">
                            
                            <div onclick="toggleSectionDisplay('<?php echo $sec['id']; ?>')" 
                                 class="p-4 md:p-5 flex items-center justify-between cursor-pointer hover:bg-gray-50/80 transition-colors select-none">
                                <div>
                                    <h3 class="text-base md:text-lg font-black text-gray-900 tracking-tight">
                                        <?php echo htmlspecialchars($sec['section']); ?> • <?php echo htmlspecialchars($sec['row']); ?>
                                    </h3>
                                    <p class="text-xs font-bold text-gray-400 uppercase mt-0.5 tracking-wide">
                                        Total available seats: <span class="text-gray-700 font-extrabold"><?php echo count($sec['seats']); ?> entries</span>
                                    </p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="text-[10px] font-black uppercase bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded">
                                            <?php echo htmlspecialchars($sec['type']); ?>
                                        </span>
                                    
                                        <?php if(!empty($sec['view'])): ?>
                                        <button
                                            type="button"
                                            onclick="event.stopPropagation();
                                            openImageModal('<?php echo htmlspecialchars($sec['view']); ?>','section')"
                                            class="text-[10px] font-black uppercase bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded hover:bg-blue-100 transition-colors">
                                            <i class="fas fa-image mr-1"></i>
                                            Section View
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-right flex items-center gap-4">

                                    <div>
                                        <span class="block text-xl font-black text-[#024DDF] tracking-tight">
                                            $<?php echo number_format($sec['price'], 2); ?>
                                        </span>
                                        <span class="text-[10px] text-gray-400 block font-medium">ea + transaction fees</span>
                                    </div>
                                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300 transform" id="icon-<?php echo $sec['id']; ?>"></i>
                                </div>
                            </div>

                            <div id="drawer-<?php echo $sec['id']; ?>" class="hidden border-t border-gray-100 bg-gray-50/50 p-4">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-tight mb-3">
                                    Select desired seat positions from the manifest layout below:
                                </p>
                                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                                    <?php foreach ($sec['seats'] as $seat): ?>
                                        <button
                                            type="button"
                                            data-ticket-id="<?= htmlspecialchars($seat['ticket_id']) ?>"
                                            onclick="toggleSeatSelection(
                                                this,
                                                '<?= htmlspecialchars($sec['id'], ENT_QUOTES) ?>',
                                                '<?= htmlspecialchars($seat['seat_name'], ENT_QUOTES) ?>',
                                                <?= $sec['price'] ?>
                                            )"
                                            class="seat-btn bg-white border border-gray-300 rounded-lg py-2.5 px-2 text-xs font-bold text-gray-700 hover:border-[#024DDF] hover:bg-blue-50/50 transition-all text-center focus:outline-none select-none">
                                    
                                            <i class="fas fa-chair text-[10px] opacity-40 mr-1"></i>
                                            <?= htmlspecialchars($seat['seat_name']) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="lg:col-span-5 sticky top-4">
                    <div id="checkout-sidebar-panel" class="bg-white border-2 border-gray-200 rounded-xl p-6 shadow-md transition-all opacity-40 pointer-events-none select-none">
                        <h3 class="text-base font-black uppercase tracking-wider text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                            <i class="fas fa-shopping-basket text-[#024DDF]"></i> Order Review Summary
                        </h3>
                        
                        <div id="selected-seats-container" class="space-y-2 max-h-[180px] overflow-y-auto mb-4 pr-1">
                            <p class="text-xs font-bold text-gray-400 italic py-2">No active seat ticket(s) locked yet.</p>
                        </div>

                        <div class="border-t border-gray-100 pt-4 space-y-2">
                            <div class="flex justify-between text-xs font-bold text-gray-500">
                                <span>Selected Count:</span>
                                <span id="summary-count">0 seats</span>
                            </div>
                            <div class="flex justify-between items-baseline pt-2 border-t border-dashed border-gray-100">
                                <span class="text-sm font-black text-gray-900">Estimated Total:</span>
                                <span class="text-2xl font-black text-[#024DDF] tracking-tight" id="summary-total-price">$0.00</span>
                            </div>
                        </div>

                        <form id="checkoutForm" class="mt-6">
                        
                            <input type="hidden" name="serialized_seat_payload" id="serialized-seat-payload" value="">
                            <input type="hidden" name="concert_id" value="<?= $concert_id ?>">
                        
                            <button type="submit"
                                class="w-full bg-[#024DDF] hover:bg-blue-800 text-white font-black text-sm uppercase tracking-widest py-4 px-6 rounded-xl transition-all shadow focus:outline-none flex items-center justify-center gap-2">
                                Proceed to Checkout <i class="fas fa-arrow-right text-xs"></i>
                            </button>
                        
                        </form>
                    </div>
                </div>

            </div>
        </main>
        <div
            id="imageModal"
            class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[9999] hidden items-center justify-center p-4">
        
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[95vh] overflow-hidden flex flex-col">
        
                <div class="flex items-center justify-between border-b px-6 py-4">
                    <div>
                        <h3 id="modalTitle" class="text-xl font-black text-gray-900">
                            Venue Map
                        </h3>
                        <p id="modalDescription" class="text-sm text-gray-500 mt-1">
                            Use this image as a reference while selecting your seats.
                        </p>
                    </div>
        
                    <button
                        onclick="closeImageModal()"
                        class="text-gray-500 hover:text-black text-3xl leading-none">
                        &times;
                    </button>
                </div>
        
                <div class="flex items-center gap-3 px-6 py-3 bg-gray-50 border-b">
        
                    <button
                        onclick="zoomIn()"
                        class="bg-white border rounded-lg px-4 py-2 font-semibold hover:bg-gray-100">
                        +
                    </button>
        
                    <button
                        onclick="zoomOut()"
                        class="bg-white border rounded-lg px-4 py-2 font-semibold hover:bg-gray-100">
                        −
                    </button>
        
                    <button
                        onclick="resetZoom()"
                        class="bg-white border rounded-lg px-4 py-2 font-semibold hover:bg-gray-100">
                        Reset
                    </button>
        
                </div>
        
                <div class="flex-1 overflow-auto bg-gray-900 p-6">
                    <div class="min-w-max min-h-full flex items-center justify-center">
                        <img
                            id="modalImage"
                            src=""
                            class="rounded-xl shadow-2xl transition-transform duration-200 origin-center"
                            style="transform:scale(1);">
                    </div>
                </div>
        
                <div class="border-t bg-white px-6 py-4">
        
                    <div id="modalInfo"
                         class="bg-blue-50 border border-blue-100 rounded-xl p-4">
        
                        <h4 class="font-bold text-blue-800 mb-2">
                            Viewing Instructions
                        </h4>
        
                        <ul class="text-sm text-gray-600 space-y-1 list-disc pl-5">
                            <li>Use the zoom controls to inspect the seating layout.</li>
                            <li>Compare this image with the available seating list.</li>
                            <li>Seat availability is determined by the booking list, not the image.</li>
                        </ul>
        
                    </div>
        
                </div>
        
            </div>

        </div>

    <?php include "inc/footer.php"; ?>

    <script>
        // Track globally selected seat nodes data matrix array
        let pickedSeatsRegister = [];

        // Dynamic interface controller to slide open corresponding seat drawers
        function toggleSectionDisplay(sectionId) {
            const drawer = document.getElementById('drawer-' + sectionId);
            const icon = document.getElementById('icon-' + sectionId);
            const card = document.getElementById('card-' + sectionId);
            
            if (drawer.classList.contains('hidden')) {
                drawer.classList.remove('hidden');
                icon.classList.add('rotate-180');
                card.classList.remove('border-gray-200');
                card.classList.add('border-blue-200', 'shadow-md');
            } else {
                drawer.classList.add('hidden');
                icon.classList.remove('rotate-180');
                card.classList.remove('border-blue-200', 'shadow-md');
                card.classList.add('border-gray-200');
            }
        }

        // Handles multi-seat picking state management logic loop
        function toggleSeatSelection(buttonElement, sectionId, seatName, priceMetric) {
        
            const ticketId = buttonElement.dataset.ticketId;
        
            const searchIndex = pickedSeatsRegister.findIndex(
                item => item.ticket_id == ticketId
            );
        
            if (searchIndex > -1) {
        
                pickedSeatsRegister.splice(searchIndex, 1);
        
                buttonElement.classList.remove(
                    'bg-[#024DDF]',
                    'text-white',
                    'border-[#024DDF]',
                    'shadow-inner'
                );
        
                buttonElement.classList.add(
                    'bg-white',
                    'text-gray-700',
                    'border-gray-300'
                );
        
            } else {
        
                pickedSeatsRegister.push({
        
                    ticket_id: ticketId,
        
                    section: sectionId,
        
                    seat: seatName,
        
                    price: parseFloat(priceMetric)
        
                });
        
                buttonElement.classList.remove(
                    'bg-white',
                    'text-gray-700',
                    'border-gray-300'
                );
        
                buttonElement.classList.add(
                    'bg-[#024DDF]',
                    'text-white',
                    'border-[#024DDF]',
                    'shadow-inner'
                );
            }
        
            refreshSidebarStateView();
        }


        // Synchronization routine updating calculations and conditional sidebar checkout visibility 
        function refreshSidebarStateView() {
            const sidebarPanel = document.getElementById('checkout-sidebar-panel');
            const container = document.getElementById('selected-seats-container');
            const countLabel = document.getElementById('summary-count');
            const priceLabel = document.getElementById('summary-total-price');
            const hiddenPayloadInput = document.getElementById('serialized-seat-payload');

            if (pickedSeatsRegister.length === 0) {
                // If no seats selected, disable visibility configuration states completely
                sidebarPanel.classList.add('opacity-40', 'pointer-events-none', 'select-none');
                container.innerHTML = `<p class="text-xs font-bold text-gray-400 italic py-2">No active seat nodes locked yet.</p>`;
                countLabel.innerText = "0 seats";
                priceLabel.innerText = "$0.00";
                hiddenPayloadInput.value = "";
            } else {
                // Remove disabling overlay configurations, enable immediate side panel execution triggers
                sidebarPanel.classList.remove('opacity-40', 'pointer-events-none', 'select-none');
                
                let cumulativeTotalSum = 0;
                let injectionHtmlBuffer = "";

                pickedSeatsRegister.forEach(seatNode => {
                    cumulativeTotalSum += seatNode.price;
                    injectionHtmlBuffer += `
                        <div class="flex items-center justify-between bg-blue-50/60 border border-blue-100 rounded-lg p-2.5 text-xs animate-fade-in">
                            <div>
                                <span class="font-black text-gray-900 block">${seatNode.section}</span>
                                <span class="font-bold text-gray-500">${seatNode.seat}</span>
                            </div>
                            <span class="font-extrabold text-[#024DDF]">$${seatNode.price.toFixed(2)}</span>
                        </div>
                    `;
                });

                container.innerHTML = injectionHtmlBuffer;
                countLabel.innerText = `${pickedSeatsRegister.length} position(s) locked`;
                priceLabel.innerText = `$${cumulativeTotalSum.toFixed(2)}`;
                
                // Pack chosen attributes sequence payload data safely into form structure field
                hiddenPayloadInput.value = JSON.stringify(pickedSeatsRegister);
            }
        }

        let currentZoom = 1;
        
        function openImageModal(image, type = "map") {
        
            currentZoom = 1;
        
            const modal = document.getElementById("imageModal");
            const img = document.getElementById("modalImage");
        
            img.src = image;
            img.style.transform = "scale(1)";
        
            const title = document.getElementById("modalTitle");
            const desc = document.getElementById("modalDescription");
            const info = document.getElementById("modalInfo");
        
            if(type === "section"){
        
                title.innerText = "Section View";
        
                desc.innerText =
                    "Preview the perspective from this seating section before selecting your seats.";
        
                info.innerHTML = `
                    <h4 class="font-bold text-blue-800 mb-2">
                        Before You Book
                    </h4>
        
                    <ul class="text-sm text-gray-600 space-y-1 list-disc pl-5">
                        <li>This is an approximate view from the selected section.</li>
                        <li>Your exact experience may vary depending on your row and seat.</li>
                        <li>Use this preview together with the venue map.</li>
                    </ul>
                `;
        
            }else{
        
                title.innerText = "Venue Seating Map";
        
                desc.innerText =
                    "Reference map showing the location of each seating section inside the venue.";
        
                info.innerHTML = `
                    <h4 class="font-bold text-blue-800 mb-2">
                        How to Use This Map
                    </h4>
        
                    <ul class="text-sm text-gray-600 space-y-1 list-disc pl-5">
                        <li>Locate the section you intend to purchase.</li>
                        <li>Expand the matching section below to choose available seats.</li>
                        <li>Click any Section View button to preview the stage angle.</li>
                    </ul>
                `;
            }
        
            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }

        function closeImageModal() {
        
            const modal = document.getElementById("imageModal");
        
            modal.classList.remove("flex");
            modal.classList.add("hidden");
        }
        
        function zoomIn() {
        
            currentZoom += 0.2;
        
            document.getElementById("modalImage").style.transform =
                `scale(${currentZoom})`;
        }
        
        function zoomOut() {
        
            currentZoom = Math.max(0.2, currentZoom - 0.2);
        
            document.getElementById("modalImage").style.transform =
                `scale(${currentZoom})`;
        }
        
        function resetZoom() {
        
            currentZoom = 1;
        
            document.getElementById("modalImage").style.transform =
                "scale(1)";
        }
        
        document.getElementById("imageModal").addEventListener("click", function(e){
        
            if(e.target === this){
        
                closeImageModal();
        
            }
        
        });


        document
        .getElementById("checkoutForm")
        .addEventListener("submit", function(e){
        
            e.preventDefault();
        
            if(pickedSeatsRegister.length === 0){
                alert("Please select at least one seat.");
                return;
            }
        
            fetch("save_order",{
                method:"POST",
                headers:{
                    "Content-Type":"application/json"
                },
                body:JSON.stringify({
                    seats:pickedSeatsRegister
                })
            })
            .then(res => res.json())
            .then(response => {
        
                if (!response.success) {
                    // Check if backend flagged this as an authentication block
                    if (response.auth_required) {
                        // Redirect user to login portal with the error and return parameters
                        const returnUrl = encodeURIComponent(window.location.href);
                        window.location = "oauth?error=auth_required&redirect=" + returnUrl;
                        return;
                    }
        
                    alert(response.message);
                    return;
                }
        
                // Proceed to checkout if order generation succeeded 
                window.location = "auth/checkout";
        
            })
            .catch(err => {
                console.log(err);
                alert("Unable to create order.");
            });
        
        });
    </script>

    <style>
        .rotate-180 { transform: rotate(180deg); }
        .seat-btn { transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1); }
        body { overflow-x: hidden; }
    </style>
</body>
</html>
