<?php

/* =========================================================
   PHARMACYX CUSTOMER SESSION
   ========================================================= */

session_name("PHARMACYX_CUSTOMER");
session_start();


/* =========================================================
   CUSTOMER LOGIN CHECK
   ========================================================= */

if (
    !isset($_SESSION['username']) ||
    empty($_SESSION['username'])
) {
    header("Location: signin.php");
    exit();
}


/* =========================================================
   DATABASE
   ========================================================= */

require_once "./db_Config/config.php";


/* =========================================================
   LOGGED-IN CUSTOMER
   ========================================================= */

$user = $_SESSION['username'];

$user_safe = mysqli_real_escape_string(
    $Connection,
    $user
);


/* =========================================================
   CHECK ORDER ID
   ========================================================= */

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {
    die("Invalid Order ID.");
}

$order_id = (int)$_GET['id'];


/* =========================================================
   GET ORDER DETAILS
   IMPORTANT:
   Only allow the logged-in customer to download
   their own invoice.
   ========================================================= */

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

    WHERE
        o.order_id = $order_id
        AND o.user_name = '$user_safe'

    LIMIT 1
";


$result = mysqli_query(
    $Connection,
    $query
);


if (!$result) {
    die(
        "Database Error: " .
        mysqli_error($Connection)
    );
}


if (mysqli_num_rows($result) === 0) {
    die("Order Not Found.");
}


$order = mysqli_fetch_assoc($result);


/* =========================================================
   PREPARE VALUES
   ========================================================= */

$orderId = (int)$order['order_id'];

$productName =
    $order['product_name'] ??
    'Medicine';

$productDescription =
    $order['product_description'] ??
    '';

$price =
    (float)($order['price'] ?? 0);

$quantity =
    (int)($order['qty'] ?? 0);

$orderTotal =
    (float)($order['Order_total'] ?? 0);

$receiverName =
    $order['receiver_name'] ??
    $user;

$street =
    $order['street'] ??
    '';

$city =
    $order['city'] ??
    '';

$postalCode =
    $order['postal_code'] ??
    '';

$orderStatus =
    $order['order_status'] ??
    'Pending';

$paymentMethod =
    $order['payment_method'] ??
    '';


/* =========================================================
   DOWNLOAD FILE NAME
   ========================================================= */

$filename =
    "PharmacyX_Invoice_" .
    $orderId .
    ".html";


/* =========================================================
   DOWNLOAD HEADERS
   ========================================================= */

header(
    "Content-Type: text/html; charset=UTF-8"
);

header(
    'Content-Disposition: attachment; filename="' .
    $filename .
    '"'
);

header(
    "Content-Transfer-Encoding: binary"
);

header(
    "Cache-Control: no-store, no-cache, must-revalidate"
);

header(
    "Pragma: no-cache"
);

header(
    "Expires: 0"
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<title>
PharmacyX Invoice #<?php echo $orderId; ?>
</title>

<style>

body {
    font-family: Arial, Helvetica, sans-serif;
    margin: 40px;
    color: #222;
    background: #ffffff;
}

.invoice {
    max-width: 800px;
    margin: auto;
    border: 1px solid #dddddd;
    padding: 30px;
}

.header {
    text-align: center;
    border-bottom: 2px solid #0077cc;
    padding-bottom: 20px;
}

.header h1 {
    color: #0077cc;
    margin: 0 0 5px;
    font-size: 32px;
}

.header h2 {
    margin: 5px 0;
}

.header p {
    margin: 5px 0;
    color: #666666;
}

.invoice-info {
    margin-top: 25px;
}

.invoice-info p {
    margin: 7px 0;
}

.address {
    margin-top: 30px;
    padding: 15px;
    background: #f5f5f5;
}

.address h3 {
    margin-top: 0;
}

.address p {
    margin: 7px 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
}

table th,
table td {
    border: 1px solid #dddddd;
    padding: 12px;
    text-align: left;
}

table th {
    background: #0077cc;
    color: white;
}

.product-description {
    color: #666666;
    font-size: 13px;
    margin-top: 5px;
}

.total {
    text-align: right;
    margin-top: 25px;
    font-size: 22px;
    font-weight: bold;
    color: green;
}

.footer {
    text-align: center;
    margin-top: 40px;
    color: #666666;
    border-top: 1px solid #dddddd;
    padding-top: 20px;
}

.footer p {
    margin: 6px 0;
}

</style>

</head>


<body>

<div class="invoice">


    <!-- =====================================================
         HEADER
         ===================================================== -->

    <div class="header">

        <h1>
            PharmacyX
        </h1>

        <h2>
            INVOICE
        </h2>

        <p>
            Online Pharmacy
        </p>

    </div>


    <!-- =====================================================
         ORDER INFORMATION
         ===================================================== -->

    <div class="invoice-info">

        <p>
            <b>Invoice / Order ID:</b>
            #<?php
            echo htmlspecialchars(
                $orderId
            );
            ?>
        </p>

        <p>
            <b>Order Date:</b>
            <?php
            echo htmlspecialchars(
                $order['order_date'] ?? ''
            );
            ?>
        </p>

        <p>
            <b>Order Status:</b>
            <?php
            echo htmlspecialchars(
                $orderStatus
            );
            ?>
        </p>

        <p>
            <b>Payment Method:</b>
            <?php
            echo htmlspecialchars(
                $paymentMethod
            );
            ?>
        </p>

    </div>


    <!-- =====================================================
         DELIVERY ADDRESS
         ===================================================== -->

    <div class="address">

        <h3>
            Delivery Address
        </h3>

        <p>
            <b>Name:</b>
            <?php
            echo htmlspecialchars(
                $receiverName
            );
            ?>
        </p>

        <p>
            <b>Street:</b>
            <?php
            echo htmlspecialchars(
                $street
            );
            ?>
        </p>

        <p>
            <b>City:</b>
            <?php
            echo htmlspecialchars(
                $city
            );
            ?>
        </p>

        <p>
            <b>Postal Code:</b>
            <?php
            echo htmlspecialchars(
                $postalCode
            );
            ?>
        </p>

    </div>


    <!-- =====================================================
         PRODUCT TABLE
         ===================================================== -->

    <table>

        <thead>

            <tr>

                <th>
                    Medicine / Product
                </th>

                <th>
                    Price
                </th>

                <th>
                    Quantity
                </th>

                <th>
                    Total
                </th>

            </tr>

        </thead>


        <tbody>

            <tr>

                <td>

                    <?php
                    echo htmlspecialchars(
                        $productName
                    );
                    ?>

                    <?php if (!empty($productDescription)): ?>

                        <div class="product-description">

                            <?php
                            echo htmlspecialchars(
                                $productDescription
                            );
                            ?>

                        </div>

                    <?php endif; ?>

                </td>


                <td>

                    ₹<?php
                    echo number_format(
                        $price,
                        2
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo htmlspecialchars(
                        $quantity
                    );
                    ?>

                </td>


                <td>

                    ₹<?php

                    echo number_format(
                        $price * $quantity,
                        2
                    );

                    ?>

                </td>

            </tr>

        </tbody>

    </table>


    <!-- =====================================================
         ORDER TOTAL
         ===================================================== -->

    <div class="total">

        Order Total:
        ₹<?php

        echo number_format(
            $orderTotal,
            2
        );

        ?>

    </div>


    <!-- =====================================================
         FOOTER
         ===================================================== -->

    <div class="footer">

        <p>
            Thank you for shopping with PharmacyX ❤️
        </p>

        <p>
            Your medicines are being processed carefully.
        </p>

        <p>
            This is a computer-generated invoice.
        </p>

    </div>


</div>

</body>

</html>