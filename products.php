<?php

// Sankalpa M M G H C
// IT23538832

/*
=========================================================
PHARMACYX - CUSTOMER PRODUCTS PAGE
=========================================================
Prescription status is controlled ONLY by Pharmacist.

Products.prescription_required:
    Yes = Prescription Required
    No  = Prescription Not Required
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

    header('Location: ./signin.php?role=Customer');

    exit();

}


/*
=========================================================
DATABASE CONNECTION
=========================================================
*/

require_once './db_Config/config.php';

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
        Products | PharmacyX
    </title>


    <link
        rel="stylesheet"
        href="./CSS/index.css"
    >


    <link
        rel="stylesheet"
        href="./CSS/products.css"
    >


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >


    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >


    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet"
    >


    <link
        rel="icon"
        type="image/png"
        sizes="32x32"
        href="./Images/Pharmacy X Icon.png"
    >


    <link
        rel="icon"
        type="image/png"
        sizes="16x16"
        href="./Images/Pharmacy X Icon.png"
    >


    <style>


        /*
        =================================================
        CUSTOMER CART BAR
        =================================================
        */

        .customer-cart-bar {

            width: 100%;

            display: flex;

            justify-content: flex-end;

            align-items: center;

            padding: 12px 40px;

            background: #ffffff;

            border-bottom: 1px solid #eeeeee;

        }


        /*
        =================================================
        CART BUTTON
        =================================================
        */

        .customer-cart-button {

            display: inline-flex;

            align-items: center;

            gap: 10px;

            background: #0077b6;

            color: white;

            text-decoration: none;

            padding: 10px 18px;

            border-radius: 8px;

            font-size: 16px;

            font-weight: 600;

            transition: 0.2s;

        }


        .customer-cart-button:hover {

            background: #005f8f;

            transform: translateY(-1px);

        }


        /*
        =================================================
        PRESCRIPTION INFORMATION
        =================================================
        */

        .prescription-status-box {

            width: 100%;

            margin-top: 12px;

            margin-bottom: 12px;

            padding: 10px 13px;

            border-radius: 7px;

            font-size: 14px;

            line-height: 1.45;

            font-weight: 500;

        }


        /*
        -------------------------------------------------
        PRESCRIPTION REQUIRED
        -------------------------------------------------
        */

        .prescription-required {

            background: #fff1f1;

            color: #b42318;

            border: 1px solid #f2b8b8;

        }


        .prescription-required-title {

            display: block;

            font-weight: 700;

            margin-bottom: 3px;

        }


        /*
        -------------------------------------------------
        PRESCRIPTION NOT REQUIRED
        -------------------------------------------------
        */

        .prescription-not-required {

            background: #edf9f1;

            color: #18794e;

            border: 1px solid #b7e4c7;

        }


        .prescription-not-required-title {

            display: block;

            font-weight: 700;

            margin-bottom: 3px;

        }


        /*
        =================================================
        MOBILE
        =================================================
        */

        @media (max-width: 600px) {

            .customer-cart-bar {

                padding: 10px 15px;

            }


            .customer-cart-button {

                font-size: 14px;

                padding: 9px 14px;

            }


            .prescription-status-box {

                font-size: 13px;

            }

        }

    </style>

</head>


<body>


<?php include("./header.php"); ?>


<!-- =====================================================
     CUSTOMER CART BUTTON
===================================================== -->

<div class="customer-cart-bar">

    <a
        href="cart.php"
        class="customer-cart-button"
    >

        🛒

        <span>
            My Cart
        </span>

    </a>

</div>



<!-- =====================================================
     HERO SECTION
===================================================== -->

<div class="main-image-container">


    <div class="main-heading-container">

        <h1 class="main-heading">

            Quick, Easy, and Affordable

            <br>

            Healthcare Solutions.

        </h1>


        <a href="#products">

            <button class="main-heading-button">

                Shop Now

            </button>

        </a>

    </div>



    <div id="image-container">

        <img
            src="./Images/product-image-slider-1.jpg"
            alt="PharmacyX Healthcare"
            id="main-image"
        >

    </div>


</div>



<!-- =====================================================
     SEARCH BAR
===================================================== -->

<div
    style="
        text-align:center;
        margin:20px;
    "
>


    <form
        action=""
        method="GET"
    >


        <input
            type="text"
            name="search"
            placeholder="Search Medicine..."
            value="<?php

                echo isset($_GET['search'])
                    ? htmlspecialchars(
                        $_GET['search'],
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    : '';

            ?>"
            style="
                width:300px;
                padding:10px;
                border-radius:5px;
                border:1px solid #ccc;
            "
        >


        <button
            type="submit"
            style="
                padding:10px 20px;
                background:#007bff;
                color:white;
                border:none;
                border-radius:5px;
                cursor:pointer;
            "
        >

            Search

        </button>


    </form>


</div>



<!-- =====================================================
     PRODUCTS HEADING
===================================================== -->

<div
    class="products-heading-container"
    id="products"
>

    <h2 class="product-heading">

        Our Medicines

    </h2>

</div>



<?php


/*
=========================================================
SEARCH QUERY
=========================================================
*/

if (
    isset($_GET['search']) &&
    trim($_GET['search']) != ""
) {


    $search = mysqli_real_escape_string(
        $Connection,
        trim($_GET['search'])
    );


    $sql = "

        SELECT *

        FROM products

        WHERE product_name LIKE '%$search%'

        ORDER BY product_id DESC

    ";


} else {


    $sql = "

        SELECT *

        FROM products

        ORDER BY product_id DESC

    ";

}



/*
=========================================================
RUN QUERY
=========================================================
*/

$result = mysqli_query(
    $Connection,
    $sql
);


if (!$result) {

    die(
        "Database Error: "
        . mysqli_error($Connection)
    );

}



/*
=========================================================
PRODUCTS GRID START
=========================================================
*/

echo '<div class="product-content-container">';



/*
=========================================================
CHECK PRODUCTS
=========================================================
*/

if (
    mysqli_num_rows($result) == 0
) {


    echo '

        <div style="
            grid-column:1 / -1;
            text-align:center;
            padding:50px;
            font-size:22px;
            color:#777;
        ">

            No medicines found.

        </div>

    ';


} else {


    /*
    =====================================================
    PRODUCT LOOP
    =====================================================
    */

    while (
        $row = mysqli_fetch_assoc($result)
    ) {


        /*
        =================================================
        PRODUCT INFORMATION
        =================================================
        */

        $productName = htmlspecialchars(
            $row['product_name'],
            ENT_QUOTES,
            'UTF-8'
        );


        $productDescription = htmlspecialchars(
            $row['product_description'],
            ENT_QUOTES,
            'UTF-8'
        );


        $price = htmlspecialchars(
            $row['price'],
            ENT_QUOTES,
            'UTF-8'
        );


        $product_id = intval(
            $row['product_id']
        );


        /*
        =================================================
        IMAGE
        =================================================
        */

        $img = trim(
            $row['image_url']
        );


        if ($img !== "") {


            /*
            Database normally contains only:

                OIP.webp

            Actual location:

                Images/product-icons/OIP.webp
            */

            $image_URL =
                "./Images/product-icons/"
                . basename(
                    str_replace(
                        "\\",
                        "/",
                        $img
                    )
                );


        } else {


            $image_URL =
                "./Images/product-icons/"
                . "Pharmacy-Isometric-Icons-1.png";

        }



        /*
        =================================================
        PRESCRIPTION STATUS
        =================================================
        */

        $prescriptionRequired =
            isset(
                $row['prescription_required']
            )
            ? $row['prescription_required']
            : 'No';


        ?>


        <!-- =================================================
             PRODUCT CARD
        ================================================= -->

        <div class="products-container">


            <!-- =============================================
                 PRODUCT IMAGE
            ============================================== -->

            <div class="product-image-container">


                <img
                    src="<?php
                        echo htmlspecialchars(
                            $image_URL,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?>"
                    alt="<?php
                        echo $productName;
                    ?>"
                    class="item-image"
                >


            </div>



            <!-- =============================================
                 PRODUCT DETAILS
            ============================================== -->

            <div class="product-details-container">


                <!-- =========================================
                     PRODUCT NAME
                ========================================== -->

                <p class="item-name">

                    <?php

                    echo $productName;

                    ?>

                </p>



                <!-- =========================================
                     PRODUCT DESCRIPTION
                ========================================== -->

                <p class="product-discription">

                    <?php

                    echo $productDescription;

                    ?>

                </p>



                <!-- =========================================
                     PRESCRIPTION STATUS
                ========================================== -->

                <?php if (
                    $prescriptionRequired === 'Yes'
                ): ?>


                    <div
                        class="
                            prescription-status-box
                            prescription-required
                        "
                    >


                        <span
                            class="
                                prescription-required-title
                            "
                        >

                            ⚠️ Prescription Required

                        </span>


                        <span>

                            A valid prescription is required
                            before purchasing this medicine.

                        </span>


                    </div>


                <?php else: ?>


                    <div
                        class="
                            prescription-status-box
                            prescription-not-required
                        "
                    >


                        <span
                            class="
                                prescription-not-required-title
                            "
                        >

                            ✓ Prescription Not Required

                        </span>


                        <span>

                            This medicine can be purchased
                            normally without a prescription.

                        </span>


                    </div>


                <?php endif; ?>



                <!-- =========================================
                     PRICE
                ========================================== -->

                <p class="item-price">

                    Rs.
                    <?php

                    echo $price;

                    ?>

                </p>



                <!-- =========================================
                     BUTTONS
                ========================================== -->

                <div class="product-buttons">


                    <!-- =====================================
                         ADD TO CART
                    ====================================== -->

                    <form
                        action="add_to_cart.php"
                        method="POST"
                    >


                        <input
                            type="hidden"
                            name="product_id"
                            value="<?php
                                echo $product_id;
                            ?>"
                        >


                        <input
                            type="hidden"
                            name="product_name"
                            value="<?php
                                echo $productName;
                            ?>"
                        >


                        <input
                            type="hidden"
                            name="price"
                            value="<?php
                                echo $price;
                            ?>"
                        >


                        <button
                            type="submit"
                            name="cartbtn"
                            class="
                                item-buynow-button
                                cart-button
                            "
                        >

                            🛒 Add To Cart

                        </button>


                    </form>



                    <!-- =====================================
                         BUY NOW
                    ====================================== -->

                    <form
                        action="order_product.php"
                        method="POST"
                    >


                        <input
                            type="hidden"
                            name="prdct_id"
                            value="<?php
                                echo $product_id;
                            ?>"
                        >


                        <button
                            type="submit"
                            name="buynowbtn"
                            class="
                                item-buynow-button
                                buy-button
                            "
                        >

                            ⚡ Buy Now

                        </button>


                    </form>


                </div>


            </div>


        </div>


        <?php

    }

}


/*
=========================================================
PRODUCTS GRID END
=========================================================
*/

echo '</div>';

?>


<?php include("./footer.php"); ?>


<script src="./JS/products.js"></script>


</body>

</html>