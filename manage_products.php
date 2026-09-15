<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ====================================================
   PHARMACYX - MANAGE MEDICINES
   ADMIN + MANAGER
   Redesigned UI - all existing features preserved
   ==================================================== */

require_once "./db_Config/config.php";

/* ====================================================
   CHECK ADMIN SESSION
   ==================================================== */
$adminLoggedIn = false;
$adminUsername = "";

session_name("PHARMACYX_ADMIN");
session_start();

if (
    isset($_SESSION['username']) &&
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === "Admin"
) {
    $adminLoggedIn = true;
    $adminUsername = $_SESSION['username'];
}

session_write_close();

/* ====================================================
   CHECK MANAGER SESSION
   ==================================================== */
$managerLoggedIn = false;
$managerUsername = "";

session_name("PHARMACYX_MANAGER");
session_start();

if (
    isset($_SESSION['username']) &&
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === "Manager"
) {
    $managerLoggedIn = true;
    $managerUsername = $_SESSION['username'];
}

session_write_close();

/* ====================================================
   ALLOW ADMIN OR MANAGER
   ==================================================== */
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

/* ====================================================
   ADD MEDICINE - HANDLE FORM IN THIS PAGE
   ==================================================== */
$addError = "";
$addSuccess = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medicine'])) {
    $productName = trim($_POST['product_name'] ?? '');
    $productDescription = trim($_POST['product_description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $costPrice = (float)($_POST['cost_price'] ?? 0);
    $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
    $expireDate = trim($_POST['expire_date'] ?? '');
    $prescriptionRequired = ($_POST['prescription_required'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
    $imageUrl = '';

    if ($productName === '') {
        $addError = 'Medicine name is required.';
    } elseif ($price < 0 || $costPrice < 0) {
        $addError = 'Price and cost price cannot be negative.';
    } elseif ($stockQuantity < 0) {
        $addError = 'Stock quantity cannot be negative.';
    } elseif ($expireDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expireDate)) {
        $addError = 'Please enter a valid expiry date.';
    }

    /* Optional medicine image upload */
    if ($addError === '' && isset($_FILES['medicine_image']) && $_FILES['medicine_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['medicine_image']['error'] !== UPLOAD_ERR_OK) {
            $addError = 'Medicine image upload failed.';
        } elseif ($_FILES['medicine_image']['size'] > 5 * 1024 * 1024) {
            $addError = 'Medicine image must be 5 MB or smaller.';
        } else {
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif'
            ];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['medicine_image']['tmp_name']);
            if (!isset($allowed[$mime])) {
                $addError = 'Only JPG, PNG, WEBP and GIF images are allowed.';
            } else {
                $uploadDir = __DIR__ . '/Images/product-icons/';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
                    $addError = 'Unable to create the product image folder.';
                } else {
                    $fileName = 'medicine_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                    if (move_uploaded_file($_FILES['medicine_image']['tmp_name'], $uploadDir . $fileName)) {
                        $imageUrl = 'Images/product-icons/' . $fileName;
                    } else {
                        $addError = 'Unable to save the medicine image.';
                    }
                }
            }
        }
    }

    if ($addError === '') {
        $nameEsc = mysqli_real_escape_string($Connection, $productName);
        $descEsc = mysqli_real_escape_string($Connection, $productDescription);
        $imageEsc = mysqli_real_escape_string($Connection, $imageUrl);
        $dateSql = $expireDate !== '' ? "'" . mysqli_real_escape_string($Connection, $expireDate) . "'" : "NULL";

        $insertQuery = "INSERT INTO products
            (product_name, product_description, price, cost_price, stock_quantity, image_url, expire_date, prescription_required)
            VALUES
            ('$nameEsc', '$descEsc', $price, $costPrice, $stockQuantity, '$imageEsc', $dateSql, '$prescriptionRequired')";

        if (mysqli_query($Connection, $insertQuery)) {
            header('Location: manage_products.php?added=1');
            exit();
        } else {
            /* Remove uploaded image if DB insert failed */
            if ($imageUrl !== '' && is_file(__DIR__ . '/' . $imageUrl)) {
                @unlink(__DIR__ . '/' . $imageUrl);
            }
            $addError = 'Add Medicine Error: ' . mysqli_error($Connection);
        }
    }
}

if (isset($_GET['added']) && $_GET['added'] === '1') {
    $addSuccess = 'Medicine added successfully.';
}

/* ====================================================
   DELETE PRODUCT - EXISTING FUNCTIONALITY
   ==================================================== */
if (isset($_GET['delete'])) {
    $productId = intval($_GET['delete']);

    if ($productId > 0) {
        $deleteQuery = "DELETE FROM products WHERE product_id = $productId";

        if (mysqli_query($Connection, $deleteQuery)) {
            header("Location: manage_products.php");
            exit();
        } else {
            die("Delete Error: " . mysqli_error($Connection));
        }
    }
}

/* ====================================================
   SEARCH - EXISTING FUNCTIONALITY
   ==================================================== */
$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

$searchSafe = mysqli_real_escape_string($Connection, $search);

/* ====================================================
   GET PRODUCTS
   ==================================================== */
if ($search !== "") {
    $query = "
        SELECT
            product_id,
            product_name,
            product_description,
            price,
            stock_quantity,
            image_url,
            expire_date
        FROM products
        WHERE
            product_name LIKE '%$searchSafe%'
            OR product_description LIKE '%$searchSafe%'
        ORDER BY product_id DESC
    ";
} else {
    $query = "
        SELECT
            product_id,
            product_name,
            product_description,
            price,
            stock_quantity,
            image_url,
            expire_date
        FROM products
        ORDER BY product_id DESC
    ";
}

$result = mysqli_query($Connection, $query);

if (!$result) {
    die("Product Query Error: " . mysqli_error($Connection));
}

/* ====================================================
   DASHBOARD COUNTS
   ==================================================== */
$totalProducts = 0;
$lowStockProducts = 0;
$outOfStockProducts = 0;
$expiredProducts = 0;
$totalInventoryValue = 0;

$statsResult = mysqli_query(
    $Connection,
    "SELECT
        COUNT(*) AS total_products,
        COALESCE(SUM(CASE WHEN stock_quantity BETWEEN 1 AND 10 THEN 1 ELSE 0 END),0) AS low_stock,
        COALESCE(SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END),0) AS out_stock,
        COALESCE(SUM(CASE WHEN expire_date IS NOT NULL AND expire_date < CURDATE() THEN 1 ELSE 0 END),0) AS expired,
        COALESCE(SUM(price * stock_quantity),0) AS inventory_value
     FROM products"
);

if ($statsResult) {
    $stats = mysqli_fetch_assoc($statsResult);
    $totalProducts = (int)($stats['total_products'] ?? 0);
    $lowStockProducts = (int)($stats['low_stock'] ?? 0);
    $outOfStockProducts = (int)($stats['out_stock'] ?? 0);
    $expiredProducts = (int)($stats['expired'] ?? 0);
    $totalInventoryValue = (float)($stats['inventory_value'] ?? 0);
}

$displayCount = mysqli_num_rows($result);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function imagePath($image) {
    $image = trim((string)$image);

    if ($image === '') {
        return "./Images/product-icons/Pharmacy-Isometric-Icons-1.png";
    }

    if (
        strpos($image, 'Images/') === 0 ||
        strpos($image, './Images/') === 0 ||
        strpos($image, '../Images/') === 0 ||
        strpos($image, 'http://') === 0 ||
        strpos($image, 'https://') === 0
    ) {
        return $image;
    }

    return "./Images/product-icons/" . basename($image);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Medicines | PharmacyX</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root {
    --bg: #f4f7fb;
    --surface: #ffffff;
    --surface-2: #f8fafc;
    --text: #172033;
    --muted: #6b7280;
    --border: #e5eaf1;
    --primary: #0b84ff;
    --primary-dark: #0666ca;
    --success: #16a34a;
    --warning: #f59e0b;
    --danger: #ef4444;
    --shadow: 0 10px 30px rgba(15, 23, 42, .07);
    --sidebar: #101827;
    --sidebar-text: #cbd5e1;
    --sidebar-active: #1e293b;
}

body.dark {
    --bg: #0b1220;
    --surface: #111a2b;
    --surface-2: #172235;
    --text: #f1f5f9;
    --muted: #94a3b8;
    --border: #263449;
    --shadow: 0 12px 32px rgba(0,0,0,.25);
    --sidebar: #070d17;
    --sidebar-text: #aebbd0;
    --sidebar-active: #182437;
}

* { box-sizing: border-box; }

html { scroll-behavior: smooth; }

body {
    margin: 0;
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
    transition: background .25s ease, color .25s ease;
}

a { text-decoration: none; }

.app {
    min-height: 100vh;
    display: flex;
}

/* ================= MAIN ================= */
.main {
    width: 100%;
    margin-left: 0;
    min-height: 100vh;
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
    z-index: 900;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    font-size: 13px;
}

.breadcrumb strong { color: var(--text); }

.top-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(11,132,255,.09);
    color: var(--primary);
    border: 1px solid rgba(11,132,255,.14);
    border-radius: 999px;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 800;
}

.theme-toggle {
    width: 42px;
    height: 42px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text);
    border-radius: 11px;
    cursor: pointer;
    font-size: 15px;
}

.content {
    padding: 30px 34px 45px;
    max-width: 1550px;
    margin: auto;
}

.page-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 25px;
}

.page-heading h1 {
    margin: 0;
    font-size: 30px;
    letter-spacing: -.6px;
}

.page-heading p {
    margin: 7px 0 0;
    color: var(--muted);
    font-size: 13px;
}

.add-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #0b84ff, #0874df);
    color: white;
    padding: 12px 17px;
    border-radius: 11px;
    font-size: 13px;
    font-weight: 800;
    box-shadow: 0 8px 20px rgba(11,132,255,.2);
}

.add-btn:hover { transform: translateY(-1px); }

/* ================= STATS ================= */
.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}

.stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 19px;
    box-shadow: var(--shadow);
}

.stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    background: rgba(11,132,255,.1);
    color: var(--primary);
}

.stat-label { color: var(--muted); font-size: 12px; font-weight: 700; }
.stat-value { font-size: 25px; font-weight: 850; margin-top: 13px; }
.stat-sub { color: var(--muted); font-size: 11px; margin-top: 5px; }
.stat-sub.warning { color: var(--warning); }
.stat-sub.danger { color: var(--danger); }
.stat-sub.success { color: var(--success); }

/* ================= SEARCH ================= */
.toolbar {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 14px;
    display: flex;
    gap: 12px;
    align-items: center;
    box-shadow: var(--shadow);
    margin-bottom: 18px;
}

.search-form {
    flex: 1;
    display: flex;
    gap: 10px;
}

.search-input-wrap {
    flex: 1;
    position: relative;
}

.search-input-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.search-input {
    width: 100%;
    height: 44px;
    border: 1px solid var(--border);
    background: var(--surface-2);
    color: var(--text);
    border-radius: 10px;
    padding: 0 14px 0 39px;
    outline: none;
    font-size: 13px;
}

.search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(11,132,255,.1); }

.search-btn,
.clear-btn {
    height: 44px;
    border-radius: 10px;
    padding: 0 17px;
    border: 0;
    font-weight: 800;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 12px;
}

.search-btn { background: var(--primary); color: white; }
.search-btn:hover { background: var(--primary-dark); }
.clear-btn { background: var(--surface-2); border: 1px solid var(--border); color: var(--muted); }

/* ================= TABLE ================= */
.table-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: var(--shadow);
    overflow: hidden;
}

.table-header {
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid var(--border);
}

.table-header h2 { margin: 0; font-size: 16px; }
.table-header span { color: var(--muted); font-size: 12px; }

.table-scroll { overflow-x: auto; }

table {
    width: 100%;
    min-width: 1080px;
    border-collapse: collapse;
}

th {
    text-align: left;
    padding: 13px 18px;
    background: var(--surface-2);
    color: var(--muted);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .8px;
    border-bottom: 1px solid var(--border);
}

td {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
    font-size: 12px;
}

tbody tr { transition: background .18s ease; }
tbody tr:hover { background: rgba(11,132,255,.035); }
tbody tr:last-child td { border-bottom: 0; }

.medicine-cell { display: flex; align-items: center; gap: 12px; min-width: 210px; }

.product-image {
    width: 54px;
    height: 54px;
    object-fit: contain;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: white;
    padding: 4px;
    flex: 0 0 54px;
}

.product-name { font-weight: 800; font-size: 13px; color: var(--text); }
.product-id { color: var(--muted); font-size: 10px; margin-top: 4px; }

.description {
    max-width: 270px;
    color: var(--muted);
    line-height: 1.45;
}

.price { font-weight: 850; color: var(--success); white-space: nowrap; }

.stock-pill,
.expiry-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 6px 9px;
    font-weight: 800;
    font-size: 10px;
    white-space: nowrap;
}

.stock-good { color: var(--success); background: rgba(22,163,74,.09); }
.stock-low { color: var(--warning); background: rgba(245,158,11,.11); }
.stock-out { color: var(--danger); background: rgba(239,68,68,.10); }
.expired { color: var(--danger); background: rgba(239,68,68,.10); }
.valid { color: var(--success); background: rgba(22,163,74,.09); }

.actions { display: flex; gap: 7px; flex-wrap: wrap; }

.action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 10px;
    border-radius: 9px;
    font-size: 11px;
    font-weight: 800;
    transition: .2s ease;
}

.action:hover { transform: translateY(-1px); }
.edit { background: rgba(11,132,255,.1); color: var(--primary); }
.delete { background: rgba(239,68,68,.1); color: var(--danger); }

.empty-state { text-align: center; padding: 65px 20px; color: var(--muted); }
.empty-state i { font-size: 42px; opacity: .45; margin-bottom: 15px; }
.empty-state strong { display: block; color: var(--text); font-size: 15px; margin-bottom: 5px; }

/* ================= ADD MEDICINE MODAL ================= */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, .58);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 2000;
    backdrop-filter: blur(4px);
}
.modal-backdrop.show { display: flex; }
.add-modal {
    width: min(760px, 100%);
    max-height: 92vh;
    overflow-y: auto;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    box-shadow: 0 25px 70px rgba(0,0,0,.22);
}
.modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding: 20px 22px;
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    background: var(--surface);
    z-index: 2;
}
.modal-head h2 { margin: 0; font-size: 19px; }
.modal-head p { margin: 5px 0 0; color: var(--muted); font-size: 12px; }
.modal-close {
    width: 38px; height: 38px; border: 1px solid var(--border);
    background: var(--surface-2); color: var(--text); border-radius: 10px;
    cursor: pointer; font-size: 16px;
}
.add-form { padding: 22px; }
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 7px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: 12px; font-weight: 800; color: var(--text); }
.form-group input, .form-group textarea, .form-group select {
    width: 100%; border: 1px solid var(--border); background: var(--surface-2);
    color: var(--text); border-radius: 10px; padding: 11px 12px; outline: none;
    font: inherit; font-size: 13px;
}
.form-group textarea { min-height: 95px; resize: vertical; }
.form-group input:focus, .form-group textarea:focus, .form-group select:focus {
    border-color: var(--primary); box-shadow: 0 0 0 3px rgba(11,132,255,.1);
}
.form-help { color: var(--muted); font-size: 10px; }
.alert-box { margin: 0 0 18px; padding: 11px 13px; border-radius: 10px; font-size: 12px; font-weight: 700; }
.alert-error { background: rgba(239,68,68,.1); color: var(--danger); border: 1px solid rgba(239,68,68,.18); }
.alert-success { background: rgba(22,163,74,.1); color: var(--success); border: 1px solid rgba(22,163,74,.18); }
.form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--border); }
.cancel-btn, .save-btn { border: 0; border-radius: 10px; padding: 11px 16px; cursor: pointer; font-weight: 800; font-size: 12px; }
.cancel-btn { background: var(--surface-2); color: var(--muted); border: 1px solid var(--border); }
.save-btn { background: var(--primary); color: #fff; }
.save-btn:hover { background: var(--primary-dark); }
@media (max-width: 620px) { .form-grid { grid-template-columns: 1fr; } .form-group.full { grid-column: auto; } }

/* ================= RESPONSIVE ================= */
.mobile-menu { display: none; }

@media (max-width: 1050px) {
    .stats { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 800px) {
    .sidebar { width: 72px; padding: 18px 9px; }
    .brand { justify-content: center; padding: 4px 0 20px; }
    .brand > div:last-child,
    .side-title,
    .nav-link span,
    .user-mini > div:last-child { display: none; }
    .nav-link { justify-content: center; padding: 12px 8px; }
    .nav-link i { font-size: 16px; }
    .main { width: calc(100% - 72px); margin-left: 72px; }
    .topbar { padding: 0 18px; }
    .content { padding: 22px 18px 35px; }
    .page-heading { align-items: flex-start; flex-direction: column; }
    .toolbar { flex-direction: column; align-items: stretch; }
    .search-form { width: 100%; }
}

@media (max-width: 560px) {
    .stats { grid-template-columns: 1fr; }
    .page-heading h1 { font-size: 24px; }
    .role-badge { display: none; }
    .search-form { flex-direction: column; }
    .search-btn, .clear-btn { width: 100%; }
}
</style>
</head>

<body>
<main class="main">

        <header class="topbar">
            <div class="breadcrumb">
                <i class="fa-solid fa-house"></i>
                <span>/</span>
                <strong>Manage Medicines</strong>
            </div>

            <div class="top-actions">
                <div class="role-badge">
                    <i class="fa-solid fa-user-shield"></i>
                    <?php echo h($currentRole); ?> · <?php echo h($currentUser); ?>
                </div>

                <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle light and dark mode">
                    <i class="fa-solid fa-moon"></i>
                </button>
            </div>
        </header>

        <section class="content">

            <!-- ================= PAGE HEADING ================= -->
            <div class="page-heading">
                <div>
                    <h1>Manage Medicines</h1>
                    <p>View, search, add, edit and remove medicines from your pharmacy inventory.</p>
                </div>

                <button type="button" class="add-btn" id="openAddMedicine">
                    <i class="fa-solid fa-plus"></i>
                    Add Medicine
                </button>
            </div>

            <!-- ================= STATS ================= -->
            <section class="stats">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-label">Total Medicines</div>
                        <div class="stat-icon"><i class="fa-solid fa-pills"></i></div>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalProducts); ?></div>
                    <div class="stat-sub">Products in inventory</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-label">Low Stock</div>
                        <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    </div>
                    <div class="stat-value"><?php echo number_format($lowStockProducts); ?></div>
                    <div class="stat-sub warning">10 or fewer units</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-label">Out of Stock</div>
                        <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
                    </div>
                    <div class="stat-value"><?php echo number_format($outOfStockProducts); ?></div>
                    <div class="stat-sub danger">Needs restocking</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-label">Inventory Value</div>
                        <div class="stat-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    </div>
                    <div class="stat-value">₹<?php echo number_format($totalInventoryValue, 2); ?></div>
                    <div class="stat-sub success"><?php echo number_format($expiredProducts); ?> expired item(s)</div>
                </div>
            </section>

            <!-- ================= SEARCH ================= -->
            <div class="toolbar">
                <form method="GET" class="search-form">
                    <div class="search-input-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            class="search-input"
                            type="text"
                            name="search"
                            placeholder="Search medicine name or description..."
                            value="<?php echo h($search); ?>"
                        >
                    </div>

                    <button type="submit" class="search-btn">
                        <i class="fa-solid fa-search"></i>
                        Search
                    </button>

                    <?php if ($search !== ""): ?>
                        <a href="manage_products.php" class="clear-btn">
                            <i class="fa-solid fa-xmark"></i>
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- ================= PRODUCT TABLE ================= -->
            <section class="table-card">
                <div class="table-header">
                    <h2>Medicine Inventory</h2>
                    <span>
                        <?php echo number_format($displayCount); ?> medicine<?php echo $displayCount === 1 ? '' : 's'; ?> shown
                    </span>
                </div>

                <div class="table-scroll">
                    <?php if ($displayCount === 0): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-pills"></i>
                            <strong>No medicines found</strong>
                            <span>Try another search term or add a new medicine.</span>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Expiry Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php while ($product = mysqli_fetch_assoc($result)): ?>
                                <?php
                                    $productId = (int)$product['product_id'];
                                    $productName = $product['product_name'];
                                    $description = $product['product_description'];
                                    $price = (float)$product['price'];
                                    $stock = (int)$product['stock_quantity'];
                                    $image = $product['image_url'];
                                    $expireDate = $product['expire_date'];
                                    $imageSrc = imagePath($image);

                                    if ($stock <= 0) {
                                        $stockClass = 'stock-out';
                                        $stockText = 'Out of Stock';
                                        $stockIcon = 'fa-circle-xmark';
                                    } elseif ($stock <= 10) {
                                        $stockClass = 'stock-low';
                                        $stockText = $stock . ' Low';
                                        $stockIcon = 'fa-triangle-exclamation';
                                    } else {
                                        $stockClass = 'stock-good';
                                        $stockText = $stock . ' In Stock';
                                        $stockIcon = 'fa-circle-check';
                                    }

                                    $today = date('Y-m-d');

                                    if (!empty($expireDate) && $expireDate < $today) {
                                        $expiryClass = 'expired';
                                        $expiryText = $expireDate . ' · EXPIRED';
                                        $expiryIcon = 'fa-circle-xmark';
                                    } else {
                                        $expiryClass = 'valid';
                                        $expiryText = !empty($expireDate) ? $expireDate : 'Not set';
                                        $expiryIcon = 'fa-calendar-check';
                                    }
                                ?>

                                <tr>
                                    <td>
                                        <div class="medicine-cell">
                                            <img
                                                src="<?php echo h($imageSrc); ?>"
                                                class="product-image"
                                                alt="<?php echo h($productName); ?>"
                                                onerror="this.src='./Images/product-icons/Pharmacy-Isometric-Icons-1.png';"
                                            >

                                            <div>
                                                <div class="product-name"><?php echo h($productName); ?></div>
                                                <div class="product-id">Medicine ID #<?php echo $productId; ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="description">
                                            <?php echo h($description ?? ''); ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="price">₹<?php echo number_format($price, 2); ?></div>
                                    </td>

                                    <td>
                                        <span class="stock-pill <?php echo $stockClass; ?>">
                                            <i class="fa-solid <?php echo $stockIcon; ?>"></i>
                                            <?php echo h($stockText); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="expiry-pill <?php echo $expiryClass; ?>">
                                            <i class="fa-solid <?php echo $expiryIcon; ?>"></i>
                                            <?php echo h($expiryText); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="actions">
                                            <a
                                                href="edit_product.php?id=<?php echo $productId; ?>"
                                                class="action edit"
                                                title="Edit medicine"
                                            >
                                                <i class="fa-solid fa-pen-to-square"></i>
                                                Edit
                                            </a>

                                            <a
                                                href="manage_products.php?delete=<?php echo $productId; ?>"
                                                class="action delete"
                                                title="Delete medicine"
                                                onclick="return confirm('Are you sure you want to delete this medicine?');"
                                            >
                                                <i class="fa-solid fa-trash"></i>
                                                Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>

        </section>
    </main>

<!-- ================= ADD MEDICINE MODAL ================= -->
<div class="modal-backdrop" id="addMedicineModal" aria-hidden="true">
    <div class="add-modal" role="dialog" aria-modal="true" aria-labelledby="addMedicineTitle">
        <div class="modal-head">
            <div>
                <h2 id="addMedicineTitle"><i class="fa-solid fa-pills"></i> Add New Medicine</h2>
                <p>Add the medicine directly to your PharmacyX inventory.</p>
            </div>
            <button type="button" class="modal-close" id="closeAddMedicine" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="add-form">
            <input type="hidden" name="add_medicine" value="1">

            <?php if ($addError !== ''): ?>
                <div class="alert-box alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo h($addError); ?></div>
            <?php endif; ?>
            <?php if ($addSuccess !== ''): ?>
                <div class="alert-box alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo h($addSuccess); ?></div>
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group full">
                    <label for="product_name">Medicine Name *</label>
                    <input id="product_name" name="product_name" type="text" maxlength="150" required placeholder="e.g. Paracetamol 500mg">
                </div>

                <div class="form-group full">
                    <label for="product_description">Description</label>
                    <textarea id="product_description" name="product_description" maxlength="1000" placeholder="Enter medicine description, usage information, etc."></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Selling Price (₹) *</label>
                    <input id="price" name="price" type="number" min="0" step="0.01" required placeholder="200.00">
                </div>

                <div class="form-group">
                    <label for="cost_price">Cost Price (₹)</label>
                    <input id="cost_price" name="cost_price" type="number" min="0" step="0.01" value="0" placeholder="150.00">
                </div>

                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity *</label>
                    <input id="stock_quantity" name="stock_quantity" type="number" min="0" step="1" required placeholder="100">
                </div>

                <div class="form-group">
                    <label for="expire_date">Expiry Date</label>
                    <input id="expire_date" name="expire_date" type="date">
                </div>

                <div class="form-group">
                    <label for="prescription_required">Prescription Required *</label>
                    <select id="prescription_required" name="prescription_required" required>
                        <option value="No">No - OTC Medicine</option>
                        <option value="Yes">Yes - Prescription Required</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="medicine_image">Medicine Image</label>
                    <input id="medicine_image" name="medicine_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <span class="form-help">Optional. JPG, PNG, WEBP or GIF, maximum 5 MB.</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="cancel-btn" id="cancelAddMedicine">Cancel</button>
                <button type="submit" class="save-btn"><i class="fa-solid fa-plus"></i> Add Medicine</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const body = document.body;
    const button = document.getElementById('themeToggle');
    const icon = button.querySelector('i');

    /* Add Medicine modal */
    const addModal = document.getElementById('addMedicineModal');
    const openAdd = document.getElementById('openAddMedicine');
    const closeAdd = document.getElementById('closeAddMedicine');
    const cancelAdd = document.getElementById('cancelAddMedicine');

    function showAddModal() {
        if (addModal) {
            addModal.classList.add('show');
            addModal.setAttribute('aria-hidden', 'false');
            const nameInput = document.getElementById('product_name');
            if (nameInput) nameInput.focus();
        }
    }

    function hideAddModal() {
        if (addModal) {
            addModal.classList.remove('show');
            addModal.setAttribute('aria-hidden', 'true');
        }
    }

    if (openAdd) openAdd.addEventListener('click', showAddModal);
    if (closeAdd) closeAdd.addEventListener('click', hideAddModal);
    if (cancelAdd) cancelAdd.addEventListener('click', hideAddModal);
    if (addModal) {
        addModal.addEventListener('click', function (event) {
            if (event.target === addModal) hideAddModal();
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') hideAddModal();
    });

    const savedTheme = localStorage.getItem('pharmacyx_admin_theme');

    if (savedTheme === 'dark') {
        body.classList.add('dark');
        icon.className = 'fa-solid fa-sun';
    }

    button.addEventListener('click', function () {
        body.classList.toggle('dark');

        const dark = body.classList.contains('dark');
        localStorage.setItem('pharmacyx_admin_theme', dark ? 'dark' : 'light');

        icon.className = dark
            ? 'fa-solid fa-sun'
            : 'fa-solid fa-moon';
    });

    <?php if ($addError !== ''): ?>
        showAddModal();
    <?php endif; ?>
})();
</script>

</body>
</html>
