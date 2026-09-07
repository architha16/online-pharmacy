<?php

/*
=========================================================
PHARMACYX - CUSTOMER CART
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
CHECK CUSTOMER LOGIN
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
CUSTOMER USERNAME
=========================================================
*/

$user = $_SESSION['username'];

$userEscaped = mysqli_real_escape_string(
    $Connection,
    $user
);


/*
=========================================================
GET CUSTOMER CART
=========================================================
*/

$sql = "

    SELECT *

    FROM cart

    WHERE user_name='$userEscaped'

    ORDER BY cart_id DESC

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


/*
=========================================================
TOTAL
=========================================================
*/

$total = 0;


/*
=========================================================
BUY STATUS

If any prescription medicine does not have
an Approved prescription, checkout is locked.
=========================================================
*/

$canBuy = true;

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
    My Cart | PharmacyX
</title>


<!-- GOOGLE FONT -->

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>

/*
=========================================================
RESET
=========================================================
*/

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

    font-family: 'Poppins', sans-serif;

}


/*
=========================================================
BODY
=========================================================
*/

body {

    background: #eef6ff;

    min-height: 100vh;

}


/*
=========================================================
HEADER
=========================================================
*/

.header {

    background: #0077b6;

    color: white;

    padding: 20px;

    text-align: center;

    font-size: 30px;

    font-weight: bold;

    box-shadow: 0 4px 10px rgba(0,0,0,.2);

}


/*
=========================================================
MAIN CONTAINER
=========================================================
*/

.container {

    width: 95%;

    max-width: 1250px;

    margin: 40px auto;

}


/*
=========================================================
TOP NAVIGATION
=========================================================
*/

.top-navigation {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;

}


.back-products {

    display: inline-block;

    background: #0077b6;

    color: white;

    padding: 10px 18px;

    border-radius: 7px;

    text-decoration: none;

    font-weight: 600;

}


.back-products:hover {

    background: #005f8c;

}


/*
=========================================================
TABLE
=========================================================
*/

.table-wrapper {

    width: 100%;

    overflow-x: auto;

}


table {

    width: 100%;

    min-width: 950px;

    border-collapse: collapse;

    background: white;

    border-radius: 10px;

    overflow: hidden;

    box-shadow: 0 10px 25px rgba(0,0,0,.15);

}


th {

    background: #0077b6;

    color: white;

    padding: 18px;

    font-size: 16px;

}


td {

    padding: 18px;

    text-align: center;

    border-bottom: 1px solid #eee;

}


tr:hover {

    background: #f7fbff;

}


/*
=========================================================
MEDICINE NAME
=========================================================
*/

.medicineName {

    font-weight: bold;

    color: #333;

    font-size: 16px;

}


/*
=========================================================
QUANTITY BUTTONS
=========================================================
*/

.qtyBtn {

    display: inline-block;

    background: #0077b6;

    color: white;

    padding: 5px 12px;

    border-radius: 5px;

    text-decoration: none;

    font-size: 18px;

    margin: 5px;

    font-weight: bold;

}


.qtyBtn:hover {

    background: #005f8c;

}


/*
=========================================================
REMOVE BUTTON
=========================================================
*/

.removeBtn {

    display: inline-block;

    background: #dc3545;

    color: white;

    padding: 8px 15px;

    text-decoration: none;

    border-radius: 5px;

}


.removeBtn:hover {

    background: #b52b3b;

}


/*
=========================================================
PRESCRIPTION
=========================================================
*/

.prescription-required {

    display: inline-block;

    background: #fff3cd;

    color: #856404;

    padding: 6px 10px;

    border-radius: 6px;

    font-size: 13px;

    font-weight: bold;

    margin-top: 5px;

}


.uploadBtn {

    display: inline-block;

    background: #dc3545;

    color: white;

    padding: 9px 14px;

    border-radius: 6px;

    text-decoration: none;

    font-weight: bold;

    margin-top: 8px;

}


.uploadBtn:hover {

    background: #b52b3b;

}


/*
=========================================================
PENDING
=========================================================
*/

.pending {

    display: inline-block;

    background: #fff3cd;

    color: #856404;

    padding: 8px 12px;

    border-radius: 6px;

    font-weight: bold;

    font-size: 13px;

}


/*
=========================================================
APPROVED
=========================================================
*/

.approved {

    display: inline-block;

    background: #d4edda;

    color: #155724;

    padding: 8px 12px;

    border-radius: 6px;

    font-weight: bold;

    font-size: 13px;

}


/*
=========================================================
REJECTED
=========================================================
*/

.rejected {

    display: inline-block;

    background: #f8d7da;

    color: #721c24;

    padding: 8px 12px;

    border-radius: 6px;

    font-weight: bold;

    font-size: 13px;

}


/*
=========================================================
NO PRESCRIPTION
=========================================================
*/

.no-prescription {

    color: #198754;

    font-weight: bold;

}


/*
=========================================================
GRAND TOTAL
=========================================================
*/

.totalRow {

    background: #d4edda;

    font-size: 20px;

    font-weight: bold;

}


.totalRow td {

    padding: 20px;

}


/*
=========================================================
CHECKOUT AREA
=========================================================
*/

.checkout {

    text-align: center;

    margin-top: 30px;

    padding-bottom: 40px;

}


/*
=========================================================
BUY BUTTON
=========================================================
*/

.buyBtn {

    display: inline-block;

    background: #28a745;

    color: white;

    padding: 15px 40px;

    font-size: 22px;

    border-radius: 8px;

    text-decoration: none;

    font-weight: bold;

}


.buyBtn:hover {

    background: #1f8a38;

}


/*
=========================================================
DISABLED BUY BUTTON
=========================================================
*/

.disabledBuyBtn {

    display: inline-block;

    background: #999;

    color: white;

    padding: 15px 40px;

    font-size: 20px;

    border-radius: 8px;

    font-weight: bold;

    cursor: not-allowed;

}


/*
=========================================================
CONTINUE SHOPPING
=========================================================
*/

.continueBtn {

    display: inline-block;

    background: #0077b6;

    color: white;

    padding: 15px 30px;

    font-size: 18px;

    border-radius: 8px;

    text-decoration: none;

    margin-right: 20px;

}


.continueBtn:hover {

    background: #005f8c;

}


/*
=========================================================
WARNING
=========================================================
*/

.warningBox {

    background: #fff3cd;

    color: #856404;

    border: 1px solid #ffeeba;

    padding: 18px;

    margin-top: 25px;

    border-radius: 8px;

    text-align: center;

    font-weight: bold;

}


/*
=========================================================
SUCCESS
=========================================================
*/

.successBox {

    background: #d4edda;

    color: #155724;

    border: 1px solid #c3e6cb;

    padding: 18px;

    margin-top: 25px;

    border-radius: 8px;

    text-align: center;

    font-weight: bold;

}


/*
=========================================================
EMPTY CART
=========================================================
*/

.empty {

    text-align: center;

    font-size: 22px;

    padding: 50px;

    background: white;

    border-radius: 10px;

    box-shadow: 0 10px 25px rgba(0,0,0,.12);

}


/*
=========================================================
EMPTY CART ICON
=========================================================
*/

.empty-icon {

    font-size: 55px;

    margin-bottom: 15px;

}


/*
=========================================================
RESPONSIVE
=========================================================
*/

@media (max-width: 700px) {

    .header {

        font-size: 23px;

    }


    .container {

        width: 96%;

        margin-top: 25px;

    }


    .continueBtn {

        margin-right: 0;

        margin-bottom: 15px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">

    🛒 PharmacyX Shopping Cart

</div>



<!-- =====================================================
     MAIN CONTAINER
===================================================== -->

<div class="container">


<?php

/*
=========================================================
EMPTY CART
=========================================================
*/

if (
    mysqli_num_rows($result) == 0
) {

?>

    <div class="empty">

        <div class="empty-icon">

            🛒

        </div>


        <div>

            Your cart is empty.

        </div>


        <br>


        <a
            class="continueBtn"
            href="products.php"
        >

            Continue Shopping

        </a>

    </div>

<?php

}

else {

?>


<!-- =====================================================
     TOP NAVIGATION
===================================================== -->

<div class="top-navigation">

    <a
        href="products.php"
        class="back-products"
    >

        ← Continue Shopping

    </a>

</div>



<!-- =====================================================
     CART TABLE
===================================================== -->

<div class="table-wrapper">

<table>

<thead>

<tr>

    <th>
        Medicine
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

    <th>
        Prescription
    </th>

    <th>
        Action
    </th>

</tr>

</thead>


<tbody>


<?php


/*
=========================================================
LOOP THROUGH CART
=========================================================
*/

while (
    $row = mysqli_fetch_assoc($result)
) {


    /*
    =====================================================
    CART PRODUCT ID
    =====================================================
    */

    $productId =
        (int)$row['product_id'];


    /*
    =====================================================
    GET PRODUCT INFORMATION
    =====================================================
    */

    $productSql = "

        SELECT

            product_name,

            prescription_required

        FROM products

        WHERE product_id='$productId'

        LIMIT 1

    ";


    $productResult =
        mysqli_query(
            $Connection,
            $productSql
        );


    /*
    =====================================================
    DEFAULT VALUES
    =====================================================
    */

    $prescriptionRequired = false;

    $productName =
        $row['product_name'];


    /*
    =====================================================
    PRODUCT EXISTS
    =====================================================
    */

    if (
        $productResult &&
        mysqli_num_rows($productResult) > 0
    ) {

        $product =
            mysqli_fetch_assoc(
                $productResult
            );


        $productName =
            $product['product_name'];


        /*
        =================================================
        CHECK PRESCRIPTION
        =================================================
        */

        if (
            isset($product['prescription_required']) &&
            $product['prescription_required'] === 'Yes'
        ) {

            $prescriptionRequired = true;

        }

    }


    /*
    =====================================================
    ITEM TOTAL
    =====================================================
    */

    $itemTotal =
        $row['price'] *
        $row['quantity'];


    $total +=
        $itemTotal;


    /*
    =====================================================
    PRESCRIPTION STATUS
    =====================================================
    */

    $prescriptionStatus = "";


    if (
        $prescriptionRequired
    ) {


        /*
        =================================================
        GET LATEST PRESCRIPTION
        =================================================
        */

        $prescriptionQuery = "

            SELECT

                status

            FROM prescriptions

            WHERE user_name='$userEscaped'

            AND product_id='$productId'

            ORDER BY id DESC

            LIMIT 1

        ";


        $prescriptionResult =
            mysqli_query(
                $Connection,
                $prescriptionQuery
            );


        if (
            $prescriptionResult &&
            mysqli_num_rows(
                $prescriptionResult
            ) > 0
        ) {

            $prescriptionRow =
                mysqli_fetch_assoc(
                    $prescriptionResult
                );


            $prescriptionStatus =
                $prescriptionRow['status'];

        }


        /*
        =================================================
        NOT APPROVED = CANNOT BUY
        =================================================
        */

        if (
            $prescriptionStatus !== 'Approved'
        ) {

            $canBuy = false;

        }

    }


?>


<tr>


<!-- ===================================================
     MEDICINE
=================================================== -->

<td>

    <div class="medicineName">

        <?php

        echo htmlspecialchars(
            $productName
        );

        ?>

    </div>

</td>



<!-- ===================================================
     PRICE
=================================================== -->

<td>

    ₹<?php

    echo number_format(
        $row['price'],
        2
    );

    ?>

</td>



<!-- ===================================================
     QUANTITY
=================================================== -->

<td>


<a
    class="qtyBtn"
    href="minus.php?id=<?php echo (int)$row['cart_id']; ?>"
>

    −

</a>


<b>

    <?php

    echo (int)$row['quantity'];

    ?>

</b>


<a
    class="qtyBtn"
    href="plus.php?id=<?php echo (int)$row['cart_id']; ?>"
>

    +

</a>


</td>



<!-- ===================================================
     ITEM TOTAL
=================================================== -->

<td>

    ₹<?php

    echo number_format(
        $itemTotal,
        2
    );

    ?>

</td>



<!-- ===================================================
     PRESCRIPTION
=================================================== -->

<td>


<?php

if (
    $prescriptionRequired
) {

?>


<div class="prescription-required">

    🩺 Prescription Required

</div>


<br>


<?php


/*
=========================================================
NO PRESCRIPTION
=========================================================
*/

if (
    $prescriptionStatus === ""
) {

?>

    <a
        class="uploadBtn"
        href="prescription_upload.php?product_id=<?php echo $productId; ?>"
    >

        📄 Upload Prescription

    </a>

<?php


}


/*
=========================================================
PENDING
=========================================================
*/

elseif (
    $prescriptionStatus === 'Pending'
) {

?>

    <div class="pending">

        ⏳ Pending Approval

    </div>

<?php


}


/*
=========================================================
APPROVED
=========================================================
*/

elseif (
    $prescriptionStatus === 'Approved'
) {

?>

    <div class="approved">

        ✅ Approved

    </div>

<?php


}


/*
=========================================================
REJECTED
=========================================================
*/

elseif (
    $prescriptionStatus === 'Rejected'
) {

?>

    <div class="rejected">

        ❌ Rejected

    </div>


    <br>


    <a
        class="uploadBtn"
        href="prescription_upload.php?product_id=<?php echo $productId; ?>"
    >

        📄 Upload Again

    </a>

<?php

}

?>


<?php

}

else {

?>

    <span class="no-prescription">

        ✓ No Prescription Required

    </span>

<?php

}

?>


</td>



<!-- ===================================================
     REMOVE
=================================================== -->

<td>

    <a
        class="removeBtn"
        href="delete.php?id=<?php echo (int)$row['cart_id']; ?>"
        onclick="return confirm('Remove this item from your cart?')"
    >

        🗑 Remove

    </a>

</td>


</tr>


<?php

}

?>


<!-- ===================================================
     GRAND TOTAL
=================================================== -->

<tr class="totalRow">

    <td colspan="3">

        Grand Total

    </td>


    <td>

        ₹<?php

        echo number_format(
            $total,
            2
        );

        ?>

    </td>


    <td colspan="2">

    </td>

</tr>


</tbody>

</table>

</div>



<?php

/*
=========================================================
PRESCRIPTION WARNING
=========================================================
*/

if (
    !$canBuy
) {

?>

<div class="warningBox">

    ⚠️

    <br><br>

    One or more medicines in your cart require
    an approved prescription.

    <br><br>

    Please upload your prescription and wait for
    the pharmacist to approve it before proceeding
    to payment.

</div>

<?php

}

else {

?>

<div class="successBox">

    ✅

    <br><br>

    All required prescriptions have been approved.

    <br>

    You can proceed to payment.

</div>

<?php

}

?>



<!-- ===================================================
     CHECKOUT
=================================================== -->

<div class="checkout">


<a
    href="products.php"
    class="continueBtn"
>

    ← Continue Shopping

</a>


<?php

if (
    $canBuy
) {

?>

<a
    href="paymentpage.php"
    class="buyBtn"
>

    💳 Buy Now

</a>

<?php

}

else {

?>

<span class="disabledBuyBtn">

    🔒 Buy Now Locked

</span>

<?php

}

?>


</div>


<?php

}

?>


</div>


</body>

</html>