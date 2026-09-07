<?php

/*
=========================================================
PHARMACYX - TRACK ORDER
CUSTOMER ORDER TRACKING
=========================================================
*/


/*
=========================================================
CUSTOMER SESSION
=========================================================
*/

session_name("PHARMACYX_CUSTOMER");
session_start();


/*
=========================================================
CUSTOMER LOGIN CHECK
=========================================================
*/

if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Customer'
) {

    header("Location: signin.php?role=Customer");
    exit();

}


/*
=========================================================
DATABASE CONNECTION
=========================================================
*/

require_once "./db_Config/config.php";


/*
=========================================================
LOGGED-IN CUSTOMER
=========================================================
*/

$user = $_SESSION['username'];


/*
=========================================================
GET ORDER ID
=========================================================
*/

if (
    isset($_GET['id']) &&
    is_numeric($_GET['id']) &&
    (int)$_GET['id'] > 0
) {
    $order_id = (int)$_GET['id'];
} else {
    // If no order ID is supplied, get the latest order
    // belonging to the logged-in customer.
    $safeUserForLatest = mysqli_real_escape_string($Connection, $user);

    $latestOrderQuery = "
        SELECT order_id
        FROM orders
        WHERE user_name = '$safeUserForLatest'
        ORDER BY order_id DESC
        LIMIT 1
    ";

    $latestOrderResult = mysqli_query($Connection, $latestOrderQuery);

    if (!$latestOrderResult) {
        die(
            "Database Error: " .
            htmlspecialchars(mysqli_error($Connection))
        );
    }

    if (mysqli_num_rows($latestOrderResult) == 0) {
        die("No Orders Found");
    }

    $latestOrder = mysqli_fetch_assoc($latestOrderResult);
    $order_id = (int)$latestOrder['order_id'];
}


/*
=========================================================
SECURE USERNAME
=========================================================
*/

$safeUser = mysqli_real_escape_string(
    $Connection,
    $user
);


/*
=========================================================
GET ORDER DETAILS
=========================================================
*/

$query = "

SELECT

    o.*,

    p.product_name,
    p.image_url,
    p.price

FROM orders o

INNER JOIN products p
    ON o.product_id = p.product_id

WHERE o.order_id = '$order_id'

AND o.user_name = '$safeUser'

LIMIT 1

";


$result = mysqli_query(
    $Connection,
    $query
);


if (!$result) {

    die(
        "Database Error: " .
        htmlspecialchars(
            mysqli_error($Connection)
        )
    );

}


/*
=========================================================
ORDER NOT FOUND
=========================================================
*/

if (mysqli_num_rows($result) == 0) {

    die("Order Not Found");

}


$order = mysqli_fetch_assoc($result);


/*
=========================================================
ORDER STATUS
=========================================================
*/

$status =
    $order['order_status'] ?? 'Pending';


/*
=========================================================
EXPECTED DELIVERY DATE
=========================================================
*/

$expected = date(

    "d M Y",

    strtotime(
        $order['order_date'] . " +2 days"
    )

);


/*
=========================================================
STATUS CLASS
=========================================================
*/

$statusClass = "pending";


switch ($status) {

    case "Accepted":

        $statusClass = "accepted";

        break;


    case "Packed":

        $statusClass = "packed";

        break;


    case "Out for Delivery":

        $statusClass = "delivery";

        break;


    case "Delivered":

        $statusClass = "delivered";

        break;


    case "Rejected":

        $statusClass = "rejected";

        break;


    case "Cancelled":

        $statusClass = "cancelled";

        break;


    case "Pending":

    default:

        $statusClass = "pending";

        break;

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

Track Order #

<?php

echo (int)$order['order_id'];

?>

- PharmacyX

</title>


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
>


<style>

/* =====================================================
   BODY
===================================================== */

body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #eef5ff;

}


/* =====================================================
   CONTAINER
===================================================== */

.container {

    width: 75%;

    margin: 40px auto;

}


/* =====================================================
   TITLE
===================================================== */

.title {

    text-align: center;

    color: #0077cc;

    margin-bottom: 30px;

}


/* =====================================================
   CARD
===================================================== */

.card {

    background: white;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 0 12px rgba(0,0,0,0.12);

    display: flex;

    gap: 30px;

    align-items: center;

}


/* =====================================================
   PRODUCT IMAGE
===================================================== */

.image {

    width: 220px;

    height: 220px;

    display: flex;

    align-items: center;

    justify-content: center;

    border: 1px solid #ddd;

    border-radius: 10px;

    background: white;

    overflow: hidden;

    flex-shrink: 0;

}


.image img {

    width: 100%;

    height: 100%;

    object-fit: contain;

}


/* =====================================================
   DETAILS
===================================================== */

.details {

    flex: 1;

}


.details h2 {

    margin-top: 0;

    color: #222;

}


.info {

    margin: 12px 0;

    font-size: 16px;

}


/* =====================================================
   STATUS BADGES
===================================================== */

.badge {

    display: inline-block;

    padding: 7px 15px;

    border-radius: 20px;

    color: white;

    font-weight: bold;

}


/* Pending */

.pending {

    background: #ff9800;

}


/* Accepted */

.accepted {

    background: #28a745;

}


/* Packed */

.packed {

    background: #6f42c1;

}


/* Out for Delivery */

.delivery {

    background: #ff6600;

}


/* Delivered */

.delivered {

    background: #2196f3;

}


/* Rejected */

.rejected {

    background: #dc3545;

}


/* Cancelled */

.cancelled {

    background: #6c757d;

}


/* =====================================================
   SECTIONS
===================================================== */

.section {

    margin-top: 30px;

    padding: 20px;

    background: #f8f9fa;

    border-radius: 10px;

}


.section h3 {

    margin-top: 0;

    color: #0077cc;

}


/* =====================================================
   TRACKING
===================================================== */

.tracking-step {

    line-height: 45px;

    font-size: 18px;

}


/* =====================================================
   BUTTONS
===================================================== */

.action-button {

    display: inline-block;

    color: white;

    padding: 15px 30px;

    text-decoration: none;

    border-radius: 8px;

    font-size: 18px;

}


.cancel-button {

    background: red;

    margin-right: 15px;

}


.invoice-button {

    background: #007bff;

}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 768px) {

    .container {

        width: 90%;

    }


    .card {

        flex-direction: column;

        align-items: stretch;

    }


    .image {

        width: 100%;

        height: 250px;

    }


    .action-button {

        display: block;

        margin: 10px 0;

        text-align: center;

    }

}

</style>

</head>


<body>


<div class="container">


<!-- =================================================
     PAGE TITLE
================================================= -->

<h1 class="title">

    <i class="fa fa-truck"></i>

    Track Your Order

</h1>



<!-- =================================================
     PRODUCT INFORMATION
================================================= -->

<div class="card">


    <!-- PRODUCT IMAGE -->

    <div class="image">

        <img

            src="./Images/product-icons/<?php

                echo htmlspecialchars(
                    $order['image_url']
                    ?? ''
                );

            ?>"

            alt="<?php

                echo htmlspecialchars(
                    $order['product_name']
                    ?? 'Medicine'
                );

            ?>"

            onerror="
                this.src='./Images/product-icons/Pharmacy-Isometric-Icons-1.png';
            "

        >

    </div>



    <!-- ORDER DETAILS -->

    <div class="details">


        <h2>

            <?php

            echo htmlspecialchars(

                $order['product_name']
                ?? 'Medicine'

            );

            ?>

        </h2>



        <!-- ORDER ID -->

        <div class="info">

            <b>
                Order ID :
            </b>

            #

            <?php

            echo (int)$order['order_id'];

            ?>

        </div>



        <!-- QUANTITY -->

        <div class="info">

            <b>
                Quantity :
            </b>

            <?php

            echo (int)$order['qty'];

            ?>

        </div>



        <!-- TOTAL -->

        <div class="info">

            <b>
                Total :
            </b>

            ₹<?php

            echo number_format(

                (float)$order['Order_total'],

                2

            );

            ?>

        </div>



        <!-- PAYMENT -->

        <div class="info">

            <b>
                Payment :
            </b>

            <?php

            echo htmlspecialchars(

                $order['payment_method']
                ?? 'Not Available'

            );

            ?>

        </div>



        <!-- ORDER DATE -->

        <div class="info">

            <b>
                Order Date :
            </b>

            <?php

            echo htmlspecialchars(

                $order['order_date']
                ?? ''

            );

            ?>

        </div>



        <!-- CURRENT STATUS -->

        <div class="info">

            <b>
                Current Status :
            </b>


            <span
                class="badge <?php echo $statusClass; ?>"
            >

                <?php

                echo htmlspecialchars(
                    $status
                );

                ?>

            </span>

        </div>


    </div>

</div>



<!-- =================================================
     ORDER TRACKING
================================================= -->

<div class="section">


    <h3>

        <i class="fa fa-map-marker-alt"></i>

        Order Tracking

    </h3>


    <div class="tracking-step">


    <?php


    /*
    =================================================
    CANCELLED
    =================================================
    */

    if ($status === "Cancelled") {

        echo "

        <div style='
            color:red;
            font-weight:bold;
            font-size:18px;
        '>

            ❌ Order Cancelled

        </div>

        ";

    }


    /*
    =================================================
    REJECTED
    =================================================
    */

    elseif ($status === "Rejected") {

        echo "

        <div style='
            color:red;
            font-weight:bold;
            font-size:18px;
        '>

            ❌ Order Rejected

        </div>

        ";

    }


    /*
    =================================================
    NORMAL ORDER
    =================================================
    */

    else {


        $steps = array(

            "Pending",

            "Accepted",

            "Packed",

            "Out for Delivery",

            "Delivered"

        );


        $completed = true;


        foreach ($steps as $step) {


            if ($completed) {

                echo "

                <div style='color:green;'>

                    ✔ $step

                </div>

                ";

            }

            else {

                echo "

                <div style='color:gray;'>

                    ○ $step

                </div>

                ";

            }


            if ($status === $step) {

                $completed = false;

            }

        }

    }

    ?>


    </div>

</div>



<!-- =================================================
     DELIVERY ADDRESS
================================================= -->

<div class="section">


    <h3>

        <i class="fa fa-home"></i>

        Delivery Address

    </h3>


    <p>

        <b>
            Name :
        </b>

        <?php

        echo htmlspecialchars(
            $order['receiver_name']
            ?? ''
        );

        ?>

    </p>


    <p>

        <b>
            Street :
        </b>

        <?php

        echo htmlspecialchars(
            $order['street']
            ?? ''
        );

        ?>

    </p>


    <p>

        <b>
            City :
        </b>

        <?php

        echo htmlspecialchars(
            $order['city']
            ?? ''
        );

        ?>

    </p>


    <p>

        <b>
            Postal Code :
        </b>

        <?php

        echo htmlspecialchars(
            $order['postal_code']
            ?? ''
        );

        ?>

    </p>


</div>



<!-- =================================================
     ESTIMATED DELIVERY
================================================= -->

<div class="section">


    <h3>

        <i class="fa fa-calendar"></i>

        Estimated Delivery

    </h3>


    <p
        style="
            font-size:20px;
        "
    >


    <?php


    if ($status === "Delivered") {

        echo "

        <span
            style='
                color:green;
                font-weight:bold;
            '
        >

            Delivered Successfully

        </span>

        ";

    }


    elseif (
        $status === "Rejected" ||
        $status === "Cancelled"
    ) {

        echo "

        <span
            style='
                color:red;
                font-weight:bold;
            '
        >

            Order Cancelled

        </span>

        ";

    }


    else {

        echo "

        <span
            style='
                color:#0077cc;
                font-weight:bold;
            '
        >

            $expected

        </span>

        ";

    }


    ?>


    </p>

</div>



<!-- =================================================
     PAYMENT DETAILS
================================================= -->

<div class="section">


    <h3>

        <i class="fa fa-credit-card"></i>

        Payment Details

    </h3>


    <p>

        <b>
            Payment Method :
        </b>

        <?php

        echo htmlspecialchars(

            $order['payment_method']
            ?? 'Not Available'

        );

        ?>

    </p>


    <?php

    /*
    -----------------------------------------------------
    GET PAYMENT RECORD
    -----------------------------------------------------
    */

    $paymentOrderId =
        (int)$order['order_id'];


    $paymentQuery = mysqli_query(

        $Connection,

        "

        SELECT

            amount,
            bank,
            remark,
            payment_date,
            receipt_url

        FROM payment

        WHERE order_id = '$paymentOrderId'

        LIMIT 1

        "

    );


    if (
        $paymentQuery &&
        mysqli_num_rows($paymentQuery) > 0
    ) {


        $payment =
            mysqli_fetch_assoc(
                $paymentQuery
            );

    ?>

        <p>

            <b>
                Amount Paid :
            </b>

            ₹<?php

            echo number_format(

                (float)$payment['amount'],

                2

            );

            ?>

        </p>


        <?php

        if (
            !empty($payment['bank'])
        ) {

        ?>

            <p>

                <b>
                    Bank :
                </b>

                <?php

                echo htmlspecialchars(
                    $payment['bank']
                );

                ?>

            </p>

        <?php

        }


        if (
            !empty($payment['payment_date'])
        ) {

        ?>

            <p>

                <b>
                    Payment Date :
                </b>

                <?php

                echo htmlspecialchars(
                    $payment['payment_date']
                );

                ?>

            </p>

        <?php

        }

    }

    ?>


</div>



<!-- =================================================
     ACTION BUTTONS
================================================= -->

<div
    style="
        margin-top:30px;
        text-align:center;
    "
>


    <!-- BACK TO MY ORDERS -->

    <a

        href="my_orders.php"

        class="action-button invoice-button"

    >

        <i class="fa fa-box"></i>

        My Orders

    </a>


    <!-- DOWNLOAD INVOICE -->

    <a

        href="download_invoice.php?id=<?php

            echo (int)$order['order_id'];

        ?>"

        class="action-button invoice-button"

        style="margin-left:10px;"

    >

        <i class="fa fa-file-pdf"></i>

        Download Invoice

    </a>


</div>


</div>


</body>

</html>