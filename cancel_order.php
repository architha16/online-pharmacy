<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: signin.php");
    exit();
}

require_once "./db_Config/config.php";

$user = $_SESSION['username'];

/* Check order ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Order ID");
}

$order_id = (int) $_GET['id'];

/* Check whether this order belongs to the logged-in user */
$query = "
    SELECT order_id, order_status
    FROM orders
    WHERE order_id = $order_id
    AND user_name = '" . mysqli_real_escape_string($Connection, $user) . "'
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

$status = $order['order_status'];

/* Only Pending orders can be cancelled */
if ($status != "Pending") {
    echo "<script>
        alert('This order cannot be cancelled because its status is $status.');
        window.location='my_orders.php';
    </script>";
    exit();
}

/* Cancel the order */
$update = "
    UPDATE orders
    SET order_status = 'Cancelled'
    WHERE order_id = $order_id
    AND user_name = '" . mysqli_real_escape_string($Connection, $user) . "'
";

if (!mysqli_query($Connection, $update)) {
    die("Cancellation Error: " . mysqli_error($Connection));
}

/* Success */
echo "<script>
    alert('Order cancelled successfully!');
    window.location='my_orders.php';
</script>";

exit();
?>*