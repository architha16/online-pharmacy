<?php

/*
=========================================================
PHARMACYX ROLE-BASED LOGOUT
=========================================================
*/


/*
=========================================================
GET ROLE
=========================================================
*/

$role = $_GET['role'] ?? '';



/*
=========================================================
SESSION NAME BASED ON ROLE
=========================================================
*/

$sessionNames = [

    "Admin" =>
        "PHARMACYX_ADMIN",

    "Manager" =>
        "PHARMACYX_MANAGER",

    "Pharmacist" =>
        "PHARMACYX_PHARMACIST",

    "Customer" =>
        "PHARMACYX_CUSTOMER"

];



/*
=========================================================
CHECK ROLE
=========================================================
*/

if (
    !isset($sessionNames[$role])
) {

    /*
    If role was not supplied,
    try to detect an existing session.
    */

    session_start();

    $role =
        $_SESSION['user_type'] ?? '';

    session_unset();

    session_destroy();

    header("Location: signin.php");

    exit();

}



/*
=========================================================
START CORRECT ROLE SESSION
=========================================================
*/

session_name(
    $sessionNames[$role]
);

session_start();



/*
=========================================================
REMOVE SESSION DATA
=========================================================
*/

$_SESSION = [];



/*
=========================================================
DELETE SESSION COOKIE
=========================================================
*/

if (
    ini_get("session.use_cookies")
) {

    $params =
        session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );

}



/*
=========================================================
DESTROY SESSION
=========================================================
*/

session_destroy();



/*
=========================================================
REDIRECT TO ROLE LOGIN
=========================================================
*/

header(
    "Location: signin.php?role=" .
    urlencode($role)
);

exit();

?>