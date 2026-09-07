<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


/*
====================================================
DATABASE
====================================================
*/

require_once "./db_Config/config.php";


/*
====================================================
CHECK ADMIN SESSION
====================================================
*/

$adminLoggedIn = false;

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
ALLOW ADMIN OR MANAGER
====================================================
*/

if (
    !$adminLoggedIn &&
    !$managerLoggedIn
) {

    die("Access Denied. Admin or Manager access required.");

}


/*
====================================================
CHECK REQUEST
====================================================
*/

/*
Your new manage_orders.php sends:

order_id
order_status

The code below also supports the old:

id
status
*/

if (isset($_POST['order_id'])) {

    $order_id = intval($_POST['order_id']);

}
elseif (isset($_POST['id'])) {

    $order_id = intval($_POST['id']);

}
else {

    die("Order ID is missing.");

}


if (isset($_POST['order_status'])) {

    $status = trim($_POST['order_status']);

}
elseif (isset($_POST['status'])) {

    $status = trim($_POST['status']);

}
else {

    die("Order status is missing.");

}


/*
====================================================
CHECK ORDER ID
====================================================
*/

if ($order_id <= 0) {

    die("Invalid Order ID.");

}


/*
====================================================
ALLOWED STATUSES
====================================================
*/

$allowedStatuses = [

    "Pending",
    "Accepted",
    "Packed",
    "Out for Delivery",
    "Delivered",
    "Rejected"

];


if (
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
) {

    die("Invalid order status.");

}


/*
====================================================
UPDATE REJECTED ORDER
====================================================
*/

if ($status === "Rejected") {

    $option = "";

    $reason = "";


    /*
    Get rejection option if provided
    */

    if (isset($_POST['reject_option'])) {

        $option = trim(
            $_POST['reject_option']
        );

    }


    /*
    Get rejection reason if provided
    */

    if (isset($_POST['reason'])) {

        $reason = trim(
            $_POST['reason']
        );

    }


    /*
    If option is not Other,
    use the selected option as reason.
    */

    if (
        $option !== "" &&
        $option !== "Other"
    ) {

        $reason = $option;

    }


    /*
    Escape values
    */

    $optionSafe = mysqli_real_escape_string(
        $Connection,
        $option
    );

    $reasonSafe = mysqli_real_escape_string(
        $Connection,
        $reason
    );


    /*
    Update rejected order
    */

    $sql = "

        UPDATE orders

        SET
            order_status = 'Rejected',
            rejection_reason = '$reasonSafe'

        WHERE order_id = $order_id

    ";

}
else {

    /*
    ====================================================
    NORMAL STATUS UPDATE
    ====================================================
    */

    $statusSafe = mysqli_real_escape_string(
        $Connection,
        $status
    );


    $sql = "

        UPDATE orders

        SET
            order_status = '$statusSafe'

        WHERE order_id = $order_id

    ";

}


/*
====================================================
RUN UPDATE
====================================================
*/

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
====================================================
CHECK ORDER EXISTS
====================================================
*/

$checkOrder = mysqli_query(
    $Connection,
    "

    SELECT order_id
    FROM orders
    WHERE order_id = $order_id
    LIMIT 1

    "
);


if (
    !$checkOrder ||
    mysqli_num_rows($checkOrder) === 0
) {

    die("Order not found.");

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