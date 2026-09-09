<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
====================================================
PHARMACYX - MANAGE ORDERS
ADMIN + MANAGER
====================================================
*/

require_once "./db_Config/config.php";

/*
====================================================
CHECK ADMIN SESSION
====================================================
*/

$adminLoggedIn = false;
$managerLoggedIn = false;
$activeRole = "";
$activeUsername = "";

session_name("PHARMACYX_ADMIN");
session_start();

if (
    isset($_SESSION['username']) &&
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === "Admin"
) {
    $adminLoggedIn = true;
    $activeRole = "Admin";
    $activeUsername = $_SESSION['username'];
}

session_write_close();

/*
====================================================
CHECK MANAGER SESSION
====================================================
*/

if (!$adminLoggedIn) {

    session_name("PHARMACYX_MANAGER");
    session_start();

    if (
        isset($_SESSION['username']) &&
        isset($_SESSION['user_type']) &&
        $_SESSION['user_type'] === "Manager"
    ) {
        $managerLoggedIn = true;
        $activeRole = "Manager";
        $activeUsername = $_SESSION['username'];
    }

    session_write_close();
}

/*
====================================================
ADMIN OR MANAGER ONLY
====================================================
*/

if (!$adminLoggedIn && !$managerLoggedIn) {
    die("Access Denied. Admin or Manager access required.");
}

/*
====================================================
REOPEN THE CORRECT ROLE SESSION
====================================================
Header.php expects the correct role session to be
active when it is included.
====================================================
*/

if ($adminLoggedIn) {
    session_name("PHARMACYX_ADMIN");
    session_start();
} else {
    session_name("PHARMACYX_MANAGER");
    session_start();
}

/*
====================================================
GET ALL ORDERS
====================================================
*/

$query = "
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
        o.prescription_url,

        p.product_name,
        p.image_url

    FROM orders o

    LEFT JOIN products p
        ON o.product_id = p.product_id

    ORDER BY o.order_id DESC
";

$result = mysqli_query($Connection, $query);

if (!$result) {
    session_write_close();
    die(
        "Order Query Error: " .
        mysqli_error($Connection)
    );
}

/*
====================================================
STORE ORDERS IN ARRAY
This allows statistics + filtering without running
multiple database queries.
====================================================
*/

$orders = [];

while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}

$totalOrders = count($orders);
$pendingOrders = 0;
$acceptedOrders = 0;
$packedOrders = 0;
$deliveryOrders = 0;
$shippedOrders = 0;
$deliveredOrders = 0;
$rejectedOrders = 0;
$cancelledOrders = 0;
$totalRevenue = 0;

foreach ($orders as $row) {

    $status = $row['order_status'] ?? 'Pending';

    switch ($status) {
        case 'Pending':
            $pendingOrders++;
            break;

        case 'Accepted':
            $acceptedOrders++;
            break;

        case 'Packed':
            $packedOrders++;
            break;

        case 'Out for Delivery':
            $deliveryOrders++;
            break;

        case 'Shipped':
            $shippedOrders++;
            break;

        case 'Delivered':
            $deliveredOrders++;
            break;

        case 'Rejected':
            $rejectedOrders++;
            break;

        case 'Cancelled':
            $cancelledOrders++;
            break;
    }

    if ($status !== 'Cancelled' && $status !== 'Rejected') {
        $totalRevenue += (float)($row['Order_total'] ?? 0);
    }
}

$totalActiveOrders =
    $pendingOrders +
    $acceptedOrders +
    $packedOrders +
    $deliveryOrders +
    $shippedOrders;

function pharmacyxStatusClass($status)
{
    switch ($status) {
        case 'Accepted':
            return 'status-accepted';

        case 'Packed':
            return 'status-packed';

        case 'Out for Delivery':
            return 'status-delivery';

        case 'Shipped':
            return 'status-shipped';

        case 'Delivered':
            return 'status-delivered';

        case 'Rejected':
            return 'status-rejected';

        case 'Cancelled':
            return 'status-cancelled';

        default:
            return 'status-pending';
    }
}

function pharmacyxInitials($name)
{
    $name = trim((string)$name);

    if ($name === '') {
        return 'CU';
    }

    $parts = preg_split('/\s+/', $name);

    if (count($parts) >= 2) {
        return strtoupper(
            substr($parts[0], 0, 1) .
            substr($parts[1], 0, 1)
        );
    }

    return strtoupper(substr($name, 0, 2));
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

<title>Manage Orders | PharmacyX</title>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
>

<style>

/* =====================================================
   GLOBAL
===================================================== */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family:
        Inter,
        Arial,
        Helvetica,
        sans-serif;
    background: #f4f7fb;
    color: #172033;
}

/* =====================================================
   DARK MODE
===================================================== */

body.dark-mode {
    background: #111827;
    color: #e5e7eb;
}

body.dark-mode .heading-left h1 {
    color: #f3f4f6;
}

body.dark-mode .heading-left p {
    color: #9ca3af;
}

body.dark-mode .role-badge {
    background: #172554;
    color: #93c5fd;
}

body.dark-mode .summary-card,
body.dark-mode .filter-panel,
body.dark-mode .orders-card,
body.dark-mode .modal-card {
    background: #1f2937 !important;
    color: #e5e7eb;
    border-color: #374151 !important;
}

body.dark-mode .summary-card h3,
body.dark-mode .summary-card p,
body.dark-mode .table-title,
body.dark-mode .orders-card h2,
body.dark-mode .detail-box label {
    color: #d1d5db;
}

body.dark-mode input,
body.dark-mode select {
    background: #111827 !important;
    color: #f3f4f6 !important;
    border-color: #374151 !important;
}

body.dark-mode input::placeholder {
    color: #6b7280;
}

body.dark-mode table {
    color: #e5e7eb;
}

body.dark-mode th {
    background: #111827 !important;
    color: #9ca3af !important;
}

body.dark-mode td {
    border-color: #374151 !important;
    color: #e5e7eb;
}

body.dark-mode tr:hover {
    background: #273449 !important;
}

body.dark-mode .medicine-name,
body.dark-mode .customer-name,
body.dark-mode .amount {
    color: #f3f4f6 !important;
}

body.dark-mode .subtle,
body.dark-mode .date-text {
    color: #9ca3af !important;
}

body.dark-mode .modal-overlay {
    background: rgba(0, 0, 0, 0.72) !important;
}

/* =====================================================
   THEME TOGGLE
===================================================== */

.theme-toggle {
    width: 44px;
    height: 44px;
    border: 1px solid #dbe4ef;
    border-radius: 12px;
    background: #ffffff;
    color: #334155;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 17px;
    transition: 0.2s ease;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
}

.theme-toggle:hover {
    transform: translateY(-1px);
}

body.dark-mode .theme-toggle {
    background: #1f2937;
    color: #fbbf24;
    border-color: #374151;
}

button,
select,
input {
    font-family: inherit;
}

button,
a {
    -webkit-tap-highlight-color: transparent;
}

/* =====================================================
   PAGE
===================================================== */

.orders-page {
    width: 94%;
    max-width: 1500px;
    margin: 32px auto 60px;
}

.page-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 24px;
}

.heading-left h1 {
    margin: 0 0 7px;
    font-size: 31px;
    color: #162238;
}

.heading-left p {
    margin: 0;
    color: #718096;
    font-size: 15px;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 14px;
    border-radius: 10px;
    background: #eaf4ff;
    color: #0077cc;
    font-size: 13px;
    font-weight: 700;
}

/* =====================================================
   SUMMARY CARDS
===================================================== */

.summary-grid {
    display: grid;
    grid-template-columns:
        repeat(4, minmax(0, 1fr));
    gap: 17px;
    margin-bottom: 24px;
}

.summary-card {
    position: relative;
    overflow: hidden;
    background: #ffffff;
    border: 1px solid #e7edf5;
    border-radius: 16px;
    padding: 20px;
    box-shadow:
        0 5px 20px rgba(35, 55, 80, 0.06);
}

.summary-card::after {
    content: "";
    position: absolute;
    width: 80px;
    height: 80px;
    right: -28px;
    bottom: -30px;
    border-radius: 50%;
    background: rgba(0, 119, 204, 0.06);
}

.summary-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.summary-icon {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: #eaf4ff;
    color: #0077cc;
    font-size: 18px;
}

.summary-label {
    margin-top: 15px;
    color: #718096;
    font-size: 13px;
    font-weight: 600;
}

.summary-number {
    margin-top: 5px;
    font-size: 27px;
    font-weight: 800;
    color: #172033;
}

/* =====================================================
   TOOLBAR
===================================================== */

.toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    background: #ffffff;
    border: 1px solid #e7edf5;
    border-radius: 16px;
    padding: 15px;
    margin-bottom: 18px;
    box-shadow:
        0 5px 20px rgba(35, 55, 80, 0.05);
}

.search-box {
    position: relative;
    flex: 1 1 280px;
}

.search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #91a0b5;
}

.search-box input {
    width: 100%;
    height: 44px;
    padding: 0 14px 0 42px;
    border: 1px solid #dce4ee;
    border-radius: 10px;
    outline: none;
    background: #fbfcfe;
    color: #172033;
    font-size: 14px;
}

.search-box input:focus {
    border-color: #0077cc;
    background: #ffffff;
}

.filter-select {
    min-width: 160px;
    height: 44px;
    padding: 0 12px;
    border: 1px solid #dce4ee;
    border-radius: 10px;
    background: #fbfcfe;
    color: #344054;
    outline: none;
}

.filter-select:focus {
    border-color: #0077cc;
}

.date-filter {
    min-width: 145px;
    height: 44px;
    padding: 0 12px;
    border: 1px solid #dce4ee;
    border-radius: 10px;
    background: #fbfcfe;
    color: #344054;
    outline: none;
    font-family: inherit;
}

.date-filter:focus {
    border-color: #0077cc;
}

body.dark-mode .date-filter {
    background: #172033;
    border-color: #2d3a50;
    color: #e8eef8;
}

.period-filter-group { display:flex; align-items:center; gap:6px; padding:4px; background:#f3f7fc; border:1px solid #e2eaf3; border-radius:10px; flex-wrap:wrap; }
.period-btn { border:none; background:transparent; color:#52637a; padding:9px 12px; border-radius:7px; font-size:13px; font-weight:700; cursor:pointer; white-space:nowrap; }
.period-btn:hover { background:#e7f1fb; color:#087df5; }
.period-btn.active { background:#087df5; color:#fff; box-shadow:0 2px 6px rgba(8,125,245,.20); }
body.dark-mode .period-filter-group { background:#172235; border-color:#2b3a52; }
body.dark-mode .period-btn { color:#aebbd0; }
body.dark-mode .period-btn:hover { background:#22324a; color:#8fc3ff; }
body.dark-mode .period-btn.active { background:#087df5; color:#fff; }
@media (max-width:900px) { .period-filter-group { width:100%; justify-content:center; } }

.reset-button {
    height: 44px;
    padding: 0 16px;
    border: 1px solid #dce4ee;
    border-radius: 10px;
    background: #ffffff;
    color: #4a5568;
    cursor: pointer;
    font-weight: 700;
}

.reset-button:hover {
    background: #f3f7fb;
}

/* =====================================================
   TABLE
===================================================== */

.orders-card {
    background: #ffffff;
    border: 1px solid #e7edf5;
    border-radius: 16px;
    overflow: hidden;
    box-shadow:
        0 5px 20px rgba(35, 55, 80, 0.06);
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding: 19px 21px;
    border-bottom: 1px solid #edf1f6;
}

.table-title {
    margin: 0;
    font-size: 18px;
    color: #172033;
}

.table-count {
    color: #718096;
    font-size: 13px;
}

.table-scroll {
    width: 100%;
    overflow-x: auto;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1050px;
}

.orders-table th {
    padding: 13px 15px;
    background: #f8fafc;
    border-bottom: 1px solid #e8edf3;
    color: #667085;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.orders-table td {
    padding: 15px;
    border-bottom: 1px solid #edf1f6;
    vertical-align: middle;
    font-size: 14px;
}

.orders-table tbody tr:hover {
    background: #fbfdff;
}

.order-number {
    font-weight: 800;
    color: #0077cc;
}

.customer-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 145px;
}

.customer-avatar {
    width: 35px;
    height: 35px;
    flex: 0 0 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #eaf4ff;
    color: #0077cc;
    font-size: 12px;
    font-weight: 800;
}

.customer-name {
    font-weight: 700;
    color: #26364d;
}

.product-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 210px;
}

.product-thumb {
    width: 44px;
    height: 44px;
    object-fit: contain;
    padding: 4px;
    border: 1px solid #e6ebf2;
    border-radius: 9px;
    background: #ffffff;
}

.product-placeholder {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    background: #f1f5f9;
    color: #8a98aa;
}

.product-title {
    max-width: 170px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 700;
    color: #26364d;
}

.qty-badge {
    display: inline-flex;
    min-width: 28px;
    height: 28px;
    align-items: center;
    justify-content: center;
    padding: 0 8px;
    border-radius: 8px;
    background: #f1f5f9;
    color: #475467;
    font-weight: 700;
}

.amount {
    font-weight: 800;
    white-space: nowrap;
    color: #172033;
}

.payment {
    color: #475467;
    font-weight: 600;
}

.order-date {
    color: #667085;
    white-space: nowrap;
}

/* =====================================================
   STATUS
===================================================== */

.status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
}

.status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.status-pending {
    color: #b26a00;
    background: #fff4dc;
}

.status-accepted {
    color: #18794e;
    background: #e8f8ef;
}

.status-packed {
    color: #6741a5;
    background: #f1eaff;
}

.status-delivery {
    color: #c54f00;
    background: #fff0e5;
}

.status-shipped {
    color: #275db3;
    background: #eaf2ff;
}

.status-delivered {
    color: #147b8a;
    background: #e7f8fa;
}

.status-rejected {
    color: #bd1f1f;
    background: #ffebeb;
}

.status-cancelled {
    color: #667085;
    background: #eef1f5;
}

/* =====================================================
   ACTION
===================================================== */

.view-button {
    height: 36px;
    padding: 0 12px;
    border: 1px solid #d9e3ef;
    border-radius: 9px;
    background: #ffffff;
    color: #0077cc;
    cursor: pointer;
    font-weight: 800;
    font-size: 12px;
}

.view-button:hover {
    background: #eaf4ff;
    border-color: #b8d9f2;
}

.no-orders {
    padding: 65px 20px;
    text-align: center;
    color: #718096;
}

.no-orders i {
    display: block;
    margin-bottom: 12px;
    font-size: 40px;
    color: #b7c3d2;
}

.no-orders strong {
    display: block;
    margin-bottom: 5px;
    color: #475467;
    font-size: 18px;
}

/* =====================================================
   MODAL
===================================================== */

.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(15, 23, 42, 0.55);
}

.modal-overlay.show {
    display: flex;
}

.order-modal {
    width: 100%;
    max-width: 760px;
    max-height: 90vh;
    overflow-y: auto;
    background: #ffffff;
    border-radius: 18px;
    box-shadow:
        0 20px 60px rgba(0, 0, 0, .22);
}

.modal-head {
    position: sticky;
    top: 0;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding: 19px 22px;
    background: #ffffff;
    border-bottom: 1px solid #edf1f6;
}

.modal-head h2 {
    margin: 0;
    font-size: 20px;
    color: #172033;
}

.close-modal {
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 9px;
    background: #f2f5f8;
    color: #667085;
    cursor: pointer;
    font-size: 17px;
}

.close-modal:hover {
    background: #e7edf3;
}

.modal-body {
    padding: 22px;
}

.modal-product {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid #e7edf5;
    border-radius: 13px;
    background: #fafcff;
}

.modal-product img,
.modal-product .product-placeholder {
    width: 62px;
    height: 62px;
}

.modal-product h3 {
    margin: 0 0 5px;
    font-size: 17px;
}

.modal-product p {
    margin: 0;
    color: #718096;
    font-size: 13px;
}

.detail-grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 13px;
}

.detail-box {
    padding: 14px;
    border: 1px solid #e7edf5;
    border-radius: 11px;
}

.detail-box.full {
    grid-column: 1 / -1;
}

.detail-label {
    display: block;
    margin-bottom: 5px;
    color: #8491a3;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .4px;
}

.detail-value {
    color: #26364d;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.5;
}

.rejection-box {
    margin-top: 16px;
    padding: 13px 15px;
    border-left: 4px solid #dc3545;
    border-radius: 8px;
    background: #fff1f1;
    color: #9f1c1c;
    font-size: 13px;
}

.prescription-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-top: 16px;
    padding: 10px 13px;
    border-radius: 9px;
    background: #eaf4ff;
    color: #0077cc;
    text-decoration: none;
    font-size: 13px;
    font-weight: 800;
}

.status-update-panel {
    margin-top: 20px;
    padding: 17px;
    border-radius: 13px;
    background: #f6f8fb;
    border: 1px solid #e7edf5;
}

.status-update-panel h3 {
    margin: 0 0 12px;
    font-size: 15px;
}

.status-form {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-form select {
    flex: 1;
    height: 43px;
    padding: 0 11px;
    border: 1px solid #d8e1ec;
    border-radius: 9px;
    background: #ffffff;
    color: #344054;
    outline: none;
}

.status-form button {
    height: 43px;
    padding: 0 17px;
    border: 0;
    border-radius: 9px;
    background: #0077cc;
    color: #ffffff;
    cursor: pointer;
    font-weight: 800;
}

.status-form button:hover {
    background: #005fa3;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media screen and (max-width: 1000px) {

    .summary-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}

@media screen and (max-width: 650px) {

    .orders-page {
        width: 92%;
        margin-top: 22px;
    }

    .page-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .heading-left h1 {
        font-size: 25px;
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .toolbar {
        align-items: stretch;
    }

    .search-box,
    .filter-select,
    .reset-button {
        width: 100%;
        flex-basis: 100%;
    }

    .detail-grid {
        grid-template-columns: 1fr;
    }

    .detail-box.full {
        grid-column: auto;
    }

    .status-form {
        flex-direction: column;
        align-items: stretch;
    }
}

</style>

</head>

<body>

<main class="orders-page">

    <!-- =================================================
         PAGE HEADING
    ================================================== -->

    <section class="page-heading">

        <div class="heading-left">

            <h1>
                <i class="fa-solid fa-receipt"></i>
                Manage Orders
            </h1>

            <p>
                Monitor customer orders and update their delivery status.
            </p>

        </div>

        <div style="display:flex;align-items:center;gap:10px;">

            <div class="role-badge">

                <i class="fa-solid fa-user-shield"></i>

                <?php echo htmlspecialchars($activeRole); ?>

                &nbsp;·&nbsp;

                <?php echo htmlspecialchars($activeUsername); ?>

            </div>

            <button
                type="button"
                class="theme-toggle"
                id="themeToggle"
                aria-label="Toggle dark mode"
                title="Toggle dark mode"
            >
                <i class="fa-solid fa-moon" id="themeIcon"></i>
            </button>

        </div>

    </section>


    <!-- =================================================
         SUMMARY
    ================================================== -->

    <section class="summary-grid">

        <div class="summary-card">

            <div class="summary-top">

                <div class="summary-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>

            </div>

            <div class="summary-label">
                Total Orders
            </div>

            <div class="summary-number">
                <?php echo $totalOrders; ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-top">

                <div class="summary-icon">
                    <i class="fa-solid fa-clock"></i>
                </div>

            </div>

            <div class="summary-label">
                Pending Orders
            </div>

            <div class="summary-number">
                <?php echo $pendingOrders; ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-top">

                <div class="summary-icon">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>

            </div>

            <div class="summary-label">
                Active Delivery
            </div>

            <div class="summary-number">
                <?php echo $totalActiveOrders; ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-top">

                <div class="summary-icon">
                    <i class="fa-solid fa-indian-rupee-sign"></i>
                </div>

            </div>

            <div class="summary-label">
                Order Value
            </div>

            <div class="summary-number">
                ₹<?php echo number_format($totalRevenue, 2); ?>
            </div>

        </div>

    </section>


    <!-- =================================================
         SEARCH + FILTER
    ================================================== -->

    <section class="toolbar">

        <div class="search-box">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="search"
                id="orderSearch"
                placeholder="Search order ID, customer or medicine..."
                autocomplete="off"
            >

        </div>


        <select
            id="statusFilter"
            class="filter-select"
        >

            <option value="all">
                All Statuses
            </option>

            <option value="Pending">
                Pending
            </option>

            <option value="Accepted">
                Accepted
            </option>

            <option value="Packed">
                Packed
            </option>

            <option value="Out for Delivery">
                Out for Delivery
            </option>

            <option value="Shipped">
                Shipped
            </option>

            <option value="Delivered">
                Delivered
            </option>

            <option value="Rejected">
                Rejected
            </option>

            <option value="Cancelled">
                Cancelled
            </option>

        </select>


        <select
            id="paymentFilter"
            class="filter-select"
        >

            <option value="all">
                All Payments
            </option>

            <option value="Online">
                Online
            </option>

            <option value="Cash on Delivery">
                Cash on Delivery
            </option>

            <option value="COD">
                COD
            </option>

        </select>


        <!-- ORDER PERIOD FILTERS -->
        <div class="period-filter-group" aria-label="Order period filter">
            <button type="button" class="period-btn active" data-period="all">All Orders</button>
            <button type="button" class="period-btn" data-period="day">Today</button>
            <button type="button" class="period-btn" data-period="month">This Month</button>
            <button type="button" class="period-btn" data-period="year">This Year</button>
        </div>

        <button
            type="button"
            class="reset-button"
            id="resetFilters"
        >
            <i class="fa-solid fa-rotate-left"></i>
            Reset
        </button>

    </section>


    <!-- =================================================
         ORDERS TABLE
    ================================================== -->

    <section class="orders-card">

        <div class="table-header">

            <h2 class="table-title">
                Customer Orders
            </h2>

            <span
                class="table-count"
                id="visibleCount"
            >
                <?php echo $totalOrders; ?> orders
            </span>

        </div>


        <div class="table-scroll">

            <?php if ($totalOrders === 0) { ?>

                <div class="no-orders">

                    <i class="fa-solid fa-box-open"></i>

                    <strong>
                        No customer orders found
                    </strong>

                    <span>
                        Orders will appear here after customers complete checkout.
                    </span>

                </div>

            <?php } else { ?>

                <table class="orders-table">

                    <thead>

                        <tr>

                            <th>
                                Order
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Medicine
                            </th>

                            <th>
                                Qty
                            </th>

                            <th>
                                Total
                            </th>

                            <th>
                                Payment
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody id="ordersBody">

                    <?php foreach ($orders as $index => $order) {

                        $orderId =
                            (int)$order['order_id'];

                        $status =
                            $order['order_status']
                            ?? 'Pending';

                        $customer =
                            $order['user_name']
                            ?? '-';

                        $product =
                            $order['product_name']
                            ?? 'Unknown Product';

                        $payment =
                            $order['payment_method']
                            ?? 'Not Available';

                        $statusClass =
                            pharmacyxStatusClass($status);

                        $searchText = strtolower(
                            $orderId . ' ' .
                            $customer . ' ' .
                            $product . ' ' .
                            ($order['receiver_name'] ?? '')
                        );

                        $paymentText =
                            strtolower($payment);

                        $details = [
                            'id' =>
                                $orderId,

                            'customer' =>
                                $customer,

                            'product' =>
                                $product,

                            'quantity' =>
                                (int)($order['qty'] ?? 0),

                            'total' =>
                                number_format(
                                    (float)($order['Order_total'] ?? 0),
                                    2
                                ),

                            'payment' =>
                                $payment,

                            'date' =>
                                $order['order_date'] ?? '-',

                            'status' =>
                                $status,

                            'type' =>
                                $order['order_type'] ?? '-',

                            'receiver' =>
                                $order['receiver_name'] ?? '-',

                            'address' =>
                                trim(
                                    ($order['street'] ?? '') .
                                    ', ' .
                                    ($order['city'] ?? '') .
                                    ' - ' .
                                    ($order['postal_code'] ?? '')
                                ),

                            'reason' =>
                                $order['rejection_reason'] ?? '',

                            'image' =>
                                $order['image_url'] ?? '',

                            /*
                             * Prescription files are physically stored in
                             * ./uploads/ by the customer upload workflow.
                             *
                             * The database stores the filename, so always
                             * build the browser URL here. This prevents the
                             * browser from requesting:
                             *   /onlinepharmacy/filename.jpg
                             * instead of:
                             *   /onlinepharmacy/uploads/filename.jpg
                             */
                            'prescription' => (
                                !empty($order['prescription_url'])
                                ? './uploads/' . basename(
                                    trim((string)$order['prescription_url'])
                                )
                                : ''
                            )
                        ];

                    ?>

                        <tr
                            class="order-row"
                            data-search="<?php
                                echo htmlspecialchars(
                                    $searchText,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            data-status="<?php
                                echo htmlspecialchars(
                                    $status,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            data-payment="<?php
                                echo htmlspecialchars(
                                    $paymentText,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            data-date="<?php
                                echo htmlspecialchars(
                                    $order['order_date'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                        >

                            <td>

                                <span class="order-number">
                                    #<?php echo $orderId; ?>
                                </span>

                            </td>


                            <td>

                                <div class="customer-cell">

                                    <div class="customer-avatar">
                                        <?php
                                        echo htmlspecialchars(
                                            pharmacyxInitials($customer)
                                        );
                                        ?>
                                    </div>

                                    <span class="customer-name">
                                        <?php
                                        echo htmlspecialchars(
                                            $customer
                                        );
                                        ?>
                                    </span>

                                </div>

                            </td>


                            <td>

                                <div class="product-cell">

                                    <?php if (!empty($order['image_url'])) { ?>

                                        <img
                                            class="product-thumb"
                                            src="./Images/product-icons/<?php
                                                echo htmlspecialchars(
                                                    basename(
                                                        $order['image_url']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );
                                            ?>"
                                            alt="Medicine"
                                            onerror="this.style.display='none';"
                                        >

                                    <?php } else { ?>

                                        <div class="product-placeholder">
                                            <i class="fa-solid fa-pills"></i>
                                        </div>

                                    <?php } ?>


                                    <span
                                        class="product-title"
                                        title="<?php
                                            echo htmlspecialchars(
                                                $product
                                            );
                                        ?>"
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $product
                                        );
                                        ?>
                                    </span>

                                </div>

                            </td>


                            <td>

                                <span class="qty-badge">
                                    <?php
                                    echo (int)(
                                        $order['qty'] ?? 0
                                    );
                                    ?>
                                </span>

                            </td>


                            <td>

                                <span class="amount">
                                    ₹<?php
                                    echo number_format(
                                        (float)(
                                            $order['Order_total']
                                            ?? 0
                                        ),
                                        2
                                    );
                                    ?>
                                </span>

                            </td>


                            <td>

                                <span class="payment">
                                    <?php
                                    echo htmlspecialchars(
                                        $payment
                                    );
                                    ?>
                                </span>

                            </td>


                            <td>

                                <span class="order-date">
                                    <?php
                                    echo htmlspecialchars(
                                        $order['order_date']
                                        ?? '-'
                                    );
                                    ?>
                                </span>

                            </td>


                            <td>

                                <span
                                    class="status <?php
                                        echo $statusClass;
                                    ?>"
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $status
                                    );
                                    ?>
                                </span>

                            </td>


                            <td>

                                <button
                                    type="button"
                                    class="view-button"
                                    onclick='openOrderModal(
                                        <?php
                                        echo json_encode(
                                            $details,
                                            JSON_HEX_TAG |
                                            JSON_HEX_APOS |
                                            JSON_HEX_QUOT |
                                            JSON_HEX_AMP
                                        );
                                        ?>
                                    )'
                                >
                                    <i class="fa-solid fa-eye"></i>
                                    View
                                </button>

                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            <?php } ?>

        </div>

    </section>

</main>


<!-- =====================================================
     ORDER DETAILS MODAL
===================================================== -->

<div
    class="modal-overlay"
    id="orderModal"
    onclick="closeOrderModal(event)"
>

    <div
        class="order-modal"
        onclick="event.stopPropagation()"
    >

        <div class="modal-head">

            <h2 id="modalTitle">
                Order Details
            </h2>

            <button
                type="button"
                class="close-modal"
                onclick="closeOrderModal()"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <div class="modal-body">

            <div class="modal-product">

                <div id="modalProductImage"></div>

                <div>

                    <h3 id="modalProductName">
                        Medicine
                    </h3>

                    <p id="modalProductMeta">
                        Order details
                    </p>

                </div>

            </div>


            <div class="detail-grid">

                <div class="detail-box">

                    <span class="detail-label">
                        Customer
                    </span>

                    <div
                        class="detail-value"
                        id="modalCustomer"
                    ></div>

                </div>


                <div class="detail-box">

                    <span class="detail-label">
                        Receiver
                    </span>

                    <div
                        class="detail-value"
                        id="modalReceiver"
                    ></div>

                </div>


                <div class="detail-box">

                    <span class="detail-label">
                        Quantity
                    </span>

                    <div
                        class="detail-value"
                        id="modalQuantity"
                    ></div>

                </div>


                <div class="detail-box">

                    <span class="detail-label">
                        Total Amount
                    </span>

                    <div
                        class="detail-value"
                        id="modalTotal"
                    ></div>

                </div>


                <div class="detail-box">

                    <span class="detail-label">
                        Payment
                    </span>

                    <div
                        class="detail-value"
                        id="modalPayment"
                    ></div>

                </div>


                <div class="detail-box">

                    <span class="detail-label">
                        Order Type
                    </span>

                    <div
                        class="detail-value"
                        id="modalType"
                    ></div>

                </div>


                <div class="detail-box">

                    <span class="detail-label">
                        Order Date
                    </span>

                    <div
                        class="detail-value"
                        id="modalDate"
                    ></div>

                </div>


                <div class="detail-box">

                    <span class="detail-label">
                        Current Status
                    </span>

                    <div id="modalStatus"></div>

                </div>


                <div class="detail-box full">

                    <span class="detail-label">
                        Delivery Address
                    </span>

                    <div
                        class="detail-value"
                        id="modalAddress"
                    ></div>

                </div>

            </div>


            <div
                class="rejection-box"
                id="modalRejection"
                style="display:none;"
            ></div>


            <a
                href="#"
                target="_blank"
                class="prescription-link"
                id="modalPrescription"
                style="display:none;"
            >
                <i class="fa-solid fa-file-prescription"></i>
                View Prescription
            </a>


            <!-- =================================================
                 STATUS UPDATE
            ================================================== -->

            <div class="status-update-panel">

                <h3>
                    <i class="fa-solid fa-pen-to-square"></i>
                    Change Order Status
                </h3>

                <form
                    action="update_order_status.php"
                    method="POST"
                    class="status-form"
                >

                    <input
                        type="hidden"
                        name="order_id"
                        id="modalOrderId"
                    >

                    <select
                        name="order_status"
                        id="modalStatusSelect"
                        required
                    >

                        <option value="Pending">
                            Pending
                        </option>

                        <option value="Accepted">
                            Accepted
                        </option>

                        <option value="Packed">
                            Packed
                        </option>

                        <option value="Out for Delivery">
                            Out for Delivery
                        </option>

                        <option value="Shipped">
                            Shipped
                        </option>

                        <option value="Delivered">
                            Delivered
                        </option>

                        <option value="Rejected">
                            Rejected
                        </option>

                        <option value="Cancelled">
                            Cancelled
                        </option>

                    </select>


                    <button type="submit">

                        <i class="fa-solid fa-check"></i>
                        Update Status

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>


<script>

/* =====================================================
   LIGHT / DARK MODE TOGGLE
===================================================== */

const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");

function applyTheme(theme) {

    const dark = theme === "dark";

    document.body.classList.toggle("dark-mode", dark);

    if (themeIcon) {
        themeIcon.className = dark
            ? "fa-solid fa-sun"
            : "fa-solid fa-moon";
    }

    if (themeToggle) {
        themeToggle.title = dark
            ? "Switch to light mode"
            : "Switch to dark mode";
        themeToggle.setAttribute(
            "aria-label",
            dark ? "Switch to light mode" : "Switch to dark mode"
        );
    }
}

const savedTheme = localStorage.getItem("pharmacyx_admin_theme") || "light";
applyTheme(savedTheme);

if (themeToggle) {
    themeToggle.addEventListener("click", function() {
        const nextTheme = document.body.classList.contains("dark-mode")
            ? "light"
            : "dark";

        localStorage.setItem("pharmacyx_admin_theme", nextTheme);
        applyTheme(nextTheme);
    });
}

/* =====================================================
   SEARCH + FILTER
===================================================== */

const searchInput =
    document.getElementById("orderSearch");

const statusFilter =
    document.getElementById("statusFilter");

const paymentFilter =
    document.getElementById("paymentFilter");

const resetFilters =
    document.getElementById("resetFilters");

const visibleCount =
    document.getElementById("visibleCount");

const rows =
    Array.from(
        document.querySelectorAll(".order-row")
    );

function filterOrders() {

    const search =
        (searchInput?.value || "")
        .toLowerCase()
        .trim();

    const status =
        statusFilter?.value || "all";

    const payment =
        paymentFilter?.value || "all";

    const selectedPeriod =
        document.querySelector(".period-btn.active")?.dataset.period || "all";

    const now = new Date();
    const todayString = now.getFullYear() + "-" + String(now.getMonth() + 1).padStart(2, "0") + "-" + String(now.getDate()).padStart(2, "0");
    const monthString = now.getFullYear() + "-" + String(now.getMonth() + 1).padStart(2, "0");
    const yearString = String(now.getFullYear());

    let visible = 0;

    rows.forEach(function(row) {

        const rowSearch =
            row.dataset.search || "";

        const rowStatus =
            row.dataset.status || "";

        const rowPayment =
            row.dataset.payment || "";

        const rowDateTime =
            row.dataset.date || "";

        const rowDay =
            rowDateTime.slice(0, 10);

        const rowMonth =
            rowDateTime.slice(0, 7);

        const rowYear =
            rowDateTime.slice(0, 4);

        const matchesSearch =
            search === "" ||
            rowSearch.includes(search);

        const matchesStatus =
            status === "all" ||
            rowStatus === status;

        const matchesPayment =
            payment === "all" ||
            rowPayment.includes(
                payment.toLowerCase()
            );

        const matchesPeriod =
            selectedPeriod === "all" ||
            (selectedPeriod === "day" && rowDay === todayString) ||
            (selectedPeriod === "month" && rowMonth === monthString) ||
            (selectedPeriod === "year" && rowYear === yearString);

        const show =
            matchesSearch &&
            matchesStatus &&
            matchesPayment &&
            matchesPeriod;

        row.style.display =
            show ? "" : "none";

        if (show) {
            visible++;
        }

    });

    if (visibleCount) {

        visibleCount.textContent =
            visible +
            (visible === 1 ? " order" : " orders");

    }

}


searchInput?.addEventListener(
    "input",
    filterOrders
);

statusFilter?.addEventListener(
    "change",
    filterOrders
);

paymentFilter?.addEventListener(
    "change",
    filterOrders
);

document.querySelectorAll(".period-btn").forEach(function(button) {
    button.addEventListener("click", function() {
        document.querySelectorAll(".period-btn").forEach(function(btn) {
            btn.classList.remove("active");
        });
        this.classList.add("active");
        filterOrders();
    });
});


resetFilters?.addEventListener(
    "click",
    function() {

        if (searchInput) {
            searchInput.value = "";
        }

        if (statusFilter) {
            statusFilter.value = "all";
        }

        if (paymentFilter) {
            paymentFilter.value = "all";
        }
filterOrders();

    }
);


/* =====================================================
   ORDER MODAL
===================================================== */

function escapeHtml(value) {

    const div =
        document.createElement("div");

    div.textContent =
        value ?? "";

    return div.innerHTML;
}


function openOrderModal(order) {

    document.getElementById("modalTitle")
        .textContent =
        "Order #" + order.id;


    document.getElementById("modalProductName")
        .textContent =
        order.product || "Medicine";


    document.getElementById("modalProductMeta")
        .textContent =
        "Quantity: " +
        order.quantity +
        "  •  ₹" +
        order.total;


    document.getElementById("modalCustomer")
        .textContent =
        order.customer || "-";


    document.getElementById("modalReceiver")
        .textContent =
        order.receiver || "-";


    document.getElementById("modalQuantity")
        .textContent =
        order.quantity;


    document.getElementById("modalTotal")
        .textContent =
        "₹" + order.total;


    document.getElementById("modalPayment")
        .textContent =
        order.payment || "-";


    document.getElementById("modalType")
        .textContent =
        order.type || "-";


    document.getElementById("modalDate")
        .textContent =
        order.date || "-";


    document.getElementById("modalAddress")
        .textContent =
        order.address || "-";


    document.getElementById("modalOrderId")
        .value =
        order.id;


    document.getElementById("modalStatusSelect")
        .value =
        order.status;


    const statusElement =
        document.getElementById("modalStatus");

    statusElement.innerHTML =
        '<span class="status ' +
        getStatusClass(order.status) +
        '">' +
        escapeHtml(order.status) +
        '</span>';


    const rejection =
        document.getElementById("modalRejection");

    if (order.reason) {

        rejection.style.display =
            "block";

        rejection.innerHTML =
            "<strong>Rejection Reason:</strong> " +
            escapeHtml(order.reason);

    } else {

        rejection.style.display =
            "none";

        rejection.textContent =
            "";

    }


    const prescription =
        document.getElementById(
            "modalPrescription"
        );

    if (order.prescription) {

        prescription.href =
            order.prescription;

        prescription.style.display =
            "inline-flex";

    } else {

        prescription.removeAttribute("href");

        prescription.style.display =
            "none";

    }


    const imageContainer =
        document.getElementById(
            "modalProductImage"
        );

    imageContainer.innerHTML = "";

    if (order.image) {

        const image =
            document.createElement("img");

        image.src =
            "./Images/product-icons/" +
            order.image
            .split("/")
            .pop();

        image.alt =
            "Medicine";

        image.className =
            "product-thumb";

        image.style.width =
            "62px";

        image.style.height =
            "62px";

        image.onerror =
            function() {

                imageContainer.innerHTML =
                    '<div class="product-placeholder">' +
                    '<i class="fa-solid fa-pills"></i>' +
                    '</div>';

            };

        imageContainer.appendChild(image);

    } else {

        imageContainer.innerHTML =
            '<div class="product-placeholder">' +
            '<i class="fa-solid fa-pills"></i>' +
            '</div>';

    }


    document.getElementById("orderModal")
        .classList.add("show");

    document.body.style.overflow =
        "hidden";
}


function getStatusClass(status) {

    switch (status) {

        case "Accepted":
            return "status-accepted";

        case "Packed":
            return "status-packed";

        case "Out for Delivery":
            return "status-delivery";

        case "Shipped":
            return "status-shipped";

        case "Delivered":
            return "status-delivered";

        case "Rejected":
            return "status-rejected";

        case "Cancelled":
            return "status-cancelled";

        default:
            return "status-pending";

    }

}


function closeOrderModal(event) {

    if (
        event &&
        event.target &&
        event.target.id !== "orderModal"
    ) {
        return;
    }

    document.getElementById("orderModal")
        .classList.remove("show");

    document.body.style.overflow =
        "";
}


document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {
            closeOrderModal();
        }

    }
);

</script>


</body>
</html>

<?php

/*
====================================================
CLOSE ROLE SESSION
====================================================
*/

session_write_close();

?>
