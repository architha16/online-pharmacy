<?php

/*
====================================================
PHARMACYX - VIEW PRESCRIPTIONS
PHARMACIST ONLY
====================================================
*/

/* =====================================================
   PHARMACIST SESSION
   ===================================================== */

session_name("PHARMACYX_PHARMACIST");
session_start();

require_once "./db_Config/config.php";


/* =====================================================
   PHARMACIST ACCESS CHECK
   ===================================================== */

if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Pharmacist'
) {
    header("Location: signin.php?role=Pharmacist");
    exit();
}


$pharmacistUsername = $_SESSION['username'];


/* =====================================================
   GET ALL PRESCRIPTIONS
   ===================================================== */

$query = "
    SELECT *
    FROM prescriptions
    ORDER BY upload_date DESC
";

$result = mysqli_query($Connection, $query);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>View Prescriptions | PharmacyX</title>


    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 20px;
            margin: 0;
        }


        h2 {
            text-align: center;
            margin-bottom: 25px;
        }


        .page-container {
            width: 95%;
            margin: auto;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }


        table th,
        table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
            vertical-align: middle;
        }


        th {
            background: #28a745;
            color: white;
        }


        tr:hover {
            background: #f9f9f9;
        }


        a {
            text-decoration: none;
            color: blue;
            font-weight: bold;
        }


        a:hover {
            text-decoration: underline;
        }


        input[type="text"] {
            width: 180px;
            padding: 8px;
            border: 1px solid #bbb;
            border-radius: 4px;
            box-sizing: border-box;
        }


        .recommended-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
        }


        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }


        button {
            padding: 8px 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            font-weight: bold;
        }


        .save-btn {
            background: #007bff;
        }


        .save-btn:hover {
            background: #0056b3;
        }


        .approve-btn {
            background: #28a745;
        }


        .approve-btn:hover {
            background: #1e7e34;
        }


        .reject-btn {
            background: #dc3545;
        }


        .reject-btn:hover {
            background: #bd2130;
        }


        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }


        .status-pending {
            background: #fff3cd;
            color: #856404;
        }


        .status-approved {
            background: #d4edda;
            color: #155724;
        }


        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }


        .reason-box {
            margin-top: 8px;
        }


        .reason-box textarea {
            width: 220px;
            min-height: 70px;
            padding: 8px;
            border: 1px solid #bbb;
            border-radius: 4px;
            resize: vertical;
            box-sizing: border-box;
        }


        .reject-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }


        .empty-message {
            text-align: center;
            padding: 30px;
            background: white;
            border: 1px solid #ddd;
            color: #666;
        }


        .product-info {
            font-size: 13px;
            color: #555;
            margin-top: 5px;
        }


        .already-approved {
            color: #198754;
            font-weight: bold;
        }


        .already-rejected {
            color: #dc3545;
            font-weight: bold;
        }


        @media(max-width: 900px) {

            .page-container {
                width: 100%;
                overflow-x: auto;
            }

            table {
                min-width: 1100px;
            }

        }

    </style>

</head>


<body>


<div class="page-container">

    <h2>
        Uploaded Prescriptions
    </h2>


    <?php if ($result && mysqli_num_rows($result) > 0): ?>

    <table>

        <tr>

            <th>ID</th>

            <th>Patient Name</th>

            <th>Mobile</th>

            <th>Prescription</th>

            <th>Status</th>

            <th>Recommended Medicines</th>

            <th>Action</th>

        </tr>


        <?php while ($row = mysqli_fetch_assoc($result)): ?>

        <?php

            $status = $row['status'] ?? 'Pending';

            $recommendedMedicines =
                $row['recommended_medicines'] ?? '';

            $rejectionReason =
                $row['rejection_reason'] ?? '';

        ?>


        <tr>

            <!-- ID -->

            <td>

                <?php
                echo (int)$row['id'];
                ?>

            </td>


            <!-- PATIENT NAME -->

            <td>

                <?php
                echo htmlspecialchars(
                    $row['patient_name'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

            </td>


            <!-- MOBILE -->

            <td>

                <?php
                echo htmlspecialchars(
                    $row['mobile'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

            </td>


            <!-- PRESCRIPTION -->

            <td>

                <?php if (!empty($row['prescription_file'])): ?>

                    <a
                        href="uploads/<?php
                            echo rawurlencode(
                                $row['prescription_file']
                            );
                        ?>"
                        target="_blank"
                    >
                        📄 View Prescription
                    </a>

                <?php else: ?>

                    No File

                <?php endif; ?>

            </td>


            <!-- STATUS -->

            <td>

                <?php if ($status === 'Approved'): ?>

                    <span class="status status-approved">
                        ✅ Approved
                    </span>

                <?php elseif ($status === 'Rejected'): ?>

                    <span class="status status-rejected">
                        ❌ Rejected
                    </span>

                <?php else: ?>

                    <span class="status status-pending">
                        ⏳ Pending
                    </span>

                <?php endif; ?>


                <?php if (
                    $status === 'Rejected' &&
                    !empty($rejectionReason)
                ): ?>

                    <div class="product-info">

                        Reason:

                        <?php
                        echo htmlspecialchars(
                            $rejectionReason,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>

                    </div>

                <?php endif; ?>

            </td>


            <!-- RECOMMENDED MEDICINES -->

            <td>

                <form
                    action="update_prescription.php"
                    method="POST"
                    class="recommended-form"
                >

                    <input
                        type="hidden"
                        name="id"
                        value="<?php
                            echo (int)$row['id'];
                        ?>"
                    >


                    <input
                        type="hidden"
                        name="action"
                        value="save_recommendation"
                    >


                    <input
                        type="text"
                        name="recommended_medicines"
                        value="<?php
                            echo htmlspecialchars(
                                $recommendedMedicines,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"
                        placeholder="Enter Medicines"
                    >


                    <button
                        type="submit"
                        class="save-btn"
                    >
                        💾 Save
                    </button>

                </form>

            </td>


            <!-- ACTION -->

            <td>


                <?php if ($status !== 'Approved'): ?>

                    <!-- APPROVE -->

                    <form
                        action="update_prescription.php"
                        method="POST"
                        style="display:inline;"
                        onsubmit="
                            return confirm(
                                'Are you sure you want to APPROVE this prescription?'
                            );
                        "
                    >

                        <input
                            type="hidden"
                            name="id"
                            value="<?php
                                echo (int)$row['id'];
                            ?>"
                        >


                        <input
                            type="hidden"
                            name="action"
                            value="approve"
                        >


                        <button
                            type="submit"
                            class="approve-btn"
                        >
                            ✅ Approve
                        </button>

                    </form>

                <?php endif; ?>


                <?php if ($status !== 'Rejected'): ?>

                    <!-- REJECT -->

                    <form
                        action="update_prescription.php"
                        method="POST"
                        class="reject-form"
                        onsubmit="
                            return validateReject(
                                this
                            );
                        "
                    >

                        <input
                            type="hidden"
                            name="id"
                            value="<?php
                                echo (int)$row['id'];
                            ?>"
                        >


                        <input
                            type="hidden"
                            name="action"
                            value="reject"
                        >


                        <div class="reason-box">

                            <textarea
                                name="rejection_reason"
                                placeholder="Enter rejection reason"
                            ></textarea>

                        </div>


                        <button
                            type="submit"
                            class="reject-btn"
                        >
                            ❌ Reject
                        </button>

                    </form>

                <?php endif; ?>


                <?php if ($status === 'Approved'): ?>

                    <div class="already-approved">
                        Prescription Approved
                    </div>

                <?php endif; ?>


                <?php if ($status === 'Rejected'): ?>

                    <div class="already-rejected">
                        Prescription Rejected
                    </div>

                <?php endif; ?>


            </td>

        </tr>


        <?php endwhile; ?>

    </table>


    <?php else: ?>


        <div class="empty-message">

            📄 No prescriptions uploaded yet.

        </div>


    <?php endif; ?>


</div>


<script>

function validateReject(form)
{
    const reason =
        form.querySelector(
            'textarea[name="rejection_reason"]'
        ).value.trim();


    if (reason === "")
    {
        alert(
            "Please enter a rejection reason."
        );

        return false;
    }


    return confirm(
        "Are you sure you want to REJECT this prescription?"
    );
}

</script>


</body>

</html>