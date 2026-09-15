<?php
/*
=========================================================
PHARMACYX - TRACK ORDER
CUSTOMER ORDER TRACKING
=========================================================
*/

session_name("PHARMACYX_CUSTOMER");
session_start();

/* Prevent stale tracking pages */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

/* Customer login check */
if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Customer'
) {
    header("Location: signin.php?role=Customer");
    exit();
}

require_once "./db_Config/config.php";

$user = $_SESSION['username'];
$userEscaped = mysqli_real_escape_string($Connection, $user);

/* Accept either ?id=3 or ?order_id=3 */
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$orderId) {
    $orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
}

if (!$orderId || $orderId <= 0) {
    die("Invalid order ID.");
}

$orderId = (int)$orderId;

/*
Fetch only the logged-in customer's order.
This also prevents a customer from viewing another
customer's order by manually changing the URL.
*/
$query = "
    SELECT
        o.order_id,
        o.user_name,
        o.order_status,
        o.order_type,
        o.qty,
        o.receiver_name,
        o.street,
        o.city,
        o.postal_code,
        o.Order_total,
        o.payment_method,
        o.order_date,

        p.product_id,
        p.product_name,
        p.product_description,
        p.price,
        p.image_url

    FROM orders o

    LEFT JOIN products p
        ON o.product_id = p.product_id

    WHERE o.order_id = $orderId
      AND o.user_name = '$userEscaped'

    LIMIT 1
";

$result = mysqli_query($Connection, $query);

if (!$result) {
    die(
        "Order Query Error: " .
        htmlspecialchars(mysqli_error($Connection), ENT_QUOTES, 'UTF-8')
    );
}

if (mysqli_num_rows($result) === 0) {
    die(
        '<div style="font-family:Arial,sans-serif;padding:40px;text-align:center;">
            <h2 style="color:#dc3545;">Order Not Found</h2>
            <p>This order does not exist or does not belong to your account.</p>
            <a href="my_orders.php"
               style="display:inline-block;padding:10px 18px;background:#0077cc;color:white;text-decoration:none;border-radius:6px;">
               Back to My Orders
            </a>
        </div>'
    );
}

$order = mysqli_fetch_assoc($result);

/* Product image path helper */
function pharmacyxTrackImagePath($imageValue)
{
    $imageValue = trim((string)$imageValue);
    $fallback = './Images/product-icons/Pharmacy-Isometric-Icons-1.png';

    if ($imageValue === '') {
        return $fallback;
    }

    if (preg_match('#^(https?:)?//#i', $imageValue)) {
        return $imageValue;
    }

    $imageValue = str_replace('\\', '/', $imageValue);

    while (strpos($imageValue, './') === 0) {
        $imageValue = substr($imageValue, 2);
    }

    $imageValue = ltrim($imageValue, '/');

    if (stripos($imageValue, 'Images/') === 0) {
        return './' . $imageValue;
    }

    if (stripos($imageValue, 'product-icons/') === 0) {
        return './Images/' . $imageValue;
    }

    return './Images/product-icons/' . basename($imageValue);
}

$status = trim((string)($order['order_status'] ?? 'Pending'));

$statusSteps = [
    'Pending',
    'Accepted',
    'Packed',
    'Out for Delivery',
    'Delivered'
];

$currentIndex = array_search($status, $statusSteps, true);
if ($currentIndex === false) {
    $currentIndex = 0;
}

$isTerminalProblem = ($status === 'Rejected' || $status === 'Cancelled');

$imagePath = pharmacyxTrackImagePath($order['image_url'] ?? '');

$productName = $order['product_name'] ?? 'Medicine';
$productDescription = $order['product_description'] ?? 'Medicine product';
$qty = (int)($order['qty'] ?? 0);
$total = (float)($order['Order_total'] ?? 0);
$price = (float)($order['price'] ?? 0);
$payment = $order['payment_method'] ?? 'Not Available';
$orderDate = $order['order_date'] ?? '';

$statusClass = 'pending';

switch ($status) {
    case 'Accepted':
        $statusClass = 'accepted';
        break;
    case 'Packed':
        $statusClass = 'packed';
        break;
    case 'Shipped':
        $statusClass = 'shipped';
        break;
    case 'Out for Delivery':
        $statusClass = 'delivery';
        break;
    case 'Delivered':
        $statusClass = 'delivered';
        break;
    case 'Rejected':
        $statusClass = 'rejected';
        break;
    case 'Cancelled':
        $statusClass = 'cancelled';
        break;
    default:
        $statusClass = 'pending';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Order #<?php echo $orderId; ?> - PharmacyX</title>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* { box-sizing:border-box; }

body {
    margin:0;
    font-family:Arial,Helvetica,sans-serif;
    background:#eef5ff;
    color:#1f2937;
}

.container {
    width:90%;
    max-width:1050px;
    margin:35px auto 60px;
}

.page-title {
    text-align:center;
    color:#0878d1;
    margin:10px 0 30px;
    font-size:32px;
}

.card {
    background:#fff;
    border-radius:14px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
    margin-bottom:22px;
}

.order-summary { padding:25px; }

.product-row {
    display:flex;
    align-items:center;
    gap:25px;
}

.product-image {
    width:160px;
    height:160px;
    flex:0 0 160px;
    border:1px solid #ddd;
    border-radius:10px;
    overflow:hidden;
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
}

.product-image img {
    width:100%;
    height:100%;
    object-fit:contain;
}

.product-details { flex:1; }

.product-details h2 {
    margin:0 0 12px;
    color:#222;
}

.description {
    color:#666;
    line-height:1.5;
    margin-bottom:12px;
}

.info { margin:7px 0; }

.total {
    color:#138a2e;
    font-size:19px;
    font-weight:bold;
}

.status-badge {
    display:inline-block;
    padding:8px 16px;
    border-radius:20px;
    color:#fff;
    font-weight:bold;
}

.pending { background:#ff9800; }
.accepted { background:#28a745; }
.packed { background:#6f42c1; }
.shipped { background:#673ab7; }
.delivery { background:#ff6600; }
.delivered { background:#2196f3; }
.rejected { background:#dc3545; }
.cancelled { background:#6c757d; }

.tracking-card,
.address-card {
    padding:25px;
}

.section-title {
    color:#0878d1;
    margin:0 0 25px;
    font-size:21px;
}

.timeline {
    position:relative;
    margin:0;
    padding:0;
}

.timeline::before {
    content:"";
    position:absolute;
    left:12px;
    top:12px;
    bottom:12px;
    width:3px;
    background:#d9e3ee;
}

.step {
    position:relative;
    display:flex;
    align-items:flex-start;
    gap:18px;
    margin:0 0 25px;
    min-height:42px;
}

.step:last-child { margin-bottom:0; }

.step-dot {
    position:relative;
    z-index:2;
    width:28px;
    height:28px;
    border-radius:50%;
    background:#fff;
    border:3px solid #b8c5d3;
    flex:0 0 28px;
}

.step.completed .step-dot {
    background:#28a745;
    border-color:#28a745;
}

.step.current .step-dot {
    background:#0878d1;
    border-color:#0878d1;
    box-shadow:0 0 0 5px rgba(8,120,209,.12);
}

.step-content { padding-top:2px; }

.step-name {
    font-size:17px;
    font-weight:bold;
    color:#777;
}

.step.completed .step-name,
.step.current .step-name {
    color:#1f2937;
}

.step-note {
    margin-top:4px;
    color:#777;
    font-size:14px;
}

.problem-card {
    padding:22px;
    border-left:5px solid #dc3545;
}

.problem-card h3 {
    margin-top:0;
    color:#dc3545;
}

.address-row { margin:8px 0; }

.actions {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.btn {
    display:inline-block;
    padding:11px 18px;
    border-radius:7px;
    text-decoration:none;
    color:#fff;
    font-weight:bold;
}

.back-btn { background:#0878d1; }
.invoice-btn { background:#28a745; }

@media (max-width:700px) {
    .container { width:95%; }

    .product-row {
        flex-direction:column;
        align-items:flex-start;
    }

    .product-image {
        width:140px;
        height:140px;
        flex-basis:140px;
    }

    .page-title { font-size:27px; }
}
</style>
</head>

<body>

<div class="container">

    <h1 class="page-title">
        <i class="fa fa-truck"></i>
        Track Your Order
    </h1>

    <div class="card order-summary">
        <div class="product-row">

            <div class="product-image">
                <img
                    src="<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?php echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'); ?>"
                    onerror="this.onerror=null;this.src='./Images/product-icons/Pharmacy-Isometric-Icons-1.png';"
                >
            </div>

            <div class="product-details">

                <h2>
                    <?php echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'); ?>
                </h2>

                <div class="description">
                    <?php echo htmlspecialchars($productDescription, ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <div class="info">
                    <b>Order ID:</b> #<?php echo $orderId; ?>
                </div>

                <div class="info">
                    <b>Quantity:</b> <?php echo $qty; ?>
                </div>

                <div class="info">
                    <b>Price:</b> ₹<?php echo number_format($price, 2); ?>
                </div>

                <div class="info total">
                    <b>Total:</b> ₹<?php echo number_format($total, 2); ?>
                </div>

                <div class="info">
                    <b>Payment:</b>
                    <?php echo htmlspecialchars($payment, ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <div class="info">
                    <b>Order Date:</b>
                    <?php echo htmlspecialchars($orderDate, ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <div class="info" style="margin-top:15px;">
                    <b>Current Status:</b>
                    <span class="status-badge <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>

            </div>
        </div>
    </div>

    <?php if ($isTerminalProblem): ?>

        <div class="card problem-card">
            <h3>
                <i class="fa fa-circle-exclamation"></i>
                Order <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
            </h3>
            <p>
                This order is no longer moving through the normal delivery process.
            </p>
        </div>

    <?php else: ?>

        <div class="card tracking-card">

            <h2 class="section-title">
                <i class="fa fa-location-dot"></i>
                Order Tracking
            </h2>

            <div class="timeline">

                <?php
                $notes = [
                    'Pending' => 'Order has been received and is waiting for processing.',
                    'Accepted' => 'Pharmacy has accepted your order.',
                    'Packed' => 'Your medicine has been packed.',
                    'Out for Delivery' => 'Your order is on the way to your address.',
                    'Delivered' => 'Your order has been delivered successfully.'
                ];
                ?>

                <?php foreach ($statusSteps as $index => $step): ?>

                    <?php
                    $stepClass = '';

                    if ($index < $currentIndex) {
                        $stepClass = 'completed';
                    } elseif ($index === $currentIndex) {
                        $stepClass = 'current';
                    }
                    ?>

                    <div class="step <?php echo $stepClass; ?>">

                        <div class="step-dot">
                            <?php if ($index < $currentIndex): ?>
                                <i class="fa fa-check"
                                   style="color:#fff;font-size:13px;margin:5px;"></i>
                            <?php elseif ($index === $currentIndex): ?>
                                <i class="fa fa-circle"
                                   style="color:#fff;font-size:10px;margin:6px;"></i>
                            <?php endif; ?>
                        </div>

                        <div class="step-content">

                            <div class="step-name">
                                <?php echo htmlspecialchars($step, ENT_QUOTES, 'UTF-8'); ?>
                            </div>

                            <?php if ($index === $currentIndex): ?>
                                <div class="step-note">
                                    <?php echo htmlspecialchars($notes[$step], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

    <div class="card address-card">

        <h2 class="section-title">
            <i class="fa fa-house"></i>
            Delivery Address
        </h2>

        <div class="address-row">
            <b>Name:</b>
            <?php echo htmlspecialchars($order['receiver_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <div class="address-row">
            <b>Street:</b>
            <?php echo htmlspecialchars($order['street'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <div class="address-row">
            <b>City:</b>
            <?php echo htmlspecialchars($order['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <div class="address-row">
            <b>Postal Code:</b>
            <?php echo htmlspecialchars($order['postal_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </div>

    </div>

    <div class="actions">

        <a class="btn back-btn" href="my_orders.php">
            <i class="fa fa-arrow-left"></i>
            Back to My Orders
        </a>

        <a
            class="btn invoice-btn"
            href="download_invoice.php?id=<?php echo $orderId; ?>"
        >
            <i class="fa fa-file-pdf"></i>
            Download Invoice
        </a>

    </div>

</div>

</body>
</html>
