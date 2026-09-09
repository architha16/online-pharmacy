<?php
/* =========================================================
   PHARMACYX - CUSTOMER PENDING PRESCRIPTION STATUS
   ========================================================= */

session_name("PHARMACYX_CUSTOMER");
session_start();

/* Customer login check */
if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Customer'
) {
    header("Location: signin.php?role=Customer");
    exit();
}

/* Database */
require_once "./db_Config/config.php";

$username = $_SESSION['username'];
$userEscaped = mysqli_real_escape_string($Connection, $username);

/* =========================================================
   PRESCRIPTION DISPLAY RULE

   Show only:
   1. Pending prescriptions

   Approved prescriptions move to My Orders and become
   available there for payment.

   Hide:
   - Approved prescriptions
   - Rejected prescriptions
   - Completed prescriptions

   The exact prescription_id is used so a new prescription for
   the same medicine is treated as a new prescription.
   ========================================================= */

/* =========================================================
   CONTINUE TO PAYMENT
   ========================================================= */
if (
    isset($_GET['continue_payment']) &&
    is_numeric($_GET['continue_payment'])
) {

    $prescriptionId = (int)$_GET['continue_payment'];

    $paymentCheckSql = "
        SELECT
            pr.id,
            pr.product_id,
            pr.status
        FROM prescriptions pr
        WHERE pr.id = '$prescriptionId'
          AND pr.user_name = '$userEscaped'
          AND pr.status = 'Approved'
          AND NOT EXISTS (
              SELECT 1
              FROM Orders o
              INNER JOIN Payment pay
                  ON pay.order_id = o.order_id
              WHERE o.prescription_id = pr.id
          )
        LIMIT 1
    ";

    $paymentCheck = mysqli_query(
        $Connection,
        $paymentCheckSql
    );

    if (
        !$paymentCheck ||
        mysqli_num_rows($paymentCheck) === 0
    ) {
        echo "<script>
            alert('This prescription is not available for payment. It may still be pending, rejected, or already paid.');
            window.location='prescription_status.php';
        </script>";
        exit();
    }

    $approvedPrescription = mysqli_fetch_assoc($paymentCheck);
    $approvedProductId = (int)$approvedPrescription['product_id'];

    /* Save the exact prescription ID for paymentpage.php. */
    $_SESSION['PHARMACYX_CURRENT_PRESCRIPTION_IDS'] = [
        $approvedProductId => $prescriptionId
    ];

    /* Continue the EXISTING approved prescription checkout.
       Do NOT send new_checkout=1 here, because paymentpage.php
       would clear the exact approved prescription ID from the session. */
    $_SESSION['PHARMACYX_PRESCRIPTION_CHECKOUT_START'] = date('Y-m-d H:i:s');

    header(
        "Location: paymentpage.php" .
        "?checkout_mode=buy_now" .
        "&product_id=" . $approvedProductId .
        "&quantity=1"
    );
    exit();
}

/* =========================================================
   GET CUSTOMER PRESCRIPTIONS
   =========================================================

   Pending -> shown here
   Approved -> moved to My Orders for payment
   Rejected -> hidden
   ========================================================= */

$sql = "
    SELECT
        pr.id,
        pr.product_id,
        pr.patient_name,
        pr.mobile,
        pr.prescription_file,
        pr.status,
        pr.upload_date,
        p.product_name
    FROM prescriptions pr
    LEFT JOIN Products p
        ON pr.product_id = p.product_id
    WHERE pr.user_name = '$userEscaped'
      AND pr.status = 'Pending'
    ORDER BY pr.id DESC
";

$result = mysqli_query($Connection, $sql);

if (!$result) {
    die(
        "Prescription Status Error: " .
        htmlspecialchars(
            mysqli_error($Connection),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

include("./header.php");
?>

<style>
    .prescription-status-page {
        max-width: 1000px;
        margin: 35px auto 60px;
        padding: 0 20px;
        font-family: Arial, sans-serif;
    }

    .prescription-status-title {
        text-align: center;
        background: #ffffff;
        border-radius: 12px;
        padding: 22px;
        margin-bottom: 25px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }

    .prescription-status-title h1 {
        margin: 0 0 8px;
        color: #0878d1;
        font-size: 28px;
    }

    .prescription-status-title p {
        margin: 0;
        color: #666;
    }

    .prescription-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 22px;
        margin-bottom: 22px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.10);
    }

    .prescription-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eeeeee;
        padding-bottom: 14px;
        margin-bottom: 15px;
    }

    .prescription-card-top h2 {
        margin: 0;
        font-size: 20px;
        color: #222;
    }

    .status-pending,
    .status-approved {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 14px;
    }

    .status-pending {
        background: #fff2c9;
        color: #8a6800;
    }

    .status-approved {
        background: #d8f3df;
        color: #176b35;
    }

    .detail-row {
        display: flex;
        margin: 9px 0;
        line-height: 1.5;
    }

    .detail-label {
        width: 150px;
        font-weight: bold;
        color: #444;
    }

    .detail-value {
        flex: 1;
        color: #333;
    }

    .pending-box,
    .approved-box {
        margin-top: 18px;
        padding: 18px;
        border-radius: 7px;
        line-height: 1.6;
    }

    .pending-box {
        background: #fff8df;
        border-left: 5px solid #f0b400;
        color: #765b00;
    }

    .approved-box {
        background: #eaf8ee;
        border-left: 5px solid #28a745;
        color: #176b35;
    }

    .continue-payment {
        display: inline-block;
        margin-top: 18px;
        margin-left: 8px;
        padding: 10px 17px;
        background: #28a745;
        color: white !important;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
    }

    .continue-payment:hover {
        background: #218838;
    }

    .view-prescription {
        display: inline-block;
        margin-top: 18px;
        padding: 10px 17px;
        background: #0878d1;
        color: white !important;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
    }

    .view-prescription:hover {
        opacity: 0.9;
    }

    .empty {
        text-align: center;
        background: #ffffff;
        border-radius: 12px;
        padding: 45px 25px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }

    .empty h2 {
        margin-top: 0;
        color: #333;
    }

    .empty p {
        color: #666;
        margin-bottom: 20px;
    }

    .back-products {
        display: inline-block;
        padding: 10px 18px;
        background: #0878d1;
        color: white !important;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
    }

    @media (max-width: 650px) {
        .prescription-status-page {
            padding: 0 10px;
            margin-top: 20px;
        }

        .prescription-card-top {
            align-items: flex-start;
            gap: 10px;
        }

        .detail-row {
            display: block;
        }

        .detail-label {
            width: auto;
            margin-bottom: 2px;
        }
    }
</style>

<div class="prescription-status-page">

    <div class="prescription-status-title">
        <h1>🩺 Prescription Status</h1>
        <p>Pending prescriptions stay here until pharmacist approval. Approved medicines move to My Orders for payment.</p>
    </div>

<?php if (mysqli_num_rows($result) === 0): ?>

    <div class="empty">
        <h2>No Pending Prescriptions</h2>
        <p>
            You currently have no prescriptions waiting for pharmacist approval.
        </p>
        <a href="products.php" class="back-products">← Back to Products</a>
    </div>

<?php else: ?>

<?php while ($prescription = mysqli_fetch_assoc($result)): ?>

    <?php
        $prescriptionId = (int)$prescription['id'];
        $productName = $prescription['product_name'] ?? 'Medicine';
        $patientName = $prescription['patient_name'] ?? '';
        $mobile = $prescription['mobile'] ?? '';
        $uploadDate = $prescription['upload_date'] ?? 'Not Available';
        $fileName = basename($prescription['prescription_file'] ?? '');
    ?>

    <div class="prescription-card">
        <div class="prescription-card-top">
            <h2>Prescription #<?php echo $prescriptionId; ?></h2>
            <span class="status-pending">⏳ Pending</span>
        </div>

        <div class="detail-row">
            <div class="detail-label">Medicine</div>
            <div class="detail-value"><?php echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Patient Name</div>
            <div class="detail-value"><?php echo htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Mobile</div>
            <div class="detail-value"><?php echo htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Uploaded On</div>
            <div class="detail-value"><?php echo htmlspecialchars($uploadDate, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Current Status</div>
            <div class="detail-value"><span class="status-pending">⏳ Pending Pharmacist Approval</span></div>
        </div>

        <div class="pending-box">
            <strong>⏳ Prescription Pending</strong><br><br>
            Your prescription has been uploaded successfully and is waiting for pharmacist approval.<br><br>
            <strong>Do not make a payment yet.</strong> Once the pharmacist approves this prescription, the medicine will automatically appear in <strong>My Orders</strong> with a <strong>Pay Now</strong> option.
        </div>

        <?php if ($fileName !== ''): ?>
            <a href="uploads/<?php echo rawurlencode($fileName); ?>" target="_blank" class="view-prescription">
                📄 View Prescription
            </a>
        <?php endif; ?>
    </div>

<?php endwhile; ?>

<?php endif; ?>

</div>

</body>
</html>
