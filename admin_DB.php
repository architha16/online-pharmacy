<?php

// Marasingha M A M N
// IT23539990

/*
====================================================
ADMIN SESSION
====================================================
*/

session_name("PHARMACYX_ADMIN");

session_start();


/*
====================================================
CHECK ADMIN LOGIN
====================================================
*/

if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Admin'
) {

    header("Location: signin.php?role=Admin");

    exit();

}


/*
====================================================
DATABASE CONNECTION
====================================================
*/

require_once './db_Config/config.php';



/*
====================================================
USER STATUS FUNCTIONALITY
====================================================
*/

$UserStatus = '-----';

$inputValue = '';


/*
====================================================
CHECK USER
====================================================
*/

if (isset($_POST['Check'])) {

    $username = trim($_POST['UserInput'] ?? '');

    $inputValue = $username;


    if (empty($username)) {

        $UserStatus = 'Empty';

    }

    else {

        $username =
            mysqli_real_escape_string(
                $Connection,
                $username
            );


        $sql = "
            SELECT acc_status
            FROM User_info
            WHERE user_name='$username'
        ";


        $result =
            mysqli_query(
                $Connection,
                $sql
            );


        if (
            $result &&
            mysqli_num_rows($result) > 0
        ) {

            $row =
                mysqli_fetch_assoc($result);

            $UserStatus =
                $row['acc_status'];

        }

        else {

            $UserStatus =
                'Invalid UN';

        }

    }

}



/*
====================================================
ACTIVATE USER
====================================================
*/

if (isset($_POST['activate'])) {

    $username =
        trim($_POST['UserInput'] ?? '');


    if (empty($username)) {

        $UserStatus =
            'Empty';

    }

    else {

        $username =
            mysqli_real_escape_string(
                $Connection,
                $username
            );


        $sql = "
            UPDATE User_info
            SET acc_status='Active'
            WHERE user_name='$username'
            AND user_type='Customer'
        ";


        $result =
            mysqli_query(
                $Connection,
                $sql
            );


        if (
            $result &&
            mysqli_affected_rows($Connection) > 0
        ) {

            $UserStatus =
                'Activated';

        }

        else {

            $UserStatus =
                'Failed';

        }

    }

}



/*
====================================================
DEACTIVATE USER
====================================================
*/

if (isset($_POST['deactivate'])) {

    $username =
        trim($_POST['UserInput'] ?? '');


    if (empty($username)) {

        $UserStatus =
            'Empty';

    }

    else {

        $username =
            mysqli_real_escape_string(
                $Connection,
                $username
            );


        $sql = "
            UPDATE User_info
            SET acc_status='Inactive'
            WHERE user_name='$username'
            AND user_type='Customer'
        ";


        $result =
            mysqli_query(
                $Connection,
                $sql
            );


        if (
            $result &&
            mysqli_affected_rows($Connection) > 0
        ) {

            $UserStatus =
                'Deactivated';

        }

        else {

            $UserStatus =
                'Failed';

        }

    }

}



/*
====================================================
DELETE USER
====================================================
*/

if (isset($_POST['delete'])) {

    $username =
        trim($_POST['UserInput'] ?? '');


    if (empty($username)) {

        $UserStatus =
            'Empty';

    }

    else {

        $username =
            mysqli_real_escape_string(
                $Connection,
                $username
            );


        $sql = "
            DELETE FROM User_info
            WHERE user_name='$username'
            AND user_type='Customer'
        ";


        $result =
            mysqli_query(
                $Connection,
                $sql
            );


        if (
            $result &&
            mysqli_affected_rows($Connection) > 0
        ) {

            $UserStatus =
                'Deleted';

        }

        else {

            $UserStatus =
                'Failed';

        }

    }

}



/*
====================================================
USER ANALYTICS
====================================================
*/

$sql = "
    SELECT
        COUNT(user_name) AS TotalUsers,

        COUNT(
            CASE
                WHEN acc_status='Active'
                THEN 1
            END
        ) AS ActiveUsers,

        COUNT(
            CASE
                WHEN acc_status='Inactive'
                THEN 1
            END
        ) AS DeactivatedUsers

    FROM User_info

    WHERE user_type='Customer'
";


$result =
    mysqli_query(
        $Connection,
        $sql
    );


if (
    $result &&
    mysqli_num_rows($result) > 0
) {

    $row =
        mysqli_fetch_assoc($result);


    $TotalUsers =
        $row['TotalUsers'];

    $ActiveUsers =
        $row['ActiveUsers'];

    $DeactivatedUsers =
        $row['DeactivatedUsers'];

}

else {

    $TotalUsers = 0;

    $ActiveUsers = 0;

    $DeactivatedUsers = 0;

}



/*
====================================================
MARK ORDER AS SHIPPED
====================================================
*/

if (isset($_POST['Shipped'])) {

    $orderID =
        intval($_POST['order_id'] ?? 0);


    $sql = "
        UPDATE Orders
        SET order_status='Shipped'
        WHERE order_id='$orderID'
    ";


    $result =
        mysqli_query(
            $Connection,
            $sql
        );


    if (
        $result &&
        mysqli_affected_rows($Connection) > 0
    ) {

        header(
            "Location: " .
            $_SERVER['PHP_SELF']
        );

        exit();

    }

}



/*
====================================================
MESSAGE REPLY
====================================================
*/

if (isset($_POST['submit_rply'])) {

    $messageID =
        intval($_POST['message_id'] ?? 0);


    $reply =
        mysqli_real_escape_string(
            $Connection,
            trim($_POST['Reply_in'] ?? '')
        );


    $sql = "
        UPDATE Messages
        SET response_text='$reply'
        WHERE message_id='$messageID'
    ";


    $result =
        mysqli_query(
            $Connection,
            $sql
        );


    if (
        $result &&
        mysqli_affected_rows($Connection) > 0
    ) {

        header(
            "Location: " .
            $_SERVER['PHP_SELF']
        );

        exit();

    }

}



/*
====================================================
DELETE MESSAGE
====================================================
*/

if (isset($_POST['Delete_msg'])) {

    $messageID =
        intval($_POST['message_id'] ?? 0);


    $sql = "
        DELETE FROM Messages
        WHERE message_id='$messageID'
    ";


    $result =
        mysqli_query(
            $Connection,
            $sql
        );


    if (
        $result &&
        mysqli_affected_rows($Connection) > 0
    ) {

        header(
            "Location: " .
            $_SERVER['PHP_SELF']
        );

        exit();

    }

}


/* ====================================================
   MODERN ADMIN DASHBOARD STATISTICS
   These are read-only dashboard queries. Existing
   Admin functionality above is preserved.
==================================================== */

function adminScalar($Connection, $sql, $field, $default = 0)
{
    $result = mysqli_query($Connection, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row && isset($row[$field])) {
            return $row[$field];
        }
    }
    return $default;
}

$dashboardTotalOrders = (int) adminScalar(
    $Connection,
    "SELECT COUNT(*) AS total FROM Orders",
    'total'
);

$dashboardTotalSales = (float) adminScalar(
    $Connection,
    "SELECT COALESCE(SUM(Order_total),0) AS total FROM Orders",
    'total'
);

$dashboardTotalCustomers = (int) adminScalar(
    $Connection,
    "SELECT COUNT(*) AS total FROM User_info WHERE user_type='Customer'",
    'total'
);

$dashboardActiveCustomers = (int) adminScalar(
    $Connection,
    "SELECT COUNT(*) AS total FROM User_info WHERE user_type='Customer' AND acc_status='Active'",
    'total'
);

$dashboardLowStock = (int) adminScalar(
    $Connection,
    "SELECT COUNT(*) AS total FROM Products WHERE stock_quantity BETWEEN 1 AND 10",
    'total'
);

$dashboardOutOfStock = (int) adminScalar(
    $Connection,
    "SELECT COUNT(*) AS total FROM Products WHERE stock_quantity <= 0",
    'total'
);

$dashboardTotalProducts = (int) adminScalar(
    $Connection,
    "SELECT COUNT(*) AS total FROM Products",
    'total'
);

$dashboardPendingOrders = (int) adminScalar(
    $Connection,
    "SELECT COUNT(*) AS total FROM Orders WHERE order_status='Pending'",
    'total'
);

$dashboardPendingPrescriptions = (int) adminScalar(
    $Connection,
    "SELECT COUNT(*) AS total FROM prescriptions WHERE status='Pending'",
    'total'
);

$dashboardUnreadMessages = (int) adminScalar(
    $Connection,
    "SELECT COUNT(*) AS total FROM Messages WHERE response_text IS NULL",
    'total'
);

$dashboardPaidAmount = (float) adminScalar(
    $Connection,
    "SELECT COALESCE(SUM(amount),0) AS total FROM Payment",
    'total'
);

$dashboardProfit = (float) adminScalar(
    $Connection,
    "SELECT COALESCE(SUM((p.price - p.cost_price) * o.qty),0) AS total
     FROM Orders o
     INNER JOIN Products p ON p.product_id=o.product_id",
    'total'
);

$dashboardExpirySoon = (int) adminScalar(
    $Connection,
    "SELECT COUNT(*) AS total
     FROM Products
     WHERE expire_date IS NOT NULL
       AND expire_date >= CURDATE()
       AND expire_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)",
    'total'
);

$dashboardTodaySales = (float) adminScalar(
    $Connection,
    "SELECT COALESCE(SUM(Order_total),0) AS total
     FROM Orders
     WHERE DATE(order_date)=CURDATE()",
    'total'
);

$dashboardTodayOrders = (int) adminScalar(
    $Connection,
    "SELECT COUNT(*) AS total
     FROM Orders
     WHERE DATE(order_date)=CURDATE()",
    'total'
);

/* Top-selling medicines */
$topMedicines = [];
$topMedicineResult = mysqli_query(
    $Connection,
    "SELECT p.product_name,
            COALESCE(SUM(o.qty),0) AS units_sold,
            COALESCE(SUM(o.Order_total),0) AS sales_amount
     FROM Orders o
     INNER JOIN Products p ON p.product_id=o.product_id
     GROUP BY o.product_id, p.product_name
     ORDER BY units_sold DESC, sales_amount DESC
     LIMIT 5"
);

if ($topMedicineResult) {
    while ($topRow = mysqli_fetch_assoc($topMedicineResult)) {
        $topMedicines[] = $topRow;
    }
}

/* Recent orders */
$recentOrders = [];
$recentOrderResult = mysqli_query(
    $Connection,
    "SELECT o.order_id,
            o.user_name,
            o.Order_total,
            o.order_status,
            o.order_date,
            o.order_type
     FROM Orders o
     ORDER BY o.order_id DESC
     LIMIT 6"
);

if ($recentOrderResult) {
    while ($recentRow = mysqli_fetch_assoc($recentOrderResult)) {
        $recentOrders[] = $recentRow;
    }
}

/* Recent payments */
$recentPayments = [];
$recentPaymentResult = mysqli_query(
    $Connection,
    "SELECT payment_id, order_id, amount, payment_date
     FROM Payment
     ORDER BY payment_id DESC
     LIMIT 5"
);

if ($recentPaymentResult) {
    while ($paymentRow = mysqli_fetch_assoc($recentPaymentResult)) {
        $recentPayments[] = $paymentRow;
    }
}

/* Low-stock medicines */
$lowStockMedicines = [];
$lowStockResult = mysqli_query(
    $Connection,
    "SELECT product_name, stock_quantity
     FROM Products
     WHERE stock_quantity <= 10
     ORDER BY stock_quantity ASC, product_name ASC
     LIMIT 5"
);

if ($lowStockResult) {
    while ($stockRow = mysqli_fetch_assoc($lowStockResult)) {
        $lowStockMedicines[] = $stockRow;
    }
}

/* Expiring-soon medicines for admin notifications */
$expiringMedicines = [];
$expiringResult = mysqli_query(
    $Connection,
    "SELECT product_id, product_name, expire_date
     FROM Products
     WHERE expire_date IS NOT NULL
       AND expire_date >= CURDATE()
       AND expire_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY expire_date ASC, product_name ASC
     LIMIT 5"
);

if ($expiringResult) {
    while ($expiryRow = mysqli_fetch_assoc($expiringResult)) {
        $expiringMedicines[] = $expiryRow;
    }
}

/* Total notification count shown on the bell */
$dashboardNotificationCount =
    $dashboardPendingOrders +
    $dashboardLowStock +
    $dashboardExpirySoon;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PharmacyX Admin Dashboard</title>
<link rel="icon" type="image/png" href="./Images/Pharmacy X Icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#f4f7fb;--card:#fff;--text:#172033;--muted:#7b8798;--border:#e5ebf3;--primary:#1683f5;--primary-soft:#e9f4ff;--green:#16a36b;--green-soft:#e8f8f1;--orange:#e99a17;--orange-soft:#fff4df;--red:#e74d4d;--red-soft:#ffebeb;--purple:#7957d5;--shadow:0 8px 24px rgba(31,56,88,.07)}
*{box-sizing:border-box}body{margin:0;font-family:Inter,Arial,sans-serif;background:var(--bg);color:var(--text);transition:.2s}a{text-decoration:none;color:inherit}
body.dark{--bg:#0e1624;--card:#172131;--text:#f2f6fb;--muted:#9aa8ba;--border:#2a3749;--primary-soft:#173b60;--green-soft:#143b2d;--orange-soft:#493512;--red-soft:#4b2528;--shadow:0 8px 24px rgba(0,0,0,.25)}
.layout{display:flex;min-height:100vh}.sidebar{width:235px;flex:0 0 235px;background:#101a2a;color:#d9e2ee;padding:20px 14px;display:flex;flex-direction:column;position:sticky;top:0;height:100vh}.brand{display:flex;align-items:center;gap:10px;padding:0 10px 20px;border-bottom:1px solid rgba(255,255,255,.09)}.brand img{width:38px;height:38px;object-fit:contain}.brand strong{display:block;font-size:16px}.brand small{font-size:9px;color:#8fa0b7}.nav-title{font-size:9px;text-transform:uppercase;letter-spacing:1.3px;color:#71829a;font-weight:800;margin:22px 10px 8px}.nav a{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:9px;color:#bdc9d8;font-size:11px;font-weight:600;margin:3px 0}.nav a:hover,.nav a.active{background:#1e2d44;color:#fff}.nav-icon{width:18px;text-align:center}.side-bottom{margin-top:auto;border-top:1px solid rgba(255,255,255,.08);padding:18px 10px 4px}.side-bottom strong{display:block;font-size:11px}.side-bottom span{font-size:9px;color:#8190a6}
.main{flex:1;min-width:0}.topbar{height:72px;background:var(--card);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 28px;gap:16px;position:sticky;top:0;z-index:10}.topbar .search{max-width:390px;flex:1;position:relative}.search input{width:100%;height:38px;border:1px solid var(--border);background:var(--bg);color:var(--text);border-radius:9px;padding:0 38px 0 14px;outline:none;font-size:11px}.search span{position:absolute;right:13px;top:10px;color:var(--muted)}.actions{margin-left:auto;display:flex;align-items:center;gap:12px}.notification-wrap{position:relative}.bell{width:38px;height:38px;border:1px solid var(--border);background:var(--card);color:var(--text);border-radius:9px;cursor:pointer;font-size:17px;position:relative;display:grid;place-items:center}.bell:hover,.theme:hover{background:var(--primary-soft)}.bell-badge{position:absolute;top:-5px;right:-5px;min-width:17px;height:17px;padding:0 4px;border-radius:20px;background:var(--red);color:#fff;font-size:8px;font-weight:800;display:grid;place-items:center;border:2px solid var(--card)}.notification-panel{display:none;position:absolute;right:0;top:48px;width:330px;background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:0 15px 35px rgba(31,56,88,.16);z-index:100;padding:0;overflow:hidden}.notification-panel.open{display:block}.notification-head{display:flex;align-items:center;justify-content:space-between;padding:13px 15px;border-bottom:1px solid var(--border)}.notification-head strong{font-size:12px}.notification-head span{font-size:8px;color:var(--muted)}.notification-item{display:flex;align-items:flex-start;gap:10px;padding:12px 15px;border-bottom:1px solid var(--border)}.notification-item:last-child{border-bottom:0}.notification-icon{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;flex:0 0 30px;font-size:13px}.notification-icon.order{background:var(--orange-soft);color:var(--orange)}.notification-icon.stock{background:var(--red-soft);color:var(--red)}.notification-icon.expiry{background:var(--orange-soft);color:var(--orange)}.notification-text{flex:1}.notification-text strong{font-size:10px;display:block;margin-bottom:3px}.notification-text small{font-size:8px;color:var(--muted);line-height:1.4}.notification-link{font-size:8px;color:var(--primary);font-weight:800;white-space:nowrap;margin-top:3px}.notification-empty{padding:20px 15px;text-align:center;color:var(--muted);font-size:9px}.theme{width:38px;height:38px;border:1px solid var(--border);background:var(--card);color:var(--text);border-radius:9px;cursor:pointer;font-size:17px}.admin{display:flex;align-items:center;gap:9px}.admin img{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--border)}.admin strong{font-size:11px;display:block}.admin small{font-size:9px;color:var(--muted)}
.content{padding:26px 28px 35px}.heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}.heading h1{font-size:23px;margin:0 0 5px;letter-spacing:-.5px}.heading p{font-size:11px;color:var(--muted);margin:0}.date{padding:9px 12px;border:1px solid var(--border);background:var(--card);border-radius:8px;font-size:10px;color:var(--muted)}
.grid-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px}.card{background:var(--card);border:1px solid var(--border);border-radius:13px;box-shadow:var(--shadow)}.stat{padding:17px;position:relative;overflow:hidden}.stat-top{display:flex;align-items:center;gap:10px}.icon{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;font-size:17px}.blue .icon{background:var(--primary-soft);color:var(--primary)}.green .icon{background:var(--green-soft);color:var(--green)}.orange .icon{background:var(--orange-soft);color:var(--orange)}.red .icon{background:var(--red-soft);color:var(--red)}.label{font-size:10px;color:var(--muted);font-weight:600}.value{font-size:21px;font-weight:800;margin-top:8px}.sub{font-size:9px;color:var(--muted);margin-top:5px}.stat:after{content:"";position:absolute;width:80px;height:80px;border-radius:50%;right:-25px;bottom:-32px;background:var(--primary-soft)}
.grid-main{display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:14px;margin-bottom:14px}.panel{padding:17px;min-height:250px}.panel-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:15px}.panel-head h3{font-size:12px;margin:0}.panel-head a{font-size:9px;color:var(--primary);font-weight:700}.filter{border:1px solid var(--border);background:var(--card);color:var(--text);border-radius:7px;padding:6px 8px;font-size:9px}
.chart{height:180px;display:flex;align-items:flex-end;gap:8px;padding:12px 4px 25px;border-bottom:1px solid var(--border);background:linear-gradient(180deg,rgba(22,131,245,.04),transparent);border-radius:10px 10px 0 0}.bar{flex:1;background:#c7def8;border-radius:5px 5px 0 0;min-width:8px;position:relative}.bar:nth-child(2n){background:#9ec9f8}.bar span{position:absolute;bottom:-20px;left:50%;transform:translateX(-50%);font-size:8px;color:var(--muted)}.chart-total{margin-top:10px;color:var(--muted);font-size:9px}.chart-total strong{display:block;font-size:17px;color:var(--text)}
.list{display:flex;flex-direction:column;gap:2px}.item{display:grid;grid-template-columns:1fr auto;gap:8px;padding:9px 2px;border-bottom:1px solid var(--border)}.item:last-child{border:0}.name{font-size:10px;font-weight:700}.small{font-size:8px;color:var(--muted);margin-top:2px}.price{font-size:10px;font-weight:800}.stock-wrap{display:grid;grid-template-columns:105px 1fr;align-items:center;gap:15px}.donut{width:100px;height:100px;border-radius:50%;background:conic-gradient(var(--green) 0 70%,var(--orange) 70% 85%,var(--red) 85% 94%,#8995a5 94% 100%);position:relative}.donut:after{content:"";position:absolute;inset:25px;background:var(--card);border-radius:50%}.donut-center{position:absolute;inset:0;z-index:1;display:grid;place-items:center;text-align:center;font-size:8px;color:var(--muted)}.donut-center strong{font-size:17px;color:var(--text);display:block}.legend{display:flex;flex-direction:column;gap:10px}.legend-row{display:flex;justify-content:space-between;font-size:9px}.dot{display:inline-block;width:7px;height:7px;border-radius:50%;margin-right:5px}.d-green{background:var(--green)}.d-orange{background:var(--orange)}.d-red{background:var(--red)}.d-gray{background:#8995a5}
.grid-bottom{display:grid;grid-template-columns:1fr 1.55fr;gap:14px}.alert{display:grid;grid-template-columns:25px 1fr auto;align-items:center;gap:7px;padding:9px 0;border-bottom:1px solid var(--border)}.alert:last-child{border:0}.alert-icon{color:var(--red)}.alert-name{font-size:9px;font-weight:700}.alert-sub{font-size:8px;color:var(--muted)}.alert-value{font-size:9px;font-weight:800;color:var(--red)}
.table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse}.table th{font-size:8px;color:var(--muted);text-align:left;padding:8px 5px;border-bottom:1px solid var(--border)}.table td{font-size:9px;padding:10px 5px;border-bottom:1px solid var(--border)}.status{display:inline-flex;padding:4px 7px;border-radius:20px;font-size:8px;font-weight:800;background:var(--primary-soft);color:var(--primary)}.status.pending{background:var(--orange-soft);color:var(--orange)}.status.delivered{background:var(--green-soft);color:var(--green)}.status.rejected{background:var(--red-soft);color:var(--red)}.view{color:var(--primary);font-weight:700;font-size:9px}
@media(max-width:1100px){.sidebar{width:200px;flex-basis:200px}.grid-stats{grid-template-columns:repeat(2,1fr)}.grid-main{grid-template-columns:1fr 1fr}.grid-main .panel:first-child{grid-column:1/-1}}
@media(max-width:760px){.sidebar{display:none}.content{padding:18px 14px}.topbar{padding:0 14px}.admin div{display:none}.grid-stats,.grid-main,.grid-bottom{grid-template-columns:1fr}.grid-main .panel:first-child{grid-column:auto}.date{display:none}.topbar .search{max-width:none}.notification-panel{position:fixed;top:68px;right:12px;left:12px;width:auto;max-width:none}}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
    <div class="brand"><img src="./Images/Pharmacy X Icon.png" alt="PharmacyX"><div><strong>PharmacyX</strong><small>Admin Management</small></div></div>
    <nav class="nav">
        <div class="nav-title">Main Menu</div>
        <a href="admin_DB.php" class="active"><span class="nav-icon">⌂</span>Dashboard</a>
        <a href="manage_orders.php"><span class="nav-icon">▣</span>Orders</a>
        <a href="manage_products.php"><span class="nav-icon">▦</span>Products</a>
        <a href="manage_products.php"><span class="nav-icon">▤</span>Inventory</a>
        <a href="manage_users.php"><span class="nav-icon">♙</span>Customers</a>
        <a href="manage_orders.php"><span class="nav-icon">₹</span>Payments</a>
        <div class="nav-title">Management</div>
        <a href="manage_users.php"><span class="nav-icon">♙</span>User Management</a>
    </nav>
    <div class="side-bottom"><strong>Pharmacy Management</strong><span>Smart. Secure. Efficient.</span></div>
</aside>
<main class="main">
<header class="topbar">
    <div class="search"><input id="dashboardSearch" type="search" placeholder="Search medicine, customer, order..."><span>⌕</span></div>
    <div class="actions">
        <div class="notification-wrap">
            <button class="bell" id="notificationBell" type="button" title="Notifications" aria-label="Notifications" aria-expanded="false">
                🔔
                <?php if ($dashboardNotificationCount > 0): ?>
                    <span class="bell-badge"><?php echo $dashboardNotificationCount > 99 ? '99+' : $dashboardNotificationCount; ?></span>
                <?php endif; ?>
            </button>
            <div class="notification-panel" id="notificationPanel">
                <div class="notification-head">
                    <strong>Notifications</strong>
                    <span><?php echo (int)$dashboardNotificationCount; ?> alert<?php echo $dashboardNotificationCount === 1 ? '' : 's'; ?></span>
                </div>

                <?php if ($dashboardPendingOrders > 0): ?>
                    <div class="notification-item">
                        <div class="notification-icon order">📦</div>
                        <div class="notification-text">
                            <strong><?php echo (int)$dashboardPendingOrders; ?> Pending Order<?php echo $dashboardPendingOrders === 1 ? '' : 's'; ?></strong>
                            <small>Orders are waiting for admin action.</small>
                        </div>
                        <a class="notification-link" href="manage_orders.php">View</a>
                    </div>
                <?php endif; ?>

                <?php if ($dashboardLowStock > 0): ?>
                    <div class="notification-item">
                        <div class="notification-icon stock">⚠</div>
                        <div class="notification-text">
                            <strong><?php echo (int)$dashboardLowStock; ?> Low Stock Item<?php echo $dashboardLowStock === 1 ? '' : 's'; ?></strong>
                            <small>Medicine stock is at or below 10 units.</small>
                        </div>
                        <a class="notification-link" href="manage_products.php">View</a>
                    </div>
                <?php endif; ?>

                <?php if ($dashboardExpirySoon > 0): ?>
                    <div class="notification-item">
                        <div class="notification-icon expiry">⏳</div>
                        <div class="notification-text">
                            <strong><?php echo (int)$dashboardExpirySoon; ?> Expiring Soon</strong>
                            <small>Medicine expires within the next 30 days.</small>
                        </div>
                        <a class="notification-link" href="manage_products.php">View</a>
                    </div>
                <?php endif; ?>

                <?php if ($dashboardNotificationCount === 0): ?>
                    <div class="notification-empty">No new notifications. Everything looks good.</div>
                <?php endif; ?>
            </div>
        </div>
        <button class="theme" id="themeToggle" type="button" title="Toggle light/dark mode">🌙</button>
        <div class="admin">
            <?php
            $adminPic = basename($_SESSION['profilePic_url'] ?? '');
            $adminName = $_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Admin';
            $adminImage = $adminPic !== '' ? './Images/Profile Pictures/' . htmlspecialchars($adminPic, ENT_QUOTES, 'UTF-8') : './Images/Pharmacy X Icon.png';
            ?>
            <img src="<?php echo $adminImage; ?>" alt="Admin">
            <div><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></strong><small>Administrator</small></div>
        </div>
    </div>
</header>
<section class="content">
    <div class="heading"><div><h1>Dashboard</h1><p>PharmacyX business overview and daily operations.</p></div><div class="date">▣ <?php echo date('d M Y'); ?></div></div>

    <section class="grid-stats">
        <div class="card stat blue"><div class="stat-top"><div class="icon">₹</div><div class="label">Total Sales</div></div><div class="value">₹<?php echo number_format($dashboardTotalSales,2); ?></div><div class="sub">Sales from all orders</div></div>
        <div class="card stat green"><div class="stat-top"><div class="icon">▣</div><div class="label">Total Orders</div></div><div class="value"><?php echo number_format($dashboardTotalOrders); ?></div><div class="sub">Today: <?php echo number_format($dashboardTodayOrders); ?></div></div>
        <div class="card stat orange"><div class="stat-top"><div class="icon">♙</div><div class="label">Total Customers</div></div><div class="value"><?php echo number_format($dashboardTotalCustomers); ?></div><div class="sub"><?php echo number_format($dashboardActiveCustomers); ?> active customers</div></div>
        <div class="card stat red"><div class="stat-top"><div class="icon">⚠</div><div class="label">Low Stock Items</div></div><div class="value"><?php echo number_format($dashboardLowStock); ?></div><div class="sub"><?php echo number_format($dashboardOutOfStock); ?> out of stock</div></div>
    </section>

    <section class="grid-main">
        <div class="card panel" id="sales-overview">
            <div class="panel-head"><h3>Sales Overview</h3><select class="filter"><option>This Month</option><option>Today</option><option>This Year</option></select></div>
            <div class="chart">
                <?php foreach ([35,48,58,50,68,76,61,54,72,66,84,94] as $i=>$height): ?><div class="bar" style="height:<?php echo $height; ?>%"><span><?php echo $i+1; ?></span></div><?php endforeach; ?>
            </div>
            <div class="chart-total"><strong>₹<?php echo number_format($dashboardTotalSales,2); ?></strong>Total sales across orders</div>
        </div>

        <div class="card panel" id="top-medicines">
            <div class="panel-head"><h3>Top Selling Medicines</h3><a href="manage_products.php">View Products</a></div>
            <div class="list">
            <?php if ($topMedicines): foreach ($topMedicines as $medicine): ?>
                <div class="item"><div><div class="name"><?php echo htmlspecialchars($medicine['product_name'], ENT_QUOTES, 'UTF-8'); ?></div><div class="small"><?php echo (int)$medicine['units_sold']; ?> units sold</div></div><div class="price">₹<?php echo number_format((float)$medicine['sales_amount'],2); ?></div></div>
            <?php endforeach; else: ?><div class="small">No sales data yet.</div><?php endif; ?>
            </div>
        </div>

        <div class="card panel" id="stock-summary">
            <div class="panel-head"><h3>Stock Summary</h3><a href="manage_products.php">Inventory</a></div>
            <div class="stock-wrap">
                <div class="donut"><div class="donut-center"><div><strong><?php echo number_format($dashboardTotalProducts); ?></strong>Items</div></div></div>
                <div class="legend">
                    <div class="legend-row"><span><i class="dot d-green"></i>In Stock</span><strong><?php echo max(0,$dashboardTotalProducts-$dashboardLowStock-$dashboardOutOfStock); ?></strong></div>
                    <div class="legend-row"><span><i class="dot d-orange"></i>Low Stock</span><strong><?php echo $dashboardLowStock; ?></strong></div>
                    <div class="legend-row"><span><i class="dot d-red"></i>Out of Stock</span><strong><?php echo $dashboardOutOfStock; ?></strong></div>
                    <div class="legend-row"><span><i class="dot d-gray"></i>Expiring Soon</span><strong><?php echo $dashboardExpirySoon; ?></strong></div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid-bottom">
        <div class="card panel" id="alerts">
            <div class="panel-head"><h3>Expiry & Stock Alerts</h3><a href="manage_products.php">Manage Inventory</a></div>
            <?php if ($lowStockMedicines): foreach ($lowStockMedicines as $stock): ?>
                <div class="alert"><div class="alert-icon">⚠</div><div><div class="alert-name"><?php echo htmlspecialchars($stock['product_name'], ENT_QUOTES, 'UTF-8'); ?></div><div class="alert-sub">Stock: <?php echo (int)$stock['stock_quantity']; ?></div></div><div class="alert-value"><?php echo (int)$stock['stock_quantity'] <= 0 ? 'Out' : 'Low'; ?></div></div>
            <?php endforeach; else: ?><div class="small">No low-stock medicines.</div><?php endif; ?>
        </div>

        <div class="card panel" id="recent-orders">
            <div class="panel-head"><h3>Recent Orders</h3><a href="manage_orders.php">View All Orders</a></div>
            <div class="table-wrap"><table class="table"><thead><tr><th>ORDER</th><th>CUSTOMER</th><th>AMOUNT</th><th>STATUS</th><th>DATE</th><th></th></tr></thead><tbody>
            <?php if ($recentOrders): foreach ($recentOrders as $order): $s=strtolower($order['order_status'] ?? ''); $cls=strpos($s,'pending')!==false?'pending':(strpos($s,'delivered')!==false?'delivered':(strpos($s,'reject')!==false?'rejected':'')); ?>
                <tr><td>#<?php echo (int)$order['order_id']; ?></td><td><?php echo htmlspecialchars($order['user_name'], ENT_QUOTES, 'UTF-8'); ?></td><td>₹<?php echo number_format((float)$order['Order_total'],2); ?></td><td><span class="status <?php echo $cls; ?>"><?php echo htmlspecialchars($order['order_status'], ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php echo htmlspecialchars(date('d M', strtotime($order['order_date'])), ENT_QUOTES, 'UTF-8'); ?></td><td><a class="view" href="manage_orders.php">View</a></td></tr>
            <?php endforeach; else: ?><tr><td colspan="6">No orders found.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div>
    </section>
</section>
</main>
</div>
<script>
(function(){
 const body=document.body, btn=document.getElementById('themeToggle');
 const saved=localStorage.getItem('pharmacyx_admin_theme');
 if(saved==='dark') body.classList.add('dark');
 function icon(){btn.textContent=body.classList.contains('dark')?'☀️':'🌙';}
 icon();
 btn.addEventListener('click',function(){body.classList.toggle('dark');localStorage.setItem('pharmacyx_admin_theme',body.classList.contains('dark')?'dark':'light');icon();});
 const bell=document.getElementById('notificationBell');
 const panel=document.getElementById('notificationPanel');
 if(bell && panel){
   bell.addEventListener('click',function(event){
     event.stopPropagation();
     const isOpen=panel.classList.toggle('open');
     bell.setAttribute('aria-expanded',isOpen?'true':'false');
   });
   panel.addEventListener('click',function(event){event.stopPropagation();});
   document.addEventListener('click',function(){
     panel.classList.remove('open');
     bell.setAttribute('aria-expanded','false');
   });
   document.addEventListener('keydown',function(event){
     if(event.key==='Escape'){
       panel.classList.remove('open');
       bell.setAttribute('aria-expanded','false');
     }
   });
 }
 const search=document.getElementById('dashboardSearch');
 search.addEventListener('input',function(){const q=this.value.trim().toLowerCase();document.querySelectorAll('.item,.alert,.table tbody tr').forEach(el=>{el.style.display=!q||el.innerText.toLowerCase().includes(q)?'':'none';});});
})();
</script>
</body>
</html>
