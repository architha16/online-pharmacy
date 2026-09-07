<?php

/*
=========================================================
PHARMACYX - MY ORDERS
CUSTOMER ORDER HISTORY
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
GET LOGGED-IN CUSTOMER
=========================================================
*/

$user = $_SESSION['username'];


/*
=========================================================
SECURE USERNAME FOR DATABASE QUERY
=========================================================
*/

$userEscaped = mysqli_real_escape_string(
    $Connection,
    $user
);


/*
=========================================================
GET ALL ORDERS OF LOGGED-IN CUSTOMER
=========================================================
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

    p.product_name,
    p.product_description,
    p.price,
    p.image_url

FROM orders o

LEFT JOIN products p
    ON o.product_id = p.product_id

WHERE o.user_name = '$userEscaped'

ORDER BY o.order_id DESC

";


$result = mysqli_query(
    $Connection,
    $query
);


if (!$result) {

    die(
        "Order Query Error: " .
        htmlspecialchars(
            mysqli_error($Connection)
        )
    );

}

/* =========================================================
   APPROVED PRESCRIPTIONS WAITING FOR PAYMENT
   ========================================================= */

$approvedPrescriptionQuery = "
    SELECT
        pr.id AS prescription_id,
        pr.product_id,
        pr.patient_name,
        pr.mobile,
        pr.prescription_file,
        pr.status,
        pr.upload_date,
        p.product_name,
        p.product_description,
        p.price,
        p.image_url,
        p.stock_quantity
    FROM prescriptions pr
    LEFT JOIN products p
        ON pr.product_id = p.product_id
    WHERE pr.user_name = '$userEscaped'
      AND pr.status = 'Approved'
      AND NOT EXISTS (
          SELECT 1
          FROM orders o
          WHERE o.prescription_id = pr.id
      )
    ORDER BY pr.id DESC
";

$approvedPrescriptionResult = mysqli_query(
    $Connection,
    $approvedPrescriptionQuery
);

if (!$approvedPrescriptionResult) {
    die(
        "Approved Prescription Query Error: " .
        htmlspecialchars(
            mysqli_error($Connection)
        )
    );
}

$hasOrders = mysqli_num_rows($result) > 0;
$hasApprovedPrescriptions = mysqli_num_rows($approvedPrescriptionResult) > 0;

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
    My Orders - PharmacyX
</title>


<!-- =====================================================
     FONT AWESOME
===================================================== -->

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
   MAIN CONTAINER
===================================================== */

.container {

    width: 85%;

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
   ORDER CARD
===================================================== */

.order-card {

    background: white;

    padding: 25px;

    margin-bottom: 25px;

    border-radius: 12px;

    box-shadow:
        0 0 12px rgba(0,0,0,0.12);

}


/* =====================================================
   ORDER HEADER
===================================================== */

.order-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    border-bottom: 1px solid #ddd;

    padding-bottom: 15px;

    margin-bottom: 20px;

}


.order-id {

    font-size: 20px;

    font-weight: bold;

    color: #0077cc;

}


/* =====================================================
   STATUS
===================================================== */

.status {

    padding: 8px 16px;

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


/* Shipped */

.shipped {

    background: #673ab7;

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
   PRODUCT
===================================================== */

.product {

    display: flex;

    gap: 25px;

    align-items: center;

}


/* =====================================================
   PRODUCT IMAGE
===================================================== */

.product-image {

    width: 150px;

    height: 150px;

    border: 1px solid #ddd;

    border-radius: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: white;

    overflow: hidden;

    flex-shrink: 0;

}


.product-image img {

    width: 100%;

    height: 100%;

    object-fit: contain;

}


/* =====================================================
   PRODUCT DETAILS
===================================================== */

.product-details {

    flex: 1;

}


.product-details h2 {

    margin-top: 0;

    color: #222;

}


.description {

    color: #666;

    margin: 10px 0;

}


.info {

    margin: 8px 0;

    font-size: 16px;

}


.total {

    color: green;

    font-size: 20px;

    font-weight: bold;

}


/* =====================================================
   BUTTONS
===================================================== */

.buttons {

    margin-top: 20px;

    display: flex;

    gap: 10px;

    flex-wrap: wrap;

}


.btn {

    display: inline-block;

    padding: 12px 20px;

    color: white;

    text-decoration: none;

    border-radius: 7px;

    font-size: 15px;

    border: none;

    cursor: pointer;

}


/* Track */

.track-btn {

    background: #0077cc;

}


.track-btn:hover {

    background: #005fa3;

}


/* Cancel */

.cancel-btn {

    background: #dc3545;

}


.cancel-btn:hover {

    background: #b02a37;

}


/* Invoice */

.invoice-btn {

    background: #28a745;

}


.invoice-btn:hover {

    background: #1e7e34;

}


/* =====================================================
   NO ORDERS
===================================================== */

.no-orders {

    background: white;

    padding: 40px;

    text-align: center;

    border-radius: 12px;

    font-size: 20px;

}


/* =====================================================
   PRESCRIPTION PAYMENT CARD
   ===================================================== */

.prescription-payment-card {
    background: linear-gradient(135deg, #ffffff 0%, #f5fbff 100%);
    border: 2px solid #28a745;
    padding: 25px;
    margin-bottom: 25px;
    border-radius: 12px;
    box-shadow: 0 0 12px rgba(0,0,0,0.10);
}

.prescription-payment-badge {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 20px;
    background: #fff3cd;
    color: #856404;
    font-weight: bold;
    margin-left: 10px;
}

.prescription-payment-card .payment-message {
    margin-top: 15px;
    padding: 15px;
    border-left: 5px solid #28a745;
    background: #eaf8ee;
    color: #176b35;
    border-radius: 6px;
    line-height: 1.6;
}

.pay-now-btn {
    background: #28a745;
}

.pay-now-btn:hover {
    background: #1e7e34;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:700px) {

    .container {

        width: 95%;

    }


    .product {

        flex-direction: column;

        align-items: flex-start;

    }


    .product-image {

        width: 130px;

        height: 130px;

    }


    .order-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 10px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     MAIN CONTAINER
===================================================== -->

<div class="container">


<h1 class="title">

    <i class="fa fa-box"></i>

    My Orders

</h1>


<?php

/*
=========================================================
CHECK WHETHER CUSTOMER HAS ORDERS
=========================================================
*/

if (!$hasOrders && !$hasApprovedPrescriptions) {

?>

    <div class="no-orders">

        <i
            class="fa fa-shopping-bag"
            style="font-size:50px;color:#0077cc;"
        ></i>


        <h2>
            No Orders Found
        </h2>


        <p>
            You have not placed any orders or approved prescriptions waiting for payment yet.
        </p>

    </div>

<?php

}


/*
=========================================================
DISPLAY ALL ORDERS
=========================================================
*/

while ($order = mysqli_fetch_assoc($result)) {


    /*
    -----------------------------------------------------
    ORDER STATUS
    -----------------------------------------------------
    */

    $status =
        $order['order_status'] ?? 'Pending';


    /*
    -----------------------------------------------------
    DEFAULT STATUS CLASS
    -----------------------------------------------------
    */

    $statusClass = "pending";


    /*
    -----------------------------------------------------
    STATUS CLASS
    -----------------------------------------------------
    */

    switch ($status) {


        case "Accepted":

            $statusClass = "accepted";

            break;


        case "Packed":

            $statusClass = "packed";

            break;


        case "Shipped":

            $statusClass = "shipped";

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


    /*
    -----------------------------------------------------
    PRODUCT IMAGE
    -----------------------------------------------------
    */

    $image =
        $order['image_url'] ?? '';


    $imagePath =
        "./Images/product-icons/" .
        $image;


?>


<!-- =====================================================
     ORDER CARD
===================================================== -->

<div class="order-card">


    <!-- =================================================
         ORDER HEADER
    ================================================= -->

    <div class="order-header">


        <div class="order-id">

            Order #

            <?php

            echo htmlspecialchars(
                $order['order_id']
            );

            ?>

        </div>


        <div
            class="status <?php echo $statusClass; ?>"
        >

            <?php

            echo htmlspecialchars(
                $status
            );

            ?>

        </div>


    </div>



    <!-- =================================================
         PRODUCT
    ================================================= -->

    <div class="product">


        <!-- =============================================
             PRODUCT IMAGE
        ============================================== -->

        <div class="product-image">


        <?php

        if (!empty($image)) {

        ?>

            <img

                src="<?php

                    echo htmlspecialchars(
                        $imagePath,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                ?>"

                alt="<?php

                    echo htmlspecialchars(
                        $order['product_name']
                        ?? 'Medicine',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                ?>"

                onerror="this.src='./Images/product-icons/Pharmacy-Isometric-Icons-1.png';"

            >

        <?php

        }

        else {

        ?>

            <img

                src="./Images/product-icons/Pharmacy-Isometric-Icons-1.png"

                alt="Medicine"

            >

        <?php

        }

        ?>

        </div>



        <!-- =============================================
             PRODUCT DETAILS
        ============================================== -->

        <div class="product-details">


            <!-- PRODUCT NAME -->

            <h2>

                <?php

                echo htmlspecialchars(

                    $order['product_name']
                    ?? 'Medicine'

                );

                ?>

            </h2>



            <!-- DESCRIPTION -->

            <div class="description">

                <?php

                echo htmlspecialchars(

                    $order['product_description']
                    ?? 'Medicine product'

                );

                ?>

            </div>



            <!-- PRICE -->

            <div class="info">

                <b>
                    Price:
                </b>

                ₹<?php

                echo number_format(

                    (float)$order['price'],

                    2

                );

                ?>

            </div>



            <!-- QUANTITY -->

            <div class="info">

                <b>
                    Quantity:
                </b>

                <?php

                echo htmlspecialchars(
                    $order['qty']
                );

                ?>

            </div>



            <!-- ORDER TOTAL -->

            <div class="info total">

                <b>
                    Order Total:
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
                    Payment:
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
                    Order Date:
                </b>

                <?php

                echo htmlspecialchars(

                    $order['order_date']
                    ?? ''

                );

                ?>

            </div>



            <!-- =================================================
                 ACTION BUTTONS
            ================================================= -->

            <div class="buttons">


                <!-- =========================================
                     TRACK ORDER
                ========================================== -->

                <a

                    class="btn track-btn"

                    href="track_order.php?id=<?php

                        echo (int)$order['order_id'];

                    ?>"

                >

                    <i class="fa fa-truck"></i>

                    Track Order

                </a>



                <!-- =========================================
                     CANCEL ORDER
                ========================================== -->

                <?php

                if (

                    $status === "Pending" ||

                    $status === "Accepted"

                ) {

                ?>

                    <a

                        class="btn cancel-btn"

                        href="cancel_order.php?id=<?php

                            echo (int)$order['order_id'];

                        ?>"

                        onclick="return confirm('Are you sure you want to cancel this order?');"

                    >

                        <i class="fa fa-times"></i>

                        Cancel Order

                    </a>

                <?php

                }

                ?>



                <!-- =========================================
                     DOWNLOAD INVOICE
                ========================================== -->

                <a

                    class="btn invoice-btn"

                    href="download_invoice.php?id=<?php

                        echo (int)$order['order_id'];

                    ?>"

                >

                    <i class="fa fa-file-pdf"></i>

                    Download Invoice

                </a>


            </div>


        </div>


    </div>


</div>


<?php

}

?>


<?php if ($hasApprovedPrescriptions): ?>

    <h2 style="margin:35px 0 18px; color:#0878d1;">
        <i class="fa fa-prescription-bottle-medical"></i>
        Medicines Ready for Payment
    </h2>

    <?php while ($prescription = mysqli_fetch_assoc($approvedPrescriptionResult)): ?>

        <?php
            $prescriptionId = (int)$prescription['prescription_id'];
            $prescriptionProductName = $prescription['product_name'] ?? 'Medicine';
            $prescriptionDescription = $prescription['product_description'] ?? 'Prescription medicine';
            $prescriptionPrice = (float)($prescription['price'] ?? 0);
            $prescriptionImage = trim($prescription['image_url'] ?? '');
            $prescriptionImagePath = './Images/product-icons/' . $prescriptionImage;
        ?>

        <div class="prescription-payment-card">
            <div class="order-header">
                <div class="order-id">
                    Prescription #<?php echo $prescriptionId; ?>
                    <span class="prescription-payment-badge">Payment Required</span>
                </div>
            </div>

            <div class="product">
                <div class="product-image">
                    <?php if ($prescriptionImage !== ''): ?>
                        <img
                            src="<?php echo htmlspecialchars($prescriptionImagePath, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($prescriptionProductName, ENT_QUOTES, 'UTF-8'); ?>"
                            onerror="this.src='./Images/product-icons/Pharmacy-Isometric-Icons-1.png';"
                        >
                    <?php else: ?>
                        <img src="./Images/product-icons/Pharmacy-Isometric-Icons-1.png" alt="Medicine">
                    <?php endif; ?>
                </div>

                <div class="product-details">
                    <h2><?php echo htmlspecialchars($prescriptionProductName, ENT_QUOTES, 'UTF-8'); ?></h2>

                    <div class="description">
                        <?php echo htmlspecialchars($prescriptionDescription, ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                    <div class="info">
                        <b>Price:</b> ₹<?php echo number_format($prescriptionPrice, 2); ?>
                    </div>

                    <div class="info">
                        <b>Prescription Status:</b>
                        <span style="color:#28a745;font-weight:bold;">Approved</span>
                    </div>

                    <div class="info">
                        <b>Payment Status:</b>
                        <span style="color:#856404;font-weight:bold;">Payment Required</span>
                    </div>

                    <div class="payment-message">
                        <strong>✅ Pharmacist Approved</strong><br>
                        Your prescription has been approved. Complete payment to create and place your medicine order.
                    </div>

                    <div class="buttons">
                        <a class="btn pay-now-btn" href="prescription_status.php?continue_payment=<?php echo $prescriptionId; ?>">
                            <i class="fa fa-credit-card"></i> Pay Now
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php endwhile; ?>

<?php endif; ?>

</div>


</body>

</html>