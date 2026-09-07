<?php

/*
=========================================================
PHARMACYX
CUSTOMER PRESCRIPTION UPLOAD
=========================================================
*/

/*
=========================================================
CUSTOMER SESSION
=========================================================
*/

session_name("PHARMACYX_CUSTOMER");
session_start();


/*
=========================================================
CUSTOMER LOGIN CHECK
=========================================================
*/

if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Customer'
) {

    header("Location: signin.php?role=Customer");
    exit();

}


require_once "./db_Config/config.php";


$user = $_SESSION['username'];


/*
=========================================================
GET PRODUCT ID
=========================================================
*/

$product_id = isset($_GET['product_id'])
    ? intval($_GET['product_id'])
    : 0;


if ($product_id <= 0) {

    echo "
    <script>
        alert('Invalid medicine.');
        window.location='cart.php';
    </script>
    ";

    exit();

}


/*
=========================================================
CHECK WHETHER PRODUCT EXISTS
=========================================================
*/

$productQuery = mysqli_prepare(
    $Connection,
    "SELECT
        product_id,
        product_name,
        price,
        prescription_required
     FROM products
     WHERE product_id = ?
     LIMIT 1"
);


if (!$productQuery) {

    die(
        "Product query failed: " .
        mysqli_error($Connection)
    );

}


mysqli_stmt_bind_param(
    $productQuery,
    "i",
    $product_id
);


mysqli_stmt_execute(
    $productQuery
);


$productResult =
    mysqli_stmt_get_result(
        $productQuery
    );


if (
    !$productResult ||
    mysqli_num_rows($productResult) === 0
) {

    mysqli_stmt_close(
        $productQuery
    );

    echo "
    <script>
        alert('Medicine not found.');
        window.location='cart.php';
    </script>
    ";

    exit();

}


$product =
    mysqli_fetch_assoc(
        $productResult
    );


mysqli_stmt_close(
    $productQuery
);


/*
=========================================================
CHECK PRESCRIPTION REQUIREMENT
=========================================================
*/

if (
    $product['prescription_required'] !== 'Yes'
) {

    echo "
    <script>
        alert('This medicine does not require a prescription.');
        window.location='cart.php';
    </script>
    ";

    exit();

}


/*
=========================================================
IMPORTANT:
CHECK THAT THIS MEDICINE IS IN CUSTOMER CART
=========================================================
*/

$cartQuery = mysqli_prepare(
    $Connection,
    "SELECT cart_id
     FROM cart
     WHERE user_name = ?
       AND product_id = ?
     LIMIT 1"
);


if (!$cartQuery) {

    die(
        "Cart query failed: " .
        mysqli_error($Connection)
    );

}


mysqli_stmt_bind_param(
    $cartQuery,
    "si",
    $user,
    $product_id
);


mysqli_stmt_execute(
    $cartQuery
);


$cartResult =
    mysqli_stmt_get_result(
        $cartQuery
    );


if (
    !$cartResult ||
    mysqli_num_rows($cartResult) === 0
) {

    mysqli_stmt_close(
        $cartQuery
    );

    echo "
    <script>
        alert('This medicine is not in your cart.');
        window.location='cart.php';
    </script>
    ";

    exit();

}


mysqli_stmt_close(
    $cartQuery
);


/*
=========================================================
CHECK LATEST PRESCRIPTION
=========================================================
*/

$latestStatus = "";
$latestReason = "";
$latestFile = "";


$latestQuery = mysqli_prepare(
    $Connection,
    "SELECT
        status,
        rejection_reason,
        prescription_file
     FROM prescriptions
     WHERE user_name = ?
       AND product_id = ?
     ORDER BY id DESC
     LIMIT 1"
);


if ($latestQuery) {

    mysqli_stmt_bind_param(
        $latestQuery,
        "si",
        $user,
        $product_id
    );


    mysqli_stmt_execute(
        $latestQuery
    );


    $latestResult =
        mysqli_stmt_get_result(
            $latestQuery
        );


    if (
        $latestResult &&
        mysqli_num_rows($latestResult) > 0
    ) {

        $latest =
            mysqli_fetch_assoc(
                $latestResult
            );


        $latestStatus =
            $latest['status'] ?? "";


        $latestReason =
            $latest['rejection_reason'] ?? "";


        $latestFile =
            $latest['prescription_file'] ?? "";

    }


    mysqli_stmt_close(
        $latestQuery
    );

}


/*
=========================================================
CUSTOMER INFORMATION
=========================================================
*/

$patient_name = "";
$mobile = "";


$customerQuery = mysqli_prepare(
    $Connection,
    "SELECT
        first_name,
        last_name,
        phone_no
     FROM user_info
     WHERE user_name = ?
     LIMIT 1"
);


if ($customerQuery) {

    mysqli_stmt_bind_param(
        $customerQuery,
        "s",
        $user
    );


    mysqli_stmt_execute(
        $customerQuery
    );


    $customerResult =
        mysqli_stmt_get_result(
            $customerQuery
        );


    if (
        $customerResult &&
        mysqli_num_rows($customerResult) > 0
    ) {

        $customer =
            mysqli_fetch_assoc(
                $customerResult
            );


        $patient_name =
            trim(
                ($customer['first_name'] ?? '') .
                " " .
                ($customer['last_name'] ?? '')
            );


        $mobile =
            trim(
                $customer['phone_no'] ?? ''
            );

    }


    mysqli_stmt_close(
        $customerQuery
    );

}


/*
=========================================================
PROCESS UPLOAD
=========================================================
*/

$message = "";
$messageType = "";


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upload'])
) {


    /*
    -----------------------------------------------------
    PATIENT DETAILS
    -----------------------------------------------------
    */

    $patient_name =
        trim(
            $_POST['patient_name'] ?? ''
        );


    $mobile =
        trim(
            $_POST['mobile'] ?? ''
        );


    /*
    -----------------------------------------------------
    VALIDATE DETAILS
    -----------------------------------------------------
    */

    if (
        $patient_name === '' ||
        $mobile === ''
    ) {

        $message =
            "Please enter patient name and mobile number.";

        $messageType = "error";

    }


    /*
    -----------------------------------------------------
    CHECK FILE
    -----------------------------------------------------
    */

    elseif (
        !isset($_FILES['prescription']) ||
        $_FILES['prescription']['error'] !== UPLOAD_ERR_OK
    ) {

        $message =
            "Please select a prescription file.";

        $messageType = "error";

    }


    else {


        $file =
            $_FILES['prescription'];


        $originalName =
            basename(
                $file['name']
            );


        $temporaryFile =
            $file['tmp_name'];


        $fileSize =
            intval(
                $file['size']
            );


        /*
        -------------------------------------------------
        FILE EXTENSION
        -------------------------------------------------
        */

        $extension =
            strtolower(
                pathinfo(
                    $originalName,
                    PATHINFO_EXTENSION
                )
            );


        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'pdf'
        ];


        if (
            !in_array(
                $extension,
                $allowedExtensions,
                true
            )
        ) {

            $message =
                "Only JPG, JPEG, PNG and PDF files are allowed.";

            $messageType = "error";

        }


        /*
        -------------------------------------------------
        MAXIMUM SIZE = 5 MB
        -------------------------------------------------
        */

        elseif (
            $fileSize > 5 * 1024 * 1024
        ) {

            $message =
                "Prescription file must be less than 5 MB.";

            $messageType = "error";

        }


        else {


            /*
            -------------------------------------------------
            VERIFY MIME TYPE
            -------------------------------------------------
            */

            $allowedMimeTypes = [
                'image/jpeg',
                'image/png',
                'application/pdf'
            ];


            $finfo =
                finfo_open(
                    FILEINFO_MIME_TYPE
                );


            $mimeType =
                finfo_file(
                    $finfo,
                    $temporaryFile
                );


            finfo_close(
                $finfo
            );


            if (
                !in_array(
                    $mimeType,
                    $allowedMimeTypes,
                    true
                )
            ) {

                $message =
                    "Invalid prescription file.";

                $messageType = "error";

            }


            /*
            -------------------------------------------------
            CREATE UPLOAD FOLDER
            -------------------------------------------------
            */

            else {


                $uploadFolder =
                    __DIR__ .
                    "/uploads/";


                if (
                    !is_dir(
                        $uploadFolder
                    )
                ) {

                    if (
                        !mkdir(
                            $uploadFolder,
                            0755,
                            true
                        )
                    ) {

                        $message =
                            "Unable to create uploads folder.";

                        $messageType = "error";

                    }

                }


                if (
                    $message === ""
                ) {


                    /*
                    -----------------------------------------
                    SAFE FILE NAME
                    -----------------------------------------
                    */

                    $safeOriginalName =
                        preg_replace(
                            "/[^A-Za-z0-9._-]/",
                            "_",
                            $originalName
                        );


                    $newFileName =
                        "prescription_" .
                        $product_id .
                        "_" .
                        time() .
                        "_" .
                        bin2hex(
                            random_bytes(5)
                        ) .
                        "_" .
                        $safeOriginalName;


                    $destination =
                        $uploadFolder .
                        $newFileName;


                    /*
                    -----------------------------------------
                    MOVE FILE
                    -----------------------------------------
                    */

                    if (
                        !move_uploaded_file(
                            $temporaryFile,
                            $destination
                        )
                    ) {

                        $message =
                            "Failed to upload prescription.";

                        $messageType = "error";

                    }


                    else {


                        /*
                        -------------------------------------
                        INSERT DATABASE RECORD
                        -------------------------------------
                        */

                        $insertQuery =
                            mysqli_prepare(
                                $Connection,

                                "INSERT INTO prescriptions
                                (
                                    user_name,
                                    product_id,
                                    patient_name,
                                    mobile,
                                    prescription_file,
                                    status,
                                    rejection_reason
                                )
                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    'Pending',
                                    NULL
                                )"
                            );


                        if (!$insertQuery) {


                            /*
                            Delete uploaded file if
                            database query fails.
                            */

                            if (
                                file_exists(
                                    $destination
                                )
                            ) {

                                unlink(
                                    $destination
                                );

                            }


                            die(
                                "Database preparation failed: " .
                                mysqli_error($Connection)
                            );

                        }


                        mysqli_stmt_bind_param(
                            $insertQuery,
                            "sisss",
                            $user,
                            $product_id,
                            $patient_name,
                            $mobile,
                            $newFileName
                        );


                        if (
                            mysqli_stmt_execute(
                                $insertQuery
                            )
                        ) {


                            mysqli_stmt_close(
                                $insertQuery
                            );


                            /*
                            ---------------------------------
                            SUCCESS
                            ---------------------------------
                            */

                            echo "
                            <script>

                                alert(
                                    'Prescription uploaded successfully. Please wait for pharmacist approval.'
                                );

                                window.location.href =
                                    'cart.php';

                            </script>
                            ";

                            exit();

                        }


                        else {


                            $databaseError =
                                mysqli_stmt_error(
                                    $insertQuery
                                );


                            mysqli_stmt_close(
                                $insertQuery
                            );


                            if (
                                file_exists(
                                    $destination
                                )
                            ) {

                                unlink(
                                    $destination
                                );

                            }


                            $message =
                                "Database Error: " .
                                $databaseError;

                            $messageType =
                                "error";

                        }

                    }

                }

            }

        }

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
    Upload Prescription | PharmacyX
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    padding: 40px 20px;

    font-family:
        Arial,
        sans-serif;

    background:
        #f4f7fb;

    color:
        #222;

}


.container {

    width: 450px;

    max-width: 100%;

    margin: auto;

}


.card {

    background:
        white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 0 15px
        rgba(0,0,0,0.15);

}


h2 {

    text-align:
        center;

    color:
        #0077cc;

    margin-top: 0;

    margin-bottom:
        25px;

}


.product-box {

    background:
        #eef6ff;

    padding:
        16px;

    border-radius:
        8px;

    margin-bottom:
        20px;

    line-height:
        1.6;

}


.product-box strong {

    color:
        #0077cc;

}


.info {

    background:
        #fff3cd;

    padding:
        13px;

    border-radius:
        7px;

    margin-bottom:
        20px;

    color:
        #856404;

    line-height:
        1.5;

}


.pending {

    background:
        #fff3cd;

    color:
        #856404;

    padding:
        13px;

    border-radius:
        7px;

    margin-bottom:
        20px;

}


.rejected {

    background:
        #ffe8e8;

    color:
        #b00020;

    padding:
        15px;

    border-radius:
        7px;

    margin-bottom:
        20px;

    line-height:
        1.5;

}


.approved {

    background:
        #e8f7ed;

    color:
        #137333;

    padding:
        15px;

    border-radius:
        7px;

    margin-bottom:
        20px;

}


label {

    display:
        block;

    margin-top:
        15px;

    margin-bottom:
        7px;

    font-weight:
        bold;

}


input[type="text"],
input[type="tel"],
input[type="file"] {

    width:
        100%;

    padding:
        12px;

    border:
        1px solid #ccc;

    border-radius:
        6px;

    font-size:
        15px;

}


input[type="file"] {

    background:
        white;

}


.help-text {

    margin-top:
        7px;

    font-size:
        13px;

    color:
        #666;

    line-height:
        1.5;

}


.upload-button {

    width:
        100%;

    padding:
        13px;

    margin-top:
        25px;

    background:
        #0077cc;

    color:
        white;

    border:
        none;

    border-radius:
        6px;

    cursor:
        pointer;

    font-size:
        16px;

    font-weight:
        bold;

}


.upload-button:hover {

    background:
        #005fa3;

}


.back-button {

    display:
        block;

    text-align:
        center;

    margin-top:
        18px;

    color:
        #0077cc;

    text-decoration:
        none;

    font-weight:
        bold;

}


.back-button:hover {

    text-decoration:
        underline;

}


.message {

    margin-top:
        20px;

    padding:
        12px;

    border-radius:
        6px;

    text-align:
        center;

    font-weight:
        bold;

}


.message.error {

    background:
        #ffe8e8;

    color:
        #b00020;

}


</style>

</head>


<body>


<div class="container">


<div class="card">


<h2>
    📋 Upload Prescription
</h2>


<div class="product-box">

    <strong>
        Medicine:
    </strong>

    <?php
    echo htmlspecialchars(
        $product['product_name']
    );
    ?>


    <br>


    <strong>
        Product ID:
    </strong>

    <?php
    echo $product_id;
    ?>


    <br>


    <strong>
        Price:
    </strong>

    ₹<?php
    echo number_format(
        $product['price'],
        2
    );
    ?>


    <br>


    <strong>
        Prescription:
    </strong>

    <span style="
        color:#b00020;
        font-weight:bold;
    ">
        Required
    </span>

</div>


<?php if ($latestStatus === 'Pending') { ?>

<div class="pending">

    ⏳ <strong>Prescription Pending</strong>

    <br><br>

    Your prescription has already been uploaded
    and is waiting for pharmacist approval.

    <br><br>

    Please wait for the pharmacist to review it.

</div>


<?php } elseif ($latestStatus === 'Approved') { ?>


<div class="approved">

    ✅ <strong>Prescription Approved</strong>

    <br><br>

    Your pharmacist has approved the prescription
    for this medicine.

    <br><br>

    Please return to your cart to continue.

</div>


<?php } elseif ($latestStatus === 'Rejected') { ?>


<div class="rejected">

    ❌ <strong>Prescription Rejected</strong>

    <br><br>

    <?php if ($latestReason !== '') { ?>

        <strong>
            Pharmacist Reason:
        </strong>

        <br>

        <?php
        echo nl2br(
            htmlspecialchars(
                $latestReason
            )
        );
        ?>

        <br><br>

    <?php } ?>

    You can upload a new prescription below.

</div>


<?php } else { ?>


<div class="info">

    ⚕️ This medicine requires a valid
    prescription.

    <br><br>

    Your prescription will be reviewed by a
    pharmacist before you can proceed to payment.

</div>


<?php } ?>


<?php
/*
=========================================================
SHOW UPLOAD FORM ONLY WHEN:
- No previous prescription
OR
- Previous prescription was rejected
=========================================================
*/

if (
    $latestStatus === '' ||
    $latestStatus === 'Rejected'
) {

?>


<form
    method="POST"
    enctype="multipart/form-data"
>


<label>
    Patient Name
</label>


<input
    type="text"
    name="patient_name"
    value="<?php
        echo htmlspecialchars(
            $patient_name
        );
    ?>"
    placeholder="Enter Patient Name"
    required
>


<label>
    Mobile Number
</label>


<input
    type="tel"
    name="mobile"
    value="<?php
        echo htmlspecialchars(
            $mobile
        );
    ?>"
    placeholder="Enter Mobile Number"
    required
>


<label>
    Prescription
</label>


<input
    type="file"
    name="prescription"
    accept=".jpg,.jpeg,.png,.pdf"
    required
>


<div class="help-text">

    Accepted formats:
    JPG, JPEG, PNG and PDF.

    <br>

    Maximum file size:
    5 MB.

</div>


<input
    type="submit"
    name="upload"
    value="📤 Upload Prescription"
    class="upload-button"
>


</form>


<?php } ?>


<a
    href="cart.php"
    class="back-button"
>
    ← Back to Cart
</a>


<?php

if (
    $message !== ''
) {

?>

<div class="message <?php
    echo $messageType === 'error'
        ? 'error'
        : '';
?>">

    <?php
    echo htmlspecialchars(
        $message
    );
    ?>

</div>

<?php

}

?>


</div>

</div>


</body>

</html>