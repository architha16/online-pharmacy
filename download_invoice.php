<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: signin.php");
    exit();
}

require_once "./db_Config/config.php";

$user = $_SESSION['username'];

/* Check Order ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Order ID");
}

$order_id = (int)$_GET['id'];

$user_safe = mysqli_real_escape_string($Connection, $user);

/* Get order details */
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

    p.product_name,
    p.product_description,
    p.price,
    p.image_url

FROM orders o

LEFT JOIN products p
    ON o.product_id = p.product_id

WHERE o.order_id = $order_id
AND o.user_name = '$user_safe'

LIMIT 1
";

$result = mysqli_query($Connection, $query);

if (!$result) {
    die("Database Error: " . mysqli_error($Connection));
}

if (mysqli_num_rows($result) == 0) {
    die("Order Not Found");
}

$order = mysqli_fetch_assoc($result);

/* Invoice filename */
$filename = "PharmacyX_Invoice_" . $order_id . ".html";

/* Tell browser to download the invoice */
header("Content-Type: text/html; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>PharmacyX Invoice</title>

<style>

body {
    font-family: Arial, sans-serif;
    margin: 40px;
    color: #222;
}

.invoice {
    max-width: 800px;
    margin: auto;
    border: 1px solid #ddd;
    padding: 30px;
}

.header {
    text-align: center;
    border-bottom: 2px solid #0077cc;
    padding-bottom: 20px;
}

.header h1 {
    color: #0077cc;
    margin-bottom: 5px;
}

.invoice-info {
    margin-top: 25px;
}

.invoice-info p {
    margin: 7px 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
}

table th,
table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}

table th {
    background: #0077cc;
    color: white;
}

.total {
    text-align: right;
    margin-top: 25px;
    font-size: 22px;
    font-weight: bold;
    color: green;
}

.address {
    margin-top: 30px;
    padding: 15px;
    background: #f5f5f5;
}

.footer {
    text-align: center;
    margin-top: 40px;
    color: #666;
}

</style>

</head>

<body>

<div class="invoice">

<div class="header">

<h1>PharmacyX</h1>

<h2>INVOICE</h2>

<p>Online Pharmacy</p>

</div>


<div class="invoice-info">

<p>
<b>Invoice / Order ID:</b>
#<?php echo htmlspecialchars($order['order_id']); ?>
</p>

<p>
<b>Order Date:</b>
<?php echo htmlspecialchars($order['order_date']); ?>
</p>

<p>
<b>Order Status:</b>
<?php echo htmlspecialchars($order['order_status']); ?>
</p>

<p>
<b>Payment Method:</b>
<?php echo htmlspecialchars($order['payment_method']); ?>
</p>

</div>


<div class="address">

<h3>Delivery Address</h3>

<p>
<b>Name:</b>
<?php echo htmlspecialchars($order['receiver_name'] ?? $user); ?>
</p>

<p>
<b>Street:</b>
<?php echo htmlspecialchars($order['street'] ?? ''); ?>
</p>

<p>
<b>City:</b>
<?php echo htmlspecialchars($order['city'] ?? ''); ?>
</p>

<p>
<b>Postal Code:</b>
<?php echo htmlspecialchars($order['postal_code'] ?? ''); ?>
</p>

</div>


<table>

<tr>

<th>Medicine / Product</th>

<th>Price</th>

<th>Quantity</th>

<th>Total</th>

</tr>

<tr>

<td>

<?php
echo htmlspecialchars(
    $order['product_name'] ?? 'Medicine'
);
?>

<br>

<small>

<?php
echo htmlspecialchars(
    $order['product_description'] ?? ''
);
?>

</small>

</td>

<td>

₹<?php echo number_format($order['price'], 2); ?>

</td>

<td>

<?php echo htmlspecialchars($order['qty']); ?>

</td>

<td>

₹<?php

echo number_format(
    $order['price'] * $order['qty'],
    2
);

?>

</td>

</tr>

</table>


<div class="total">

Order Total:
₹<?php echo number_format($order['Order_total'], 2); ?>

</div>


<div class="footer">

<p>Thank you for shopping with PharmacyX ❤️</p>

<p>Your medicines are being processed carefully.</p>

</div>

</div>

</body>

</html>