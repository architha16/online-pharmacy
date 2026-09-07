<?php

/*
=========================================================
PHARMACYX PHARMACIST LOGIN
=========================================================
*/

/*
    IMPORTANT:
    Pharmacist gets its own separate session.
*/

session_name("PHARMACYX_PHARMACIST");

session_start();

require_once "./db_Config/config.php";

$error = "";


/*
=========================================================
PHARMACIST LOGIN
=========================================================
*/

if (isset($_POST['login'])) {

    $username =
        trim($_POST['username']);

    $password =
        trim($_POST['password']);


    /*
    Check empty fields
    */

    if (
        empty($username) ||
        empty($password)
    ) {

        $error =
            "Please enter Username and Password.";

    }

    else {

        /*
        ================================================
        GET PHARMACIST
        ================================================
        */

        $query = "

            SELECT *

            FROM user_info

            WHERE user_name = ?

            AND user_type = 'Pharmacist'

            LIMIT 1

        ";


        $stmt =
            mysqli_prepare(
                $Connection,
                $query
            );


        if (!$stmt) {

            die(
                "Database Error: " .
                mysqli_error($Connection)
            );

        }


        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $username
        );


        mysqli_stmt_execute($stmt);


        $result =
            mysqli_stmt_get_result($stmt);


        /*
        ================================================
        USER FOUND
        ================================================
        */

        if (
            mysqli_num_rows($result) == 1
        ) {

            $user =
                mysqli_fetch_assoc($result);


            /*
            ============================================
            CHECK PASSWORD
            ============================================
            */

            if (
                $user['password'] !== $password
            ) {

                $error =
                    "Incorrect Password.";

            }


            /*
            ============================================
            CHECK ACCOUNT STATUS
            ============================================
            */

            elseif (
                $user['acc_status'] !== 'Active'
            ) {

                $error =
                    "Your account is inactive.";

            }


            /*
            ============================================
            LOGIN SUCCESS
            ============================================
            */

            else {


                /*
                ========================================
                SAVE PHARMACIST SESSION
                ========================================
                */

                $_SESSION['username'] =
                    $user['user_name'];


                $_SESSION['firstname'] =
                    $user['first_name'];


                $_SESSION['user_type'] =
                    $user['user_type'];


                if (
                    isset(
                        $user['profilepic_url']
                    )
                ) {

                    $_SESSION['profilePic_url'] =
                        $user['profilepic_url'];

                }


                /*
                ========================================
                REDIRECT
                ========================================
                */

                header(
                    "Location: pharmacist_dashboard.php"
                );

                exit();

            }

        }

        else {

            $error =
                "Invalid Pharmacist Username.";

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
    Pharmacist Login | PharmacyX
</title>


<style>


/*
=========================================================
BODY
=========================================================
*/

body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #f4f7fb;

}


/*
=========================================================
CONTAINER
=========================================================
*/

.container {

    min-height: 100vh;

    display: flex;

    justify-content: center;

    align-items: center;

}


/*
=========================================================
LOGIN BOX
=========================================================
*/

.login-box {

    width: 400px;

    max-width: 90%;

    background: white;

    padding: 35px;

    border-radius: 12px;

    box-shadow:
        0 5px 25px
        rgba(0,0,0,0.15);

}


/*
=========================================================
TITLE
=========================================================
*/

h2 {

    text-align: center;

    color: #008fd5;

    margin-bottom: 10px;

}


/*
=========================================================
ROLE
=========================================================
*/

.role {

    text-align: center;

    margin-bottom: 25px;

    font-weight: bold;

    color: #555;

}


/*
=========================================================
LABEL
=========================================================
*/

label {

    display: block;

    margin-top: 15px;

    margin-bottom: 6px;

}


/*
=========================================================
INPUT
=========================================================
*/

input[type="text"],
input[type="password"] {

    width: 100%;

    box-sizing: border-box;

    padding: 12px;

    border:
        1px solid #ccc;

    border-radius: 6px;

    font-size: 15px;

}


/*
=========================================================
SHOW PASSWORD
=========================================================
*/

.show-password {

    margin-top: 12px;

    margin-bottom: 20px;

}


/*
=========================================================
LOGIN BUTTON
=========================================================
*/

button {

    width: 100%;

    padding: 13px;

    border: none;

    border-radius: 6px;

    background: #008fd5;

    color: white;

    font-size: 16px;

    cursor: pointer;

}


button:hover {

    background: #0073ad;

}


/*
=========================================================
ERROR
=========================================================
*/

.error {

    margin-top: 18px;

    text-align: center;

    color: red;

    font-weight: bold;

}


/*
=========================================================
BACK
=========================================================
*/

.back {

    text-align: center;

    margin-top: 20px;

}


.back a {

    color: #008fd5;

    text-decoration: none;

}


.back a:hover {

    text-decoration: underline;

}


</style>


</head>


<body>


<div class="container">


<div class="login-box">


<h2>

PharmacyX

</h2>


<div class="role">

⚕️ Pharmacist Login

</div>


<form
    method="POST"
>


<label>

Username

</label>


<input
    type="text"
    name="username"
    placeholder="Enter Username"
    required
>


<label>

Password

</label>


<input
    type="password"
    name="password"
    id="password"
    placeholder="Enter Password"
    required
>


<div class="show-password">


<input
    type="checkbox"
    onclick="showPassword()"
>

Show Password


</div>


<button
    type="submit"
    name="login"
>

Login

</button>


<?php

if (
    !empty($error)
) {

?>

<div class="error">

<?php

echo htmlspecialchars(
    $error
);

?>

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


</div>


<script>

function showPassword()
{

    const password =
        document.getElementById(
            "password"
        );


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