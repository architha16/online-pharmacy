<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


/*
====================================================
PHARMACYX - MANAGE USERS
ADMIN + MANAGER ACCESS
====================================================
*/


/*
====================================================
CHECK ADMIN SESSION
====================================================
*/

session_name("PHARMACYX_ADMIN");
session_start();

$adminLoggedIn = false;
$adminUsername = "";

if (
    isset($_SESSION['username']) &&
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === 'Admin'
) {

    $adminLoggedIn = true;
    $adminUsername = $_SESSION['username'];
}

session_write_close();


/*
====================================================
CHECK MANAGER SESSION
====================================================
*/

session_name("PHARMACYX_MANAGER");
session_start();

$managerLoggedIn = false;
$managerUsername = "";

if (
    isset($_SESSION['username']) &&
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === 'Manager'
) {

    $managerLoggedIn = true;
    $managerUsername = $_SESSION['username'];
}

session_write_close();


/*
====================================================
DETERMINE CURRENT USER
====================================================
*/

if ($adminLoggedIn) {

    $currentUser = $adminUsername;
    $currentRole = "Admin";

} elseif ($managerLoggedIn) {

    $currentUser = $managerUsername;
    $currentRole = "Manager";

} else {

    header("Location: signin.php?role=Manager");
    exit();

}


/*
====================================================
DATABASE CONNECTION
====================================================
*/

require_once "./db_Config/config.php";


/*
====================================================
DELETE USER
====================================================
*/

if (isset($_GET['delete'])) {

    $deleteUser = mysqli_real_escape_string(
        $Connection,
        $_GET['delete']
    );


    /*
    ================================================
    PREVENT CURRENT USER FROM DELETING HIMSELF
    ================================================
    */

    if ($deleteUser === $currentUser) {

        echo "
        <script>
            alert('You cannot delete your own account.');
            window.location='manage_users.php';
        </script>
        ";

        exit();
    }


    /*
    ================================================
    DELETE USER
    ================================================
    */

    $deleteQuery = "
        DELETE FROM user_info
        WHERE user_name='$deleteUser'
    ";


    if (mysqli_query($Connection, $deleteQuery)) {

        echo "
        <script>
            alert('User deleted successfully.');
            window.location='manage_users.php';
        </script>
        ";

        exit();

    } else {

        die(
            "Delete Error: " .
            mysqli_error($Connection)
        );
    }
}


/*
====================================================
CHANGE ACCOUNT STATUS
====================================================
*/

if (
    isset($_GET['status']) &&
    isset($_GET['user'])
) {

    $status = mysqli_real_escape_string(
        $Connection,
        $_GET['status']
    );

    $targetUser = mysqli_real_escape_string(
        $Connection,
        $_GET['user']
    );


    /*
    ================================================
    ONLY ACTIVE / INACTIVE ALLOWED
    ================================================
    */

    if (
        ($status === 'Active' || $status === 'Inactive') &&
        $targetUser !== $currentUser
    ) {

        $updateStatus = "
            UPDATE user_info
            SET acc_status='$status'
            WHERE user_name='$targetUser'
        ";


        if (!mysqli_query(
            $Connection,
            $updateStatus
        )) {

            die(
                "Status Update Error: " .
                mysqli_error($Connection)
            );
        }
    }


    header("Location: manage_users.php");
    exit();
}


/*
====================================================
SEARCH
====================================================
*/

$search = "";

if (isset($_GET['search'])) {

    $search = trim($_GET['search']);
}


$searchSafe = mysqli_real_escape_string(
    $Connection,
    $search
);


/*
====================================================
GET USERS
====================================================
*/

if ($search !== "") {

    $query = "

        SELECT
            user_name,
            first_name,
            last_name,
            email,
            phone_no,
            profilepic_url,
            acc_status,
            user_type

        FROM user_info

        WHERE

            user_name LIKE '%$searchSafe%'

            OR first_name LIKE '%$searchSafe%'

            OR last_name LIKE '%$searchSafe%'

            OR email LIKE '%$searchSafe%'

            OR user_type LIKE '%$searchSafe%'

        ORDER BY user_name ASC

    ";

} else {

    $query = "

        SELECT
            user_name,
            first_name,
            last_name,
            email,
            phone_no,
            profilepic_url,
            acc_status,
            user_type

        FROM user_info

        ORDER BY user_name ASC

    ";
}


$result = mysqli_query(
    $Connection,
    $query
);


if (!$result) {

    die(
        "User Query Error: " .
        mysqli_error($Connection)
    );
}


/*
====================================================
STATISTICS
====================================================
*/

$statsResult = mysqli_query(
    $Connection,
    "
    SELECT
        COUNT(*) AS total,
        SUM(user_type='Customer') AS customers,
        SUM(user_type IN ('Admin','Manager','Pharmacist')) AS staff,
        SUM(acc_status='Inactive') AS inactive
    FROM user_info
    "
);


if ($statsResult) {

    $statsRow = mysqli_fetch_assoc($statsResult);

} else {

    $statsRow = [
        'total' => 0,
        'customers' => 0,
        'staff' => 0,
        'inactive' => 0
    ];
}


$totalUsers = mysqli_num_rows($result);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Manage Users - PharmacyX</title>


<!-- Font Awesome -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
>


<style>

/* ==================================================
   THEME VARIABLES
================================================== */

:root {

    --bg: #f4f7fb;

    --card: #ffffff;

    --text: #172033;

    --muted: #718096;

    --border: #e5eaf1;

    --primary: #087fdf;

    --primary2: #0669bd;

    --soft: #eaf5ff;

    --shadow:
        0 10px 30px rgba(26, 54, 93, 0.08);

    --danger: #ef4444;

    --warning: #f59e0b;

    --success: #16a34a;
}


/* ==================================================
   DARK MODE
================================================== */

body.dark {

    --bg: #0d1422;

    --card: #151e2d;

    --text: #edf3fb;

    --muted: #9aa8ba;

    --border: #29364a;

    --primary: #3b9df5;

    --primary2: #2584d8;

    --soft: #1b3047;

    --shadow:
        0 12px 35px rgba(0, 0, 0, 0.28);
}


/* ==================================================
   GLOBAL
================================================== */

* {
    box-sizing: border-box;
}


html {
    scroll-behavior: smooth;
}


body {

    margin: 0;

    background: var(--bg);

    color: var(--text);

    font-family:
        Inter,
        "Segoe UI",
        Arial,
        sans-serif;

    transition:
        background-color 0.2s ease,
        color 0.2s ease;
}


a {
    text-decoration: none;
}


/* ==================================================
   TOP BAR
================================================== */

.topbar {

    height: 76px;

    background: var(--card);

    border-bottom:
        1px solid var(--border);

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        0 34px;

    position: sticky;

    top: 0;

    z-index: 100;
}


.brand {

    display: flex;

    align-items: center;

    gap: 12px;
}


.brand-icon {

    width: 42px;

    height: 42px;

    border-radius: 12px;

    background: var(--soft);

    color: var(--primary);

    display: grid;

    place-items: center;

    font-size: 19px;
}


.brand strong {

    font-size: 21px;

    color: var(--text);
}


.brand small {

    display: block;

    color: var(--muted);

    font-size: 11px;

    margin-top: 2px;
}


.top-actions {

    display: flex;

    align-items: center;

    gap: 10px;
}


/* ==================================================
   ROLE
================================================== */

.role {

    padding:
        9px 14px;

    border-radius: 20px;

    background: var(--soft);

    color: var(--primary);

    font-size: 13px;

    font-weight: 700;
}


/* ==================================================
   DASHBOARD BUTTON
================================================== */

.dashboard {

    height: 40px;

    padding:
        0 15px;

    border:
        1px solid var(--border);

    border-radius: 11px;

    background: var(--card);

    color: var(--text);

    cursor: pointer;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 700;

    transition: 0.2s;
}


.dashboard:hover {

    border-color:
        var(--primary);

    color:
        var(--primary);
}


/* ==================================================
   THEME BUTTON
================================================== */

.theme {

    width: 40px;

    height: 40px;

    border:
        1px solid var(--border);

    border-radius: 11px;

    background: var(--card);

    color: var(--text);

    cursor: pointer;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 17px;

    transition: 0.2s;
}


.theme:hover {

    border-color:
        var(--primary);

    color:
        var(--primary);

    transform: none;
}


/* ==================================================
   MAIN CONTAINER
================================================== */

.container {

    width:
        min(1400px, 94%);

    margin:
        28px auto 50px;
}


/* ==================================================
   PAGE HEADING
================================================== */

.heading {

    display: flex;

    justify-content:
        space-between;

    align-items:
        flex-end;

    margin-bottom: 22px;
}


.heading h1 {

    margin: 0;

    font-size: 30px;

    letter-spacing:
        -0.5px;
}


.heading p {

    margin:
        7px 0 0;

    color:
        var(--muted);

    font-size: 14px;
}


/* ==================================================
   STATISTICS
================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 16px;

    margin-bottom: 20px;
}


.stat {

    background:
        var(--card);

    border:
        1px solid var(--border);

    border-radius: 16px;

    padding: 19px;

    box-shadow:
        var(--shadow);
}


.stat-top {

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;
}


.stat-icon {

    width: 38px;

    height: 38px;

    border-radius: 11px;

    background:
        var(--soft);

    color:
        var(--primary);

    display: grid;

    place-items: center;
}


.stat-label {

    color:
        var(--muted);

    font-size: 12px;

    font-weight: 700;
}


.stat-value {

    font-size: 25px;

    font-weight: 800;

    margin-top: 12px;
}


.stat-sub {

    color:
        var(--muted);

    font-size: 11px;

    margin-top: 4px;
}


/* ==================================================
   PANEL
================================================== */

.panel {

    background:
        var(--card);

    border:
        1px solid var(--border);

    border-radius: 16px;

    box-shadow:
        var(--shadow);

    margin-bottom: 20px;

    overflow: hidden;
}


/* ==================================================
   SEARCH
================================================== */

.search-panel {

    padding: 16px;
}


.search-form {

    display: flex;

    gap: 10px;
}


.search-input {

    flex: 1;

    position: relative;
}


.search-input i {

    position: absolute;

    left: 14px;

    top: 14px;

    color:
        #9aa7b8;

    pointer-events: none;
}


.search-input input {

    width: 100%;

    height: 46px;

    padding:
        0 15px 0 40px;

    border:
        1px solid var(--border);

    border-radius: 11px;

    background:
        var(--bg);

    color:
        var(--text);

    outline: none;

    font-size: 13px;

    transition: 0.2s;
}


.search-input input::placeholder {

    color:
        var(--muted);
}


.search-input input:focus {

    border-color:
        var(--primary);

    box-shadow:
        0 0 0 3px
        rgba(8, 127, 223, 0.10);
}


/* ==================================================
   BUTTONS
================================================== */

.btn {

    border: 0;

    border-radius: 10px;

    height: 46px;

    padding:
        0 18px;

    cursor: pointer;

    font-weight: 700;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    font-size: 13px;
}


.search-btn {

    background:
        var(--primary);

    color:
        #ffffff;
}


.search-btn:hover {

    background:
        var(--primary2);
}


.clear-btn {

    background:
        var(--soft);

    color:
        var(--primary);

    white-space:
        nowrap;
}


/* ==================================================
   TABLE HEADER
================================================== */

.table-head {

    padding:
        18px 20px;

    border-bottom:
        1px solid var(--border);

    display: flex;

    align-items:
        center;

    justify-content:
        space-between;
}


.table-head h2 {

    font-size: 16px;

    margin: 0;

    color:
        var(--text);
}


.count {

    color:
        var(--muted);

    font-size: 12px;
}


/* ==================================================
   TABLE WRAPPER
================================================== */

.table-wrap {

    overflow-x:
        auto;
}


/* ==================================================
   TABLE
================================================== */

.table {

    width: 100%;

    border-collapse:
        collapse;

    min-width:
        1050px;

    table-layout:
        auto;
}


.table th {

    padding:
        13px 16px;

    text-align:
        left;

    font-size:
        10px;

    letter-spacing:
        0.7px;

    color:
        var(--muted);

    background:
        var(--bg);

    border-bottom:
        1px solid var(--border);

    white-space:
        nowrap;
}


.table td {

    padding:
        14px 16px;

    border-bottom:
        1px solid var(--border);

    font-size:
        13px;

    vertical-align:
        middle;
}


/* ==================================================
   ROW HOVER
   IMPORTANT:
   ONLY THE ROW BACKGROUND CHANGES.
   NOTHING MOVES.
================================================== */

.table tbody tr {

    transition:
        background-color 0.15s ease;
}


.table tbody tr:hover {

    background:
        var(--soft);
}


/* ==================================================
   USER CELL
================================================== */

.user {

    display: flex;

    align-items:
        center;

    gap: 11px;

    min-height:
        44px;
}


/* ==================================================
   PROFILE IMAGE
   FIXED SIZE - NO SHAKE
================================================== */

.user-photo {

    width:
        42px;

    height:
        42px;

    min-width:
        42px;

    max-width:
        42px;

    min-height:
        42px;

    max-height:
        42px;

    flex:
        0 0 42px;

    display:
        block;

    border-radius:
        12px;

    object-fit:
        cover;

    object-position:
        center;

    border:
        1px solid var(--border);

    background:
        var(--soft);

    margin:
        0;

    padding:
        0;

    vertical-align:
        middle;

    transform:
        none !important;

    animation:
        none !important;

    transition:
        none !important;

    position:
        relative;
}


/* Prevent any hover movement */

.table tbody tr:hover .user-photo {

    transform:
        none !important;

    animation:
        none !important;
}


/* ==================================================
   USERNAME
================================================== */

.username {

    font-weight:
        800;

    color:
        var(--text);

    white-space:
        nowrap;
}


.sub {

    display:
        block;

    color:
        var(--muted);

    font-size:
        10px;

    margin-top:
        3px;

    white-space:
        nowrap;
}


/* ==================================================
   ROLE BADGES
================================================== */

.badge {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        6px 10px;

    border-radius:
        20px;

    color:
        #ffffff;

    font-size:
        10px;

    font-weight:
        800;

    white-space:
        nowrap;
}


.admin {

    background:
        #8b5cf6;
}


.manager {

    background:
        #6366f1;
}


.pharmacist {

    background:
        #0f9f95;
}


.customer {

    background:
        #1683d8;
}


.active {

    background:
        var(--success);
}


.inactive {

    background:
        #64748b;
}


/* ==================================================
   ACTION BUTTONS
================================================== */

.actions {

    display:
        flex;

    gap:
        7px;

    flex-wrap:
        wrap;
}


.action {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        6px;

    padding:
        8px 10px;

    border-radius:
        9px;

    font-size:
        11px;

    font-weight:
        800;

    color:
        #ffffff;

    white-space:
        nowrap;

    transition:
        opacity 0.15s ease;
}


.action:hover {

    opacity:
        0.88;
}


.activate {

    background:
        var(--success);
}


.deactivate {

    background:
        var(--warning);
}


.delete {

    background:
        var(--danger);
}


/* ==================================================
   CURRENT USER
================================================== */

.self {

    color:
        var(--muted);

    font-size:
        11px;

    font-weight:
        700;

    white-space:
        nowrap;
}


/* ==================================================
   EMPTY STATE
================================================== */

.empty {

    padding:
        60px;

    text-align:
        center;

    color:
        var(--muted);
}


.empty i {

    font-size:
        35px;

    margin-bottom:
        12px;
}


/* ==================================================
   FOOTER
================================================== */

.footer-note {

    text-align:
        center;

    color:
        var(--muted);

    font-size:
        11px;

    margin-top:
        18px;
}


/* ==================================================
   RESPONSIVE
================================================== */

@media (max-width: 1100px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media (max-width: 900px) {

    .topbar {

        padding:
            0 18px;
    }


    .role {

        display:
            none;
    }
}


@media (max-width: 600px) {

    .stats {

        grid-template-columns:
            1fr;
    }


    .heading {

        align-items:
            flex-start;

        flex-direction:
            column;

        gap:
            12px;
    }


    .search-form {

        flex-direction:
            column;
    }


    .dashboard {

        display:
            none;
    }


    .container {

        width:
            92%;
    }


    .topbar {

        height:
            68px;
    }


    .brand strong {

        font-size:
            18px;
    }
}

</style>

</head>


<body>


<!-- =================================================
     TOP BAR
================================================== -->

<div class="topbar">

    <div class="brand">

        <div class="brand-icon">

            <i class="fa-solid fa-users"></i>

        </div>


        <div>

            <strong>
                PharmacyX
            </strong>

            <small>
                User Administration
            </small>

        </div>

    </div>


    <div class="top-actions">


        <span class="role">

            <i class="fa-solid fa-shield-halved"></i>

            <?php
            echo htmlspecialchars($currentRole);
            ?>

            ·

            <?php
            echo htmlspecialchars($currentUser);
            ?>

        </span>


        <a
            class="dashboard"
            href="<?php
                echo $currentRole === 'Admin'
                    ? 'admin_DB.php'
                    : 'manager_DB.php';
            ?>"
        >

            <i class="fa-solid fa-house"></i>

            &nbsp;

            Dashboard

        </a>


        <button
            class="theme"
            id="themeToggle"
            type="button"
            title="Toggle light/dark mode"
        >

            🌙

        </button>

    </div>

</div>



<!-- =================================================
     MAIN CONTENT
================================================== -->

<main class="container">


    <!-- PAGE HEADING -->

    <div class="heading">

        <div>

            <h1>
                Manage Users
            </h1>

            <p>
                View, search and manage PharmacyX user accounts.
            </p>

        </div>

    </div>



    <!-- =================================================
         STATISTICS
    ================================================= -->

    <section class="stats">


        <!-- TOTAL USERS -->

        <div class="stat">

            <div class="stat-top">

                <span class="stat-label">
                    TOTAL USERS
                </span>

                <div class="stat-icon">

                    <i class="fa-solid fa-users"></i>

                </div>

            </div>


            <div class="stat-value">

                <?php
                echo (int)$statsRow['total'];
                ?>

            </div>


            <div class="stat-sub">
                All registered accounts
            </div>

        </div>



        <!-- CUSTOMERS -->

        <div class="stat">

            <div class="stat-top">

                <span class="stat-label">
                    CUSTOMERS
                </span>

                <div class="stat-icon">

                    <i class="fa-solid fa-user"></i>

                </div>

            </div>


            <div class="stat-value">

                <?php
                echo (int)$statsRow['customers'];
                ?>

            </div>


            <div class="stat-sub">
                Customer accounts
            </div>

        </div>



        <!-- STAFF -->

        <div class="stat">

            <div class="stat-top">

                <span class="stat-label">
                    STAFF
                </span>

                <div class="stat-icon">

                    <i class="fa-solid fa-user-shield"></i>

                </div>

            </div>


            <div class="stat-value">

                <?php
                echo (int)$statsRow['staff'];
                ?>

            </div>


            <div class="stat-sub">
                Admin, Manager & Pharmacist
            </div>

        </div>



        <!-- INACTIVE -->

        <div class="stat">

            <div class="stat-top">

                <span class="stat-label">
                    INACTIVE
                </span>

                <div class="stat-icon">

                    <i class="fa-solid fa-user-slash"></i>

                </div>

            </div>


            <div class="stat-value">

                <?php
                echo (int)$statsRow['inactive'];
                ?>

            </div>


            <div class="stat-sub">
                Accounts currently inactive
            </div>

        </div>


    </section>



    <!-- =================================================
         SEARCH PANEL
    ================================================= -->

    <section class="panel search-panel">


        <form
            method="GET"
            class="search-form"
        >


            <div class="search-input">

                <i class="fa-solid fa-magnifying-glass"></i>


                <input
                    type="text"
                    name="search"
                    placeholder="Search by username, name, email or role..."
                    value="<?php
                        echo htmlspecialchars($search);
                    ?>"
                >

            </div>



            <button
                class="btn search-btn"
                type="submit"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

                Search

            </button>



            <?php if ($search !== ""): ?>

                <a
                    class="btn clear-btn"
                    href="manage_users.php"
                >

                    <i class="fa-solid fa-xmark"></i>

                    Clear

                </a>

            <?php endif; ?>


        </form>

    </section>



    <!-- =================================================
         USERS TABLE
    ================================================= -->

    <section class="panel">


        <div class="table-head">

            <h2>

                <i class="fa-solid fa-address-book"></i>

                User Accounts

            </h2>


            <span class="count">

                <?php
                echo $totalUsers;
                ?>

                user<?php
                    echo $totalUsers == 1 ? '' : 's';
                ?>

                shown

            </span>

        </div>



        <div class="table-wrap">


            <table class="table">


                <thead>

                    <tr>

                        <th>
                            USER
                        </th>

                        <th>
                            EMAIL
                        </th>

                        <th>
                            PHONE
                        </th>

                        <th>
                            ROLE
                        </th>

                        <th>
                            STATUS
                        </th>

                        <th>
                            ACTIONS
                        </th>

                    </tr>

                </thead>



                <tbody>


                <?php if ($totalUsers === 0): ?>


                    <tr>

                        <td colspan="6">


                            <div class="empty">

                                <i class="fa-solid fa-user-slash"></i>

                                <br>

                                No users found.

                            </div>


                        </td>

                    </tr>


                <?php else: ?>


                    <?php while ($row = mysqli_fetch_assoc($result)): ?>


                        <?php

                        $username =
                            $row['user_name'];

                        $fullName =
                            trim(
                                ($row['first_name'] ?? '') .
                                ' ' .
                                ($row['last_name'] ?? '')
                            );

                        $role =
                            $row['user_type'];

                        $accountStatus =
                            $row['acc_status'];


                        /*
                        ========================================
                        ROLE CLASS
                        ========================================
                        */

                        $roleClass =
                            strtolower($role);

                        if (
                            !in_array(
                                $roleClass,
                                [
                                    'admin',
                                    'manager',
                                    'pharmacist',
                                    'customer'
                                ],
                                true
                            )
                        ) {

                            $roleClass =
                                'customer';
                        }


                        /*
                        ========================================
                        STATUS CLASS
                        ========================================
                        */

                        $statusClass =
                            $accountStatus === 'Inactive'
                            ? 'inactive'
                            : 'active';


                        /*
                        ========================================
                        PROFILE IMAGE
                        ========================================
                        */

                        if (
                            !empty(
                                $row['profilepic_url']
                            )
                        ) {

                            $profileImage =
                                './Images/' .
                                ltrim(
                                    $row['profilepic_url'],
                                    '/'
                                );

                        } else {

                            $profileImage =
                                './Images/default-profile.png';
                        }

                        ?>


                        <tr>


                            <!-- USER -->

                            <td>

                                <div class="user">


                                    <img
                                        class="user-photo"
                                        src="<?php
                                            echo htmlspecialchars(
                                                $profileImage
                                            );
                                        ?>"
                                        alt="Profile"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='./Images/default-profile.png';"
                                    >


                                    <div>

                                        <span class="username">

                                            <?php
                                            echo htmlspecialchars(
                                                $username
                                            );
                                            ?>

                                        </span>


                                        <span class="sub">

                                            <?php
                                            echo htmlspecialchars(
                                                $fullName
                                            );
                                            ?>

                                        </span>

                                    </div>


                                </div>

                            </td>



                            <!-- EMAIL -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row['email']
                                );
                                ?>

                            </td>



                            <!-- PHONE -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row['phone_no'] ?? '-'
                                );
                                ?>

                            </td>



                            <!-- ROLE -->

                            <td>

                                <span
                                    class="badge <?php
                                        echo $roleClass;
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $role
                                    );
                                    ?>

                                </span>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <span
                                    class="badge <?php
                                        echo $statusClass;
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $accountStatus
                                    );
                                    ?>

                                </span>

                            </td>



                            <!-- ACTIONS -->

                            <td>


                                <?php if (
                                    $username === $currentUser
                                ): ?>


                                    <span class="self">

                                        <i class="fa-solid fa-lock"></i>

                                        Current
                                        <?php
                                        echo htmlspecialchars(
                                            $currentRole
                                        );
                                        ?>

                                    </span>


                                <?php else: ?>


                                    <div class="actions">


                                        <?php if (
                                            $accountStatus === 'Active'
                                        ): ?>


                                            <!-- DEACTIVATE -->

                                            <a
                                                class="action deactivate"
                                                href="manage_users.php?status=Inactive&user=<?php
                                                    echo urlencode(
                                                        $username
                                                    );
                                                ?>"
                                                onclick="return confirm('Deactivate this user?');"
                                            >

                                                <i class="fa-solid fa-ban"></i>

                                                Deactivate

                                            </a>


                                        <?php else: ?>


                                            <!-- ACTIVATE -->

                                            <a
                                                class="action activate"
                                                href="manage_users.php?status=Active&user=<?php
                                                    echo urlencode(
                                                        $username
                                                    );
                                                ?>"
                                                onclick="return confirm('Activate this user?');"
                                            >

                                                <i class="fa-solid fa-check"></i>

                                                Activate

                                            </a>


                                        <?php endif; ?>


                                        <!-- DELETE -->

                                        <a
                                            class="action delete"
                                            href="manage_users.php?delete=<?php
                                                echo urlencode(
                                                    $username
                                                );
                                            ?>"
                                            onclick="return confirm('Are you sure you want to permanently delete this user?');"
                                        >

                                            <i class="fa-solid fa-trash"></i>

                                            Delete

                                        </a>


                                    </div>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php endif; ?>


                </tbody>

            </table>


        </div>

    </section>



    <!-- FOOTER -->

    <div class="footer-note">

        PharmacyX · Secure User Administration

    </div>


</main>



<!-- =================================================
     LIGHT / DARK MODE
================================================== -->

<script>

(function () {

    const body =
        document.body;

    const button =
        document.getElementById(
            "themeToggle"
        );


    /*
    ================================================
    GET SAVED THEME
    ================================================
    */

    const savedTheme =
        localStorage.getItem(
            "pharmacyx_admin_theme"
        );


    /*
    ================================================
    APPLY SAVED THEME
    ================================================
    */

    if (
        savedTheme === "dark"
    ) {

        body.classList.add(
            "dark"
        );
    }


    /*
    ================================================
    UPDATE ICON
    ================================================
    */

    function updateIcon() {

        if (
            body.classList.contains(
                "dark"
            )
        ) {

            button.textContent =
                "☀️";

            button.title =
                "Switch to light mode";

        } else {

            button.textContent =
                "🌙";

            button.title =
                "Switch to dark mode";
        }
    }


    updateIcon();


    /*
    ================================================
    TOGGLE THEME
    ================================================
    */

    button.addEventListener(
        "click",
        function () {

            body.classList.toggle(
                "dark"
            );


            const isDark =
                body.classList.contains(
                    "dark"
                );


            localStorage.setItem(
                "pharmacyx_admin_theme",
                isDark
                    ? "dark"
                    : "light"
            );


            updateIcon();

        }
    );

})();

</script>


</body>

</html>