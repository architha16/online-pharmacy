<!-- By Marasingha MAMN IT23539990 -->

<?php

/*
=========================================================
PHARMACYX HEADER
=========================================================

IMPORTANT:

The individual PHP page must start the correct PharmacyX
session BEFORE including this header.

Customer:
session_name("PHARMACYX_CUSTOMER");
session_start();

Pharmacist:
session_name("PHARMACYX_PHARMACIST");
session_start();

Admin:
session_name("PHARMACYX_ADMIN");
session_start();

Manager:
session_name("PHARMACYX_MANAGER");
session_start();


IMPORTANT:

This header DOES NOT start a session.

It only reads the session that was already started
by the current page.

=========================================================
*/


/*
=========================================================
CHECK LOGIN STATUS
=========================================================
*/

$pharmacyxLoggedIn =
    isset($_SESSION['username']) &&
    isset($_SESSION['user_type']);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >


    <!-- =================================================
         PHARMACYX CSS
    ================================================== -->

    <link
        rel="stylesheet"
        href="./CSS/partials.css"
    >


    <!-- =================================================
         GOOGLE FONTS
    ================================================== -->

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


    <!-- =================================================
         FONT AWESOME
    ================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css"
    >


    <!-- =================================================
         PHARMACYX JAVASCRIPT
    ================================================== -->

    <script src="./JS/partials.js"></script>


    <style>

        /*
        =================================================
        LOGIN BUTTONS
        =================================================
        */

        .login-buttons {

            display: flex;

            gap: 10px;

            align-items: center;

        }


        .login-buttons button {

            padding: 10px 18px;

            border: none;

            border-radius: 5px;

            background: #007bff;

            color: white;

            cursor: pointer;

            font-weight: bold;

        }


        .login-buttons button:hover {

            background: #0056b3;

        }


        /*
        =================================================
        PROFILE DROPDOWN
        =================================================
        */

        .Profile_dropdown {

            z-index: 9999;

        }

    </style>

</head>


<body>


<header>


<div class="main_header">


<!-- =====================================================
     PHARMACYX LOGO
===================================================== -->

<a href="index.php">

    <img
        src="./Images/Pharmacy X.png"
        alt="PharmacyX Logo"
    >

</a>



<!-- =====================================================
     MAIN NAVIGATION
===================================================== -->

<nav>

<ul>


    <!-- =================================================
         HOME
    ================================================== -->

    <li>

        <a href="index.php">

            Home

        </a>

    </li>



    <!-- =================================================
         PRODUCTS
         
         Products remains in the MAIN NAVIGATION.
         It is NOT inside the customer dropdown.
    ================================================== -->

    <li>

        <a href="products.php">

            Products

        </a>

    </li>



    <!-- =================================================
         ABOUT
    ================================================== -->

    <li>

        <a href="aboutUs.php">

            About

        </a>

    </li>



    <!-- =================================================
         CONTACT
    ================================================== -->

    <li>

        <a href="contact.php">

            Contact

        </a>

    </li>


</ul>

</nav>



<?php

/*
=========================================================
NOT LOGGED IN
=========================================================
*/

if (
    !$pharmacyxLoggedIn
) {

?>


<!-- =====================================================
     LOGIN BUTTONS
===================================================== -->

<div class="login-buttons">


    <!-- =================================================
         CUSTOMER LOGIN
    ================================================== -->

    <button
        type="button"
        onclick="
            location.href='signin.php?role=Customer'
        "
    >

        Customer Login

    </button>



    <!-- =================================================
         PHARMACIST LOGIN
    ================================================== -->

    <button
        type="button"
        onclick="
            location.href='signin.php?role=Pharmacist'
        "
    >

        Pharmacist Login

    </button>


</div>


<?php

}

else {

    /*
    =====================================================
    USER IS LOGGED IN
    =====================================================
    */

    $DBLink = '';



    /*
    =====================================================
    ADMIN
    =====================================================
    */

    if (
        isset($_SESSION['user_type']) &&
        $_SESSION['user_type'] === "Admin"
    ) {

        $DBLink = '

            <a href="admin_DB.php">
                Admin Dashboard
            </a>

            <a href="manage_users.php">
                Manage Users
            </a>

            <a href="manage_products.php">
                Manage Medicines
            </a>

            <a href="manage_orders.php">
                Manage Orders
            </a>

        ';

    }



    /*
    =====================================================
    MANAGER
    =====================================================
    */

    elseif (
        isset($_SESSION['user_type']) &&
        $_SESSION['user_type'] === "Manager"
    ) {

        $DBLink = '

            <a href="manager_DB.php">
                Manager Dashboard
            </a>

            <a href="manage_products.php">
                Manage Medicines
            </a>

            <a href="manage_orders.php">
                Manage Orders
            </a>

        ';

    }



    /*
    =====================================================
    PHARMACIST
    =====================================================
    */

    elseif (
        isset($_SESSION['user_type']) &&
        $_SESSION['user_type'] === "Pharmacist"
    ) {

        $DBLink = '

            <a href="pharmacist_dashboard.php">
                Dashboard
            </a>

            <a href="view_prescriptions.php">
                View Prescriptions
            </a>

            <a href="view_orders.php">
                Customer Orders
            </a>

            <a href="pharmacist_medicines.php">
                Manage Medicine Prescriptions
            </a>

        ';

    }



    /*
    =====================================================
    CUSTOMER
    =====================================================
    */

    elseif (
        isset($_SESSION['user_type']) &&
        $_SESSION['user_type'] === "Customer"
    ) {

        /*
        -------------------------------------------------
        CUSTOMER MENU
        -------------------------------------------------

        Customer gets:

        - Prescription Status
        - My Orders
        - Track Medicine

        Products is already available in the
        MAIN NAVIGATION.

        Prescription upload is NOT shown here.

        Prescription upload happens during checkout
        for prescription-required medicines.
        */

        $DBLink = '

            <a href="prescription_status.php">
                Prescription Status
            </a>

            <a href="my_orders.php">
                My Orders
            </a>

            <a href="track_order.php">
                Track Medicine
            </a>

        ';

    }



    /*
    =====================================================
    DEFAULT PROFILE IMAGE
    =====================================================
    */

    $profilePic =
        "./Images/Profile_Pics/student-avatar-illustratio.jpg";



    /*
    =====================================================
    USER PROFILE IMAGE
    =====================================================
    */

    if (
        isset($_SESSION['profilePic_url']) &&
        trim($_SESSION['profilePic_url']) !== ""
    ) {

        $profilePic =
            "./Images/Profile_Pics/" .
            basename(
                $_SESSION['profilePic_url']
            );

    }


?>


<!-- =====================================================
     USER PROFILE
===================================================== -->

<div
    class="user_profile"
    onclick="dropdownmenu()"
>


    <!-- =================================================
         PROFILE IMAGE
    ================================================== -->

    <img
        src="<?php

            echo htmlspecialchars(
                $profilePic,
                ENT_QUOTES,
                'UTF-8'
            );

        ?>"
        alt="Profile"
    >



    <!-- =================================================
         USERNAME
    ================================================== -->

    <h3 class="username no-select">

        Hello

        <?php

        echo htmlspecialchars(
            $_SESSION['firstname']
                ?? $_SESSION['username'],
            ENT_QUOTES,
            'UTF-8'
        );

        ?>

        <i class="fas fa-caret-down"></i>

    </h3>



    <!-- =================================================
         PROFILE DROPDOWN
    ================================================= -->

    <div
        class="Profile_dropdown"
        id="prof_dropdown"
    >


        <div class="dropdown_items">


            <!-- =========================================
                 MY PROFILE
            ========================================== -->

            <a href="my_account.php">

                My Profile

            </a>



            <!-- =========================================
                 ROLE-SPECIFIC LINKS
            ========================================== -->

            <?php

            echo $DBLink;

            ?>



            <!-- =========================================
                 SIGN OUT
            ========================================== -->

            <a
                href="logout.php"
                onclick="
                    return confirm(
                        'Do you want to Sign Out?'
                    );
                "
            >

                Sign Out

            </a>


        </div>


    </div>


</div>


<?php

}

?>


</div>

</header>