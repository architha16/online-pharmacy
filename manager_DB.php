<?php
/*
=========================================================
PHARMACYX MANAGER DASHBOARD
Redesigned UI + Light/Dark Mode + Sales/Profit Analytics
=========================================================
*/

session_name("PHARMACYX_MANAGER");
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "./db_Config/config.php";

/* =========================================================
   MANAGER ACCESS
========================================================= */
if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Manager'
) {
    header("Location: signin.php?role=Manager");
    exit();
}

$managerUsername = $_SESSION['username'];
$managerFirstname = $_SESSION['firstname'] ?? $managerUsername;

/* =========================================================
   HELPER
========================================================= */
function mx_fetch_value($Connection, $sql, $field, $default = 0) {
    $result = mysqli_query($Connection, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row && isset($row[$field])) {
            return $row[$field];
        }
    }
    return $default;
}

/* =========================================================
   UPDATE ORDER STATUS
   Existing feature preserved
========================================================= */
if (isset($_POST['update_status'])) {

    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status = $_POST['order_status'] ?? '';

    $allowed_statuses = [
        'Pending',
        'Shipped',
        'Delivered'
    ];

    if ($order_id > 0 && in_array($new_status, $allowed_statuses, true)) {

        $stmt = mysqli_prepare(
            $Connection,
            "UPDATE orders
             SET order_status = ?
             WHERE order_id = ?"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    header("Location: manager_DB.php#recent-orders");
    exit();
}

/* =========================================================
   ADD ADMIN
   Existing feature preserved
========================================================= */
$adminMessage = "";

if (isset($_POST['addAdmin'])) {

    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = trim($_POST['userpassword'] ?? '');

    if (
        !empty($firstname) &&
        !empty($lastname) &&
        !empty($username) &&
        !empty($email) &&
        !empty($password)
    ) {

        $check = mysqli_prepare(
            $Connection,
            "SELECT user_name
             FROM user_info
             WHERE user_name = ?"
        );

        if ($check) {
            mysqli_stmt_bind_param($check, "s", $username);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {

                $adminMessage = "Username already exists.";

            } else {

                $user_type = "Admin";
                $acc_status = "Active";

                $stmt = mysqli_prepare(
                    $Connection,
                    "INSERT INTO user_info
                    (
                        first_name,
                        last_name,
                        user_name,
                        email,
                        password,
                        user_type,
                        acc_status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                if ($stmt) {
                    mysqli_stmt_bind_param(
                        $stmt,
                        "sssssss",
                        $firstname,
                        $lastname,
                        $username,
                        $email,
                        $password,
                        $user_type,
                        $acc_status
                    );

                    if (mysqli_stmt_execute($stmt)) {
                        $adminMessage = "Admin added successfully.";
                    } else {
                        $adminMessage = "Failed to add admin.";
                    }

                    mysqli_stmt_close($stmt);
                } else {
                    $adminMessage = "Failed to prepare admin request.";
                }
            }

            mysqli_stmt_close($check);
        } else {
            $adminMessage = "Failed to check username.";
        }

    } else {
        $adminMessage = "Please fill all fields.";
    }
}

/* =========================================================
   MANAGE ADMIN
   Existing feature preserved
========================================================= */
$AdminStatus = "----";
$inputValue = "";

if (isset($_POST['check'])) {

    $username = trim($_POST['adminInput'] ?? '');
    $inputValue = $username;

    if (empty($username)) {

        $AdminStatus = "Empty";

    } else {

        $stmt = mysqli_prepare(
            $Connection,
            "SELECT acc_status
             FROM user_info
             WHERE user_name = ?
             AND user_type = 'Admin'
             LIMIT 1"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $AdminStatus = $row['acc_status'];
            } else {
                $AdminStatus = "Invalid Username";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_POST['activate'])) {

    $username = trim($_POST['adminInput'] ?? '');
    $inputValue = $username;

    if (!empty($username)) {

        $stmt = mysqli_prepare(
            $Connection,
            "UPDATE user_info
             SET acc_status = 'Active'
             WHERE user_name = ?
             AND user_type = 'Admin'"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);

            $AdminStatus =
                mysqli_affected_rows($Connection) > 0
                ? "Activated"
                : "Failed";

            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_POST['deactivate'])) {

    $username = trim($_POST['adminInput'] ?? '');
    $inputValue = $username;

    if (!empty($username)) {

        $stmt = mysqli_prepare(
            $Connection,
            "UPDATE user_info
             SET acc_status = 'Inactive'
             WHERE user_name = ?
             AND user_type = 'Admin'"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);

            $AdminStatus =
                mysqli_affected_rows($Connection) > 0
                ? "Deactivated"
                : "Failed";

            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_POST['delete'])) {

    $username = trim($_POST['adminInput'] ?? '');
    $inputValue = $username;

    if (!empty($username)) {

        $stmt = mysqli_prepare(
            $Connection,
            "DELETE FROM user_info
             WHERE user_name = ?
             AND user_type = 'Admin'"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);

            $AdminStatus =
                mysqli_affected_rows($Connection) > 0
                ? "Deleted"
                : "Failed";

            mysqli_stmt_close($stmt);
        }
    }
}

/* =========================================================
   KPI: SALES
========================================================= */
$totalSales = (float) mx_fetch_value(
    $Connection,
    "SELECT COALESCE(SUM(Order_total),0) AS total_sales FROM orders",
    "total_sales"
);

$todaySales = (float) mx_fetch_value(
    $Connection,
    "SELECT COALESCE(SUM(Order_total),0) AS value
     FROM orders
     WHERE DATE(order_date) = CURDATE()",
    "value"
);

$monthlySales = (float) mx_fetch_value(
    $Connection,
    "SELECT COALESCE(SUM(Order_total),0) AS value
     FROM orders
     WHERE YEAR(order_date)=YEAR(CURDATE())
     AND MONTH(order_date)=MONTH(CURDATE())",
    "value"
);

$yearlySales = (float) mx_fetch_value(
    $Connection,
    "SELECT COALESCE(SUM(Order_total),0) AS value
     FROM orders
     WHERE YEAR(order_date)=YEAR(CURDATE())",
    "value"
);

/* =========================================================
   KPI: PROFIT
========================================================= */
$totalProfit = (float) mx_fetch_value(
    $Connection,
    "SELECT COALESCE(SUM((p.price-p.cost_price)*o.qty),0) AS value
     FROM orders o
     INNER JOIN products p ON o.product_id=p.product_id",
    "value"
);

$todayProfit = (float) mx_fetch_value(
    $Connection,
    "SELECT COALESCE(SUM((p.price-p.cost_price)*o.qty),0) AS value
     FROM orders o
     INNER JOIN products p ON o.product_id=p.product_id
     WHERE DATE(o.order_date)=CURDATE()",
    "value"
);

$monthlyProfit = (float) mx_fetch_value(
    $Connection,
    "SELECT COALESCE(SUM((p.price-p.cost_price)*o.qty),0) AS value
     FROM orders o
     INNER JOIN products p ON o.product_id=p.product_id
     WHERE YEAR(o.order_date)=YEAR(CURDATE())
     AND MONTH(o.order_date)=MONTH(CURDATE())",
    "value"
);

$yearlyProfit = (float) mx_fetch_value(
    $Connection,
    "SELECT COALESCE(SUM((p.price-p.cost_price)*o.qty),0) AS value
     FROM orders o
     INNER JOIN products p ON o.product_id=p.product_id
     WHERE YEAR(o.order_date)=YEAR(CURDATE())",
    "value"
);

/* =========================================================
   KPI: ORDERS / PRODUCTS / STOCK
========================================================= */
$totalOrders = (int) mx_fetch_value(
    $Connection,
    "SELECT COUNT(*) AS value FROM orders",
    "value"
);

$todayOrders = (int) mx_fetch_value(
    $Connection,
    "SELECT COUNT(*) AS value
     FROM orders
     WHERE DATE(order_date)=CURDATE()",
    "value"
);

$monthlyOrders = (int) mx_fetch_value(
    $Connection,
    "SELECT COUNT(*) AS value
     FROM orders
     WHERE YEAR(order_date)=YEAR(CURDATE())
     AND MONTH(order_date)=MONTH(CURDATE())",
    "value"
);

$yearlyOrders = (int) mx_fetch_value(
    $Connection,
    "SELECT COUNT(*) AS value
     FROM orders
     WHERE YEAR(order_date)=YEAR(CURDATE())",
    "value"
);

$totalProducts = (int) mx_fetch_value(
    $Connection,
    "SELECT COUNT(*) AS value FROM products",
    "value"
);

$lowStock = (int) mx_fetch_value(
    $Connection,
    "SELECT COUNT(*) AS value
     FROM products
     WHERE stock_quantity <= 10",
    "value"
);

$outOfStock = (int) mx_fetch_value(
    $Connection,
    "SELECT COUNT(*) AS value
     FROM products
     WHERE stock_quantity <= 0",
    "value"
);

$activeCustomers = (int) mx_fetch_value(
    $Connection,
    "SELECT COUNT(*) AS value
     FROM user_info
     WHERE user_type='Customer'
     AND acc_status='Active'",
    "value"
);

$avgOrderValue = $totalOrders > 0
    ? $totalSales / $totalOrders
    : 0;

$profitMargin = $totalSales > 0
    ? ($totalProfit / $totalSales) * 100
    : 0;

/* =========================================================
   ORDER STATUS
========================================================= */
$pendingOrders = (int) mx_fetch_value(
    $Connection,
    "SELECT COUNT(*) AS value
     FROM orders
     WHERE order_status='Pending'",
    "value"
);

$shippedOrders = (int) mx_fetch_value(
    $Connection,
    "SELECT COUNT(*) AS value
     FROM orders
     WHERE order_status='Shipped'",
    "value"
);

$deliveredOrders = (int) mx_fetch_value(
    $Connection,
    "SELECT COUNT(*) AS value
     FROM orders
     WHERE order_status='Delivered'",
    "value"
);

/* =========================================================
   DAILY SALES / PROFIT — LAST 7 DAYS
========================================================= */
$dailyLabels = [];
$dailySalesData = [];
$dailyProfitData = [];

$dailyResult = mysqli_query(
    $Connection,
    "SELECT
        DATE(o.order_date) AS sale_day,
        COALESCE(SUM(o.Order_total),0) AS sales,
        COALESCE(SUM((p.price-p.cost_price)*o.qty),0) AS profit
     FROM orders o
     LEFT JOIN products p ON o.product_id=p.product_id
     WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(o.order_date)
     ORDER BY sale_day ASC"
);

$dailyMap = [];

if ($dailyResult) {
    while ($row = mysqli_fetch_assoc($dailyResult)) {
        $dailyMap[$row['sale_day']] = [
            'sales' => (float)$row['sales'],
            'profit' => (float)$row['profit']
        ];
    }
}

for ($i = 6; $i >= 0; $i--) {
    $dateKey = date('Y-m-d', strtotime("-$i days"));
    $dailyLabels[] = date('d M', strtotime($dateKey));
    $dailySalesData[] = $dailyMap[$dateKey]['sales'] ?? 0;
    $dailyProfitData[] = $dailyMap[$dateKey]['profit'] ?? 0;
}

/* =========================================================
   MONTHLY SALES / PROFIT — LAST 12 MONTHS
========================================================= */
$monthlyLabels = [];
$monthlySalesData = [];
$monthlyProfitData = [];

$monthlyResult = mysqli_query(
    $Connection,
    "SELECT
        DATE_FORMAT(o.order_date,'%Y-%m') AS sale_month,
        COALESCE(SUM(o.Order_total),0) AS sales,
        COALESCE(SUM((p.price-p.cost_price)*o.qty),0) AS profit
     FROM orders o
     LEFT JOIN products p ON o.product_id=p.product_id
     WHERE o.order_date >= DATE_SUB(
        DATE_FORMAT(CURDATE(),'%Y-%m-01'),
        INTERVAL 11 MONTH
     )
     GROUP BY DATE_FORMAT(o.order_date,'%Y-%m')
     ORDER BY sale_month ASC"
);

$monthlyMap = [];

if ($monthlyResult) {
    while ($row = mysqli_fetch_assoc($monthlyResult)) {
        $monthlyMap[$row['sale_month']] = [
            'sales' => (float)$row['sales'],
            'profit' => (float)$row['profit']
        ];
    }
}

for ($i = 11; $i >= 0; $i--) {
    $timestamp = strtotime("first day of -$i months");
    $monthKey = date('Y-m', $timestamp);

    $monthlyLabels[] = date('M Y', $timestamp);
    $monthlySalesData[] = $monthlyMap[$monthKey]['sales'] ?? 0;
    $monthlyProfitData[] = $monthlyMap[$monthKey]['profit'] ?? 0;
}

/* =========================================================
   YEARLY SALES / PROFIT — LAST 5 YEARS
========================================================= */
$yearlyLabels = [];
$yearlySalesData = [];
$yearlyProfitData = [];

$yearlyResult = mysqli_query(
    $Connection,
    "SELECT
        YEAR(o.order_date) AS sale_year,
        COALESCE(SUM(o.Order_total),0) AS sales,
        COALESCE(SUM((p.price-p.cost_price)*o.qty),0) AS profit
     FROM orders o
     LEFT JOIN products p ON o.product_id=p.product_id
     WHERE YEAR(o.order_date) >= YEAR(CURDATE())-4
     GROUP BY YEAR(o.order_date)
     ORDER BY sale_year ASC"
);

$yearlyMap = [];

if ($yearlyResult) {
    while ($row = mysqli_fetch_assoc($yearlyResult)) {
        $yearlyMap[(string)$row['sale_year']] = [
            'sales' => (float)$row['sales'],
            'profit' => (float)$row['profit']
        ];
    }
}

for ($i = 4; $i >= 0; $i--) {
    $year = (int)date('Y') - $i;
    $yearlyLabels[] = (string)$year;
    $yearlySalesData[] = $yearlyMap[(string)$year]['sales'] ?? 0;
    $yearlyProfitData[] = $yearlyMap[(string)$year]['profit'] ?? 0;
}

/* =========================================================
   TOP SELLING MEDICINES
========================================================= */
$topProducts = mysqli_query(
    $Connection,
    "SELECT
        p.product_name,
        COALESCE(SUM(o.qty),0) AS units,
        COALESCE(SUM(o.Order_total),0) AS revenue
     FROM orders o
     INNER JOIN products p ON o.product_id=p.product_id
     GROUP BY o.product_id, p.product_name
     ORDER BY units DESC
     LIMIT 6"
);

$topProductLabels = [];
$topProductUnits = [];

if ($topProducts) {
    while ($row = mysqli_fetch_assoc($topProducts)) {
        $topProductLabels[] = $row['product_name'];
        $topProductUnits[] = (int)$row['units'];
    }
}

/* =========================================================
   ACTIVE DASHBOARD VIEW
========================================================= */
$activeView = 'dashboard';

if (
    isset($_POST['addAdmin']) ||
    isset($_POST['check']) ||
    isset($_POST['activate']) ||
    isset($_POST['deactivate']) ||
    isset($_POST['delete'])
) {
    $activeView = 'admin';
}

/* =========================================================
   RECENT ORDERS
========================================================= */
$recentOrders = mysqli_query(
    $Connection,
    "SELECT
        o.order_id,
        o.user_name,
        o.order_date,
        o.order_status,
        o.Order_total,
        p.product_name
     FROM orders o
     LEFT JOIN products p
        ON o.product_id=p.product_id
     ORDER BY o.order_id DESC
     LIMIT 8"
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manager Dashboard - PharmacyX</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
    --bg: #f4f7fb;
    --surface: #ffffff;
    --surface-2: #f8fafc;
    --text: #172033;
    --muted: #718096;
    --border: #e5eaf1;
    --primary: #1479e8;
    --primary-soft: #e9f3ff;
    --success: #18a66a;
    --success-soft: #e7f8f0;
    --warning: #f2a900;
    --warning-soft: #fff5dc;
    --danger: #e55353;
    --danger-soft: #ffebeb;
    --shadow: 0 8px 28px rgba(25, 48, 82, .07);
}

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    font-family: "Inter", Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
    transition: background .25s ease, color .25s ease;
}

body.dark {
    --bg: #0c1420;
    --surface: #141e2d;
    --surface-2: #192537;
    --text: #edf4fb;
    --muted: #9aaabd;
    --border: #2a394d;
    --primary-soft: #172f4b;
    --success-soft: #123529;
    --warning-soft: #3b3015;
    --danger-soft: #3b2024;
    --shadow: 0 10px 30px rgba(0,0,0,.25);
}

button,
input,
select {
    font: inherit;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 245px;
    background: #101a2a;
    color: #dce7f4;
    padding: 22px 16px;
    z-index: 100;
    display: flex;
    flex-direction: column;
    border-right: 1px solid rgba(255,255,255,.07);
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 4px 8px 22px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

.sidebar-logo {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #1597ff, #1264d8);
    color: white;
    font-size: 18px;
}

.sidebar-brand strong {
    display: block;
    font-size: 17px;
    color: #fff;
}

.sidebar-brand small {
    display: block;
    color: #91a4bb;
    font-size: 10px;
    margin-top: 3px;
}

.sidebar-title {
    color: #6f8299;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.3px;
    padding: 24px 10px 10px;
}

.side-nav {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.side-link {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 11px;
    border-radius: 11px;
    color: #b8c7d9;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: .2s ease;
}

.side-link:hover {
    color: #fff;
    background: rgba(255,255,255,.07);
}

.side-link.active {
    color: #fff;
    background: linear-gradient(90deg, rgba(20,121,232,.28), rgba(20,121,232,.12));
    box-shadow: inset 3px 0 0 #2b9bff;
}

.side-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    color: #87a5c7;
}

.side-link.active .side-icon {
    color: #fff;
    background: rgba(43,155,255,.20);
}

.sidebar-bottom {
    margin-top: auto;
    padding: 14px 5px 2px;
    border-top: 1px solid rgba(255,255,255,.08);
}

.manager-mini {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 8px;
}

.manager-mini .avatar {
    width: 32px;
    height: 32px;
}

.manager-mini strong {
    display: block;
    color: #fff;
    font-size: 11px;
}

.manager-mini small {
    display: block;
    color: #8397ad;
    font-size: 9px;
    margin-top: 2px;
}

.app-shell {
    margin-left: 245px;
    min-height: 100vh;
}

.view-section {
    display: none;
}

.view-section.active-view {
    display: block;
}

.page-view {
    min-height: calc(100vh - 160px);
}

.compact-head {
    margin-bottom: 18px;
}

.topbar {
    height: 72px;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 34px;
    position: sticky;
    top: 0;
    z-index: 50;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 800;
    font-size: 20px;
    color: var(--text);
}

.brand-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #1597ff, #1264d8);
    color: white;
}

.brand small {
    display: block;
    color: var(--muted);
    font-size: 10px;
    font-weight: 500;
    margin-top: 2px;
}

.top-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 12px 7px 7px;
    border: 1px solid var(--border);
    background: var(--surface-2);
    border-radius: 14px;
    font-size: 13px;
}

.avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: var(--primary);
    color: white;
    font-weight: 700;
}

.user-role {
    color: var(--muted);
    font-size: 11px;
    margin-top: 2px;
}

.icon-btn {
    width: 42px;
    height: 42px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--surface);
    color: var(--text);
    cursor: pointer;
    transition: .2s ease;
}

.icon-btn:hover {
    transform: translateY(-1px);
    border-color: var(--primary);
    color: var(--primary);
}

.logout-btn {
    text-decoration: none;
    color: var(--danger);
    border-color: var(--border);
    display: grid;
    place-items: center;
}

.page {
    width: min(1500px, calc(100% - 48px));
    margin: 0 auto;
    padding: 30px 0 50px;
}

.page-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 24px;
}

.page-head h1 {
    margin: 0;
    font-size: 30px;
    letter-spacing: -.7px;
}

.page-head p {
    margin: 7px 0 0;
    color: var(--muted);
    font-size: 13px;
}

.head-date {
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--surface);
    color: var(--muted);
    font-size: 12px;
}

/* KPI */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 18px;
}

.kpi {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 19px;
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
}

.kpi::after {
    content: "";
    position: absolute;
    width: 72px;
    height: 72px;
    border-radius: 50%;
    right: -25px;
    bottom: -28px;
    background: var(--primary-soft);
}

.kpi-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.kpi-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    color: var(--primary);
    background: var(--primary-soft);
}

.kpi-label {
    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
    margin-top: 14px;
}

.kpi-value {
    font-size: 25px;
    font-weight: 800;
    margin-top: 4px;
    letter-spacing: -.5px;
}

.kpi-note {
    color: var(--muted);
    font-size: 11px;
    margin-top: 6px;
}

/* PERIOD CARDS */
.period-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}

.period {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 16px;
    box-shadow: var(--shadow);
}

.period-title {
    display: flex;
    justify-content: space-between;
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
}

.period-value {
    font-size: 19px;
    font-weight: 800;
    margin-top: 9px;
}

.period.sales .period-value {
    color: var(--primary);
}

.period.profit .period-value {
    color: var(--success);
}

/* SECTION */
.section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 28px 0 13px;
}

.section-title h2 {
    font-size: 16px;
    margin: 0;
}

.section-title span {
    color: var(--muted);
    font-size: 11px;
}

/* CHART GRID */
.chart-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 18px;
    margin-bottom: 18px;
}

.chart-grid.equal {
    grid-template-columns: 1fr 1fr;
}

.panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 20px;
    box-shadow: var(--shadow);
}

.panel-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 15px;
}

.panel-head h3 {
    margin: 0;
    font-size: 14px;
}

.panel-head p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 11px;
}

.chart-box {
    height: 270px;
    position: relative;
}

.chart-box.small {
    height: 240px;
}

/* STRATEGY */
.strategy-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

.strategy-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 18px;
    box-shadow: var(--shadow);
}

.strategy-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    background: var(--primary-soft);
    color: var(--primary);
}

.strategy-card h4 {
    margin: 13px 0 5px;
    font-size: 13px;
}

.strategy-card p {
    color: var(--muted);
    font-size: 11px;
    line-height: 1.55;
    margin: 0;
}

.strategy-value {
    font-size: 20px;
    font-weight: 800;
    margin-top: 8px;
}

/* RECENT ORDERS */
.orders-panel {
    overflow-x: auto;
}

.order-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 760px;
}

.order-table th {
    text-align: left;
    padding: 12px 10px;
    color: var(--muted);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .5px;
    border-bottom: 1px solid var(--border);
}

.order-table td {
    padding: 13px 10px;
    border-bottom: 1px solid var(--border);
    font-size: 12px;
}

.order-table tbody tr:hover {
    background: var(--surface-2);
}

.status {
    display: inline-flex;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
}

.status-pending {
    background: var(--warning-soft);
    color: var(--warning);
}

.status-shipped {
    background: #eee9ff;
    color: #7554d8;
}

.status-delivered {
    background: var(--success-soft);
    color: var(--success);
}

.update-form {
    display: flex;
    gap: 6px;
}

.update-form select {
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text);
    border-radius: 8px;
    padding: 6px 7px;
    font-size: 10px;
}

.update-btn {
    border: 0;
    border-radius: 8px;
    padding: 6px 9px;
    background: var(--primary);
    color: white;
    cursor: pointer;
    font-size: 10px;
    font-weight: 700;
}

/* ADMIN MANAGEMENT */
.admin-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.form-panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 20px;
    box-shadow: var(--shadow);
}

.form-panel h3 {
    margin: 0 0 15px;
    font-size: 14px;
}

.form-input {
    width: 100%;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text);
    padding: 10px 12px;
    border-radius: 9px;
    margin-bottom: 9px;
    outline: none;
}

.form-input:focus {
    border-color: var(--primary);
}

.form-button {
    border: 0;
    border-radius: 9px;
    padding: 10px 14px;
    background: var(--primary);
    color: white;
    cursor: pointer;
    font-weight: 700;
    font-size: 12px;
}

.admin-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 13px;
}

.activate {
    background: var(--success);
}

.deactivate {
    background: var(--warning);
}

.delete {
    background: var(--danger);
}

.message {
    padding: 10px 12px;
    border-radius: 9px;
    background: var(--success-soft);
    color: var(--success);
    font-size: 12px;
    margin-bottom: 12px;
}

.admin-status {
    color: var(--muted);
    font-size: 12px;
}

.admin-status strong {
    color: var(--text);
}

/* FOOTER */
.footer {
    text-align: center;
    color: var(--muted);
    font-size: 11px;
    padding: 24px 0 4px;
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .sidebar {
        width: 76px;
        padding: 18px 10px;
    }

    .sidebar-brand {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    .sidebar-brand > div:last-child,
    .sidebar-title,
    .side-link span:last-child,
    .manager-mini > div:last-child {
        display: none;
    }

    .side-link {
        justify-content: center;
        padding: 10px;
    }

    .side-icon {
        width: 34px;
        height: 34px;
    }

    .manager-mini {
        justify-content: center;
    }

    .app-shell {
        margin-left: 76px;
    }
}

@media (max-width: 1200px) {
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .period-grid {
        grid-template-columns: repeat(3, 1fr);
    }

    .strategy-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 850px) {
    .chart-grid,
    .chart-grid.equal,
    .admin-grid {
        grid-template-columns: 1fr;
    }

    .page {
        width: min(100% - 24px, 1500px);
    }

    .topbar {
        padding: 0 15px;
    }

    .user-chip .user-text {
        display: none;
    }
}

@media (max-width: 600px) {
    .kpi-grid,
    .period-grid,
    .strategy-grid {
        grid-template-columns: 1fr;
    }

    .page-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .page-head h1 {
        font-size: 24px;
    }

    .brand-text {
        display: none;
    }
}
</style>
</head>

<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo">
            <i class="fa-solid fa-pills"></i>
        </div>
        <div>
            <strong>PharmacyX</strong>
            <small>Manager Panel</small>
        </div>
    </div>

    <div class="sidebar-title">MENU</div>

    <nav class="side-nav">
        <a href="#dashboard" class="side-link active" data-view="dashboard">
            <span class="side-icon"><i class="fa-solid fa-chart-pie"></i></span>
            <span>Dashboard</span>
        </a>

        <a href="#recent-orders" class="side-link" data-view="recent-orders">
            <span class="side-icon"><i class="fa-solid fa-cart-shopping"></i></span>
            <span>Recent Orders</span>
        </a>

        <a href="#admin-management" class="side-link" data-view="admin-management">
            <span class="side-icon"><i class="fa-solid fa-user-gear"></i></span>
            <span>Admin Management</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="manager-mini">
            <div class="avatar">
                <?php echo strtoupper(substr($managerFirstname, 0, 1)); ?>
            </div>
            <div>
                <strong><?php echo htmlspecialchars($managerFirstname); ?></strong>
                <small>Manager</small>
            </div>
        </div>
    </div>
</aside>

<div class="app-shell">

<header class="topbar">
    <div class="brand">
        <div class="brand-icon">
            <i class="fa-solid fa-pills"></i>
        </div>
        <div class="brand-text">
            PharmacyX
            <small>Manager Management</small>
        </div>
    </div>

    <div class="top-actions">

        <div class="user-chip">
            <div class="avatar">
                <?php echo strtoupper(substr($managerFirstname, 0, 1)); ?>
            </div>

            <div class="user-text">
                <strong><?php echo htmlspecialchars($managerFirstname); ?></strong>
                <div class="user-role">Manager</div>
            </div>
        </div>

        <button
            type="button"
            class="icon-btn"
            id="themeToggle"
            title="Switch to dark mode"
            aria-label="Toggle light and dark mode"
        >
            <i class="fa-solid fa-moon"></i>
        </button>

        <a
            href="logout.php"
            class="icon-btn logout-btn"
            title="Logout"
            aria-label="Logout"
        >
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>

    </div>
</header>

<main class="page">

    <section id="dashboard" class="view-section dashboard-view">

    <div class="page-head">
        <div>
            <h1>Manager Dashboard</h1>
            <p>Monitor pharmacy performance, sales, profit, orders and business health.</p>
        </div>

        <div class="head-date">
            <i class="fa-regular fa-calendar"></i>
            <?php echo date('d M Y'); ?>
        </div>
    </div>

    <!-- MAIN KPI CARDS -->
    <div class="kpi-grid">

        <div class="kpi">
            <div class="kpi-top">
                <div class="kpi-icon">
                    <i class="fa-solid fa-indian-rupee-sign"></i>
                </div>
            </div>
            <div class="kpi-label">Total Sales</div>
            <div class="kpi-value">₹<?php echo number_format($totalSales, 2); ?></div>
            <div class="kpi-note">All recorded orders</div>
        </div>

        <div class="kpi">
            <div class="kpi-top">
                <div class="kpi-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
            <div class="kpi-label">Total Profit</div>
            <div class="kpi-value">₹<?php echo number_format($totalProfit, 2); ?></div>
            <div class="kpi-note"><?php echo number_format($profitMargin, 1); ?>% overall margin</div>
        </div>

        <div class="kpi">
            <div class="kpi-top">
                <div class="kpi-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
            </div>
            <div class="kpi-label">Total Orders</div>
            <div class="kpi-value"><?php echo $totalOrders; ?></div>
            <div class="kpi-note"><?php echo $todayOrders; ?> orders today</div>
        </div>

        <div class="kpi">
            <div class="kpi-top">
                <div class="kpi-icon">
                    <i class="fa-solid fa-pills"></i>
                </div>
            </div>
            <div class="kpi-label">Medicine Inventory</div>
            <div class="kpi-value"><?php echo $totalProducts; ?></div>
            <div class="kpi-note"><?php echo $lowStock; ?> low-stock items</div>
        </div>

    </div>

    <!-- DAY / MONTH / YEAR SALES + PROFIT -->
    <div class="section-title">
        <h2>Sales & Profit Performance</h2>
        <span>Current day, month and year</span>
    </div>

    <div class="period-grid">

        <div class="period sales">
            <div class="period-title">
                <span>Today Sales</span>
                <i class="fa-solid fa-sun"></i>
            </div>
            <div class="period-value">₹<?php echo number_format($todaySales, 2); ?></div>
        </div>

        <div class="period sales">
            <div class="period-title">
                <span>Monthly Sales</span>
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <div class="period-value">₹<?php echo number_format($monthlySales, 2); ?></div>
        </div>

        <div class="period sales">
            <div class="period-title">
                <span>Yearly Sales</span>
                <i class="fa-solid fa-calendar"></i>
            </div>
            <div class="period-value">₹<?php echo number_format($yearlySales, 2); ?></div>
        </div>

        <div class="period profit">
            <div class="period-title">
                <span>Today Profit</span>
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div class="period-value">₹<?php echo number_format($todayProfit, 2); ?></div>
        </div>

        <div class="period profit">
            <div class="period-title">
                <span>Monthly Profit</span>
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div class="period-value">₹<?php echo number_format($monthlyProfit, 2); ?></div>
        </div>

        <div class="period profit">
            <div class="period-title">
                <span>Yearly Profit</span>
                <i class="fa-solid fa-coins"></i>
            </div>
            <div class="period-value">₹<?php echo number_format($yearlyProfit, 2); ?></div>
        </div>

    </div>

    <!-- DAILY TREND + ORDER STATUS -->
    <div class="chart-grid">

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h3>Daily Sales & Profit</h3>
                    <p>Last 7 days performance</p>
                </div>
            </div>

            <div class="chart-box">
                <canvas id="dailyChart"></canvas>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h3>Order Status</h3>
                    <p>Current order distribution</p>
                </div>
            </div>

            <div class="chart-box small">
                <canvas id="statusChart"></canvas>
            </div>
        </section>

    </div>

    <!-- MONTHLY + YEARLY -->
    <div class="chart-grid equal">

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h3>Monthly Sales & Profit</h3>
                    <p>12-month business trend</p>
                </div>
            </div>

            <div class="chart-box">
                <canvas id="monthlyChart"></canvas>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h3>Yearly Sales & Profit</h3>
                    <p>Five-year performance view</p>
                </div>
            </div>

            <div class="chart-box">
                <canvas id="yearlyChart"></canvas>
            </div>
        </section>

    </div>

    <!-- TOP MEDICINES -->
    <div class="chart-grid equal">

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h3>Top Selling Medicines</h3>
                    <p>Highest units sold</p>
                </div>
            </div>

            <div class="chart-box">
                <canvas id="topProductsChart"></canvas>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h3>Business Health</h3>
                    <p>Important management indicators</p>
                </div>
            </div>

            <div class="strategy-grid" style="grid-template-columns:1fr 1fr;">

                <div class="strategy-card">
                    <div class="strategy-icon">
                        <i class="fa-solid fa-bullseye"></i>
                    </div>
                    <h4>Average Order Value</h4>
                    <div class="strategy-value">
                        ₹<?php echo number_format($avgOrderValue, 2); ?>
                    </div>
                </div>

                <div class="strategy-card">
                    <div class="strategy-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <h4>Active Customers</h4>
                    <div class="strategy-value">
                        <?php echo $activeCustomers; ?>
                    </div>
                </div>

                <div class="strategy-card">
                    <div class="strategy-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h4>Low Stock</h4>
                    <div class="strategy-value">
                        <?php echo $lowStock; ?>
                    </div>
                </div>

                <div class="strategy-card">
                    <div class="strategy-icon">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <h4>Out of Stock</h4>
                    <div class="strategy-value">
                        <?php echo $outOfStock; ?>
                    </div>
                </div>

            </div>
        </section>

    </div>

    <!-- BUSINESS STRATEGY MODEL -->
    <div class="section-title">
        <h2>Business Strategy Overview</h2>
        <span>Use these indicators for management decisions</span>
    </div>

    <div class="strategy-grid">

        <div class="strategy-card">
            <div class="strategy-icon">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <h4>Revenue Growth</h4>
            <p>Compare daily, monthly and yearly sales trends to identify growth or declining periods.</p>
        </div>

        <div class="strategy-card">
            <div class="strategy-icon">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <h4>Profit Optimization</h4>
            <p>Track profit against sales and focus on medicines with healthy margins and strong demand.</p>
        </div>

        <div class="strategy-card">
            <div class="strategy-icon">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <h4>Inventory Strategy</h4>
            <p>Monitor low and out-of-stock medicines so popular products can be restocked before sales are lost.</p>
        </div>

        <div class="strategy-card">
            <div class="strategy-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <h4>Customer Strategy</h4>
            <p>Use order volume and active-customer data to understand demand and improve customer retention.</p>
        </div>

    </div>

    </section><!-- /#dashboard -->

    <!-- RECENT ORDERS VIEW -->
    <section id="recent-orders" class="view-section page-view">

    <div class="page-head compact-head">
        <div>
            <h1>Recent Orders</h1>
            <p>Review the latest customer orders and update their delivery status.</p>
        </div>
    </div>

    <div class="section-title">
        <h2>Recent Orders</h2>
        <span>Latest 8 orders</span>
    </div>

    <section class="panel orders-panel">

        <table class="order-table">

            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Medicine</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($recentOrders && mysqli_num_rows($recentOrders) > 0): ?>

                <?php while ($order = mysqli_fetch_assoc($recentOrders)): ?>

                    <?php
                    $status = $order['order_status'];

                    if ($status === 'Delivered') {
                        $statusClass = 'status-delivered';
                    } elseif ($status === 'Shipped') {
                        $statusClass = 'status-shipped';
                    } else {
                        $statusClass = 'status-pending';
                    }
                    ?>

                    <tr>

                        <td>
                            <strong>#<?php echo (int)$order['order_id']; ?></strong>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($order['user_name']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($order['product_name'] ?? 'Medicine'); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($order['order_date']); ?>
                        </td>

                        <td>
                            <strong>
                                ₹<?php echo number_format((float)$order['Order_total'], 2); ?>
                            </strong>
                        </td>

                        <td>
                            <span class="status <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>

                        <td>
                            <form method="POST" class="update-form">

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?php echo (int)$order['order_id']; ?>"
                                >

                                <select name="order_status">

                                    <option
                                        value="Pending"
                                        <?php echo $status === 'Pending' ? 'selected' : ''; ?>
                                    >
                                        Pending
                                    </option>

                                    <option
                                        value="Shipped"
                                        <?php echo $status === 'Shipped' ? 'selected' : ''; ?>
                                    >
                                        Shipped
                                    </option>

                                    <option
                                        value="Delivered"
                                        <?php echo $status === 'Delivered' ? 'selected' : ''; ?>
                                    >
                                        Delivered
                                    </option>

                                </select>

                                <button
                                    type="submit"
                                    name="update_status"
                                    class="update-btn"
                                >
                                    Update
                                </button>

                            </form>
                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="7" style="text-align:center;padding:30px;">
                        No orders found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </section>

    </section><!-- /#recent-orders -->

    <!-- ADMIN MANAGEMENT VIEW -->
    <section id="admin-management" class="view-section page-view">

    <div class="page-head compact-head">
        <div>
            <h1>Admin Management</h1>
            <p>Add administrators and manage existing administrator accounts.</p>
        </div>
    </div>

    <div class="section-title">
        <h2>Admin Management</h2>
        <span>Existing manager admin controls</span>
    </div>

    <div class="admin-grid">

        <!-- ADD ADMIN -->
        <section class="form-panel">

            <h3>
                <i class="fa-solid fa-user-plus"></i>
                Add Admin
            </h3>

            <?php if (!empty($adminMessage)): ?>
                <div class="message">
                    <?php echo htmlspecialchars($adminMessage); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <input
                    class="form-input"
                    type="text"
                    name="firstname"
                    placeholder="First Name"
                    required
                >

                <input
                    class="form-input"
                    type="text"
                    name="lastname"
                    placeholder="Last Name"
                    required
                >

                <input
                    class="form-input"
                    type="text"
                    name="username"
                    placeholder="Username"
                    required
                >

                <input
                    class="form-input"
                    type="email"
                    name="email"
                    placeholder="Email"
                    required
                >

                <input
                    class="form-input"
                    type="password"
                    name="userpassword"
                    placeholder="Password"
                    required
                >

                <button
                    class="form-button"
                    type="submit"
                    name="addAdmin"
                >
                    Add Admin
                </button>

            </form>

        </section>

        <!-- MANAGE ADMIN -->
        <section class="form-panel">

            <h3>
                <i class="fa-solid fa-users-gear"></i>
                Manage Admins
            </h3>

            <form method="POST">

                <input
                    class="form-input"
                    type="text"
                    name="adminInput"
                    placeholder="Admin Username"
                    value="<?php echo htmlspecialchars($inputValue); ?>"
                    required
                >

                <button
                    class="form-button"
                    type="submit"
                    name="check"
                >
                    Check Status
                </button>

            </form>

            <div class="admin-status" style="margin-top:14px;">
                <strong>Status:</strong>
                <?php echo htmlspecialchars($AdminStatus); ?>
            </div>

            <div class="admin-buttons">

                <form method="POST">
                    <input
                        type="hidden"
                        name="adminInput"
                        value="<?php echo htmlspecialchars($inputValue); ?>"
                    >
                    <button
                        class="form-button activate"
                        type="submit"
                        name="activate"
                    >
                        Activate
                    </button>
                </form>

                <form method="POST">
                    <input
                        type="hidden"
                        name="adminInput"
                        value="<?php echo htmlspecialchars($inputValue); ?>"
                    >
                    <button
                        class="form-button deactivate"
                        type="submit"
                        name="deactivate"
                    >
                        Deactivate
                    </button>
                </form>

                <form method="POST">
                    <input
                        type="hidden"
                        name="adminInput"
                        value="<?php echo htmlspecialchars($inputValue); ?>"
                    >
                    <button
                        class="form-button delete"
                        type="submit"
                        name="delete"
                    >
                        Delete
                    </button>
                </form>

            </div>

        </section>

    </div>

    </section><!-- /#admin-management -->

    <div class="footer">
        PharmacyX Manager Dashboard • Business analytics and pharmacy management
    </div>

</main>

</div><!-- /.app-shell -->

<script>
/* =========================================================
   SIDEBAR VIEW NAVIGATION
========================================================= */
const sideLinks = document.querySelectorAll(".side-link");
const viewSections = document.querySelectorAll(".view-section");

function showView(viewName, updateHash = true) {
    viewSections.forEach(function(section) {
        section.classList.toggle(
            "active-view",
            section.id === viewName
        );
    });

    sideLinks.forEach(function(link) {
        link.classList.toggle(
            "active",
            link.dataset.view === viewName
        );
    });

    if (updateHash) {
        history.replaceState(
            null,
            "",
            "#" + viewName
        );
    }

    window.scrollTo({
        top: 0,
        behavior: "smooth"
    });
}

sideLinks.forEach(function(link) {
    link.addEventListener("click", function(event) {
        event.preventDefault();
        showView(link.dataset.view);
    });
});

const initialHash = window.location.hash.replace("#", "");
const serverActiveView = <?php echo json_encode($activeView); ?>;

if (
    initialHash === "recent-orders" ||
    initialHash === "admin-management"
) {
    showView(initialHash, false);
} else if (
    serverActiveView === "admin"
) {
    showView("admin-management", false);
} else {
    showView("dashboard", false);
}

/* =========================================================
   LIGHT / DARK MODE
========================================================= */
const themeToggle = document.getElementById("themeToggle");
const body = document.body;

const savedTheme = localStorage.getItem("pharmacyx_manager_theme");

if (savedTheme === "dark") {
    body.classList.add("dark");
}

function updateThemeIcon() {
    const icon = themeToggle.querySelector("i");

    if (body.classList.contains("dark")) {
        icon.className = "fa-solid fa-sun";
        themeToggle.title = "Switch to light mode";
    } else {
        icon.className = "fa-solid fa-moon";
        themeToggle.title = "Switch to dark mode";
    }
}

updateThemeIcon();

themeToggle.addEventListener("click", function () {

    body.classList.toggle("dark");

    localStorage.setItem(
        "pharmacyx_manager_theme",
        body.classList.contains("dark")
            ? "dark"
            : "light"
    );

    updateThemeIcon();

    setTimeout(updateChartsTheme, 50);
});

/* =========================================================
   CHART DEFAULTS
========================================================= */
Chart.defaults.font.family = "Inter, Arial, sans-serif";
Chart.defaults.font.size = 10;

function chartTextColor() {
    return body.classList.contains("dark")
        ? "#dbe7f3"
        : "#526174";
}

function chartGridColor() {
    return body.classList.contains("dark")
        ? "rgba(255,255,255,.08)"
        : "rgba(82,97,116,.10)";
}

const commonScales = {
    x: {
        ticks: {
            color: chartTextColor()
        },
        grid: {
            color: chartGridColor()
        }
    },
    y: {
        beginAtZero: true,
        ticks: {
            color: chartTextColor(),
            callback: function(value) {
                return "₹" + Number(value).toLocaleString("en-IN");
            }
        },
        grid: {
            color: chartGridColor()
        }
    }
};

let dailyChart;
let monthlyChart;
let yearlyChart;
let statusChart;
let topProductsChart;

/* =========================================================
   DAILY CHART
========================================================= */
dailyChart = new Chart(
    document.getElementById("dailyChart"),
    {
        type: "line",
        data: {
            labels: <?php echo json_encode($dailyLabels); ?>,
            datasets: [
                {
                    label: "Sales",
                    data: <?php echo json_encode($dailySalesData); ?>,
                    borderWidth: 3,
                    tension: .35,
                    fill: false
                },
                {
                    label: "Profit",
                    data: <?php echo json_encode($dailyProfitData); ?>,
                    borderWidth: 3,
                    tension: .35,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: "index",
                intersect: false
            },
            plugins: {
                legend: {
                    labels: {
                        color: chartTextColor()
                    }
                }
            },
            scales: commonScales
        }
    }
);

/* =========================================================
   MONTHLY CHART
========================================================= */
monthlyChart = new Chart(
    document.getElementById("monthlyChart"),
    {
        type: "line",
        data: {
            labels: <?php echo json_encode($monthlyLabels); ?>,
            datasets: [
                {
                    label: "Sales",
                    data: <?php echo json_encode($monthlySalesData); ?>,
                    borderWidth: 3,
                    tension: .35,
                    fill: false
                },
                {
                    label: "Profit",
                    data: <?php echo json_encode($monthlyProfitData); ?>,
                    borderWidth: 3,
                    tension: .35,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: "index",
                intersect: false
            },
            plugins: {
                legend: {
                    labels: {
                        color: chartTextColor()
                    }
                }
            },
            scales: commonScales
        }
    }
);

/* =========================================================
   YEARLY CHART
========================================================= */
yearlyChart = new Chart(
    document.getElementById("yearlyChart"),
    {
        type: "bar",
        data: {
            labels: <?php echo json_encode($yearlyLabels); ?>,
            datasets: [
                {
                    label: "Sales",
                    data: <?php echo json_encode($yearlySalesData); ?>,
                    borderWidth: 0
                },
                {
                    label: "Profit",
                    data: <?php echo json_encode($yearlyProfitData); ?>,
                    borderWidth: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: chartTextColor()
                    }
                }
            },
            scales: commonScales
        }
    }
);

/* =========================================================
   ORDER STATUS PIE
========================================================= */
statusChart = new Chart(
    document.getElementById("statusChart"),
    {
        type: "doughnut",
        data: {
            labels: [
                "Pending",
                "Shipped",
                "Delivered"
            ],
            datasets: [
                {
                    data: [
                        <?php echo $pendingOrders; ?>,
                        <?php echo $shippedOrders; ?>,
                        <?php echo $deliveredOrders; ?>
                    ],
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "62%",
            plugins: {
                legend: {
                    position: "bottom",
                    labels: {
                        color: chartTextColor(),
                        padding: 14
                    }
                }
            }
        }
    }
);

/* =========================================================
   TOP PRODUCTS BAR
========================================================= */
topProductsChart = new Chart(
    document.getElementById("topProductsChart"),
    {
        type: "bar",
        data: {
            labels: <?php echo json_encode($topProductLabels); ?>,
            datasets: [
                {
                    label: "Units Sold",
                    data: <?php echo json_encode($topProductUnits); ?>,
                    borderWidth: 0,
                    borderRadius: 8
                }
            ]
        },
        options: {
            indexAxis: "y",
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: chartTextColor()
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        color: chartTextColor()
                    },
                    grid: {
                        color: chartGridColor()
                    }
                },
                y: {
                    ticks: {
                        color: chartTextColor()
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    }
);

/* =========================================================
   UPDATE CHART TEXT AFTER THEME CHANGE
========================================================= */
function updateChartsTheme() {

    const charts = [
        dailyChart,
        monthlyChart,
        yearlyChart,
        statusChart,
        topProductsChart
    ];

    charts.forEach(function(chart) {

        if (!chart) return;

        if (chart.options.plugins &&
            chart.options.plugins.legend &&
            chart.options.plugins.legend.labels) {

            chart.options.plugins.legend.labels.color =
                chartTextColor();
        }

        if (chart.options.scales) {

            Object.keys(chart.options.scales).forEach(function(axis) {

                const scale = chart.options.scales[axis];

                if (scale.ticks) {
                    scale.ticks.color = chartTextColor();
                }

                if (scale.grid) {
                    scale.grid.color = chartGridColor();
                }
            });
        }

        chart.update();
    });
}
</script>

</body>
</html>
