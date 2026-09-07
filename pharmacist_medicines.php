<?php
/*
=========================================================
PHARMACYX - PHARMACIST MEDICINE MANAGEMENT
=========================================================
ONLY PHARMACIST CAN CHANGE PRESCRIPTION STATUS

Prescription Options:
    Yes = Prescription Required
    No  = Prescription Not Required

Database:
    Products.prescription_required
=========================================================
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);


/* =====================================================
   PHARMACIST SESSION
===================================================== */

session_name("PHARMACYX_PHARMACIST");
session_start();


/* =====================================================
   DATABASE CONNECTION
===================================================== */

require_once "./db_Config/config.php";


/* =====================================================
   PHARMACIST LOGIN CHECK
===================================================== */

if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Pharmacist'
) {
    header("Location: signin.php?role=Pharmacist");
    exit();
}


$username = $_SESSION['username'];


/* =====================================================
   MESSAGES
===================================================== */

$success_message = "";
$error_message = "";


/* =====================================================
   UPDATE PRESCRIPTION STATUS
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_id = isset($_POST['product_id'])
        ? filter_var($_POST['product_id'], FILTER_VALIDATE_INT)
        : false;

    $prescription_required = isset($_POST['prescription_required'])
        ? trim($_POST['prescription_required'])
        : "";


    /* -------------------------------------------------
       VALIDATE INPUT
    ------------------------------------------------- */

    if (
        $product_id === false ||
        $product_id <= 0
    ) {

        $error_message = "Invalid medicine selected.";

    } elseif (
        !in_array(
            $prescription_required,
            ['Yes', 'No'],
            true
        )
    ) {

        $error_message = "Invalid prescription option selected.";

    } else {

        /* -------------------------------------------------
           CHECK MEDICINE EXISTS
        ------------------------------------------------- */

        $check_sql = "
            SELECT
                product_id,
                product_name
            FROM Products
            WHERE product_id = ?
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare(
            $Connection,
            $check_sql
        );


        if (!$check_stmt) {

            $error_message = "Database error while checking medicine.";

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "i",
                $product_id
            );

            mysqli_stmt_execute($check_stmt);

            $check_result =
                mysqli_stmt_get_result($check_stmt);

            $product =
                mysqli_fetch_assoc($check_result);

            mysqli_stmt_close($check_stmt);


            if (!$product) {

                $error_message =
                    "Medicine not found.";

            } else {

                /* -------------------------------------------------
                   UPDATE PRESCRIPTION STATUS
                ------------------------------------------------- */

                $update_sql = "
                    UPDATE Products
                    SET prescription_required = ?
                    WHERE product_id = ?
                ";

                $update_stmt = mysqli_prepare(
                    $Connection,
                    $update_sql
                );


                if (!$update_stmt) {

                    $error_message =
                        "Unable to update medicine.";

                } else {

                    mysqli_stmt_bind_param(
                        $update_stmt,
                        "si",
                        $prescription_required,
                        $product_id
                    );


                    if (
                        mysqli_stmt_execute(
                            $update_stmt
                        )
                    ) {

                        $medicine_name =
                            htmlspecialchars(
                                $product['product_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            );


                        if (
                            $prescription_required === 'Yes'
                        ) {

                            $success_message =
                                "Medicine <strong>"
                                . $medicine_name
                                . "</strong> is now marked as "
                                . "<strong>Prescription Required</strong>.";

                        } else {

                            $success_message =
                                "Medicine <strong>"
                                . $medicine_name
                                . "</strong> is now marked as "
                                . "<strong>Prescription Not Required</strong>.";
                        }

                    } else {

                        $error_message =
                            "Failed to update prescription status.";
                    }


                    mysqli_stmt_close(
                        $update_stmt
                    );
                }
            }
        }
    }
}


/* =====================================================
   SEARCH
===================================================== */

$search = "";

if (isset($_GET['search'])) {

    $search =
        trim($_GET['search']);
}


/* =====================================================
   FETCH MEDICINES
===================================================== */

if ($search !== "") {

    $search_value =
        "%" . $search . "%";


    $sql = "
        SELECT
            product_id,
            product_name,
            product_description,
            price,
            stock_quantity,
            image_url,
            expire_date,
            prescription_required
        FROM Products
        WHERE product_name LIKE ?
        ORDER BY product_id DESC
    ";


    $stmt = mysqli_prepare(
        $Connection,
        $sql
    );


    if (!$stmt) {

        $error_message =
            "Unable to load medicines.";

        $result = false;

    } else {

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $search_value
        );

        mysqli_stmt_execute($stmt);

        $result =
            mysqli_stmt_get_result($stmt);
    }

} else {

    $sql = "
        SELECT
            product_id,
            product_name,
            product_description,
            price,
            stock_quantity,
            image_url,
            expire_date,
            prescription_required
        FROM Products
        ORDER BY product_id DESC
    ";


    $result =
        mysqli_query(
            $Connection,
            $sql
        );
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
        Manage Medicine Prescriptions | PharmacyX
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            padding: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f4f7fb;

            color: #222;
        }


        /* =================================================
           HEADER
        ================================================= */

        .top-header {

            background: white;

            padding: 18px 35px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            border-bottom: 1px solid #ddd;

            box-shadow:
                0 2px 8px rgba(0,0,0,0.08);
        }


        .logo {

            font-size: 26px;

            font-weight: bold;

            color: #1f6feb;
        }


        .logo span {

            color: #16a085;
        }


        .pharmacist-info {

            display: flex;

            align-items: center;

            gap: 15px;
        }


        .role-badge {

            background: #e8f4ff;

            color: #1264a3;

            padding: 8px 14px;

            border-radius: 20px;

            font-size: 14px;

            font-weight: bold;
        }


        .username {

            font-weight: bold;

            color: #333;
        }


        .dashboard-btn {

            text-decoration: none;

            background: #1f6feb;

            color: white;

            padding: 10px 17px;

            border-radius: 6px;

            font-size: 14px;

            font-weight: bold;
        }


        .dashboard-btn:hover {

            background: #1557b0;
        }


        /* =================================================
           MAIN CONTAINER
        ================================================= */

        .container {

            width: 95%;

            max-width: 1450px;

            margin: 30px auto;
        }


        .page-title {

            margin: 0 0 8px;

            font-size: 30px;

            color: #222;
        }


        .page-description {

            margin: 0 0 25px;

            color: #666;

            font-size: 15px;
        }


        /* =================================================
           SUCCESS / ERROR
        ================================================= */

        .success-message {

            background: #e7f8ee;

            color: #18794e;

            border: 1px solid #a9e6c4;

            padding: 14px 18px;

            border-radius: 7px;

            margin-bottom: 20px;

            font-size: 15px;
        }


        .error-message {

            background: #fdeaea;

            color: #b42318;

            border: 1px solid #f2b8b8;

            padding: 14px 18px;

            border-radius: 7px;

            margin-bottom: 20px;

            font-size: 15px;
        }


        /* =================================================
           INFORMATION BOX
        ================================================= */

        .info-box {

            background: #eef7ff;

            border-left: 5px solid #1f6feb;

            padding: 17px 20px;

            border-radius: 6px;

            margin-bottom: 25px;

            color: #31516f;

            line-height: 1.6;
        }


        .info-box strong {

            color: #174a78;
        }


        /* =================================================
           SEARCH
        ================================================= */

        .search-box {

            background: white;

            padding: 18px;

            border-radius: 9px;

            box-shadow:
                0 2px 10px rgba(0,0,0,0.06);

            margin-bottom: 25px;
        }


        .search-form {

            display: flex;

            gap: 10px;
        }


        .search-input {

            flex: 1;

            padding: 12px 14px;

            border: 1px solid #ccc;

            border-radius: 6px;

            font-size: 15px;

            outline: none;
        }


        .search-input:focus {

            border-color: #1f6feb;
        }


        .search-btn {

            border: none;

            background: #1f6feb;

            color: white;

            padding: 12px 22px;

            border-radius: 6px;

            cursor: pointer;

            font-size: 15px;

            font-weight: bold;
        }


        .search-btn:hover {

            background: #1557b0;
        }


        .clear-btn {

            text-decoration: none;

            background: #777;

            color: white;

            padding: 12px 20px;

            border-radius: 6px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: bold;
        }


        .clear-btn:hover {

            background: #555;
        }


        /* =================================================
           TABLE
        ================================================= */

        .table-container {

            background: white;

            border-radius: 10px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.08);

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 1200px;
        }


        thead {

            background: #1f6feb;

            color: white;
        }


        th {

            padding: 15px 12px;

            text-align: left;

            font-size: 14px;

            white-space: nowrap;
        }


        td {

            padding: 14px 12px;

            border-bottom: 1px solid #eee;

            vertical-align: middle;

            font-size: 14px;
        }


        tbody tr:hover {

            background: #f8fbff;
        }


        /* =================================================
           IMAGE
        ================================================= */

        .product-image {

            width: 70px;

            height: 70px;

            object-fit: contain;

            border-radius: 8px;

            border: 1px solid #ddd;

            background: white;

            padding: 3px;
        }


        .no-image {

            width: 70px;

            height: 70px;

            border: 1px solid #ddd;

            border-radius: 8px;

            display: flex;

            align-items: center;

            justify-content: center;

            text-align: center;

            font-size: 11px;

            color: #999;

            background: #f7f7f7;
        }


        /* =================================================
           MEDICINE
        ================================================= */

        .medicine-name {

            font-weight: bold;

            color: #222;

            font-size: 15px;

            margin-bottom: 4px;
        }


        .product-id {

            color: #777;

            font-size: 12px;
        }


        .description {

            max-width: 260px;

            color: #666;

            line-height: 1.4;
        }


        /* =================================================
           STOCK
        ================================================= */

        .stock-good {

            color: #16803c;

            font-weight: bold;
        }


        .stock-low {

            color: #c77700;

            font-weight: bold;
        }


        .stock-out {

            color: #c62828;

            font-weight: bold;
        }


        /* =================================================
           PRESCRIPTION STATUS
        ================================================= */

        .status-required {

            display: inline-block;

            background: #fff0f0;

            color: #c62828;

            border: 1px solid #f1b6b6;

            padding: 8px 13px;

            border-radius: 20px;

            font-weight: bold;

            font-size: 13px;

            white-space: nowrap;
        }


        .status-not-required {

            display: inline-block;

            background: #eaf8ef;

            color: #18794e;

            border: 1px solid #b7e4c7;

            padding: 8px 13px;

            border-radius: 20px;

            font-weight: bold;

            font-size: 13px;

            white-space: nowrap;
        }


        /* =================================================
           PRESCRIPTION FORM
        ================================================= */

        .prescription-form {

            display: flex;

            align-items: center;

            gap: 8px;

            min-width: 310px;
        }


        .prescription-select {

            padding: 10px;

            border: 1px solid #ccc;

            border-radius: 6px;

            background: white;

            font-size: 13px;

            min-width: 205px;

            cursor: pointer;

            outline: none;
        }


        .prescription-select:focus {

            border-color: #1f6feb;
        }


        .save-btn {

            border: none;

            background: #16a085;

            color: white;

            padding: 10px 14px;

            border-radius: 6px;

            cursor: pointer;

            font-weight: bold;

            white-space: nowrap;
        }


        .save-btn:hover {

            background: #12816a;
        }


        /* =================================================
           EMPTY
        ================================================= */

        .empty-message {

            text-align: center;

            padding: 55px 20px;

            color: #777;

            font-size: 17px;
        }


        /* =================================================
           FOOTER
        ================================================= */

        .footer {

            text-align: center;

            color: #777;

            padding: 30px 10px;

            font-size: 13px;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 768px) {

            .top-header {

                padding: 15px;

                flex-direction: column;

                gap: 15px;

                align-items: flex-start;
            }


            .pharmacist-info {

                width: 100%;

                flex-wrap: wrap;
            }


            .container {

                width: 97%;

                margin-top: 20px;
            }


            .page-title {

                font-size: 24px;
            }


            .search-form {

                flex-direction: column;
            }


            .search-btn,
            .clear-btn {

                width: 100%;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<div class="top-header">

    <div class="logo">
        Pharmacy<span>X</span>
    </div>


    <div class="pharmacist-info">

        <div class="role-badge">
            💊 Pharmacist
        </div>


        <div class="username">

            <?php

            echo htmlspecialchars(
                $username,
                ENT_QUOTES,
                'UTF-8'
            );

            ?>

        </div>


        <a
            href="pharmacist_dashboard.php"
            class="dashboard-btn"
        >
            ← Dashboard
        </a>

    </div>

</div>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="container">


    <h1 class="page-title">
        💊 Manage Medicine Prescriptions
    </h1>


    <p class="page-description">

        Pharmacists can manually decide whether each medicine
        requires a prescription.

    </p>



    <!-- =================================================
         SUCCESS MESSAGE
    ================================================= -->

    <?php if ($success_message !== ""): ?>

        <div class="success-message">

            ✅

            <?php
            echo $success_message;
            ?>

        </div>

    <?php endif; ?>



    <!-- =================================================
         ERROR MESSAGE
    ================================================= -->

    <?php if ($error_message !== ""): ?>

        <div class="error-message">

            ❌

            <?php

            echo htmlspecialchars(
                $error_message,
                ENT_QUOTES,
                'UTF-8'
            );

            ?>

        </div>

    <?php endif; ?>



    <!-- =================================================
         INFORMATION
    ================================================= -->

    <div class="info-box">

        <strong>Pharmacist Control:</strong>

        Select
        <strong>Prescription Required</strong>
        when the customer must upload a prescription.

        Select
        <strong>Prescription Not Required</strong>
        when the medicine can be purchased normally.

        <br>

        Customers cannot change this setting.

    </div>



    <!-- =================================================
         SEARCH
    ================================================= -->

    <div class="search-box">

        <form
            method="GET"
            action="pharmacist_medicines.php"
            class="search-form"
        >


            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Search medicine name..."
                value="<?php

                    echo htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                ?>"
            >


            <button
                type="submit"
                class="search-btn"
            >
                🔍 Search
            </button>


            <?php if ($search !== ""): ?>

                <a
                    href="pharmacist_medicines.php"
                    class="clear-btn"
                >
                    Clear
                </a>

            <?php endif; ?>


        </form>

    </div>



    <!-- =================================================
         MEDICINES TABLE
    ================================================= -->

    <div class="table-container">


        <?php if (
            $result &&
            mysqli_num_rows($result) > 0
        ): ?>


            <table>


                <thead>

                    <tr>

                        <th>
                            Image
                        </th>

                        <th>
                            Medicine
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Stock
                        </th>

                        <th>
                            Expiry Date
                        </th>

                        <th>
                            Current Status
                        </th>

                        <th>
                            Change Prescription Setting
                        </th>

                    </tr>

                </thead>



                <tbody>


                <?php while (
                    $medicine =
                    mysqli_fetch_assoc($result)
                ): ?>


                    <?php

                    $product_id =
                        (int)$medicine['product_id'];

                    $product_name =
                        $medicine['product_name'];

                    $description =
                        $medicine['product_description'];

                    $price =
                        (float)$medicine['price'];

                    $stock =
                        (int)$medicine['stock_quantity'];

                    $image_url =
                        trim($medicine['image_url']);

                    $expire_date =
                        $medicine['expire_date'];

                    $prescription_required =
                        $medicine['prescription_required'];


                    /*
                    =================================================
                    IMAGE PATH FIX
                    =================================================

                    Database contains only filename:

                    1787743293_9580.webp

                    Actual file:

                    Images/product-icons/1787743293_9580.webp
                    */

                    if ($image_url !== "") {

                        $image_filename =
                            basename(
                                str_replace(
                                    "\\",
                                    "/",
                                    $image_url
                                )
                            );

                        $image_path =
                            "Images/product-icons/"
                            . $image_filename;

                    } else {

                        $image_path = "";

                    }

                    ?>


                    <tr>


                        <!-- =====================================
                             IMAGE
                        ====================================== -->

                        <td>


                            <?php if (
                                $image_path !== ""
                            ): ?>


                                <img
                                    src="<?php

                                        echo htmlspecialchars(
                                            $image_path,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );

                                    ?>"
                                    alt="<?php

                                        echo htmlspecialchars(
                                            $product_name,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );

                                    ?>"
                                    class="product-image"

                                    onerror="
                                        this.style.display='none';
                                        this.nextElementSibling.style.display='flex';
                                    "
                                >


                                <div
                                    class="no-image"
                                    style="display:none;"
                                >
                                    Image<br>
                                    Not Found
                                </div>


                            <?php else: ?>


                                <div class="no-image">
                                    No Image
                                </div>


                            <?php endif; ?>


                        </td>



                        <!-- =====================================
                             MEDICINE
                        ====================================== -->

                        <td>

                            <div class="medicine-name">

                                <?php

                                echo htmlspecialchars(
                                    $product_name,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </div>


                            <div class="product-id">

                                Product ID:
                                <?php
                                echo $product_id;
                                ?>

                            </div>

                        </td>



                        <!-- =====================================
                             DESCRIPTION
                        ====================================== -->

                        <td>

                            <div class="description">

                                <?php

                                if (
                                    !empty($description)
                                ) {

                                    echo htmlspecialchars(
                                        $description,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                } else {

                                    echo "No description available.";

                                }

                                ?>

                            </div>

                        </td>



                        <!-- =====================================
                             PRICE
                        ====================================== -->

                        <td>

                            ₹<?php

                            echo number_format(
                                $price,
                                2
                            );

                            ?>

                        </td>



                        <!-- =====================================
                             STOCK
                        ====================================== -->

                        <td>

                            <?php

                            if ($stock <= 0) {

                                echo '
                                    <span class="stock-out">
                                        Out of Stock
                                    </span>
                                ';

                            } elseif ($stock <= 10) {

                                echo '
                                    <span class="stock-low">
                                        '
                                        . $stock .
                                        ' Low Stock
                                    </span>
                                ';

                            } else {

                                echo '
                                    <span class="stock-good">
                                        '
                                        . $stock .
                                        '
                                    </span>
                                ';
                            }

                            ?>

                        </td>



                        <!-- =====================================
                             EXPIRY DATE
                        ====================================== -->

                        <td>

                            <?php

                            if (
                                !empty($expire_date)
                            ) {

                                echo htmlspecialchars(
                                    $expire_date,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                            } else {

                                echo "N/A";

                            }

                            ?>

                        </td>



                        <!-- =====================================
                             CURRENT PRESCRIPTION STATUS
                        ====================================== -->

                        <td>


                            <?php if (
                                $prescription_required === 'Yes'
                            ): ?>


                                <span class="status-required">

                                    💊 Prescription Required

                                </span>


                            <?php else: ?>


                                <span class="status-not-required">

                                    🛒 Prescription Not Required

                                </span>


                            <?php endif; ?>


                        </td>



                        <!-- =====================================
                             CHANGE PRESCRIPTION STATUS
                        ====================================== -->

                        <td>


                            <form
                                method="POST"
                                action="pharmacist_medicines.php"
                                class="prescription-form"
                            >


                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?php

                                        echo $product_id;

                                    ?>"
                                >


                                <select
                                    name="prescription_required"
                                    class="prescription-select"
                                    required
                                >


                                    <option
                                        value="Yes"

                                        <?php

                                        if (
                                            $prescription_required
                                            === 'Yes'
                                        ) {

                                            echo 'selected';

                                        }

                                        ?>
                                    >

                                        Prescription Required

                                    </option>


                                    <option
                                        value="No"

                                        <?php

                                        if (
                                            $prescription_required
                                            === 'No'
                                        ) {

                                            echo 'selected';

                                        }

                                        ?>
                                    >

                                        Prescription Not Required

                                    </option>


                                </select>


                                <button
                                    type="submit"
                                    class="save-btn"
                                >

                                    💾 Save

                                </button>


                            </form>


                        </td>


                    </tr>


                <?php endwhile; ?>


                </tbody>


            </table>


        <?php else: ?>


            <div class="empty-message">

                <?php if ($search !== ""): ?>

                    🔍 No medicines found for

                    <strong>
                        "<?php

                        echo htmlspecialchars(
                            $search,
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        ?>"
                    </strong>

                <?php else: ?>

                    💊 No medicines available.

                <?php endif; ?>

            </div>


        <?php endif; ?>


    </div>


</div>



<!-- =====================================================
     FOOTER
===================================================== -->

<div class="footer">

    PharmacyX — Pharmacist Medicine Prescription Management

</div>



</body>

</html>


<?php

/* =====================================================
   CLOSE SEARCH STATEMENT
===================================================== */

if (
    isset($stmt) &&
    $stmt
) {

    mysqli_stmt_close($stmt);

}

?>