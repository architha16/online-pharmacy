<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


/*
====================================================
PHARMACYX - PHARMACIST CUSTOMER ORDERS
====================================================
*/


/*
====================================================
PHARMACIST SESSION
====================================================
*/

session_name("PHARMACYX_PHARMACIST");

session_start();


/*
====================================================
CHECK PHARMACIST LOGIN
====================================================
*/

if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Pharmacist'
) {

    header("Location: signin.php?role=Pharmacist");

    exit();

}


/*
====================================================
DATABASE CONNECTION
====================================================
*/

require_once "./db_Config/config.php";


/*
====================================================
GET ALL CUSTOMER ORDERS
====================================================
*/

$sql = "
    SELECT *
    FROM orders
    ORDER BY order_date DESC
";


$result = mysqli_query(
    $Connection,
    $sql
);


if (!$result) {

    die(
        "Database Error: " .
        mysqli_error($Connection)
    );

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Customer Orders | PharmacyX
</title>


<style>

/*
====================================================
GENERAL
====================================================
*/

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #f4f7fb;

    color: #333;

}


/*
====================================================
HEADER
====================================================
*/

.header {

    background: #008fd5;

    color: white;

    padding: 20px 35px;

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.header h1 {

    margin: 0;

    font-size: 24px;

}


.header a {

    background: white;

    color: #008fd5;

    padding: 10px 18px;

    border-radius: 6px;

    text-decoration: none;

    font-weight: bold;

}


.header a:hover {

    background: #f0f0f0;

}


/*
====================================================
MAIN CONTAINER
====================================================
*/

.container {

    padding: 30px;

}


.container h2 {

    color: #0077b5;

    margin-bottom: 20px;

}


/*
====================================================
ORDER COUNT
====================================================
*/

.order-count {

    margin-bottom: 20px;

    font-size: 16px;

    color: #555;

}


.order-count strong {

    color: #008fd5;

}


/*
====================================================
TABLE BOX
====================================================
*/

.table-box {

    background: white;

    padding: 20px;

    border-radius: 10px;

    box-shadow:
        0 3px 15px rgba(0,0,0,0.12);

    overflow-x: auto;

}


/*
====================================================
TABLE
====================================================
*/

table {

    width: 100%;

    min-width: 1250px;

    border-collapse: collapse;

}


th {

    background: #008fd5;

    color: white;

    padding: 13px;

    text-align: left;

    white-space: nowrap;

}


td {

    padding: 12px;

    border-bottom: 1px solid #ddd;

    vertical-align: middle;

}


tr:hover {

    background: #f5faff;

}


/*
====================================================
STATUS
====================================================
*/

.status {

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 13px;

    font-weight: bold;

    display: inline-block;

}


/*
PENDING
*/

.pending {

    background: #fff3cd;

    color: #856404;

}


/*
ACCEPTED
*/

.accepted {

    background: #d4edda;

    color: #155724;

}


/*
PACKED
*/

.packed {

    background: #cce5ff;

    color: #004085;

}


/*
OUT FOR DELIVERY
*/

.delivery {

    background: #e2e3e5;

    color: #383d41;

}


/*
DELIVERED
*/

.delivered {

    background: #d4edda;

    color: #155724;

}


/*
REJECTED
*/

.rejected {

    background: #f8d7da;

    color: #721c24;

}


/*
====================================================
PRESCRIPTION
====================================================
*/

.prescription {

    color: #0077cc;

    font-weight: bold;

    text-decoration: none;

}


.prescription:hover {

    text-decoration: underline;

}


/*
====================================================
NO ORDERS
====================================================
*/

.no-orders {

    text-align: center;

    padding: 50px;

    color: #777;

    font-size: 20px;

}


/*
====================================================
BACK BUTTON
====================================================
*/

.back {

    display: inline-block;

    margin-top: 20px;

    padding: 11px 20px;

    background: #008fd5;

    color: white;

    text-decoration: none;

    border-radius: 6px;

}


.back:hover {

    background: #0073ad;

}


/*
====================================================
MOBILE
====================================================
*/

@media screen and (max-width: 700px) {

    .header {

        padding: 15px;

    }


    .header h1 {

        font-size: 18px;

    }


    .container {

        padding: 15px;

    }

}

</style>

</head>


<body>


<!--
====================================================
HEADER
====================================================
-->

<div class="header">

    <h1>

        PharmacyX - Pharmacist

    </h1>


    <a href="pharmacist_dashboard.php">

        Dashboard

    </a>

</div>



<!--
====================================================
MAIN
====================================================
-->

<div class="container">


<h2>

    Customer Orders

</h2>



<!--
====================================================
ORDER COUNT
====================================================
-->

<div class="order-count">

    Total Orders:

    <strong>

        <?php echo mysqli_num_rows($result); ?>

    </strong>

</div>



<!--
====================================================
TABLE BOX
====================================================
-->

<div class="table-box">


<?php

/*
====================================================
CHECK ORDERS
====================================================
*/

if (mysqli_num_rows($result) == 0) {

?>

    <div class="no-orders">

        No customer orders found.

    </div>

<?php

}

else {

?>


<!--
====================================================
ORDERS TABLE
====================================================
-->

<table>


<thead>

<tr>

    <th>
        Order ID
    </th>

    <th>
        Customer
    </th>

    <th>
        Status
    </th>

    <th>
        Quantity
    </th>

    <th>
        Receiver
    </th>

    <th>
        Address
    </th>

    <th>
        Product ID
    </th>

    <th>
        Total
    </th>

    <th>
        Payment
    </th>

    <th>
        Order Date
    </th>

    <th>
        Prescription
    </th>

    <th>
        Rejection Reason
    </th>

</tr>

</thead>



<tbody>


<?php

/*
====================================================
DISPLAY ORDERS
====================================================
*/

while (
    $order = mysqli_fetch_assoc($result)
) {


    /*
    ================================================
    ORDER ID
    ================================================
    */

    $orderId =

        htmlspecialchars(
            $order['order_id'] ?? ''
        );


    /*
    ================================================
    CUSTOMER
    ================================================
    */

    $username =

        htmlspecialchars(
            $order['user_name'] ?? ''
        );


    /*
    ================================================
    STATUS
    ================================================
    */

    $status =

        htmlspecialchars(
            $order['order_status'] ?? 'Pending'
        );


    /*
    ================================================
    QUANTITY
    ================================================
    */

    $qty =

        htmlspecialchars(
            $order['qty'] ?? ''
        );


    /*
    ================================================
    RECEIVER
    ================================================
    */

    $receiver =

        htmlspecialchars(
            $order['receiver_name'] ?? ''
        );


    /*
    ================================================
    STREET
    ================================================
    */

    $street =

        htmlspecialchars(
            $order['street'] ?? ''
        );


    /*
    ================================================
    CITY
    ================================================
    */

    $city =

        htmlspecialchars(
            $order['city'] ?? ''
        );


    /*
    ================================================
    POSTAL CODE
    ================================================
    */

    $postal =

        htmlspecialchars(
            $order['postal_code'] ?? ''
        );


    /*
    ================================================
    PRODUCT ID
    ================================================
    */

    $productId =

        htmlspecialchars(
            $order['product_id'] ?? ''
        );


    /*
    ================================================
    TOTAL
    ================================================
    */

    $total =

        htmlspecialchars(
            $order['Order_total'] ?? ''
        );


    /*
    ================================================
    PAYMENT
    ================================================
    */

    $payment =

        htmlspecialchars(
            $order['payment_method'] ?? ''
        );


    /*
    ================================================
    ORDER DATE
    ================================================
    */

    $date =

        htmlspecialchars(
            $order['order_date'] ?? ''
        );


    /*
    ================================================
    PRESCRIPTION
    ================================================
    */

    $prescription =

        $order['prescription_url'] ?? '';


    /*
    ================================================
    REJECTION REASON
    ================================================
    */

    $rejection =

        htmlspecialchars(
            $order['rejection_reason'] ?? ''
        );


    /*
    ================================================
    STATUS CLASS
    ================================================
    */

    $statusClass = "pending";


    if ($status === "Accepted") {

        $statusClass = "accepted";

    }

    elseif ($status === "Packed") {

        $statusClass = "packed";

    }

    elseif ($status === "Out for Delivery") {

        $statusClass = "delivery";

    }

    elseif ($status === "Delivered") {

        $statusClass = "delivered";

    }

    elseif ($status === "Rejected") {

        $statusClass = "rejected";

    }

?>


<tr>


<!-- ORDER ID -->

<td>

    <?php echo $orderId; ?>

</td>



<!-- CUSTOMER -->

<td>

    <?php echo $username; ?>

</td>



<!-- STATUS -->

<td>

    <span
        class="status <?php echo $statusClass; ?>"
    >

        <?php echo $status; ?>

    </span>

</td>



<!-- QUANTITY -->

<td>

    <?php echo $qty; ?>

</td>



<!-- RECEIVER -->

<td>

    <?php echo $receiver; ?>

</td>



<!-- ADDRESS -->

<td>

    <?php echo $street; ?>

    <br>

    <?php echo $city; ?>

    -

    <?php echo $postal; ?>

</td>



<!-- PRODUCT ID -->

<td>

    <?php echo $productId; ?>

</td>



<!-- TOTAL -->

<td>

    Rs. <?php echo $total; ?>

</td>



<!-- PAYMENT -->

<td>

    <?php echo $payment; ?>

</td>



<!-- ORDER DATE -->

<td>

    <?php echo $date; ?>

</td>



<!-- PRESCRIPTION -->

<td>


<?php

if (!empty($prescription)) {

?>

    <a
        href="<?php echo htmlspecialchars($prescription); ?>"
        target="_blank"
        class="prescription"
    >

        View

    </a>

<?php

}

else {

    echo "None";

}

?>


</td>



<!-- REJECTION REASON -->

<td>

<?php

if (!empty($rejection)) {

    echo $rejection;

}

else {

    echo "-";

}

?>

</td>


</tr>


<?php

}

?>


</tbody>


</table>


<?php

}

?>


</div>



<!--
====================================================
BACK TO DASHBOARD
====================================================
-->

<a
    href="pharmacist_dashboard.php"
    class="back"
>

    ← Back to Dashboard

</a>


</div>


</body>

</html>