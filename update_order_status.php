<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


/*
====================================================
PHARMACYX
UPDATE ORDER STATUS
ADMIN + MANAGER
====================================================
*/


/*
====================================================
DATABASE CONNECTION
====================================================
*/

require_once "./db_Config/config.php";


/*
====================================================
CHECK REQUEST METHOD
====================================================
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    die("Invalid request. Please update the order from Manage Orders.");

}


/*
====================================================
GET ORDER ID
====================================================
*/

$orderId = isset($_POST['order_id'])
    ? intval($_POST['order_id'])
    : 0;


/*
====================================================
GET ORDER STATUS
====================================================
*/

$orderStatus = isset($_POST['order_status'])
    ? trim($_POST['order_status'])
    : "";


/*
====================================================
CHECK ORDER ID
====================================================
*/

if ($orderId <= 0) {

    die("Invalid Order ID.");

}


/*
====================================================
CHECK STATUS
====================================================
*/

if ($orderStatus === "") {

    die("Order status is missing.");

}


/*
====================================================
ALLOWED STATUSES
====================================================
*/

$allowedStatuses = array(

    "Pending",

    "Accepted",

    "Packed",

    "Out for Delivery",

    "Delivered",

    "Rejected"

);


if (!in_array($orderStatus, $allowedStatuses, true)) {

    die("Invalid order status.");

}


/*
====================================================
CHECK ADMIN SESSION
====================================================
*/

$adminLoggedIn = false;


/*
Start Admin session
*/

session_name("PHARMACYX_ADMIN");

session_start();


if (
    isset($_SESSION['username']) &&
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === "Admin"
) {

    $adminLoggedIn = true;

}


session_write_close();


/*
====================================================
CHECK MANAGER SESSION
====================================================
*/

$managerLoggedIn = false;


/*
Start Manager session
*/

session_name("PHARMACYX_MANAGER");

session_start();


if (
    isset($_SESSION['username']) &&
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === "Manager"
) {

    $managerLoggedIn = true;

}


session_write_close();


/*
====================================================
CHECK ACCESS
====================================================
*/

if (
    !$adminLoggedIn &&
    !$managerLoggedIn
) {

    die(
        "Access Denied. Admin or Manager access required."
    );

}


/*
====================================================
CHECK ORDER EXISTS
====================================================
*/

$checkQuery = "

SELECT order_id

FROM orders

WHERE order_id = ?

LIMIT 1

";


$checkStmt = mysqli_prepare(
    $Connection,
    $checkQuery
);


if (!$checkStmt) {

    die(
        "Database Error: " .
        mysqli_error($Connection)
    );

}


mysqli_stmt_bind_param(
    $checkStmt,
    "i",
    $orderId
);


mysqli_stmt_execute(
    $checkStmt
);


$checkResult = mysqli_stmt_get_result(
    $checkStmt
);


if (
    mysqli_num_rows($checkResult) == 0
) {

    mysqli_stmt_close($checkStmt);

    die("Order not found.");

}


mysqli_stmt_close($checkStmt);


/*
====================================================
ESCAPE STATUS
====================================================
*/

$orderStatusSafe = mysqli_real_escape_string(
    $Connection,
    $orderStatus
);


/*
====================================================
UPDATE ORDER
====================================================
*/

if ($orderStatus === "Rejected") {


    /*
    -----------------------------------------------
    REJECTED ORDER
    -----------------------------------------------
    */

    if ($adminLoggedIn) {

        $rejectionReason = "Rejected by Admin";

    }
    else {

        $rejectionReason = "Rejected by Manager";

    }


    $rejectionReasonSafe = mysqli_real_escape_string(
        $Connection,
        $rejectionReason
    );


    $updateQuery = "

    UPDATE orders

    SET

        order_status = '$orderStatusSafe',

        rejection_reason = '$rejectionReasonSafe'

    WHERE order_id = $orderId

    ";

}
else {


    /*
    -----------------------------------------------
    NORMAL STATUS
    -----------------------------------------------
    */

    $updateQuery = "

    UPDATE orders

    SET

        order_status = '$orderStatusSafe'

    WHERE order_id = $orderId

    ";

}


/*
====================================================
EXECUTE UPDATE
====================================================
*/

$updateResult = mysqli_query(
    $Connection,
    $updateQuery
);


if (!$updateResult) {

    die(
        "Update Error: " .
        mysqli_error($Connection)
    );

}


/*
====================================================
CHECK WHETHER UPDATE ACTUALLY HAPPENED
====================================================
*/

if (mysqli_affected_rows($Connection) < 0) {

    die("Order status could not be updated.");

}


/*
====================================================
SUCCESS
====================================================
*/

header(
    "Location: manage_orders.php"
);

exit();

?>