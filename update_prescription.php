<?php

/*
=========================================================
PHARMACYX - UPDATE PRESCRIPTION
PHARMACIST ONLY
=========================================================
*/


error_reporting(E_ALL);
ini_set('display_errors', 1);


/*
=========================================================
PHARMACIST SESSION
=========================================================
*/

session_name("PHARMACYX_PHARMACIST");
session_start();


/*
=========================================================
DATABASE
=========================================================
*/

require_once "./db_Config/config.php";


/*
=========================================================
PHARMACIST LOGIN CHECK
=========================================================
*/

if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Pharmacist'
) {

    header("Location: signin.php?role=Pharmacist");
    exit();

}


/*
=========================================================
POST REQUEST ONLY
=========================================================
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: view_prescriptions.php");
    exit();

}


/*
=========================================================
GET VALUES
=========================================================
*/

$id = isset($_POST['id'])
    ? (int)$_POST['id']
    : 0;

$action = isset($_POST['action'])
    ? trim($_POST['action'])
    : '';

$recommended_medicines = isset(
    $_POST['recommended_medicines']
)
    ? trim($_POST['recommended_medicines'])
    : '';

$rejection_reason = isset(
    $_POST['rejection_reason']
)
    ? trim($_POST['rejection_reason'])
    : '';


/*
=========================================================
VALIDATE ID
=========================================================
*/

if ($id <= 0) {

    die("Invalid prescription ID.");

}


/*
=========================================================
CHECK PRESCRIPTION
=========================================================
*/

$check = mysqli_prepare(
    $Connection,
    "
    SELECT id, status
    FROM prescriptions
    WHERE id = ?
    LIMIT 1
    "
);

mysqli_stmt_bind_param(
    $check,
    "i",
    $id
);

mysqli_stmt_execute($check);

$result = mysqli_stmt_get_result($check);

$prescription = mysqli_fetch_assoc($result);

mysqli_stmt_close($check);


if (!$prescription) {

    die("Prescription not found.");

}


/*
=========================================================
1. SAVE RECOMMENDED MEDICINES
=========================================================
*/

if ($action === 'save_recommendation') {

    $stmt = mysqli_prepare(
        $Connection,
        "
        UPDATE prescriptions

        SET recommended_medicines = ?

        WHERE id = ?
        "
    );

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $recommended_medicines,
        $id
    );

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        echo "
        <script>

            alert(
                'Recommended medicines saved successfully.'
            );

            window.location.href =
                'view_prescriptions.php';

        </script>
        ";

        exit();

    }

    $error = mysqli_error($Connection);

    mysqli_stmt_close($stmt);

    die(
        "Database Error: " . $error
    );
}


/*
=========================================================
2. APPROVE PRESCRIPTION
=========================================================

IMPORTANT:
Recommended medicines are OPTIONAL.

The pharmacist can approve even when
recommended_medicines is empty.
=========================================================
*/

if ($action === 'approve') {

    /*
    -----------------------------------------------------
    If the Approve button sends a recommendation,
    save it.

    If it doesn't send one, keep the existing
    recommendation unchanged.
    -----------------------------------------------------
    */

    if ($recommended_medicines !== '') {

        $stmt = mysqli_prepare(
            $Connection,
            "
            UPDATE prescriptions

            SET
                status = 'Approved',
                recommended_medicines = ?,
                rejection_reason = NULL

            WHERE id = ?
            "
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $recommended_medicines,
            $id
        );

    } else {

        /*
        -------------------------------------------------
        NO RECOMMENDATION
        JUST APPROVE
        -------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $Connection,
            "
            UPDATE prescriptions

            SET
                status = 'Approved',
                rejection_reason = NULL

            WHERE id = ?
            "
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $id
        );

    }


    /*
    -----------------------------------------------------
    EXECUTE APPROVAL
    -----------------------------------------------------
    */

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        echo "
        <script>

            alert(
                'Prescription Approved Successfully.'
            );

            window.location.href =
                'view_prescriptions.php';

        </script>
        ";

        exit();

    }


    $error = mysqli_error($Connection);

    mysqli_stmt_close($stmt);

    die(
        "Database Error: " . $error
    );
}


/*
=========================================================
3. REJECT PRESCRIPTION
=========================================================
*/

if ($action === 'reject') {

    /*
    -----------------------------------------------------
    Rejection reason IS mandatory.
    -----------------------------------------------------
    */

    if ($rejection_reason === '') {

        echo "
        <script>

            alert(
                'Please enter a rejection reason.'
            );

            history.back();

        </script>
        ";

        exit();

    }


    /*
    -----------------------------------------------------
    SAVE REJECTION
    -----------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $Connection,
        "
        UPDATE prescriptions

        SET
            status = 'Rejected',
            rejection_reason = ?,
            recommended_medicines = NULL

        WHERE id = ?
        "
    );


    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $rejection_reason,
        $id
    );


    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        echo "
        <script>

            alert(
                'Prescription Rejected Successfully.'
            );

            window.location.href =
                'view_prescriptions.php';

        </script>
        ";

        exit();

    }


    $error = mysqli_error($Connection);

    mysqli_stmt_close($stmt);

    die(
        "Database Error: " . $error
    );
}


/*
=========================================================
INVALID ACTION
=========================================================
*/

echo "
<script>

    alert('Invalid action.');

    window.location.href =
        'view_prescriptions.php';

</script>
";

exit();

?>