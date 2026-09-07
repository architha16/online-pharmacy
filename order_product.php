<?php

// =========================================================
// PHARMACYX - ORDER PRODUCT / BUY NOW
// =========================================================

// Customer session
session_name("PHARMACYX_CUSTOMER");
session_start();


// =========================================================
// CHECK CUSTOMER LOGIN
// =========================================================

if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Customer'
) {
    header("Location: signin.php?role=Customer");
    exit();
}


// =========================================================
// DATABASE
// =========================================================

require_once "./db_Config/config.php";


// =========================================================
// GET PRODUCT ID
// =========================================================

$product_id = 0;

if (isset($_POST['buynowbtn'])) {

    $product_id = isset($_POST['prdct_id'])
        ? (int)$_POST['prdct_id']
        : 0;

} elseif (isset($_GET['prdct_id'])) {

    $product_id = (int)$_GET['prdct_id'];
}


// =========================================================
// VALIDATE PRODUCT ID
// =========================================================

if ($product_id <= 0) {

    header("Location: products.php");
    exit();
}


// =========================================================
// GET PRODUCT DETAILS
// =========================================================

$productSql = "
    SELECT *
    FROM products
    WHERE product_id = ?
    LIMIT 1
";

$productStmt = mysqli_prepare(
    $Connection,
    $productSql
);

if (!$productStmt) {

    die(
        "Product Query Error: " .
        mysqli_error($Connection)
    );
}

mysqli_stmt_bind_param(
    $productStmt,
    "i",
    $product_id
);

mysqli_stmt_execute(
    $productStmt
);

$productResult = mysqli_stmt_get_result(
    $productStmt
);


if (
    !$productResult ||
    mysqli_num_rows($productResult) === 0
) {

    die("Medicine not found.");
}


$product = mysqli_fetch_assoc(
    $productResult
);

mysqli_stmt_close(
    $productStmt
);


// =========================================================
// PRODUCT INFORMATION
// =========================================================

$productName =
    $product['product_name'];

$productDescription =
    $product['product_description'];

$productPrice =
    (float)$product['price'];

$productStock =
    (int)$product['stock_quantity'];

$productImage =
    trim(
        $product['image_url'] ?? ''
    );

$prescriptionRequired =
    (
        ($product['prescription_required'] ?? 'No')
        === 'Yes'
    );


// =========================================================
// CUSTOMER
// =========================================================

$username =
    $_SESSION['username'];


// =========================================================
// STOCK CHECK
// =========================================================

if ($productStock <= 0) {

    echo "<script>

        alert('Sorry, this medicine is currently out of stock.');

        window.location='products.php';

    </script>";

    exit();
}


// =========================================================
// IMAGE PATH
// =========================================================

if ($productImage !== '') {

    if (
        strpos(
            $productImage,
            'Images/'
        ) === 0
        ||
        strpos(
            $productImage,
            './Images/'
        ) === 0
    ) {

        $imageSrc =
            $productImage;

    } else {

        $imageSrc =
            "./Images/product-icons/" .
            basename($productImage);
    }

} else {

    $imageSrc =
        "./Images/product-icons/Pharmacy-Isometric-Icons-1.png";
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
        Order Now | PharmacyX
    </title>


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="./CSS/index.css"
    >


    <!-- Order Product CSS -->

    <link
        rel="stylesheet"
        href="./CSS/order_product.css"
    >


    <!-- Google Font -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet"
    >


    <!-- Favicon -->

    <link
        rel="icon"
        type="image/png"
        sizes="32x32"
        href="./Images/Pharmacy X Icon.png"
    >


    <style>

        /* =====================================================
           PRODUCT INFORMATION
           ===================================================== */

        .order-product-info {

            background: #eef7ff;

            border: 1px solid #cfe8ff;

            border-radius: 10px;

            padding: 18px;

            margin-bottom: 25px;
        }


        .order-product-image {

            width: 130px;

            height: 130px;

            object-fit: contain;

            display: block;

            margin: 0 auto 15px auto;

            background: white;

            border-radius: 10px;

            padding: 10px;

            border: 1px solid #ddd;
        }


        .order-product-name {

            text-align: center;

            font-size: 21px;

            font-weight: bold;

            color: #0077b6;

            margin-bottom: 10px;
        }


        .order-product-description {

            text-align: center;

            color: #555;

            margin-bottom: 12px;
        }


        .order-product-price {

            text-align: center;

            font-size: 18px;

            font-weight: bold;

            color: #087df5;

            margin-bottom: 8px;
        }


        .order-product-stock {

            text-align: center;

            font-weight: bold;

            margin-bottom: 10px;
        }


        /* =====================================================
           PRESCRIPTION MESSAGE
           ===================================================== */

        .prescription-message {

            background: #fff3cd;

            color: #856404;

            border: 1px solid #ffeeba;

            padding: 12px;

            border-radius: 8px;

            margin-top: 12px;

            text-align: center;

            font-weight: bold;
        }


        /* =====================================================
           OTC MESSAGE
           ===================================================== */

        .otc-message {

            background: #d4edda;

            color: #155724;

            border: 1px solid #c3e6cb;

            padding: 12px;

            border-radius: 8px;

            margin-top: 12px;

            text-align: center;

            font-weight: bold;
        }


        /* =====================================================
           STEP
           ===================================================== */

        .step {

            margin-top: 10px;
        }


        .step h3 {

            margin-bottom: 12px;
        }


        /* =====================================================
           QUANTITY
           ===================================================== */

        .step label {

            display: block;

            font-weight: bold;

            margin-bottom: 6px;
        }


        .step input[type="number"] {

            width: 100%;

            box-sizing: border-box;

            padding: 10px;

            border: 1px solid #ccc;

            border-radius: 5px;

            font-size: 15px;

            margin-bottom: 10px;
        }


        /* =====================================================
           BUTTONS
           ===================================================== */

        .button-row {

            display: flex;

            gap: 12px;

            margin-top: 20px;
        }


        .continue-button {

            flex: 1;

            padding: 13px;

            background: #6c757d;

            color: white;

            text-align: center;

            text-decoration: none;

            border-radius: 6px;

            font-weight: bold;
        }


        .place-button {

            flex: 1;

            padding: 13px;

            background: #009688;

            color: white;

            border: none;

            border-radius: 6px;

            font-weight: bold;

            cursor: pointer;

            font-size: 16px;
        }


        .place-button:hover {

            background: #00796b;
        }


        .continue-button:hover {

            background: #545b62;
        }


        /* =====================================================
           MOBILE
           ===================================================== */

        @media(max-width:600px) {

            .button-row {

                flex-direction: column;
            }

        }

    </style>

</head>


<body>


<?php

include("./header.php");

?>


<div class="container">


    <!-- =====================================================
         PAGE TITLE
         ===================================================== -->

    <h2>
        Place Your Order
    </h2>


    <!-- =====================================================
         PRODUCT INFORMATION
         ===================================================== -->

    <div class="order-product-info">


        <!-- PRODUCT IMAGE -->

        <img

            src="<?php
                echo htmlspecialchars(
                    $imageSrc
                );
            ?>"

            alt="<?php
                echo htmlspecialchars(
                    $productName
                );
            ?>"

            class="order-product-image"

            onerror="
                this.src='./Images/product-icons/Pharmacy-Isometric-Icons-1.png';
            "

        >


        <!-- PRODUCT NAME -->

        <div class="order-product-name">

            <?php

            echo htmlspecialchars(
                $productName
            );

            ?>

        </div>


        <!-- PRODUCT DESCRIPTION -->

        <div class="order-product-description">

            <?php

            echo htmlspecialchars(
                $productDescription
            );

            ?>

        </div>


        <!-- PRODUCT PRICE -->

        <div class="order-product-price">

            Price:

            ₹<?php

            echo number_format(
                $productPrice,
                2
            );

            ?>

        </div>


        <!-- STOCK -->

        <div class="order-product-stock">

            Available Stock:

            <?php

            echo $productStock;

            ?>

        </div>


        <!-- PRESCRIPTION STATUS -->

        <?php

        if ($prescriptionRequired) {

        ?>

            <div class="prescription-message">

                🩺 Prescription Required

                <br>

                You must have an approved prescription
                before payment.

            </div>

        <?php

        } else {

        ?>

            <div class="otc-message">

                ✓ No Prescription Required

            </div>

        <?php

        }

        ?>

    </div>


    <!-- =====================================================
         STEP 1
         ===================================================== -->

    <form

        action="./paymentpage.php"

        method="POST"

    >


        <div class="step">


            <h3>

                Step 1

            </h3>


            <!-- =================================================
                 QUANTITY
                 ================================================= -->

            <label for="quantity">

                Quantity

            </label>


            <input

                type="number"

                id="quantity"

                name="quantity"

                min="1"

                max="<?php echo $productStock; ?>"

                value="1"

                required

            >


            <!-- =================================================
                 PRODUCT ID
                 ================================================= -->

            <input

                type="hidden"

                name="product_id"

                value="<?php

                    echo $product_id;

                ?>"

            >


            <!-- =================================================
                 USER NAME
                 ================================================= -->

            <input

                type="hidden"

                name="user_name"

                value="<?php

                    echo htmlspecialchars(
                        $username,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                ?>"

            >


            <!-- =================================================
                 BUY NOW MODE
                 ================================================= -->

            <input

                type="hidden"

                name="buy_now"

                value="1"

            >


            <!-- =================================================
                 CHECKOUT MODE
                 ================================================= -->

            <input

                type="hidden"

                name="checkout_mode"

                value="buy_now"

            >


            <!-- =================================================
                 BUTTONS
                 ================================================= -->

            <div class="button-row">


                <!-- CONTINUE SHOPPING -->

                <a

                    href="products.php"

                    class="continue-button"

                >

                    ← Continue Shopping

                </a>


                <!-- PLACE MY ORDER -->

                <button

                    type="submit"

                    name="placeorder"

                    id="place-order-btn"

                    class="place-button"

                >

                    Place My Order

                </button>


            </div>


        </div>


    </form>


</div>


<?php

include("./footer.php");

?>


</body>

</html>