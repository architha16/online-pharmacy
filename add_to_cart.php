<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


/*
====================================================
PHARMACYX - ADD TO CART
CUSTOMER
====================================================
*/


/*
====================================================
CUSTOMER SESSION
====================================================
*/

session_name("PHARMACYX_CUSTOMER");

session_start();


/*
====================================================
DATABASE
====================================================
*/

require_once "./db_Config/config.php";


/*
====================================================
CHECK CUSTOMER LOGIN
====================================================
*/

if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== "Customer"
) {

    header("Location: signin.php?role=Customer");
    exit();

}


/*
====================================================
CHECK ADD TO CART BUTTON
====================================================
*/

if (!isset($_POST['cartbtn'])) {

    header("Location: products.php");
    exit();

}


/*
====================================================
GET CUSTOMER
====================================================
*/

$user = $_SESSION['username'];

$userSafe = mysqli_real_escape_string(
    $Connection,
    $user
);


/*
====================================================
GET PRODUCT DATA
====================================================
*/

$product_id = isset($_POST['product_id'])
    ? intval($_POST['product_id'])
    : 0;

$product_name = isset($_POST['product_name'])
    ? trim($_POST['product_name'])
    : "";

$price = isset($_POST['price'])
    ? floatval($_POST['price'])
    : 0;


/*
====================================================
VALIDATE PRODUCT
====================================================
*/

if ($product_id <= 0) {

    die("Invalid Product ID.");

}

if ($product_name === "") {

    die("Product name is missing.");

}

if ($price < 0) {

    die("Invalid product price.");

}


/*
====================================================
ESCAPE PRODUCT DATA
====================================================
*/

$productNameSafe = mysqli_real_escape_string(
    $Connection,
    $product_name
);


/*
====================================================
CHECK WHETHER PRODUCT ALREADY
EXISTS IN CUSTOMER CART
====================================================
*/

$checkQuery = "

SELECT cart_id, quantity

FROM cart

WHERE user_name = '$userSafe'

AND product_id = $product_id

LIMIT 1

";


$checkResult = mysqli_query(
    $Connection,
    $checkQuery
);


if (!$checkResult) {

    die(
        "Cart Check Error: " .
        mysqli_error($Connection)
    );

}


/*
====================================================
PRODUCT ALREADY IN CART
====================================================
*/

if (mysqli_num_rows($checkResult) > 0) {

    $cartRow = mysqli_fetch_assoc(
        $checkResult
    );

    $cartId = intval(
        $cartRow['cart_id']
    );


    $updateQuery = "

    UPDATE cart

    SET quantity = quantity + 1

    WHERE cart_id = $cartId

    AND user_name = '$userSafe'

    ";


    if (!mysqli_query(
        $Connection,
        $updateQuery
    )) {

        die(
            "Cart Update Error: " .
            mysqli_error($Connection)
        );

    }

}


/*
====================================================
PRODUCT NOT IN CART
====================================================
*/

else {

    $insertQuery = "

    INSERT INTO cart
    (
        user_name,
        product_id,
        product_name,
        price,
        quantity
    )

    VALUES
    (
        '$userSafe',
        $product_id,
        '$productNameSafe',
        $price,
        1
    )

    ";


    if (!mysqli_query(
        $Connection,
        $insertQuery
    )) {

        die(
            "Cart Insert Error: " .
            mysqli_error($Connection)
        );

    }

}


/*
====================================================
GO TO CART
====================================================
*/

header("Location: cart.php");

exit();

?>