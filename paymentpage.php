<?php

/* =========================================================
   PHARMACYX CUSTOMER SESSION
   ========================================================= */

session_name("PHARMACYX_CUSTOMER");
session_start();


/* =========================================================
   CUSTOMER LOGIN CHECK
   ========================================================= */

if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'Customer'
) {
    header("Location: signin.php?role=Customer");
    exit();
}


/* =========================================================
   DATABASE
   ========================================================= */

require_once "./db_Config/config.php";


$user = $_SESSION['username'];

$userEscaped = mysqli_real_escape_string(
    $Connection,
    $user
);


/* =========================================================
   CHECKOUT MODE
   =========================================================
   buy_now = selected product only
   cart    = all products in cart
   ========================================================= */

$checkoutMode = $_POST['checkout_mode']
    ?? $_GET['checkout_mode']
    ?? 'cart';

$isBuyNow = ($checkoutMode === 'buy_now');


/* =========================================================
   APPROVED PRESCRIPTION ID FROM MY ORDERS
   =========================================================
   My Orders may send the exact approved prescription ID.
   Validate it against the logged-in customer and product
   before using it for this checkout.
   ========================================================= */

$requestedPrescriptionId = (int)(
    $_POST['prescription_id']
    ?? $_GET['prescription_id']
    ?? 0
);


/* =========================================================
   NEW CHECKOUT PRESCRIPTION SESSION
   =========================================================
   IMPORTANT:
   A previous Approved prescription must NOT automatically
   approve a new purchase.

   A new checkout gets a new start timestamp.
   After the customer uploads a prescription, the same
   checkout timestamp is preserved through the redirect.
   ========================================================= */

$newCheckout = false;

/*
BUY NOW:
The initial request from order_product.php contains
buy_now=1. The later redirect after prescription upload
does not contain buy_now=1, so the checkout is preserved.
*/
if (
    $isBuyNow &&
    isset($_POST['buy_now']) &&
    $_POST['buy_now'] == '1'
) {
    $newCheckout = true;
}

/*
CART:
The normal cart checkout link opens paymentpage.php
without checkout_mode. Therefore it starts a new checkout.
After prescription upload, paymentpage.php redirects with
checkout_mode=cart, so the existing checkout is preserved.
*/
if (
    !$isBuyNow &&
    !isset($_POST['checkout_mode']) &&
    !isset($_GET['checkout_mode'])
) {
    $newCheckout = true;
}

/*
Optional explicit new checkout marker.
This can also be used by cart.php/order_product.php.
*/
if (
    isset($_POST['new_checkout']) &&
    $_POST['new_checkout'] == '1'
) {
    $newCheckout = true;
}

if (
    isset($_GET['new_checkout']) &&
    $_GET['new_checkout'] == '1'
) {
    $newCheckout = true;
}

if (
    $newCheckout ||
    !isset($_SESSION['PHARMACYX_PRESCRIPTION_CHECKOUT_START'])
) {
    $_SESSION['PHARMACYX_PRESCRIPTION_CHECKOUT_START'] =
        date('Y-m-d H:i:s');
}

$prescriptionCheckoutStart =
    $_SESSION['PHARMACYX_PRESCRIPTION_CHECKOUT_START'];

/* =========================================================
   CURRENT CHECKOUT PRESCRIPTION IDS
   =========================================================
   Every new checkout starts with NO approved prescription.
   After a new upload, the inserted prescription IDs are stored
   in this session and ONLY those records are checked.
   This prevents an older Approved prescription from being reused.
*/
if ($newCheckout) {
    $_SESSION['PHARMACYX_CURRENT_PRESCRIPTION_IDS'] = [];
}

if (!isset($_SESSION['PHARMACYX_CURRENT_PRESCRIPTION_IDS'])) {
    $_SESSION['PHARMACYX_CURRENT_PRESCRIPTION_IDS'] = [];
}

$currentPrescriptionIds = $_SESSION['PHARMACYX_CURRENT_PRESCRIPTION_IDS'];


/* =========================================================
   RESTORE EXACT APPROVED PRESCRIPTION FROM MY ORDERS
   ========================================================= */

if ($requestedPrescriptionId > 0) {

    $requestedPrescriptionQuery = mysqli_query(
        $Connection,
        "SELECT id, product_id, status, prescription_file
         FROM prescriptions
         WHERE id='$requestedPrescriptionId'
           AND user_name='$userEscaped'
         LIMIT 1"
    );

    if (
        $requestedPrescriptionQuery &&
        mysqli_num_rows($requestedPrescriptionQuery) > 0
    ) {

        $requestedPrescription =
            mysqli_fetch_assoc($requestedPrescriptionQuery);

        if ($requestedPrescription['status'] === 'Approved') {

            $requestedPrescriptionProductId =
                (int)$requestedPrescription['product_id'];

            /* Do not reuse an already paid prescription. */
            $paidPrescriptionQuery = mysqli_query(
                $Connection,
                "SELECT order_id
                 FROM orders
                 WHERE prescription_id='$requestedPrescriptionId'
                 LIMIT 1"
            );

            if (
                !$paidPrescriptionQuery ||
                mysqli_num_rows($paidPrescriptionQuery) === 0
            ) {

                $_SESSION['PHARMACYX_CURRENT_PRESCRIPTION_IDS'][
                    $requestedPrescriptionProductId
                ] = $requestedPrescriptionId;

                $currentPrescriptionIds =
                    $_SESSION['PHARMACYX_CURRENT_PRESCRIPTION_IDS'];
            }
        }
    }
}


/* =========================================================
   CHECKOUT ITEMS
   ========================================================= */

$checkoutItems = [];

$total = 0;

$prescriptionRequired = false;


/* =========================================================
   BUY NOW CHECKOUT
   ========================================================= */

if ($isBuyNow) {

    $productId = (int)(
        $_POST['product_id']
        ?? $_GET['product_id']
        ?? 0
    );

    $buyNowQuantity = (int)(
        $_POST['quantity']
        ?? $_GET['quantity']
        ?? 1
    );

    if ($buyNowQuantity < 1) {
        $buyNowQuantity = 1;
    }


    if ($productId <= 0) {
        echo "<script>
            alert('Invalid product selected.');
            window.location='products.php';
        </script>";
        exit();
    }


    /* =====================================================
       GET SELECTED PRODUCT
       ===================================================== */

    $productIdEscaped = (int)$productId;

    $productQuery = mysqli_query(
        $Connection,
        "SELECT *
         FROM products
         WHERE product_id='$productIdEscaped'
         LIMIT 1"
    );


    if (
        !$productQuery ||
        mysqli_num_rows($productQuery) === 0
    ) {
        echo "<script>
            alert('Product not found.');
            window.location='products.php';
        </script>";
        exit();
    }


    $product = mysqli_fetch_assoc($productQuery);


    /* =====================================================
       STOCK CHECK
       ===================================================== */

    $availableStock = (int)$product['stock_quantity'];


    if ($availableStock <= 0) {
        echo "<script>
            alert('This medicine is currently out of stock.');
            window.location='products.php';
        </script>";
        exit();
    }


    if ($buyNowQuantity > $availableStock) {
        echo "<script>
            alert('Only " . $availableStock . " units are available.');
            window.location='order_product.php?prdct_id=" . $productId . "';
        </script>";
        exit();
    }


    /* =====================================================
       CREATE BUY NOW ITEM
       ===================================================== */

    $itemTotal =
        (float)$product['price'] *
        $buyNowQuantity;


    $checkoutItems[] = [
        'product_id' => (int)$product['product_id'],
        'product_name' => $product['product_name'],
        'product_description' => $product['product_description'],
        'image_url' => $product['image_url'],
        'price' => (float)$product['price'],
        'quantity' => $buyNowQuantity,
        'item_total' => $itemTotal,
        'prescription_required' =>
            $product['prescription_required']
    ];


    $total = $itemTotal;


    if (
        $product['prescription_required'] === 'Yes'
    ) {
        $prescriptionRequired = true;
    }

}


/* =========================================================
   CART CHECKOUT
   ========================================================= */

else {

    $cartQuery = mysqli_query(
        $Connection,
        "SELECT *
         FROM cart
         WHERE user_name='$userEscaped'"
    );


    if (!$cartQuery) {
        die(
            "Cart Error: " .
            mysqli_error($Connection)
        );
    }


    while ($cartRow = mysqli_fetch_assoc($cartQuery)) {

        $productId =
            (int)$cartRow['product_id'];


        /* =================================================
           GET PRODUCT
           ================================================= */

        $productQuery = mysqli_query(
            $Connection,
            "SELECT *
             FROM products
             WHERE product_id='$productId'
             LIMIT 1"
        );


        if (
            $productQuery &&
            mysqli_num_rows($productQuery) > 0
        ) {

            $product =
                mysqli_fetch_assoc(
                    $productQuery
                );


            $cartRow['product_name'] =
                $product['product_name'];

            $cartRow['product_description'] =
                $product['product_description'];

            $cartRow['image_url'] =
                $product['image_url'];

            $cartRow['price'] =
                (float)$product['price'];

            $cartRow['prescription_required'] =
                $product['prescription_required'];


            /* =============================================
               STOCK CHECK
               ============================================= */

            if (
                (int)$cartRow['quantity'] >
                (int)$product['stock_quantity']
            ) {

                echo "<script>
                    alert('Insufficient stock for " .
                    htmlspecialchars(
                        $product['product_name'],
                        ENT_QUOTES
                    ) .
                    ".');
                    window.location='cart.php';
                </script>";

                exit();
            }


            if (
                $product['prescription_required'] === 'Yes'
            ) {
                $prescriptionRequired = true;
            }

        }

        else {

            $cartRow['product_name'] =
                "Unknown Medicine";

            $cartRow['product_description'] =
                "Medicine details unavailable.";

            $cartRow['image_url'] = "";

            $cartRow['prescription_required'] =
                "No";
        }


        /* =================================================
           ITEM TOTAL
           ================================================= */

        $itemTotal =
            (float)$cartRow['price'] *
            (int)$cartRow['quantity'];


        $cartRow['item_total'] =
            $itemTotal;


        $total +=
            $itemTotal;


        $checkoutItems[] =
            $cartRow;
    }


    /* =====================================================
       EMPTY CART
       ===================================================== */

    if (count($checkoutItems) === 0) {

        header("Location: cart.php");
        exit();
    }
}


/* =========================================================
   PRESCRIPTION STATUS
   ========================================================= */

$prescriptionApproved = false;

$prescriptionPending = false;

$prescriptionRejected = false;

$approvedPrescriptionFile = "";


/* =========================================================
   GET PRESCRIPTION REQUIRED PRODUCT IDS
   ========================================================= */

$requiredProductIds = [];


foreach ($checkoutItems as $item) {

    if (
        ($item['prescription_required'] ?? 'No')
        === 'Yes'
    ) {

        $requiredProductIds[] =
            (int)$item['product_id'];
    }
}


$requiredProductIds =
    array_unique(
        $requiredProductIds
    );


/* =========================================================
   CHECK PRESCRIPTIONS
   ========================================================= */

if (
    $prescriptionRequired &&
    count($requiredProductIds) > 0
) {

    $approvedCount = 0;

    $pendingCount = 0;

    $rejectedCount = 0;


    foreach (
        $requiredProductIds
        as $requiredProductId
    ) {

        $requiredProductId =
            (int)$requiredProductId;


        /*
           IMPORTANT: Only the prescription uploaded for THIS
           checkout is eligible. Never fall back to an older
           Approved prescription for the same customer/product.
        */
        $currentPrescriptionId =
            (int)($currentPrescriptionIds[$requiredProductId] ?? 0);

        $prescriptionQuery = false;

        if ($currentPrescriptionId > 0) {
            $prescriptionQuery = mysqli_query(
                $Connection,

                "SELECT *
                 FROM prescriptions
                 WHERE id='$currentPrescriptionId'
                 AND user_name='$userEscaped'
                 AND product_id='$requiredProductId'
                 LIMIT 1"
            );
        }


        if (
            $prescriptionQuery &&
            mysqli_num_rows(
                $prescriptionQuery
            ) > 0
        ) {

            $prescriptionRow =
                mysqli_fetch_assoc(
                    $prescriptionQuery
                );


            $status =
                $prescriptionRow['status'];


            if ($status === 'Approved') {

                $approvedCount++;


                if (
                    empty(
                        $approvedPrescriptionFile
                    )
                ) {

                    $approvedPrescriptionFile =
                        $prescriptionRow[
                            'prescription_file'
                        ];
                }
            }

            elseif ($status === 'Pending') {

                $pendingCount++;
            }

            elseif ($status === 'Rejected') {

                $rejectedCount++;
            }
        }
    }


    /* =====================================================
       ALL APPROVED
       ===================================================== */

    if (
        $approvedCount ===
        count($requiredProductIds)
    ) {

        $prescriptionApproved = true;
    }


    /* =====================================================
       ANY PENDING
       ===================================================== */

    elseif ($pendingCount > 0) {

        $prescriptionPending = true;
    }


    /* =====================================================
       ANY REJECTED
       ===================================================== */

    elseif ($rejectedCount > 0) {

        $prescriptionRejected = true;
    }
}


/* =========================================================
   DISCOUNT
   ========================================================= */

$discount = 0;


if ($total >= 1000) {

    $discount =
        $total * 0.10;
}


$baseTotal =
    $total - $discount;


/* =========================================================
   PRESCRIPTION UPLOAD
   ========================================================= */

if (
    isset($_POST['upload_prescription'])
) {

    $patientName =
        trim(
            $_POST['patient_name'] ?? ''
        );


    $mobile =
        trim(
            $_POST['mobile'] ?? ''
        );


    if (
        empty($patientName) ||
        empty($mobile)
    ) {

        echo "<script>
            alert('Please enter patient name and mobile number.');
            history.back();
        </script>";

        exit();
    }


    if (
        !isset($_FILES['prescription']) ||
        $_FILES['prescription']['error']
        !== UPLOAD_ERR_OK
    ) {

        echo "<script>
            alert('Please select a valid prescription file.');
            history.back();
        </script>";

        exit();
    }


    $originalName =
        basename(
            $_FILES['prescription']['name']
        );


    $tmpName =
        $_FILES['prescription']['tmp_name'];


    $fileSize =
        $_FILES['prescription']['size'];


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

        echo "<script>
            alert('Only JPG, JPEG, PNG and PDF files are allowed.');
            history.back();
        </script>";

        exit();
    }


    if (
        $fileSize >
        5 * 1024 * 1024
    ) {

        echo "<script>
            alert('Prescription file must be less than 5 MB.');
            history.back();
        </script>";

        exit();
    }


    /* =====================================================
       CREATE UPLOAD FOLDER
       ===================================================== */

    $uploadFolder =
        "./uploads/";


    if (
        !is_dir($uploadFolder)
    ) {

        if (
            !mkdir(
                $uploadFolder,
                0777,
                true
            )
        ) {

            die(
                "Unable to create uploads folder."
            );
        }
    }


    /* =====================================================
       UNIQUE FILE NAME
       ===================================================== */

    $newFileName =
        time() .
        "_" .
        uniqid() .
        "_" .
        preg_replace(
            "/[^A-Za-z0-9._-]/",
            "_",
            $originalName
        );


    /* =====================================================
       MOVE FILE
       ===================================================== */

    if (
        !move_uploaded_file(
            $tmpName,
            $uploadFolder .
            $newFileName
        )
    ) {

        echo "<script>
            alert('Prescription upload failed.');
            history.back();
        </script>";

        exit();
    }


    $patientEscaped =
        mysqli_real_escape_string(
            $Connection,
            $patientName
        );


    $mobileEscaped =
        mysqli_real_escape_string(
            $Connection,
            $mobile
        );


    $fileEscaped =
        mysqli_real_escape_string(
            $Connection,
            $newFileName
        );


    /* =====================================================
       INSERT PRESCRIPTION
       FOR EACH REQUIRED MEDICINE
       ===================================================== */

    $uploadSuccessful = true;


    foreach (
        $requiredProductIds
        as $requiredProductId
    ) {

        $productId =
            (int)$requiredProductId;


        $insertSql = "

            INSERT INTO prescriptions

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
                '$userEscaped',
                '$productId',
                '$patientEscaped',
                '$mobileEscaped',
                '$fileEscaped',
                'Pending',
                NULL
            )

        ";


        if (
            !mysqli_query(
                $Connection,
                $insertSql
            )
        ) {

            $uploadSuccessful = false;

            break;
        }

        /* Save the exact prescription ID created for this checkout. */
        $newPrescriptionId = (int)mysqli_insert_id($Connection);

        if ($newPrescriptionId <= 0) {
            $uploadSuccessful = false;
            break;
        }

        $currentPrescriptionIds[$productId] = $newPrescriptionId;
    }


    if ($uploadSuccessful) {

        /* Save ONLY the newly uploaded prescription IDs for this checkout. */
        $_SESSION['PHARMACYX_CURRENT_PRESCRIPTION_IDS'] =
            $currentPrescriptionIds;

        /* =============================================
           PRESERVE CHECKOUT MODE
           ============================================= */

        if ($isBuyNow) {

            $redirectUrl =
                "paymentpage.php" .
                "?checkout_mode=buy_now" .
                "&product_id=" .
                (int)$productId .
                "&quantity=" .
                (int)$buyNowQuantity;

        }

        else {

            $redirectUrl =
                "paymentpage.php?checkout_mode=cart";
        }


        echo "<script>
            alert('Prescription uploaded successfully. Please wait for pharmacist approval.');
            window.location='prescription_status.php';
        </script>";

        exit();
    }

    else {

        die(
            "Prescription Database Error: " .
            mysqli_error($Connection)
        );
    }
}


/* =========================================================
   PAYMENT / ORDER
   ========================================================= */

if (
    isset($_POST['paynow'])
) {

    /* =====================================================
       SERVER-SIDE PRESCRIPTION APPROVAL GUARD
       =====================================================
       A prescription-required medicine can only become an
       order when the exact prescription for this checkout is
       currently Approved.
    */
    if ($prescriptionRequired) {

        foreach ($requiredProductIds as $guardProductId) {

            $guardProductId = (int)$guardProductId;
            $guardPrescriptionId = (int)($currentPrescriptionIds[$guardProductId] ?? 0);

            if ($guardPrescriptionId <= 0) {
                echo "<script>
                    alert('Payment is not available yet. Please wait for pharmacist approval.');
                    window.location='prescription_status.php';
                </script>";
                exit();
            }

            $guardSql = "
                SELECT id
                FROM prescriptions
                WHERE id='$guardPrescriptionId'
                  AND user_name='$userEscaped'
                  AND product_id='$guardProductId'
                  AND status='Approved'
                LIMIT 1
            ";

            $guardResult = mysqli_query($Connection, $guardSql);

            if (!$guardResult || mysqli_num_rows($guardResult) === 0) {
                echo "<script>
                    alert('Payment is not available. Your prescription is not approved.');
                    window.location='prescription_status.php';
                </script>";
                exit();
            }
        }
    }


    /* =====================================================
       PRESCRIPTION PROTECTION
       ===================================================== */

    if (
        $prescriptionRequired &&
        !$prescriptionApproved
    ) {

        if ($prescriptionPending) {

            echo "<script>
                alert('Your prescription is waiting for pharmacist approval.');
                history.back();
            </script>";
        }

        elseif ($prescriptionRejected) {

            echo "<script>
                alert('Your prescription was rejected. Please upload a valid prescription.');
                history.back();
            </script>";
        }

        else {

            echo "<script>
                alert('Please upload your prescription first.');
                history.back();
            </script>";
        }

        exit();
    }


    /* =====================================================
       PAYMENT METHOD
       ===================================================== */

    $paymentMethod =
        $_POST['payment_method'] ?? '';


    if (
        $paymentMethod !== 'COD' &&
        $paymentMethod !== 'Online'
    ) {

        echo "<script>
            alert('Please select a payment method.');
            history.back();
        </script>";

        exit();
    }


    /* =====================================================
       DELIVERY DETAILS
       ===================================================== */

    $firstName =
        trim(
            $_POST['first_name'] ?? ''
        );


    $lastName =
        trim(
            $_POST['last_name'] ?? ''
        );


    $street =
        trim(
            $_POST['street'] ?? ''
        );


    $city =
        trim(
            $_POST['city'] ?? ''
        );


    $postalCode =
        trim(
            $_POST['postal_code'] ?? ''
        );


    if (
        empty($firstName) ||
        empty($lastName) ||
        empty($street) ||
        empty($city) ||
        empty($postalCode)
    ) {

        echo "<script>
            alert('Please enter all delivery details.');
            history.back();
        </script>";

        exit();
    }


    /* =====================================================
       RECEIVER NAME
       ===================================================== */

    $receiverName =
        $firstName .
        " " .
        $lastName;


    /* =====================================================
       COD SHIPPING
       ===================================================== */

    $shippingCharge = 0;


    if (
        $paymentMethod === 'COD'
    ) {

        $shippingCharge = 50;
    }


    /* =====================================================
       FINAL TOTAL
       ===================================================== */

    $finalTotal =
        $baseTotal +
        $shippingCharge;


    /* =====================================================
       ONLINE PAYMENT VALIDATION
       ===================================================== */

    $bank = '';

    $remark = '';

    $slipName = '';


    if (
        $paymentMethod === 'Online'
    ) {

        $bank =
            trim(
                $_POST['bank'] ?? ''
            );


        $remark =
            trim(
                $_POST['remark'] ?? ''
            );


        if (
            empty($bank)
        ) {

            echo "<script>
                alert('Please enter bank name.');
                history.back();
            </script>";

            exit();
        }


        if (
            !isset($_FILES['slip']) ||
            $_FILES['slip']['error']
            !== UPLOAD_ERR_OK
        ) {

            echo "<script>
                alert('Please upload payment screenshot.');
                history.back();
            </script>";

            exit();
        }


        /* =============================================
           PAYMENT FILE VALIDATION
           ============================================= */

        $slipOriginal =
            basename(
                $_FILES['slip']['name']
            );


        $slipExtension =
            strtolower(
                pathinfo(
                    $slipOriginal,
                    PATHINFO_EXTENSION
                )
            );


        $allowedSlipExtensions = [
            'jpg',
            'jpeg',
            'png'
        ];


        if (
            !in_array(
                $slipExtension,
                $allowedSlipExtensions,
                true
            )
        ) {

            echo "<script>
                alert('Payment screenshot must be JPG, JPEG or PNG.');
                history.back();
            </script>";

            exit();
        }


        if (
            $_FILES['slip']['size'] >
            5 * 1024 * 1024
        ) {

            echo "<script>
                alert('Payment screenshot must be less than 5 MB.');
                history.back();
            </script>";

            exit();
        }


        /* =============================================
           PAYMENT FOLDER
           ============================================= */

        $paymentFolder =
            "./Images/payment_slips/";


        if (
            !is_dir($paymentFolder)
        ) {

            if (
                !mkdir(
                    $paymentFolder,
                    0777,
                    true
                )
            ) {

                die(
                    "Unable to create payment_slips folder."
                );
            }
        }


        /* =============================================
           UNIQUE PAYMENT FILE
           ============================================= */

        $slipName =
            time() .
            "_" .
            uniqid() .
            "_" .
            preg_replace(
                "/[^A-Za-z0-9._-]/",
                "_",
                $slipOriginal
            );


        /* =============================================
           MOVE PAYMENT SCREENSHOT
           ============================================= */

        if (
            !move_uploaded_file(
                $_FILES['slip']['tmp_name'],
                $paymentFolder .
                $slipName
            )
        ) {

            echo "<script>
                alert('Payment screenshot upload failed.');
                history.back();
            </script>";

            exit();
        }
    }


    /* =====================================================
       ESCAPE DELIVERY VALUES
       ===================================================== */

    $receiverNameEscaped =
        mysqli_real_escape_string(
            $Connection,
            $receiverName
        );


    $streetEscaped =
        mysqli_real_escape_string(
            $Connection,
            $street
        );


    $cityEscaped =
        mysqli_real_escape_string(
            $Connection,
            $city
        );


    $postalCodeEscaped =
        mysqli_real_escape_string(
            $Connection,
            $postalCode
        );


    $paymentMethodEscaped =
        mysqli_real_escape_string(
            $Connection,
            $paymentMethod
        );


    /* =====================================================
       PRESCRIPTION FILE
       ===================================================== */

    $prescriptionUrl = "NULL";


    if (
        $prescriptionRequired &&
        $prescriptionApproved &&
        !empty($approvedPrescriptionFile)
    ) {

        $approvedFileEscaped =
            mysqli_real_escape_string(
                $Connection,
                $approvedPrescriptionFile
            );


        $prescriptionUrl =
            "'" .
            $approvedFileEscaped .
            "'";
    }


    /* =====================================================
       TRANSACTION
       ===================================================== */

    mysqli_begin_transaction(
        $Connection
    );


    try {

        $firstOrderId = 0;

        $isFirstItem = true;


        /* =================================================
           CREATE ORDER FOR CHECKOUT ITEMS
           ================================================= */

        foreach (
            $checkoutItems
            as $item
        ) {

            $productId =
                (int)$item['product_id'];


            $quantity =
                (int)$item['quantity'];


            /* =============================================
               RECHECK PRODUCT STOCK
               ============================================= */

            $stockCheckQuery =
                mysqli_query(
                    $Connection,

                    "SELECT stock_quantity
                     FROM products
                     WHERE product_id='$productId'
                     LIMIT 1
                     FOR UPDATE"
                );


            if (
                !$stockCheckQuery ||
                mysqli_num_rows(
                    $stockCheckQuery
                ) === 0
            ) {

                throw new Exception(
                    "Product not found."
                );
            }


            $stockRow =
                mysqli_fetch_assoc(
                    $stockCheckQuery
                );


            $currentStock =
                (int)$stockRow['stock_quantity'];


            if (
                $quantity > $currentStock
            ) {

                throw new Exception(
                    "Insufficient stock for " .
                    $item['product_name'] .
                    ". Available stock: " .
                    $currentStock
                );
            }


            /* =============================================
               ORDER TOTAL
               ============================================= */

            $orderTotal =
                (float)$item['price'] *
                $quantity;


            /* =============================================
               APPLY DISCOUNT PROPORTIONALLY
               ============================================= */

            if ($total > 0) {

                $itemDiscount =
                    (
                        $orderTotal /
                        $total
                    ) *
                    $discount;

            }

            else {

                $itemDiscount = 0;
            }


            $itemFinalTotal =
                $orderTotal -
                $itemDiscount;


            /* =============================================
               ADD COD SHIPPING TO FIRST ITEM
               ============================================= */

            if (
                $paymentMethod === 'COD' &&
                $isFirstItem
            ) {

                $itemFinalTotal +=
                    $shippingCharge;

                $isFirstItem = false;
            }


            $itemFinalTotal =
                round(
                    $itemFinalTotal,
                    2
                );


            /* =============================================
               PRESCRIPTION ID FOR THIS ORDER
               ============================================= */

            $orderPrescriptionId =
                (int)($currentPrescriptionIds[$productId] ?? 0);

            $orderPrescriptionIdSql =
                ($orderPrescriptionId > 0)
                ? "'$orderPrescriptionId'"
                : "NULL";


            /* =============================================
               INSERT ORDER
               ============================================= */

            $orderSql = "

                INSERT INTO orders

                (
                    user_name,
                    order_status,
                    order_type,
                    qty,
                    receiver_name,
                    street,
                    city,
                    postal_code,
                    prescription_id,
                    prescription_url,
                    product_id,
                    Order_total,
                    payment_method
                )

                VALUES

                (
                    '$userEscaped',
                    'Pending',
                    'General',
                    '$quantity',
                    '$receiverNameEscaped',
                    '$streetEscaped',
                    '$cityEscaped',
                    '$postalCodeEscaped',
                    $orderPrescriptionIdSql,
                    $prescriptionUrl,
                    '$productId',
                    '$itemFinalTotal',
                    '$paymentMethodEscaped'
                )

            ";


            if (
                !mysqli_query(
                    $Connection,
                    $orderSql
                )
            ) {

                throw new Exception(
                    "Order Insert Error: " .
                    mysqli_error($Connection)
                );
            }


            $orderId =
                mysqli_insert_id(
                    $Connection
                );


            if (
                $firstOrderId === 0
            ) {

                $firstOrderId =
                    $orderId;
            }


            /* =============================================
               REDUCE PRODUCT STOCK
               ============================================= */

            $updateStockQuery =
                mysqli_query(
                    $Connection,

                    "UPDATE products
                     SET stock_quantity =
                         stock_quantity - $quantity
                     WHERE product_id='$productId'
                     AND stock_quantity >= $quantity"
                );


            if (
                !$updateStockQuery ||
                mysqli_affected_rows(
                    $Connection
                ) !== 1
            ) {

                throw new Exception(
                    "Unable to update stock."
                );
            }


            /* =============================================
               ONLINE PAYMENT RECORD
               ============================================= */

            if (
                $paymentMethod === 'Online'
            ) {

                $bankEscaped =
                    mysqli_real_escape_string(
                        $Connection,
                        $bank
                    );


                $remarkEscaped =
                    mysqli_real_escape_string(
                        $Connection,
                        $remark
                    );


                $slipEscaped =
                    mysqli_real_escape_string(
                        $Connection,
                        $slipName
                    );


                $paymentAmount =
                    $itemFinalTotal;


                $paymentSql = "

                    INSERT INTO payment

                    (
                        order_id,
                        amount,
                        bank,
                        remark,
                        receipt_url
                    )

                    VALUES

                    (
                        '$orderId',
                        '$paymentAmount',
                        '$bankEscaped',
                        '$remarkEscaped',
                        '$slipEscaped'
                    )

                ";


                if (
                    !mysqli_query(
                        $Connection,
                        $paymentSql
                    )
                ) {

                    throw new Exception(
                        "Payment Insert Error: " .
                        mysqli_error($Connection)
                    );
                }
            }
        }


        /* =================================================
           CLEAR CART ONLY FOR CART CHECKOUT
           ================================================= */

        if (!$isBuyNow) {

            $deleteCartQuery =
                mysqli_query(
                    $Connection,

                    "DELETE FROM cart
                     WHERE user_name='$userEscaped'"
                );


            if (
                !$deleteCartQuery
            ) {

                throw new Exception(
                    "Cart clearing failed: " .
                    mysqli_error($Connection)
                );
            }
        }


        /* =================================================
           COMMIT EVERYTHING
           ================================================= */

        mysqli_commit(
            $Connection
        );

        /* This checkout is finished; do not reuse its prescription
           for the customer's next checkout. */
        $_SESSION['PHARMACYX_CURRENT_PRESCRIPTION_IDS'] = [];
        unset($_SESSION['PHARMACYX_PRESCRIPTION_CHECKOUT_START']);


        /* =================================================
           SUCCESS
           ================================================= */

        echo "<script>

            alert(
                'Order Placed Successfully!'
            );

            window.location.href =
                'my_orders.php?id=" .
                (int)$firstOrderId .
                "';

        </script>";

        exit();
    }


    catch (
        Exception $e
    ) {

        /* ================================================
           ROLLBACK
           ================================================ */

        mysqli_rollback(
            $Connection
        );


        echo "<div style='
            width:80%;
            margin:50px auto;
            padding:25px;
            background:#f8d7da;
            color:#721c24;
            border:1px solid #f5c6cb;
            border-radius:10px;
            font-family:Arial;
        '>";

        echo "<h2>
            Order Could Not Be Placed
        </h2>";


        echo "<p>" .
            htmlspecialchars(
                $e->getMessage()
            ) .
            "</p>";


        echo "<p>
            Please go back and try again.
        </p>";


        echo "<a href='paymentpage.php'
            style='
                display:inline-block;
                padding:10px 18px;
                background:#007bff;
                color:white;
                text-decoration:none;
                border-radius:5px;
            '>
            Back to Payment
        </a>";


        echo "</div>";

        exit();
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
    PharmacyX Payment
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #eef5ff;
}


.container {

    width: 70%;

    margin: 30px auto;

    background: white;

    padding: 28px;

    border-radius: 14px;

    box-shadow:
        0 0 15px
        rgba(0,0,0,0.12);
}


h2 {

    text-align: center;

    color: #0b6efd;
}


.medicine {

    display: flex;

    align-items: center;

    gap: 22px;

    background: #f8f9fa;

    padding: 18px;

    border-radius: 12px;

    margin-bottom: 15px;
}


.medicine-image {

    width: 120px;

    height: 120px;

    object-fit: contain;

    background: white;

    border: 1px solid #ddd;

    border-radius: 10px;

    padding: 8px;
}


.medicine-details {

    flex: 1;
}


.medicine-details h3 {

    margin:
        0 0 8px;
}


.description {

    color: #555;

    margin-bottom: 8px;
}


.price {

    color: #087df5;

    font-weight: bold;
}


.quantity {

    margin-top: 7px;
}


.item-total {

    font-size: 18px;

    font-weight: bold;

    color: #198754;
}


.bill {

    background: #f8f9fa;

    padding: 20px;

    border-radius: 10px;

    margin-top: 25px;
}


.discount {

    color: green;
}


.total {

    color: red;

    font-size: 27px;
}


.section {

    background: #f8f9fa;

    padding: 20px;

    border-radius: 10px;

    margin-top: 20px;
}


.section h3 {

    color: #0b6efd;
}


label {

    display: block;

    margin-top: 12px;

    font-weight: bold;
}


input,
textarea {

    width: 100%;

    padding: 12px;

    margin-top: 7px;

    border: 1px solid #ccc;

    border-radius: 6px;

    font-size: 15px;
}


textarea {

    min-height: 90px;
}


/* =====================================================
   PRESCRIPTION
   ===================================================== */

.prescription-box {

    background: #fff8e1;

    border: 2px solid #ffc107;

    padding: 22px;

    border-radius: 12px;

    margin-top: 25px;
}


.prescription-box h3 {

    color: #dc3545;

    margin-top: 0;
}


.prescription-info {

    color: #555;

    line-height: 1.6;
}


.upload-btn {

    background: #dc3545;

    color: white;

    border: none;

    padding: 14px 22px;

    border-radius: 7px;

    margin-top: 18px;

    cursor: pointer;

    font-size: 16px;

    font-weight: bold;
}


.upload-btn:hover {

    background: #b52b3b;
}


.status-box {

    padding: 15px;

    border-radius: 8px;

    margin-top: 20px;

    font-weight: bold;
}


.status-pending {

    background: #fff3cd;

    color: #856404;

    border: 1px solid #ffeeba;
}


.status-approved {

    background: #d4edda;

    color: #155724;

    border: 1px solid #c3e6cb;
}


.status-rejected {

    background: #f8d7da;

    color: #721c24;

    border: 1px solid #f5c6cb;
}


/* =====================================================
   PAYMENT
   ===================================================== */

.payment-option {

    background: white;

    border: 1px solid #ddd;

    padding: 15px;

    margin: 10px 0;

    border-radius: 8px;
}


.payment-option input {

    width: auto;

    margin-right: 10px;
}


.qr {

    text-align: center;

    background: #fff3cd;

    padding: 20px;

    border-radius: 10px;

    margin-top: 20px;
}


.qr img {

    width: 220px;

    height: 220px;

    object-fit: contain;
}


.confirm-btn {

    width: 100%;

    padding: 16px;

    margin-top: 25px;

    background: #009688;

    color: white;

    border: none;

    border-radius: 6px;

    font-size: 18px;

    cursor: pointer;
}


.confirm-btn:hover {

    background: #00796b;
}


.waiting-btn {

    width: 100%;

    padding: 16px;

    margin-top: 25px;

    background: #999;

    color: white;

    border: none;

    border-radius: 6px;

    font-size: 18px;

    cursor: not-allowed;
}


.back-btn {

    display: block;

    width: 100%;

    padding: 13px;

    margin-top: 12px;

    text-align: center;

    background: #6c757d;

    color: white;

    text-decoration: none;

    border-radius: 6px;
}


.checkout-mode {

    text-align: center;

    margin-bottom: 20px;

    padding: 10px;

    border-radius: 8px;

    background: #e7f3ff;

    color: #075985;

    font-weight: bold;
}


@media(max-width:800px) {

    .container {

        width: 95%;
    }


    .medicine {

        flex-direction: column;

        align-items: flex-start;
    }
}

</style>


<script>

function showPayment()
{
    document.getElementById(
        "onlinePayment"
    ).style.display = "block";
}


function hidePayment()
{
    document.getElementById(
        "onlinePayment"
    ).style.display = "none";
}

</script>

</head>


<body>


<div class="container">


<h2>
    PharmacyX Secure Payment
</h2>


<!-- =====================================================
     CHECKOUT MODE
     ===================================================== -->

<div class="checkout-mode">

<?php

if ($isBuyNow) {

    echo "⚡ Buy Now Checkout";

}

else {

    echo "🛒 Cart Checkout";
}

?>

</div>


<!-- =====================================================
     MEDICINES
     ===================================================== -->

<h2>
    Your Medicines
</h2>


<?php

foreach (
    $checkoutItems
    as $item
) {

?>


<div class="medicine">


<?php

$imagePath =
    trim(
        $item['image_url'] ?? ''
    );


if (
    $imagePath !== ""
) {

    if (
        strpos(
            $imagePath,
            'Images/'
        ) === 0
        ||
        strpos(
            $imagePath,
            './Images/'
        ) === 0
    ) {

        $imageSrc =
            $imagePath;

    }

    else {

        $imageSrc =
            "./Images/product-icons/" .
            basename($imagePath);
    }

}

else {

    $imageSrc =
        "./Images/product-icons/Pharmacy-Isometric-Icons-1.png";
}

?>


<img

    class="medicine-image"

    src="<?php

        echo htmlspecialchars(
            $imageSrc
        );

    ?>"

    onerror="
        this.src='./Images/product-icons/Pharmacy-Isometric-Icons-1.png';
    "

>


<div class="medicine-details">


<h3>

<?php

echo htmlspecialchars(
    $item['product_name']
);

?>

</h3>


<div class="description">

<?php

echo htmlspecialchars(
    $item['product_description']
);

?>

</div>


<div class="price">

Price:

₹<?php

echo number_format(
    $item['price'],
    2
);

?>

</div>


<div class="quantity">

Quantity:

<?php

echo (int)$item['quantity'];

?>

</div>


<?php

if (
    ($item['prescription_required'] ?? 'No')
    === 'Yes'
) {

?>

<div style="
    margin-top:10px;
    color:#dc3545;
    font-weight:bold;
">

🩺 Prescription Required

</div>

<?php

}

?>


</div>


<div class="item-total">

₹<?php

echo number_format(
    $item['item_total'],
    2
);

?>

</div>


</div>


<?php

}

?>


<!-- =====================================================
     BILL
     ===================================================== -->

<div class="bill">


<h3>

Subtotal:

₹<?php

echo number_format(
    $total,
    2
);

?>

</h3>


<?php

if (
    $discount > 0
) {

?>

<h3 class="discount">

10% Discount:

-₹<?php

echo number_format(
    $discount,
    2
);

?>

</h3>

<?php

}

?>


<h2 class="total">

Grand Total:

₹<?php

echo number_format(
    $baseTotal,
    2
);

?>

</h2>


<p style="
    text-align:center;
    color:#666;
">

COD will include an additional ₹50 delivery charge.

</p>


</div>


<!-- =====================================================
     PRESCRIPTION
     ===================================================== -->

<?php

if (
    $prescriptionRequired
) {

?>


<div class="prescription-box">


<h3>

🩺 Prescription Required

</h3>


<p class="prescription-info">

<?php

if ($isBuyNow) {

    echo "This medicine requires a valid prescription.";

}

else {

    echo "One or more medicines in your cart require a valid prescription.";
}

?>

<br>

Please upload your prescription and wait
for pharmacist approval before making payment.

</p>


<?php

if (
    $prescriptionPending
) {

?>

<div class="status-box status-pending">

⏳ Prescription Status: Pending

<br><br>

Your prescription has been uploaded
and is waiting for pharmacist approval.

</div>

<?php

}


if (
    $prescriptionApproved
) {

?>

<div class="status-box status-approved">

✅ Prescription Status: Approved

<br><br>

The pharmacist has approved your prescription.

<br>

You can continue with payment.

</div>

<?php

}


if (
    $prescriptionRejected
) {

?>

<div class="status-box status-rejected">

❌ Prescription Status: Rejected

<br><br>

The pharmacist rejected your prescription.

<br>

Please upload a valid prescription again.

</div>

<?php

}


/* =====================================================
   UPLOAD FORM
   ===================================================== */

if (
    !$prescriptionApproved &&
    !$prescriptionPending
) {

?>

<form

    method="POST"

    enctype="multipart/form-data"

>

<!-- PRESERVE CHECKOUT MODE -->

<input
    type="hidden"
    name="checkout_mode"
    value="<?php
        echo $isBuyNow
            ? 'buy_now'
            : 'cart';
    ?>"
>


<?php

if ($isBuyNow) {

?>

<input
    type="hidden"
    name="product_id"
    value="<?php
        echo (int)$productId;
    ?>"
>

<?php if ($requestedPrescriptionId > 0): ?>

<input
    type="hidden"
    name="prescription_id"
    value="<?php
        echo (int)$requestedPrescriptionId;
    ?>"
>

<?php endif; ?>

<input
    type="hidden"
    name="quantity"
    value="<?php
        echo (int)$buyNowQuantity;
    ?>"
>

<?php

}

?>


<label>

Patient Name

</label>


<input

    type="text"

    name="patient_name"

    placeholder="Enter patient name"

    required

>


<label>

Mobile Number

</label>


<input

    type="text"

    name="mobile"

    placeholder="Enter mobile number"

    required

>


<label>

Upload Prescription

</label>


<input

    type="file"

    name="prescription"

    accept=".jpg,.jpeg,.png,.pdf"

    required

>


<p style="
    color:#666;
    font-size:14px;
">

Allowed: JPG, JPEG, PNG, PDF

<br>

Maximum size: 5 MB

</p>


<button

    type="submit"

    name="upload_prescription"

    class="upload-btn"

>

📄 Upload Prescription

</button>


</form>

<?php

}

?>


</div>


<?php

}

?>


<!-- =====================================================
     MAIN ORDER FORM
     ===================================================== -->

<?php

$canPlaceOrder = true;


if (
    $prescriptionRequired &&
    !$prescriptionApproved
) {

    $canPlaceOrder = false;
}

?>


<form

    method="POST"

    enctype="multipart/form-data"

>


<!-- =====================================================
     IMPORTANT CHECKOUT VALUES
     ===================================================== -->

<input
    type="hidden"
    name="checkout_mode"
    value="<?php
        echo $isBuyNow
            ? 'buy_now'
            : 'cart';
    ?>"
>


<?php

if ($isBuyNow) {

?>

<input
    type="hidden"
    name="product_id"
    value="<?php
        echo (int)$productId;
    ?>"
>

<input
    type="hidden"
    name="quantity"
    value="<?php
        echo (int)$buyNowQuantity;
    ?>"
>

<?php

}

?>


<!-- =====================================================
     DELIVERY
     ===================================================== -->

<div class="section">


<h3>

Delivery Address

</h3>


<label>

First Name

</label>


<input

    type="text"

    name="first_name"

    placeholder="Enter your first name"

    required

>


<label>

Last Name

</label>


<input

    type="text"

    name="last_name"

    placeholder="Enter your last name"

    required

>


<label>

Street Address

</label>


<input

    type="text"

    name="street"

    placeholder="Enter your street address"

    required

>


<label>

City

</label>


<input

    type="text"

    name="city"

    placeholder="Enter your city"

    required

>


<label>

Postal Code

</label>


<input

    type="text"

    name="postal_code"

    placeholder="Enter postal code"

    required

>


</div>


<!-- =====================================================
     PAYMENT METHOD
     ===================================================== -->

<div class="section">


<h3>

Payment Method

</h3>


<div class="payment-option">


<label>

<input

    type="radio"

    name="payment_method"

    value="COD"

    required

    onclick="hidePayment()"

>

Cash On Delivery (+₹50)

</label>


</div>


<div class="payment-option">


<label>

<input

    type="radio"

    name="payment_method"

    value="Online"

    onclick="showPayment()"

>

Online Payment

</label>


</div>


</div>


<!-- =====================================================
     ONLINE PAYMENT
     ===================================================== -->

<div

    id="onlinePayment"

    class="section"

    style="display:none;"

>


<div class="qr">


<h3>

Scan QR Code

</h3>


<img

    src="./Images/payment/qr.png"

    onerror="
        this.style.display='none';
    "

>


<br><br>


<b>

UPI ID:

pharmacyx@upi

</b>


</div>


<label>

Your Bank

</label>


<input

    type="text"

    name="bank"

    placeholder="Enter bank name"

>


<label>

Upload Payment Screenshot

</label>


<input

    type="file"

    name="slip"

    accept=".jpg,.jpeg,.png"

>


<p style="
    color:#666;
    font-size:14px;
">

Allowed: JPG, JPEG and PNG

<br>

Maximum size: 5 MB

</p>


<label>

Remark

</label>


<textarea

    name="remark"

    placeholder="Enter payment remark"

></textarea>


</div>


<!-- =====================================================
     CONFIRM ORDER
     ===================================================== -->

<?php

if (
    $canPlaceOrder
) {

?>

<button

    type="submit"

    name="paynow"

    class="confirm-btn"

>

💳 Confirm Order

</button>

<?php

}

else {

?>

<button

    type="button"

    class="waiting-btn"

    disabled

>

🔒 Waiting for Prescription Approval

</button>

<?php

}

?>


<?php

if ($isBuyNow) {

?>

<a
    href="products.php"
    class="back-btn"
>

← Back to Products

</a>

<?php

}

else {

?>

<a
    href="cart.php"
    class="back-btn"
>

← Back to Cart

</a>

<?php

}

?>


</form>


</div>


</body>

</html>