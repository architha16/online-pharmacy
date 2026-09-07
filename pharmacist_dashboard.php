<?php
/*
=========================================================
PHARMACYX - PHARMACIST DASHBOARD
FINAL MODERN VERSION

Features:
- Dashboard
- View Prescriptions
- Manage Medicine Prescriptions
- Customer Orders
- No iframe / no nested dashboard
- One pharmacist session throughout
- Light / Dark mode
- Working prescription approval/rejection
- Working medicine prescription requirement control
- Working customer order listing
=========================================================
*/

/* =========================================================
   PHARMACIST SESSION
========================================================= */
session_name("PHARMACYX_PHARMACIST");
session_start();

/* Prevent the browser from showing an old prescription status after an action. */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

/* =========================================================
   ACCESS CHECK
========================================================= */
if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Pharmacist'
) {
    header("Location: signin.php?role=Pharmacist");
    exit();
}

/* =========================================================
   DATABASE
========================================================= */
require_once "./db_Config/config.php";

$username = $_SESSION['username'];
$firstname = $_SESSION['firstname'] ?? $username;

/* =========================================================
   CURRENT VIEW
========================================================= */
$view = $_GET['view'] ?? 'dashboard';

$allowedViews = [
    'dashboard',
    'prescriptions',
    'medicines',
    'orders'
];

if (!in_array($view, $allowedViews, true)) {
    $view = 'dashboard';
}

/* =========================================================
   MESSAGES
========================================================= */
$successMessage = "";
$errorMessage = "";

/* =========================================================
   HANDLE PRESCRIPTION ACTIONS
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* -----------------------------------------------------
       APPROVE PRESCRIPTION
    ----------------------------------------------------- */
    if ($action === 'approve_prescription') {

        $prescriptionId = filter_input(
            INPUT_POST,
            'prescription_id',
            FILTER_VALIDATE_INT
        );

        if (!$prescriptionId) {

            $errorMessage =
                "Invalid prescription.";

        } else {

            $stmt = mysqli_prepare(
                $Connection,
                "UPDATE prescriptions
                 SET status = 'Approved',
                     rejection_reason = NULL
                 WHERE id = ?
                 LIMIT 1"
            );

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $prescriptionId
                );

                if (mysqli_stmt_execute($stmt)) {

                    /* Always verify the value stored in MySQL.
                       mysqli_affected_rows() can be 0 when the value was
                       already 'Approved', even though the database is correct. */
                    mysqli_stmt_close($stmt);
                    $stmt = null;

                    $verifyStmt = mysqli_prepare(
                        $Connection,
                        "SELECT status FROM prescriptions WHERE id = ? LIMIT 1"
                    );

                    $verifiedStatus = '';

                    if ($verifyStmt) {
                        mysqli_stmt_bind_param($verifyStmt, "i", $prescriptionId);
                        mysqli_stmt_execute($verifyStmt);
                        mysqli_stmt_bind_result($verifyStmt, $verifiedStatus);
                        mysqli_stmt_fetch($verifyStmt);
                        mysqli_stmt_close($verifyStmt);
                    }

                    if ($verifiedStatus === 'Approved') {
                        $_SESSION['PHARMACYX_PRESCRIPTION_FLASH'] =
                            'Prescription approved successfully.';
                        $_SESSION['PHARMACYX_PRESCRIPTION_FLASH_TYPE'] = 'success';
                        header('Location: pharmacist_dashboard.php?view=prescriptions&refresh=' . time());
                        exit();
                    }

                    $errorMessage =
                        'The prescription was updated, but its current database status could not be verified.';

                } else {

                    $errorMessage =
                        "Unable to approve prescription.";
                }

                if ($stmt) {
                    mysqli_stmt_close($stmt);
                }

            } else {

                $errorMessage =
                    "Database error while approving prescription.";
            }
        }

        $view = 'prescriptions';
    }


    /* -----------------------------------------------------
       REJECT PRESCRIPTION
    ----------------------------------------------------- */
    elseif ($action === 'reject_prescription') {

        $prescriptionId = filter_input(
            INPUT_POST,
            'prescription_id',
            FILTER_VALIDATE_INT
        );

        $reason = trim(
            $_POST['rejection_reason'] ?? ''
        );

        $recommendation = trim(
            $_POST['recommended_medicines'] ?? ''
        );

        if (!$prescriptionId) {

            $errorMessage =
                "Invalid prescription.";

        } elseif ($reason === '') {

            $errorMessage =
                "Please enter a rejection reason.";

        } else {

            $stmt = mysqli_prepare(
                $Connection,
                "UPDATE prescriptions
                 SET status = 'Rejected',
                     rejection_reason = ?,
                     recommended_medicines = ?
                 WHERE id = ?
                   AND status = 'Pending'
                 LIMIT 1"
            );

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssi",
                    $reason,
                    $recommendation,
                    $prescriptionId
                );

                if (mysqli_stmt_execute($stmt)) {

                    mysqli_stmt_close($stmt);
                    $stmt = null;

                    $verifyStmt = mysqli_prepare(
                        $Connection,
                        "SELECT status FROM prescriptions WHERE id = ? LIMIT 1"
                    );

                    $verifiedStatus = '';

                    if ($verifyStmt) {
                        mysqli_stmt_bind_param($verifyStmt, "i", $prescriptionId);
                        mysqli_stmt_execute($verifyStmt);
                        mysqli_stmt_bind_result($verifyStmt, $verifiedStatus);
                        mysqli_stmt_fetch($verifyStmt);
                        mysqli_stmt_close($verifyStmt);
                    }

                    if ($verifiedStatus === 'Rejected') {
                        $_SESSION['PHARMACYX_PRESCRIPTION_FLASH'] =
                            'Prescription rejected successfully.';
                        $_SESSION['PHARMACYX_PRESCRIPTION_FLASH_TYPE'] = 'success';
                        header('Location: pharmacist_dashboard.php?view=prescriptions&refresh=' . time());
                        exit();
                    }

                    $errorMessage =
                        'The prescription was updated, but its current database status could not be verified.';

                } else {

                    $errorMessage =
                        "Unable to reject prescription.";
                }

                if ($stmt) {
                    mysqli_stmt_close($stmt);
                }

            } else {

                $errorMessage =
                    "Database error while rejecting prescription.";
            }
        }

        $view = 'prescriptions';
    }


    /* -----------------------------------------------------
       UPDATE MEDICINE PRESCRIPTION REQUIREMENT
    ----------------------------------------------------- */
    elseif ($action === 'update_medicine') {

        $productId = filter_input(
            INPUT_POST,
            'product_id',
            FILTER_VALIDATE_INT
        );

        $requirement =
            $_POST['prescription_required'] ?? '';

        if (
            !$productId ||
            !in_array(
                $requirement,
                ['Yes', 'No'],
                true
            )
        ) {

            $errorMessage =
                "Invalid medicine or prescription setting.";

        } else {

            $stmt = mysqli_prepare(
                $Connection,
                "UPDATE Products
                 SET prescription_required = ?
                 WHERE product_id = ?
                 LIMIT 1"
            );

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "si",
                    $requirement,
                    $productId
                );

                if (mysqli_stmt_execute($stmt)) {

                    $successMessage =
                        "Medicine prescription setting updated successfully.";

                } else {

                    $errorMessage =
                        "Unable to update medicine.";
                }

                mysqli_stmt_close($stmt);

            } else {

                $errorMessage =
                    "Database error while updating medicine.";
            }
        }

        $view = 'medicines';
    }
}


/* =========================================================
   PRESCRIPTION ACTION FLASH MESSAGE
========================================================= */
if (!empty($_SESSION['PHARMACYX_PRESCRIPTION_FLASH'])) {
    $successMessage = $_SESSION['PHARMACYX_PRESCRIPTION_FLASH'];
    unset($_SESSION['PHARMACYX_PRESCRIPTION_FLASH']);
    unset($_SESSION['PHARMACYX_PRESCRIPTION_FLASH_TYPE']);
}

/* =========================================================
   PRESCRIPTION COUNTS
========================================================= */
$pendingPrescriptions = 0;
$approvedPrescriptions = 0;

$countResult = mysqli_query(
    $Connection,
    "SELECT
        SUM(status = 'Pending') AS pending_count,
        SUM(status = 'Approved') AS approved_count
     FROM prescriptions"
);

if ($countResult) {

    $countRow =
        mysqli_fetch_assoc($countResult);

    $pendingPrescriptions =
        (int)($countRow['pending_count'] ?? 0);

    $approvedPrescriptions =
        (int)($countRow['approved_count'] ?? 0);
}


/* =========================================================
   ORDER COUNT
========================================================= */
$totalOrders = 0;

$orderCountResult = mysqli_query(
    $Connection,
    "SELECT COUNT(*) AS total_orders
     FROM Orders"
);

if ($orderCountResult) {

    $orderCountRow =
        mysqli_fetch_assoc($orderCountResult);

    $totalOrders =
        (int)($orderCountRow['total_orders'] ?? 0);
}


/* =========================================================
   MEDICINE COUNT
========================================================= */
$totalMedicines = 0;

$medicineCountResult = mysqli_query(
    $Connection,
    "SELECT COUNT(*) AS total_medicines
     FROM Products"
);

if ($medicineCountResult) {

    $medicineCountRow =
        mysqli_fetch_assoc($medicineCountResult);

    $totalMedicines =
        (int)($medicineCountRow['total_medicines'] ?? 0);
}


/* =========================================================
   VIEW TITLES
========================================================= */
$pageTitles = [
    'dashboard'     => 'Pharmacist Dashboard',
    'prescriptions' => 'View Prescriptions',
    'medicines'     => 'Manage Medicine Prescriptions',
    'orders'        => 'Customer Orders'
];


/* =========================================================
   PRESCRIPTIONS DATA
========================================================= */
$prescriptions = [];

if ($view === 'prescriptions') {

    $prescriptionSql = "
        SELECT
            pr.id,
            pr.user_name,
            pr.product_id,
            pr.patient_name,
            pr.mobile,
            pr.prescription_file,
            pr.status,
            pr.rejection_reason,
            pr.recommended_medicines,
            pr.upload_date,
            p.product_name,
            p.price,
            p.image_url
        FROM prescriptions pr
        LEFT JOIN Products p
            ON pr.product_id = p.product_id
        ORDER BY
            CASE
                WHEN pr.status = 'Pending' THEN 0
                WHEN pr.status = 'Approved' THEN 1
                ELSE 2
            END,
            pr.id DESC
    ";

    $prescriptionResult =
        mysqli_query(
            $Connection,
            $prescriptionSql
        );

    if ($prescriptionResult) {

        while (
            $row =
            mysqli_fetch_assoc($prescriptionResult)
        ) {

            $prescriptions[] = $row;
        }
    }
}


/* =========================================================
   MEDICINES DATA
========================================================= */
$medicines = [];

if ($view === 'medicines') {

    $medicineSearch =
        trim($_GET['search'] ?? '');

    if ($medicineSearch !== '') {

        $searchValue =
            "%" . $medicineSearch . "%";

        $stmt = mysqli_prepare(
            $Connection,
            "SELECT
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
             ORDER BY product_id DESC"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $searchValue
            );

            mysqli_stmt_execute($stmt);

            $result =
                mysqli_stmt_get_result($stmt);

            while (
                $row =
                mysqli_fetch_assoc($result)
            ) {

                $medicines[] = $row;
            }

            mysqli_stmt_close($stmt);
        }

    } else {

        $medicineResult = mysqli_query(
            $Connection,
            "SELECT
                product_id,
                product_name,
                product_description,
                price,
                stock_quantity,
                image_url,
                expire_date,
                prescription_required
             FROM Products
             ORDER BY product_id DESC"
        );

        if ($medicineResult) {

            while (
                $row =
                mysqli_fetch_assoc($medicineResult)
            ) {

                $medicines[] = $row;
            }
        }
    }
}


/* =========================================================
   ORDERS DATA
========================================================= */
$orders = [];

if ($view === 'orders') {

    $orderSql = "
        SELECT
            o.order_id,
            o.user_name,
            o.order_status,
            o.order_type,
            o.qty,
            o.receiver_name,
            o.street,
            o.city,
            o.postal_code,
            o.Order_total,
            o.payment_method,
            o.order_date,
            o.rejection_reason,
            p.product_name,
            p.image_url
        FROM Orders o
        LEFT JOIN Products p
            ON o.product_id = p.product_id
        ORDER BY o.order_id DESC
    ";

    $orderResult =
        mysqli_query(
            $Connection,
            $orderSql
        );

    if ($orderResult) {

        while (
            $row =
            mysqli_fetch_assoc($orderResult)
        ) {

            $orders[] = $row;
        }
    }
}


/* =========================================================
   HELPERS
========================================================= */
function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function imagePath($image)
{
    $image = trim((string)$image);

    if ($image === '') {

        return
            "./Images/product-icons/Pharmacy-Isometric-Icons-1.png";
    }

    if (
        strpos($image, "Images/") === 0 ||
        strpos($image, "./Images/") === 0
    ) {

        return $image;
    }

    return
        "./Images/product-icons/" .
        basename($image);
}

function statusClass($status)
{
    switch ($status) {

        case 'Accepted':
            return 'accepted';

        case 'Packed':
            return 'packed';

        case 'Out for Delivery':
            return 'delivery';

        case 'Delivered':
            return 'delivered';

        case 'Rejected':
            return 'rejected';

        case 'Cancelled':
            return 'cancelled';

        case 'Approved':
            return 'approved';

        default:
            return 'pending';
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
    <?php echo h($pageTitles[$view]); ?> | PharmacyX
</title>

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
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
>

<style>

/* =========================================================
   THEME
========================================================= */

:root {

    --bg: #f3f7fc;
    --surface: #ffffff;
    --surface-2: #f8fafc;

    --sidebar: #0e192b;
    --sidebar-hover: #1b2b45;

    --primary: #1679e8;
    --primary-dark: #0d64ca;
    --primary-soft: #e8f2ff;

    --text: #172235;
    --muted: #748197;

    --border: #e1e8f0;

    --success: #19a66c;
    --success-soft: #e7f8f0;

    --warning: #e5a000;
    --warning-soft: #fff5d9;

    --danger: #dc4d58;
    --danger-soft: #ffebed;

    --purple: #7657d9;
    --purple-soft: #f0ebff;

    --shadow:
        0 12px 32px rgba(23, 43, 77, .08);
}

body.dark {

    --bg: #0b1320;
    --surface: #141f2e;
    --surface-2: #192537;

    --sidebar: #08101b;
    --sidebar-hover: #18283d;

    --primary: #3997ff;
    --primary-dark: #207fdc;
    --primary-soft: #17334f;

    --text: #edf4fb;
    --muted: #9aaabd;

    --border: #2a394c;

    --success: #2bc684;
    --success-soft: #123a2b;

    --warning: #ffc044;
    --warning-soft: #3a3018;

    --danger: #ff6e78;
    --danger-soft: #3d2025;

    --purple-soft: #292240;

    --shadow:
        0 15px 38px rgba(0,0,0,.27);
}


/* =========================================================
   GLOBAL
========================================================= */

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    width: 100%;
    height: 100%;
}

body {

    font-family:
        "Inter",
        Arial,
        sans-serif;

    background: var(--bg);
    color: var(--text);

    transition:
        background .25s ease,
        color .25s ease;
}

button,
input,
select,
textarea {
    font: inherit;
}

a {
    color: inherit;
}


/* =========================================================
   APPLICATION
========================================================= */

.app {

    width: 100%;
    min-height: 100vh;

    display: flex;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    width: 270px;
    min-width: 270px;

    min-height: 100vh;

    background: var(--sidebar);

    color: white;

    padding:
        22px 16px;

    display: flex;
    flex-direction: column;

    box-shadow:
        8px 0 30px rgba(0,0,0,.07);

    position: sticky;
    top: 0;
}


/* BRAND */

.brand {

    display: flex;

    align-items: center;

    gap: 12px;

    padding:
        4px 10px 22px;

    border-bottom:
        1px solid rgba(255,255,255,.08);
}

.brand-icon {

    width: 45px;
    height: 45px;

    border-radius: 14px;

    display: grid;
    place-items: center;

    background:
        linear-gradient(
            135deg,
            #21a0ff,
            #1767db
        );

    box-shadow:
        0 10px 22px rgba(21,123,234,.28);

    font-size: 19px;
}

.brand-name {

    font-size: 18px;

    font-weight: 800;
}

.brand-sub {

    font-size: 9px;

    color:
        rgba(255,255,255,.45);

    margin-top: 3px;
}


/* MENU */

.menu-title {

    margin:
        24px 12px 10px;

    color:
        rgba(255,255,255,.40);

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 1.3px;
}

.nav {

    display: flex;

    flex-direction: column;

    gap: 6px;
}

.nav-link {

    display: flex;

    align-items: center;

    gap: 12px;

    width: 100%;

    padding:
        13px 13px;

    border-radius: 12px;

    text-decoration: none;

    color:
        rgba(255,255,255,.72);

    font-size: 11px;

    font-weight: 650;

    transition:
        .2s ease;
}

.nav-link i {

    width: 20px;

    text-align: center;
}

.nav-link:hover {

    background:
        var(--sidebar-hover);

    color: white;

    transform:
        translateX(2px);
}

.nav-link.active {

    background:
        linear-gradient(
            135deg,
            #167ce9,
            #1268d0
        );

    color: white;

    box-shadow:
        0 9px 22px rgba(20,123,234,.25);
}


/* SIDEBAR BOTTOM */

.sidebar-bottom {

    margin-top: auto;

    padding-top: 16px;

    border-top:
        1px solid rgba(255,255,255,.08);
}

.profile {

    display: flex;

    align-items: center;

    gap: 10px;

    padding:
        7px 8px 14px;
}

.avatar {

    width: 39px;
    height: 39px;

    border-radius: 50%;

    display: grid;
    place-items: center;

    background:
        linear-gradient(
            135deg,
            #5a66ff,
            #7a46ef
        );

    font-size: 13px;

    font-weight: 800;
}

.profile-name {

    font-size: 11px;

    font-weight: 750;
}

.profile-role {

    font-size: 9px;

    color:
        rgba(255,255,255,.45);

    margin-top: 2px;
}

.logout {

    color:
        #ff8f98;
}

.logout:hover {

    background:
        rgba(220,77,88,.13);

    color:
        #ffb0b6;
}


/* =========================================================
   MAIN
========================================================= */

.main {

    flex: 1;

    min-width: 0;

    min-height: 100vh;

    display: flex;
    flex-direction: column;

    background: var(--bg);
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    height: 72px;

    flex-shrink: 0;

    background: var(--surface);

    border-bottom:
        1px solid var(--border);

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        0 28px;

    position: sticky;

    top: 0;

    z-index: 10;
}

.breadcrumb {

    display: flex;

    align-items: center;

    gap: 9px;

    font-size: 11px;
}

.breadcrumb-home {

    color: var(--primary);
}

.breadcrumb-separator {

    color: var(--muted);
}

.breadcrumb-current {

    font-weight: 700;
}

.top-actions {

    display: flex;

    align-items: center;

    gap: 10px;
}

.user-chip {

    display: flex;

    align-items: center;

    gap: 8px;

    background: var(--surface-2);

    border:
        1px solid var(--border);

    border-radius: 12px;

    padding:
        5px 11px 5px 5px;
}

.user-avatar {

    width: 32px;
    height: 32px;

    border-radius: 50%;

    display: grid;
    place-items: center;

    background: var(--primary);

    color: white;

    font-size: 11px;

    font-weight: 800;
}

.user-name {

    font-size: 10px;

    font-weight: 750;
}

.user-role {

    color: var(--muted);

    font-size: 8px;

    margin-top: 2px;
}

.theme-toggle {

    width: 40px;
    height: 40px;

    border:
        1px solid var(--border);

    border-radius: 12px;

    background: var(--surface);

    color: var(--text);

    cursor: pointer;

    display: grid;
    place-items: center;

    transition: .2s ease;
}

.theme-toggle:hover {

    color: var(--primary);

    border-color:
        var(--primary);
}


/* =========================================================
   CONTENT
========================================================= */

.content {

    padding: 26px;

    flex: 1;

    max-width: 1550px;

    width: 100%;

    margin: 0 auto;
}


/* =========================================================
   ALERTS
========================================================= */

.alert {

    padding:
        13px 16px;

    border-radius: 12px;

    margin-bottom: 18px;

    font-size: 11px;

    font-weight: 650;

    border: 1px solid transparent;
}

.alert.success {

    background:
        var(--success-soft);

    color:
        var(--success);

    border-color:
        rgba(25,166,108,.20);
}

.alert.error {

    background:
        var(--danger-soft);

    color:
        var(--danger);

    border-color:
        rgba(220,77,88,.20);
}


/* =========================================================
   DASHBOARD HOME
========================================================= */

.welcome {

    background:
        linear-gradient(
            135deg,
            #167de9,
            #1766ca
        );

    color: white;

    border-radius: 22px;

    padding:
        27px 29px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    position: relative;

    overflow: hidden;

    box-shadow:
        0 15px 38px rgba(22,125,233,.20);

    margin-bottom: 20px;
}

.welcome::after {

    content: "";

    width: 240px;
    height: 240px;

    position: absolute;

    right: -70px;
    top: -120px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.09);
}

.welcome h1 {

    margin: 0;

    font-size: 24px;

    position: relative;

    z-index: 1;
}

.welcome p {

    margin:
        7px 0 0;

    font-size: 11px;

    color:
        rgba(255,255,255,.75);

    position: relative;

    z-index: 1;
}

.welcome-icon {

    width: 64px;
    height: 64px;

    border-radius: 19px;

    display: grid;
    place-items: center;

    background:
        rgba(255,255,255,.13);

    font-size: 25px;

    position: relative;

    z-index: 1;
}


/* STATS */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

    margin-bottom: 23px;
}

.stat {

    background: var(--surface);

    border:
        1px solid var(--border);

    border-radius: 17px;

    padding: 18px;

    box-shadow: var(--shadow);
}

.stat-icon {

    width: 39px;
    height: 39px;

    border-radius: 11px;

    display: grid;
    place-items: center;

    color: var(--primary);

    background:
        var(--primary-soft);

    margin-bottom: 13px;
}

.stat-label {

    color: var(--muted);

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .8px;
}

.stat-value {

    font-size: 25px;

    font-weight: 800;

    margin-top: 5px;
}


/* QUICK */

.section-title {

    margin:
        0 0 13px;

    font-size: 15px;

    font-weight: 800;
}

.quick-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;
}

.quick-card {

    background: var(--surface);

    border:
        1px solid var(--border);

    border-radius: 18px;

    padding: 21px;

    text-decoration: none;

    box-shadow: var(--shadow);

    transition: .2s ease;
}

.quick-card:hover {

    transform:
        translateY(-3px);

    border-color:
        var(--primary);

    box-shadow:
        0 16px 35px rgba(22,123,234,.12);
}

.quick-icon {

    width: 46px;
    height: 46px;

    border-radius: 13px;

    display: grid;
    place-items: center;

    color: var(--primary);

    background:
        var(--primary-soft);

    margin-bottom: 15px;
}

.quick-card h3 {

    margin: 0;

    font-size: 13px;
}

.quick-card p {

    margin:
        7px 0 0;

    color: var(--muted);

    font-size: 10px;

    line-height: 1.5;
}

.quick-open {

    margin-top: 14px;

    display: flex;

    justify-content: flex-end;

    color: var(--primary);

    font-size: 10px;

    font-weight: 700;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 20px;
}

.page-header h1 {

    margin: 0;

    font-size: 23px;
}

.page-header p {

    margin:
        6px 0 0;

    color: var(--muted);

    font-size: 10px;
}


/* =========================================================
   PRESCRIPTION CARDS
========================================================= */

.prescription-list {

    display: grid;

    gap: 16px;
}

.prescription-card {

    background: var(--surface);

    border:
        1px solid var(--border);

    border-radius: 18px;

    padding: 20px;

    box-shadow: var(--shadow);
}

.prescription-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    margin-bottom: 15px;
}

.prescription-id {

    font-size: 11px;

    color: var(--muted);
}

.status {

    display: inline-flex;

    align-items: center;

    padding:
        6px 11px;

    border-radius: 30px;

    font-size: 9px;

    font-weight: 800;
}

.status.pending {

    background:
        var(--warning-soft);

    color:
        var(--warning);
}

.status.approved {

    background:
        var(--success-soft);

    color:
        var(--success);
}

.status.rejected {

    background:
        var(--danger-soft);

    color:
        var(--danger);
}

.prescription-body {

    display: grid;

    grid-template-columns:
        90px 1fr;

    gap: 17px;

    align-items: start;
}

.prescription-image {

    width: 90px;
    height: 90px;

    border-radius: 14px;

    border:
        1px solid var(--border);

    background: var(--surface-2);

    object-fit: contain;

    padding: 7px;
}

.prescription-info-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0,1fr));

    gap: 9px 15px;
}

.info-item label {

    display: block;

    color: var(--muted);

    font-size: 8px;

    text-transform: uppercase;

    letter-spacing: .5px;

    font-weight: 800;

    margin-bottom: 3px;
}

.info-item span {

    font-size: 10px;

    font-weight: 650;

    word-break: break-word;
}

.prescription-actions {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

    margin-top: 17px;

    padding-top: 15px;

    border-top:
        1px solid var(--border);
}

.btn {

    border: 0;

    border-radius: 9px;

    padding:
        9px 13px;

    font-size: 9px;

    font-weight: 750;

    cursor: pointer;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    gap: 6px;
}

.btn-primary {

    background: var(--primary);

    color: white;
}

.btn-success {

    background: var(--success);

    color: white;
}

.btn-danger {

    background: var(--danger);

    color: white;
}

.btn-neutral {

    background: var(--surface-2);

    color: var(--text);

    border:
        1px solid var(--border);
}

.btn:hover {

    filter: brightness(.96);

    transform:
        translateY(-1px);
}


/* REJECT FORM */

.reject-form {

    display: none;

    margin-top: 12px;

    padding: 14px;

    background: var(--surface-2);

    border-radius: 12px;

    border:
        1px solid var(--border);
}

.reject-form.open {

    display: block;
}

.form-label {

    display: block;

    font-size: 9px;

    font-weight: 750;

    margin-bottom: 6px;
}

textarea,
.search-input,
.setting-select {

    width: 100%;

    border:
        1px solid var(--border);

    background: var(--surface);

    color: var(--text);

    border-radius: 9px;

    padding:
        10px;

    outline: none;

    font-size: 10px;
}

textarea {

    min-height: 72px;

    resize: vertical;
}

textarea:focus,
.search-input:focus,
.setting-select:focus {

    border-color:
        var(--primary);

    box-shadow:
        0 0 0 3px rgba(22,123,234,.09);
}

.form-row {

    margin-bottom: 10px;
}


/* EMPTY */

.empty {

    background: var(--surface);

    border:
        1px solid var(--border);

    border-radius: 18px;

    padding: 55px 20px;

    text-align: center;

    box-shadow: var(--shadow);
}

.empty i {

    font-size: 32px;

    color: var(--muted);

    margin-bottom: 12px;
}

.empty h3 {

    margin: 0;

    font-size: 15px;
}

.empty p {

    color: var(--muted);

    font-size: 10px;
}


/* =========================================================
   MEDICINE MANAGEMENT
========================================================= */

.search-panel {

    background: var(--surface);

    border:
        1px solid var(--border);

    border-radius: 15px;

    padding: 14px;

    box-shadow: var(--shadow);

    margin-bottom: 16px;
}

.search-form {

    display: flex;

    gap: 8px;
}

.search-form .search-input {

    flex: 1;
}

.medicine-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;
}

.medicine-card {

    background: var(--surface);

    border:
        1px solid var(--border);

    border-radius: 17px;

    padding: 17px;

    box-shadow: var(--shadow);
}

.medicine-top {

    display: flex;

    align-items: flex-start;

    gap: 12px;
}

.medicine-image {

    width: 66px;
    height: 66px;

    border-radius: 13px;

    border:
        1px solid var(--border);

    background: var(--surface-2);

    object-fit: contain;

    padding: 5px;

    flex-shrink: 0;
}

.medicine-name {

    margin: 0;

    font-size: 12px;

    line-height: 1.35;
}

.medicine-price {

    color: var(--primary);

    font-size: 11px;

    font-weight: 800;

    margin-top: 5px;
}

.medicine-description {

    color: var(--muted);

    font-size: 9px;

    line-height: 1.45;

    margin:
        13px 0;
}

.medicine-meta {

    display: flex;

    justify-content: space-between;

    gap: 8px;

    padding:
        10px 0;

    border-top:
        1px solid var(--border);

    border-bottom:
        1px solid var(--border);

    font-size: 9px;
}

.stock-ok {

    color: var(--success);

    font-weight: 750;
}

.stock-low {

    color: var(--warning);

    font-weight: 750;
}

.stock-out {

    color: var(--danger);

    font-weight: 750;
}

.requirement-row {

    margin-top: 12px;
}

.requirement-row label {

    display: block;

    font-size: 8px;

    color: var(--muted);

    font-weight: 800;

    text-transform: uppercase;

    margin-bottom: 6px;
}

.requirement-controls {

    display: flex;

    gap: 7px;
}

.requirement-controls select {

    flex: 1;
}

.expiry {

    margin-top: 8px;

    font-size: 8px;

    color: var(--muted);
}


/* =========================================================
   ORDERS
========================================================= */

.order-summary {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 14px;

    margin-bottom: 17px;
}

.order-stat {

    background: var(--surface);

    border:
        1px solid var(--border);

    border-radius: 15px;

    padding: 16px;

    box-shadow: var(--shadow);
}

.order-stat-label {

    color: var(--muted);

    font-size: 8px;

    text-transform: uppercase;

    font-weight: 800;
}

.order-stat-value {

    font-size: 21px;

    font-weight: 800;

    margin-top: 5px;
}

.orders-table-wrap {

    background: var(--surface);

    border:
        1px solid var(--border);

    border-radius: 17px;

    box-shadow: var(--shadow);

    overflow: auto;
}

.orders-table {

    width: 100%;

    border-collapse: collapse;

    min-width: 900px;
}

.orders-table th {

    background: var(--surface-2);

    color: var(--muted);

    text-align: left;

    padding:
        13px 12px;

    font-size: 8px;

    text-transform: uppercase;

    letter-spacing: .6px;

    white-space: nowrap;
}

.orders-table td {

    padding:
        13px 12px;

    border-top:
        1px solid var(--border);

    font-size: 9px;

    vertical-align: middle;
}

.order-product {

    display: flex;

    align-items: center;

    gap: 9px;

    min-width: 170px;
}

.order-product img {

    width: 36px;
    height: 36px;

    object-fit: contain;

    background: var(--surface-2);

    border-radius: 8px;

    border:
        1px solid var(--border);
}

.order-id {

    font-weight: 800;

    color: var(--primary);
}

.payment {

    font-weight: 700;
}

.customer {

    font-weight: 700;
}



/* =========================================================
   PRESCRIPTION APPROVE / REJECT BUTTONS
========================================================= */

.prescription-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
}

.prescription-actions form {
    margin: 0;
}

.prescription-action-btn {
    min-width: 125px;
    border: 0;
    border-radius: 10px;
    padding: 11px 17px;
    color: white;
    font-size: 10px;
    font-weight: 800;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition: .2s ease;
}

.prescription-action-btn:hover {
    transform: translateY(-2px);
    filter: brightness(.96);
}

.approve-btn {
    background: var(--success);
    box-shadow: 0 7px 16px rgba(25,166,108,.18);
}

.reject-btn {
    background: var(--danger);
    box-shadow: 0 7px 16px rgba(220,77,88,.18);
}

.prescription-processed {
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid var(--border);
    color: var(--muted);
    font-size: 10px;
    font-weight: 650;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);
    }

    .medicine-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }
}

@media (max-width: 800px) {

    .sidebar {

        width: 82px;

        min-width: 82px;

        padding:
            18px 10px;
    }

    .brand {

        justify-content: center;

        padding:
            4px 0 20px;
    }

    .brand-text,
    .menu-title,
    .nav-label,
    .profile-info {

        display: none;
    }

    .nav-link {

        justify-content: center;

        padding:
            13px 8px;
    }

    .nav-link i {

        width: auto;
    }

    .profile {

        justify-content: center;
    }

    .quick-grid {

        grid-template-columns:
            1fr;
    }

    .content {

        padding: 18px;
    }

    .topbar {

        padding:
            0 17px;
    }
}

@media (max-width: 600px) {

    .stats,
    .medicine-grid,
    .order-summary {

        grid-template-columns:
            1fr;
    }

    .prescription-body {

        grid-template-columns:
            1fr;
    }

    .prescription-image {

        width: 80px;
        height: 80px;
    }

    .prescription-info-grid {

        grid-template-columns:
            1fr;
    }

    .user-info {

        display: none;
    }

    .welcome {

        padding: 22px;
    }

    .welcome h1 {

        font-size: 20px;
    }

    .page-header {

        flex-direction: column;
    }

    .search-form {

        flex-direction: column;
    }
}

</style>

</head>

<body>

<div class="app">


<!-- =====================================================
     SIDEBAR
====================================================== -->

<aside class="sidebar">

    <div class="brand">

        <div class="brand-icon">
            <i class="fa-solid fa-pills"></i>
        </div>

        <div class="brand-text">

            <div class="brand-name">
                PharmacyX
            </div>

            <div class="brand-sub">
                Pharmacist Panel
            </div>

        </div>

    </div>


    <div class="menu-title">
        Main Menu
    </div>


    <nav class="nav">

        <a
            href="pharmacist_dashboard.php?view=dashboard"
            class="nav-link <?php
                echo $view === 'dashboard'
                    ? 'active'
                    : '';
            ?>"
        >
            <i class="fa-solid fa-chart-pie"></i>
            <span class="nav-label">
                Dashboard
            </span>
        </a>


        <a
            href="pharmacist_dashboard.php?view=prescriptions"
            class="nav-link <?php
                echo $view === 'prescriptions'
                    ? 'active'
                    : '';
            ?>"
        >
            <i class="fa-solid fa-file-prescription"></i>
            <span class="nav-label">
                View Prescriptions
            </span>
        </a>


        <a
            href="pharmacist_dashboard.php?view=medicines"
            class="nav-link <?php
                echo $view === 'medicines'
                    ? 'active'
                    : '';
            ?>"
        >
            <i class="fa-solid fa-capsules"></i>
            <span class="nav-label">
                Manage Medicine Prescriptions
            </span>
        </a>


        <a
            href="pharmacist_dashboard.php?view=orders"
            class="nav-link <?php
                echo $view === 'orders'
                    ? 'active'
                    : '';
            ?>"
        >
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="nav-label">
                Customer Orders
            </span>
        </a>

    </nav>


    <div class="sidebar-bottom">

        <div class="profile">

            <div class="avatar">

                <?php
                echo h(
                    strtoupper(
                        substr($firstname, 0, 1)
                    )
                );
                ?>

            </div>

            <div class="profile-info">

                <div class="profile-name">
                    <?php echo h($firstname); ?>
                </div>

                <div class="profile-role">
                    Pharmacist
                </div>

            </div>

        </div>


        <a
            href="logout.php"
            class="nav-link logout"
            onclick="
                return confirm(
                    'Do you want to Sign Out?'
                );
            "
        >

            <i class="fa-solid fa-right-from-bracket"></i>

            <span class="nav-label">
                Logout
            </span>

        </a>

    </div>

</aside>


<!-- =====================================================
     MAIN
====================================================== -->

<section class="main">


<header class="topbar">

    <div class="breadcrumb">

        <span class="breadcrumb-home">
            <i class="fa-solid fa-house"></i>
        </span>

        <span class="breadcrumb-separator">
            /
        </span>

        <span class="breadcrumb-current">
            <?php echo h($pageTitles[$view]); ?>
        </span>

    </div>


    <div class="top-actions">

        <div class="user-chip">

            <div class="user-avatar">
                <?php
                echo h(
                    strtoupper(
                        substr($firstname, 0, 1)
                    )
                );
                ?>
            </div>

            <div class="user-info">

                <div class="user-name">
                    <?php echo h($firstname); ?>
                </div>

                <div class="user-role">
                    Pharmacist
                </div>

            </div>

        </div>


        <button
            type="button"
            id="themeToggle"
            class="theme-toggle"
            title="Toggle dark mode"
        >
            <i class="fa-solid fa-moon"></i>
        </button>

    </div>

</header>


<main class="content">


<?php if ($successMessage !== ''): ?>

    <div class="alert success">
        <i class="fa-solid fa-circle-check"></i>
        &nbsp;
        <?php echo h($successMessage); ?>
    </div>

<?php endif; ?>


<?php if ($errorMessage !== ''): ?>

    <div class="alert error">
        <i class="fa-solid fa-circle-exclamation"></i>
        &nbsp;
        <?php echo h($errorMessage); ?>
    </div>

<?php endif; ?>


<!-- =====================================================
     DASHBOARD
====================================================== -->

<?php if ($view === 'dashboard'): ?>


<section class="welcome">

    <div>

        <h1>
            Welcome,
            <?php echo h($firstname); ?>
            👋
        </h1>

        <p>
            Manage prescriptions, medicines and customer orders from one place.
        </p>

    </div>


    <div class="welcome-icon">
        <i class="fa-solid fa-user-doctor"></i>
    </div>

</section>


<section class="stats">


    <div class="stat">

        <div class="stat-icon">
            <i class="fa-solid fa-file-prescription"></i>
        </div>

        <div class="stat-label">
            Pending Prescriptions
        </div>

        <div class="stat-value">
            <?php echo $pendingPrescriptions; ?>
        </div>

    </div>


    <div class="stat">

        <div class="stat-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <div class="stat-label">
            Approved Prescriptions
        </div>

        <div class="stat-value">
            <?php echo $approvedPrescriptions; ?>
        </div>

    </div>


    <div class="stat">

        <div class="stat-icon">
            <i class="fa-solid fa-pills"></i>
        </div>

        <div class="stat-label">
            Total Medicines
        </div>

        <div class="stat-value">
            <?php echo $totalMedicines; ?>
        </div>

    </div>


    <div class="stat">

        <div class="stat-icon">
            <i class="fa-solid fa-cart-shopping"></i>
        </div>

        <div class="stat-label">
            Customer Orders
        </div>

        <div class="stat-value">
            <?php echo $totalOrders; ?>
        </div>

    </div>


</section>


<div class="section-title">
    Quick Actions
</div>


<section class="quick-grid">


    <a
        href="pharmacist_dashboard.php?view=prescriptions"
        class="quick-card"
    >

        <div class="quick-icon">
            <i class="fa-solid fa-file-prescription"></i>
        </div>

        <h3>
            Prescription Management
        </h3>

        <p>
            Review customer prescriptions and approve or reject them.
        </p>

        <div class="quick-open">
            Open
            &nbsp;
            <i class="fa-solid fa-arrow-right"></i>
        </div>

    </a>


    <a
        href="pharmacist_dashboard.php?view=medicines"
        class="quick-card"
    >

        <div class="quick-icon">
            <i class="fa-solid fa-capsules"></i>
        </div>

        <h3>
            Medicine Control
        </h3>

        <p>
            Set whether each medicine requires a prescription.
        </p>

        <div class="quick-open">
            Open
            &nbsp;
            <i class="fa-solid fa-arrow-right"></i>
        </div>

    </a>


    <a
        href="pharmacist_dashboard.php?view=orders"
        class="quick-card"
    >

        <div class="quick-icon">
            <i class="fa-solid fa-cart-shopping"></i>
        </div>

        <h3>
            Customer Orders
        </h3>

        <p>
            View customer medicine orders without leaving the pharmacist panel.
        </p>

        <div class="quick-open">
            Open
            &nbsp;
            <i class="fa-solid fa-arrow-right"></i>
        </div>

    </a>


</section>


<?php endif; ?>


<!-- =====================================================
     PRESCRIPTIONS
====================================================== -->

<?php if ($view === 'prescriptions'): ?>


<div class="page-header">

    <div>

        <h1>
            View Prescriptions
        </h1>

        <p>
            Review customer prescriptions and process pending requests.
        </p>

    </div>

</div>


<?php if (count($prescriptions) === 0): ?>

    <div class="empty">

        <i class="fa-solid fa-file-prescription"></i>

        <h3>
            No prescriptions found
        </h3>

        <p>
            New customer prescription requests will appear here.
        </p>

    </div>

<?php else: ?>


<div class="prescription-list">


<?php foreach ($prescriptions as $prescription): ?>

<?php

$prescriptionId =
    (int)$prescription['id'];

$status =
    $prescription['status'] ?? 'Pending';

$productName =
    $prescription['product_name']
    ?? 'Unknown Medicine';

$fileName =
    basename(
        $prescription['prescription_file']
        ?? ''
    );

$filePath =
    "./uploads/" . $fileName;

$productImage =
    imagePath(
        $prescription['image_url']
        ?? ''
    );

?>


<div class="prescription-card">


    <div class="prescription-top">

        <div class="prescription-id">

            Prescription #
            <?php echo $prescriptionId; ?>

            <br>

            Uploaded:
            <?php
            echo h(
                $prescription['upload_date']
                ?? '-'
            );
            ?>

        </div>


        <span class="status <?php
            echo h(
                statusClass($status)
            );
        ?>">

            <?php
            echo h($status);
            ?>

        </span>

    </div>


    <div class="prescription-body">


        <img
            class="prescription-image"
            src="<?php echo h($productImage); ?>"
            alt="<?php echo h($productName); ?>"
            onerror="
                this.src='./Images/product-icons/Pharmacy-Isometric-Icons-1.png';
            "
        >


        <div class="prescription-info-grid">


            <div class="info-item">

                <label>
                    Customer
                </label>

                <span>
                    <?php
                    echo h(
                        $prescription['user_name']
                    );
                    ?>
                </span>

            </div>


            <div class="info-item">

                <label>
                    Medicine
                </label>

                <span>
                    <?php
                    echo h($productName);
                    ?>
                </span>

            </div>


            <div class="info-item">

                <label>
                    Patient Name
                </label>

                <span>
                    <?php
                    echo h(
                        $prescription['patient_name']
                        ?? '-'
                    );
                    ?>
                </span>

            </div>


            <div class="info-item">

                <label>
                    Mobile
                </label>

                <span>
                    <?php
                    echo h(
                        $prescription['mobile']
                        ?? '-'
                    );
                    ?>
                </span>

            </div>


            <div class="info-item">

                <label>
                    Medicine Price
                </label>

                <span>
                    ₹<?php
                    echo number_format(
                        (float)(
                            $prescription['price']
                            ?? 0
                        ),
                        2
                    );
                    ?>
                </span>

            </div>


            <div class="info-item">

                <label>
                    Prescription File
                </label>

                <span>

                    <?php if ($fileName !== ''): ?>

                        <a
                            href="<?php echo h($filePath); ?>"
                            target="_blank"
                            class="btn btn-neutral"
                            style="padding:6px 9px"
                        >
                            <i class="fa-solid fa-eye"></i>
                            View
                        </a>

                    <?php else: ?>

                        No file

                    <?php endif; ?>

                </span>

            </div>


        </div>

    </div>


    <?php if ($status === 'Pending'): ?>


    <!-- =================================================
         APPROVE / REJECT BUTTONS
    ================================================== -->

    <div class="prescription-actions">


        <!-- APPROVE BUTTON -->

        <form
            method="POST"
            onsubmit="
                return confirm(
                    'Are you sure you want to approve this prescription?'
                );
            "
        >

            <input
                type="hidden"
                name="action"
                value="approve_prescription"
            >

            <input
                type="hidden"
                name="prescription_id"
                value="<?php
                    echo $prescriptionId;
                ?>"
            >

            <button
                type="submit"
                class="prescription-action-btn approve-btn"
            >

                <i class="fa-solid fa-circle-check"></i>

                Approve Prescription

            </button>

        </form>


        <!-- REJECT BUTTON -->

        <button
            type="button"
            class="prescription-action-btn reject-btn"
            onclick="
                toggleReject(
                    'reject-<?php
                    echo $prescriptionId;
                    ?>'
                );
            "
        >

            <i class="fa-solid fa-circle-xmark"></i>

            Reject Prescription

        </button>


    </div>


    <div
        class="reject-form"
        id="reject-<?php
            echo $prescriptionId;
        ?>"
    >

        <form
            method="POST"
        >

            <input
                type="hidden"
                name="action"
                value="reject_prescription"
            >

            <input
                type="hidden"
                name="prescription_id"
                value="<?php
                    echo $prescriptionId;
                ?>"
            >


            <div class="form-row">

                <label class="form-label">
                    Rejection Reason *
                </label>

                <textarea
                    name="rejection_reason"
                    required
                    placeholder="Enter why the prescription is being rejected..."
                ></textarea>

            </div>


            <div class="form-row">

                <label class="form-label">
                    Recommended Medicines (Optional)
                </label>

                <textarea
                    name="recommended_medicines"
                    placeholder="Enter any alternative/recommended medicines..."
                ></textarea>

            </div>


            <button
                type="submit"
                class="btn btn-danger"
            >

                <i class="fa-solid fa-paper-plane"></i>

                Submit Rejection

            </button>

        </form>

    </div>


    <?php elseif ($status === 'Approved'): ?>

        <div class="prescription-processed">
            <i class="fa-solid fa-circle-check"></i>
            This prescription has already been approved.
        </div>


    <?php elseif ($status === 'Rejected'): ?>


    <?php if (
        !empty(
            $prescription['rejection_reason']
        )
    ): ?>

        <div
            style="
                margin-top:14px;
                padding:12px;
                border-radius:10px;
                background:var(--danger-soft);
                color:var(--danger);
                font-size:10px;
            "
        >

            <strong>
                Rejection Reason:
            </strong>

            <br>

            <?php
            echo nl2br(
                h(
                    $prescription[
                        'rejection_reason'
                    ]
                )
            );
            ?>

        </div>

    <?php endif; ?>


    <?php if (
        !empty(
            $prescription[
                'recommended_medicines'
            ]
        )
    ): ?>

        <div
            style="
                margin-top:9px;
                padding:12px;
                border-radius:10px;
                background:var(--primary-soft);
                color:var(--primary);
                font-size:10px;
            "
        >

            <strong>
                Recommended Medicines:
            </strong>

            <br>

            <?php
            echo nl2br(
                h(
                    $prescription[
                        'recommended_medicines'
                    ]
                )
            );
            ?>

        </div>

    <?php endif; ?>


    <?php endif; ?>


</div>


<?php endforeach; ?>


</div>


<?php endif; ?>


<?php endif; ?>


<!-- =====================================================
     MEDICINES
====================================================== -->

<?php if ($view === 'medicines'): ?>


<div class="page-header">

    <div>

        <h1>
            Manage Medicine Prescriptions
        </h1>

        <p>
            Decide which medicines require a prescription before purchase.
        </p>

    </div>

</div>


<div class="search-panel">

    <form
        method="GET"
        action="pharmacist_dashboard.php"
        class="search-form"
    >

        <input
            type="hidden"
            name="view"
            value="medicines"
        >

        <input
            type="text"
            name="search"
            class="search-input"
            placeholder="Search medicine name..."
            value="<?php
                echo h(
                    $_GET['search'] ?? ''
                );
            ?>"
        >

        <button
            type="submit"
            class="btn btn-primary"
        >

            <i class="fa-solid fa-magnifying-glass"></i>

            Search

        </button>


        <?php if (
            trim(
                $_GET['search'] ?? ''
            ) !== ''
        ): ?>

            <a
                href="pharmacist_dashboard.php?view=medicines"
                class="btn btn-neutral"
            >
                Clear
            </a>

        <?php endif; ?>

    </form>

</div>


<?php if (count($medicines) === 0): ?>


<div class="empty">

    <i class="fa-solid fa-pills"></i>

    <h3>
        No medicines found
    </h3>

    <p>
        Try a different medicine name.
    </p>

</div>


<?php else: ?>


<div class="medicine-grid">


<?php foreach ($medicines as $medicine): ?>

<?php

$productId =
    (int)$medicine['product_id'];

$stock =
    (int)$medicine['stock_quantity'];

$requirement =
    $medicine['prescription_required']
    ?? 'No';

$image =
    imagePath(
        $medicine['image_url']
        ?? ''
    );

?>


<div class="medicine-card">


    <div class="medicine-top">


        <img
            src="<?php echo h($image); ?>"
            class="medicine-image"
            alt="<?php
                echo h(
                    $medicine['product_name']
                );
            ?>"
            onerror="
                this.src='./Images/product-icons/Pharmacy-Isometric-Icons-1.png';
            "
        >


        <div>

            <h3 class="medicine-name">

                <?php
                echo h(
                    $medicine['product_name']
                );
                ?>

            </h3>

            <div class="medicine-price">

                ₹<?php
                echo number_format(
                    (float)$medicine['price'],
                    2
                );
                ?>

            </div>

        </div>

    </div>


    <div class="medicine-description">

        <?php

        $description =
            trim(
                $medicine[
                    'product_description'
                ] ?? ''
            );

        echo h(
            $description !== ''
                ? $description
                : 'No description available.'
        );

        ?>

    </div>


    <div class="medicine-meta">

        <span>

            Stock:

            <?php if ($stock <= 0): ?>

                <span class="stock-out">
                    Out of Stock
                </span>

            <?php elseif ($stock <= 10): ?>

                <span class="stock-low">
                    <?php echo $stock; ?> left
                </span>

            <?php else: ?>

                <span class="stock-ok">
                    <?php echo $stock; ?> units
                </span>

            <?php endif; ?>

        </span>


        <span>

            ID #<?php
            echo $productId;
            ?>

        </span>

    </div>


    <div class="expiry">

        <i class="fa-regular fa-calendar"></i>

        Expiry:

        <?php
        echo h(
            $medicine['expire_date']
            ?? '-'
        );
        ?>

    </div>


    <form
        method="POST"
        class="requirement-row"
    >

        <input
            type="hidden"
            name="action"
            value="update_medicine"
        >

        <input
            type="hidden"
            name="product_id"
            value="<?php
                echo $productId;
            ?>"
        >


        <label>
            Prescription Requirement
        </label>


        <div class="requirement-controls">


            <select
                name="prescription_required"
                class="setting-select"
            >

                <option
                    value="Yes"
                    <?php
                    echo $requirement === 'Yes'
                        ? 'selected'
                        : '';
                    ?>
                >
                    Prescription Required
                </option>

                <option
                    value="No"
                    <?php
                    echo $requirement === 'No'
                        ? 'selected'
                        : '';
                    ?>
                >
                    Prescription Not Required
                </option>

            </select>


            <button
                type="submit"
                class="btn btn-primary"
                title="Save setting"
            >

                <i class="fa-solid fa-floppy-disk"></i>

                Save

            </button>

        </div>

    </form>


</div>


<?php endforeach; ?>


</div>


<?php endif; ?>


<?php endif; ?>


<!-- =====================================================
     CUSTOMER ORDERS
====================================================== -->

<?php if ($view === 'orders'): ?>


<div class="page-header">

    <div>

        <h1>
            Customer Orders
        </h1>

        <p>
            View customer medicine orders directly inside the pharmacist panel.
        </p>

    </div>

</div>


<?php

$pendingOrderCount = 0;
$activeOrderCount = 0;
$deliveredOrderCount = 0;

foreach ($orders as $orderRow) {

    $orderStatus =
        $orderRow['order_status']
        ?? 'Pending';

    if ($orderStatus === 'Pending') {
        $pendingOrderCount++;
    }

    if (
        in_array(
            $orderStatus,
            [
                'Accepted',
                'Packed',
                'Out for Delivery'
            ],
            true
        )
    ) {
        $activeOrderCount++;
    }

    if ($orderStatus === 'Delivered') {
        $deliveredOrderCount++;
    }
}

?>


<div class="order-summary">


    <div class="order-stat">

        <div class="order-stat-label">
            Total Orders
        </div>

        <div class="order-stat-value">
            <?php echo count($orders); ?>
        </div>

    </div>


    <div class="order-stat">

        <div class="order-stat-label">
            Pending
        </div>

        <div class="order-stat-value">
            <?php echo $pendingOrderCount; ?>
        </div>

    </div>


    <div class="order-stat">

        <div class="order-stat-label">
            Active / Delivered
        </div>

        <div class="order-stat-value">

            <?php
            echo
                $activeOrderCount .
                " / " .
                $deliveredOrderCount;
            ?>

        </div>

    </div>


</div>


<?php if (count($orders) === 0): ?>


<div class="empty">

    <i class="fa-solid fa-cart-shopping"></i>

    <h3>
        No customer orders found
    </h3>

    <p>
        Customer orders will appear here after successful checkout.
    </p>

</div>


<?php else: ?>


<div class="orders-table-wrap">


<table class="orders-table">


<thead>

<tr>

    <th>
        ORDER
    </th>

    <th>
        CUSTOMER
    </th>

    <th>
        MEDICINE
    </th>

    <th>
        QTY
    </th>

    <th>
        TOTAL
    </th>

    <th>
        PAYMENT
    </th>

    <th>
        STATUS
    </th>

    <th>
        DATE
    </th>

</tr>

</thead>


<tbody>


<?php foreach ($orders as $order): ?>

<?php

$orderId =
    (int)$order['order_id'];

$orderStatus =
    $order['order_status']
    ?? 'Pending';

$orderImage =
    imagePath(
        $order['image_url']
        ?? ''
    );

?>


<tr>


<td>

    <div class="order-id">
        #<?php echo $orderId; ?>
    </div>

</td>


<td>

    <div class="customer">

        <?php
        echo h(
            $order['user_name']
        );
        ?>

    </div>

    <div
        style="
            color:var(--muted);
            margin-top:3px;
        "
    >

        <?php
        echo h(
            $order['receiver_name']
            ?? '-'
        );
        ?>

    </div>

</td>


<td>

    <div class="order-product">


        <img
            src="<?php echo h($orderImage); ?>"
            alt=""
            onerror="
                this.src='./Images/product-icons/Pharmacy-Isometric-Icons-1.png';
            "
        >


        <div>

            <?php
            echo h(
                $order['product_name']
                ?? 'Unknown Medicine'
            );
            ?>

        </div>


    </div>

</td>


<td>

    <?php
    echo (int)(
        $order['qty'] ?? 0
    );
    ?>

</td>


<td>

    <strong>

        ₹<?php
        echo number_format(
            (float)(
                $order['Order_total']
                ?? 0
            ),
            2
        );
        ?>

    </strong>

</td>


<td>

    <span class="payment">

        <?php
        echo h(
            $order['payment_method']
            ?? '-'
        );
        ?>

    </span>

</td>


<td>

    <span class="status <?php
        echo h(
            statusClass(
                $orderStatus
            )
        );
    ?>">

        <?php
        echo h($orderStatus);
        ?>

    </span>

</td>


<td>

    <?php
    echo h(
        $order['order_date']
        ?? '-'
    );
    ?>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>


</div>


<?php endif; ?>


<?php endif; ?>


</main>

</section>

</div>


<script>

/* =========================================================
   DARK / LIGHT MODE
========================================================= */

const body =
    document.body;

const themeToggle =
    document.getElementById(
        "themeToggle"
    );

const savedTheme =
    localStorage.getItem(
        "pharmacyx_pharmacist_theme"
    );


if (savedTheme === "dark") {

    body.classList.add("dark");

}


function updateThemeIcon() {

    if (!themeToggle) {
        return;
    }

    const icon =
        themeToggle.querySelector("i");

    if (
        body.classList.contains("dark")
    ) {

        icon.className =
            "fa-solid fa-sun";

        themeToggle.title =
            "Switch to light mode";

    } else {

        icon.className =
            "fa-solid fa-moon";

        themeToggle.title =
            "Switch to dark mode";

    }

}


updateThemeIcon();


if (themeToggle) {

    themeToggle.addEventListener(
        "click",
        function () {

            body.classList.toggle(
                "dark"
            );

            localStorage.setItem(
                "pharmacyx_pharmacist_theme",
                body.classList.contains("dark")
                    ? "dark"
                    : "light"
            );

            updateThemeIcon();

        }
    );

}


/* =========================================================
   REJECTION FORM
========================================================= */

function toggleReject(id) {

    const form =
        document.getElementById(id);

    if (!form) {
        return;
    }

    form.classList.toggle(
        "open"
    );

}

</script>

</body>

</html>
