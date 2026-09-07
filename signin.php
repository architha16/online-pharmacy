<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


/*
====================================================
GET ROLE
====================================================
*/

$role = "Customer";

if (isset($_POST['role'])) {

    $role = $_POST['role'];

}
elseif (isset($_GET['role'])) {

    $role = $_GET['role'];

}


/*
====================================================
VALID ROLES
====================================================
*/

$allowedRoles = [
    "Admin",
    "Manager",
    "Pharmacist",
    "Customer"
];


if (!in_array($role, $allowedRoles)) {

    $role = "Customer";

}


/*
====================================================
SEPARATE SESSION NAMES
====================================================
*/

$sessionNames = [

    "Admin"       => "PHARMACYX_ADMIN",

    "Manager"     => "PHARMACYX_MANAGER",

    "Pharmacist"  => "PHARMACYX_PHARMACIST",

    "Customer"    => "PHARMACYX_CUSTOMER"

];


/*
====================================================
START ROLE-SPECIFIC SESSION
====================================================
*/

session_name($sessionNames[$role]);

session_start();


/*
====================================================
DATABASE
====================================================
*/

require_once "./db_Config/config.php";


$errors = "";


/*
====================================================
LOGIN
====================================================
*/

if (isset($_POST['signin'])) {


    $user_name = trim($_POST['username']);

    $password = trim($_POST['password']);

    $role = $_POST['role'];


    /*
    ================================================
    CHECK ROLE
    ================================================
    */

    if (!in_array($role, $allowedRoles)) {

        $errors = "Invalid user role.";

    }


    /*
    ================================================
    CHECK EMPTY FIELDS
    ================================================
    */

    elseif (
        empty($user_name) ||
        empty($password)
    ) {

        $errors = "Please enter Username and Password.";

    }


    else {


        /*
        ============================================
        FIND USER
        ============================================
        */

        $sql = "
            SELECT *
            FROM user_info
            WHERE user_name = ?
            AND user_type = ?
            LIMIT 1
        ";


        $stmt = mysqli_prepare(
            $Connection,
            $sql
        );


        if (!$stmt) {

            die(
                "Database Error: " .
                mysqli_error($Connection)
            );

        }


        mysqli_stmt_bind_param(
            $stmt,
            "ss",
            $user_name,
            $role
        );


        mysqli_stmt_execute($stmt);


        $result = mysqli_stmt_get_result($stmt);


        /*
        ============================================
        USER FOUND
        ============================================
        */

        if (mysqli_num_rows($result) == 1) {


            $row = mysqli_fetch_assoc($result);


            /*
            ========================================
            PASSWORD
            ========================================
            */

            if ($row['password'] !== $password) {

                $errors = "Incorrect Password.";

            }


            /*
            ========================================
            ACCOUNT STATUS
            ========================================
            */

            elseif ($row['acc_status'] !== "Active") {

                $errors =
                    "Your account is deactivated.";

            }


            /*
            ========================================
            LOGIN SUCCESS
            ========================================
            */

            else {


                /*
                ====================================
                REGENERATE SESSION ID
                ====================================
                */

                session_regenerate_id(true);


                /*
                ====================================
                STORE USER INFORMATION
                ====================================
                */

                $_SESSION['username'] =
                    $row['user_name'];


                $_SESSION['firstname'] =
                    $row['first_name'];


                $_SESSION['user_type'] =
                    $row['user_type'];


                $_SESSION['profilePic_url'] =
                    $row['profilepic_url'];


                /*
                ====================================
                REDIRECT BASED ON ROLE
                ====================================
                */

                switch ($row['user_type']) {


                    case "Admin":

                        header(
                            "Location: admin_DB.php"
                        );

                        exit();


                    case "Manager":

                        header(
                            "Location: manager_DB.php"
                        );

                        exit();


                    case "Pharmacist":

                        header(
                            "Location: pharmacist_dashboard.php"
                        );

                        exit();


                    case "Customer":

                        header(
                            "Location: products.php"
                        );

                        exit();

                }

            }

        }

        else {

            $errors =
                "Invalid Username or Password.";

        }


        mysqli_stmt_close($stmt);

    }

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

<?php echo htmlspecialchars($role); ?> Login

</title>


<link
    rel="stylesheet"
    href="./CSS/signin.css"
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
    href="./Images/Pharmacy X Icon.png"
>


<style>

body {

    margin: 0;

    font-family: Roboto, Arial, sans-serif;

    background: #f4f7fb;

}


.main-container {

    display: flex;

    justify-content: center;

    align-items: center;

    min-height: 100vh;

}


.login-box {

    background: white;

    width: 420px;

    padding: 35px;

    border-radius: 10px;

    box-shadow:
        0 0 20px rgba(0,0,0,.15);

}


.login-box h2 {

    text-align: center;

    margin-bottom: 25px;

    color: #0077cc;

}


.top-role {

    text-align: center;

    font-size: 18px;

    margin-bottom: 20px;

    font-weight: bold;

    color: #444;

}


input[type="text"],
input[type="password"] {

    width: 100%;

    padding: 12px;

    margin-top: 10px;

    margin-bottom: 18px;

    border: 1px solid #ccc;

    border-radius: 5px;

    font-size: 15px;

    box-sizing: border-box;

}


.showpw {

    margin-top: -8px;

    margin-bottom: 20px;

}


.showpw input {

    width: auto;

    margin-right: 5px;

}


button {

    width: 100%;

    padding: 12px;

    background: #0077cc;

    color: white;

    border: none;

    border-radius: 5px;

    font-size: 16px;

    cursor: pointer;

}


button:hover {

    background: #005fa3;

}


.error {

    text-align: center;

    color: red;

    font-weight: bold;

    margin-top: 15px;

}


.register {

    text-align: center;

    margin-top: 20px;

}


.register a {

    text-decoration: none;

    color: #0077cc;

    font-weight: bold;

}


.back {

    text-align: center;

    margin-top: 20px;

}


.back a {

    color: #0077cc;

    text-decoration: none;

}

</style>

</head>


<body>


<div class="main-container">


<div class="login-box">


<h2>

PharmacyX

</h2>


<div class="top-role">

<?php

echo htmlspecialchars($role);

?>

Login

</div>


<form method="POST">


<!-- ROLE -->

<input
    type="hidden"
    name="role"
    value="<?php echo htmlspecialchars($role); ?>"
>


<!-- USERNAME -->

<input
    type="text"
    name="username"
    placeholder="Username"
    required
>


<!-- PASSWORD -->

<input
    type="password"
    name="password"
    id="password"
    placeholder="Password"
    required
>


<!-- SHOW PASSWORD -->

<div class="showpw">

<input
    type="checkbox"
    onclick="showPassword()"
>

Show Password

</div>


<!-- LOGIN -->

<button
    type="submit"
    name="signin"
>

Login

</button>


<!-- ERROR -->

<?php

if (!empty($errors)) {

?>

<div class="error">

<?php

echo htmlspecialchars($errors);

?>

</div>

<?php

}

?>


<!-- CUSTOMER REGISTER -->

<?php

if ($role == "Customer") {

?>

<div class="register">

Don't have an account?

<br><br>

<a href="register.php">

Create Customer Account

</a>

</div>

<?php

}

?>


</form>


<div class="back">

<a href="index.php">

← Back to Home

</a>

</div>


</div>


</div>mkdir tests\selenium\pages


<script>

function showPassword()
{

    const password =
        document.getElementById("password");


    if (
        password.type === "password"
    ) {

        password.type = "text";

    }
    else {

        password.type = "password";

    }

}

</script>


</body>

</html>