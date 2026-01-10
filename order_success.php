<?php 
/**
 * order_success.php
 * Professional Event-Edition: Responsive design with ground-delivery messaging
 */
require_once 'includes/init.php';
require_once 'includes/db_connect.php';

// Security: Must be logged in and have an order_id
if (!isset($_SESSION["loggedin"]) || !isset($_GET['order_id'])) {
    header("location: index.php");
    exit;
}

$order_id = htmlspecialchars($_GET['order_id']);

require_once 'includes/header.php';
?>

<!-- Premium Animations and Styles -->
<style>
    .checkmark__circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #4f46e5;
        fill: none;
        animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    .checkmark {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: block;
        stroke-width: 3;
        stroke: #fff;
        stroke-miterlimit: 10;
        margin: 0 auto 2rem;
        box-shadow: inset 0px 0px 0px #4f46e5;
        animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
    }
    .checkmark__check {
        transform-origin: 50% 50%;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }
    @keyframes stroke { 100% { stroke-dashoffset: 0; } }
    @keyframes scale { 0%, 100% { transform: none; } 50% { transform: scale3d(1.1, 1.1, 1); } }
    @keyframes fill { 100% { box-shadow: inset 0px 0px 0px 50px #4f46e5; } }

    .pulse-glow {
        animation: pulse-indigo 2s infinite;
    }
    @keyframes pulse-indigo {
        0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); }
        70% { box-shadow: 0 0 0 15px rgba(79, 70, 229, 0); }
        100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
    }
</style>

<div class="max-w-2xl mx-auto px-4 py-10 md:py-20">
    <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 p-8 md:p-16 text-center relative overflow-hidden">
        
        <!-- Subtle Background Pattern -->
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

        <!-- Animated Success Icon -->
        <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
            <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
        </svg>

        <!-- Dynamic Event-Focused Text -->
        <h1 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tight mb-4">
            Order Received! <br>
            <span class="text-indigo-600">Enjoy the show!</span>
        </h1>
        
        <div class="inline-block bg-indigo-50 px-4 py-1 rounded-full mb-6 border border-indigo-100">
            <p class="text-indigo-700 font-bold text-sm uppercase tracking-widest">Order ID: #<?= $order_id ?></p>
        </div>

        <div class="space-y-4 text-gray-600 leading-relaxed max-w-md mx-auto">
            <p class="font-medium">
                Thank you for ordering through <span class="font-bold text-gray-800">MyStore</span>. Your request has been sent to our desk.
            </p>
            
            <div class="p-4 bg-gray-50 rounded-2xl border border-dashed border-gray-300">
                <p class="text-sm italic">
                    "Our delivery runner is using your <span class="text-indigo-600 font-bold">GPS coordinates</span> to find your exact spot on the ground. Please keep your phone reachable."
                </p>
            </div>
            
            <p class="text-xs text-gray-400">
                If you chose eSewa, we are currently verifying your transaction code.
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="user_order_detail.php?id=<?= $order_id ?>" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 px-10 rounded-2xl transition-all shadow-xl shadow-indigo-100 hover:-translate-y-1 active:scale-95 pulse-glow uppercase text-xs tracking-widest">
                Track Progress
            </a>
            <a href="products.php" class="w-full sm:w-auto bg-white border-2 border-gray-200 text-gray-500 font-bold py-4 px-10 rounded-2xl hover:bg-gray-50 hover:text-gray-800 transition-all text-xs uppercase tracking-widest">
                Shop More
            </a>
        </div>
        
        <p class="mt-8 text-[10px] font-bold text-gray-300 uppercase tracking-[0.2em]">Annual Function 2025 • Student Service</p>
    </div>
</div>

<?php 
// Correct Sequence for Footer
require_once 'includes/footer.php'; 
if(isset($conn)) { $conn->close(); }
?>