<?php

session_start();

include("db_Config/config.php");


// Check product ID
if (!isset($_GET['id'])) {
    die("Product ID is missing.");
}

$product_id = intval($_GET['id']);


// Get product
$sql = "SELECT * FROM products WHERE product_id = '$product_id'";

$result = mysqli_query($Connection, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($Connection));
}

if (mysqli_num_rows($result) == 0) {
    die("Medicine not found.");
}

$product = mysqli_fetch_assoc($result);


// UPDATE MEDICINE
if (isset($_POST['update_product'])) {

    $product_name = mysqli_real_escape_string(
        $Connection,
        trim($_POST['product_name'])
    );

    $product_description = mysqli_real_escape_string(
        $Connection,
        trim($_POST['product_description'])
    );

    $price = floatval($_POST['price']);

    $stock_quantity = intval($_POST['stock_quantity']);

    $expire_date = mysqli_real_escape_string(
        $Connection,
        $_POST['expire_date']
    );


    /*
        Keep old image by default
    */
    $image_name = $product['image_url'];


    /*
        Check if new image was uploaded
    */
    if (
        isset($_FILES['product_image']) &&
        $_FILES['product_image']['error'] == 0
    ) {

        $original_name = $_FILES['product_image']['name'];

        $temp_name = $_FILES['product_image']['tmp_name'];

        $extension = strtolower(
            pathinfo(
                $original_name,
                PATHINFO_EXTENSION
            )
        );


        /*
            Allowed image types
        */
        $allowed_extensions = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];


        if (!in_array($extension, $allowed_extensions)) {

            die("
                Invalid image format.
                Only JPG, JPEG, PNG and WEBP are allowed.
            ");

        }


        /*
            Create unique image name
        */
        $new_image_name =
            time() .
            "_" .
            rand(1000,9999) .
            "." .
            $extension;


        /*
            Image folder
        */
        $image_folder = "Images/product-icons/";


        /*
            Create folder if missing
        */
        if (!is_dir($image_folder)) {

            mkdir(
                $image_folder,
                0777,
                true
            );

        }


        /*
            Upload image
        */
        $image_path =
            $image_folder .
            $new_image_name;


        if (
            move_uploaded_file(
                $temp_name,
                $image_path
            )
        ) {

            /*
                Delete old image if it exists
            */
            if (
                !empty($product['image_url']) &&
                file_exists(
                    $image_folder .
                    $product['image_url']
                )
            ) {

                unlink(
                    $image_folder .
                    $product['image_url']
                );

            }


            /*
                Save new image name
            */
            $image_name = $new_image_name;

        }
        else {

            die("Failed to upload new image.");

        }

    }


    /*
        Escape image name
    */
    $image_name = mysqli_real_escape_string(
        $Connection,
        $image_name
    );


    /*
        Update database
    */
    $update = "
        UPDATE products
        SET
            product_name='$product_name',
            product_description='$product_description',
            price='$price',
            stock_quantity='$stock_quantity',
            image_url='$image_name',
            expire_date='$expire_date'
        WHERE product_id='$product_id'
    ";


    if (mysqli_query($Connection, $update)) {

        echo "
        <script>

            alert('Medicine updated successfully!');

            window.location.href='manage_products.php';

        </script>
        ";

        exit();

    }
    else {

        die(
            "Update Error: " .
            mysqli_error($Connection)
        );

    }

}

?>


<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>
Edit Medicine | PharmacyX
</title>


<style>

* {

    box-sizing: border-box;

}


body {

    font-family: Arial, sans-serif;

    background: #eef6ff;

    margin: 0;

    padding: 40px;

}


.container {

    width: 600px;

    max-width: 95%;

    margin: auto;

    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.15);

}


h2 {

    text-align: center;

    color: #0077cc;

    margin-bottom: 30px;

}


label {

    display: block;

    font-weight: bold;

    margin-top: 15px;

    margin-bottom: 6px;

}


input,
textarea {

    width: 100%;

    padding: 12px;

    border: 1px solid #ccc;

    border-radius: 6px;

    font-size: 15px;

}


textarea {

    height: 120px;

    resize: vertical;

}


.current-image {

    text-align: center;

    margin: 15px 0 20px 0;

    padding: 15px;

    background: #f5f9ff;

    border-radius: 8px;

}


.current-image img {

    width: 150px;

    height: 150px;

    object-fit: contain;

    border: 1px solid #ddd;

    border-radius: 8px;

    background: white;

}


.current-image p {

    margin-top: 10px;

    color: #555;

    font-weight: bold;

}


.file-info {

    color: #777;

    font-size: 13px;

    margin-top: 6px;

}


button {

    width: 100%;

    padding: 14px;

    margin-top: 25px;

    background: #0077cc;

    color: white;

    border: none;

    border-radius: 6px;

    font-size: 17px;

    font-weight: bold;

    cursor: pointer;

}


button:hover {

    background: #005fa3;

}


.back {

    display: block;

    text-align: center;

    margin-top: 20px;

    color: #0077cc;

    text-decoration: none;

}


.back:hover {

    text-decoration: underline;

}

</style>

</head>


<body>


<div class="container">


<h2>
✏️ Edit Medicine
</h2>


<form
method="POST"
enctype="multipart/form-data"
>


<!-- MEDICINE NAME -->

<label>
Medicine Name
</label>

<input
type="text"
name="product_name"
value="<?php
echo htmlspecialchars(
    $product['product_name']
);
?>"
required
>


<!-- DESCRIPTION -->

<label>
Medicine Description
</label>

<textarea
name="product_description"
required
><?php
echo htmlspecialchars(
    $product['product_description']
);
?></textarea>


<!-- PRICE -->

<label>
Price
</label>

<input
type="number"
name="price"
step="0.01"
value="<?php
echo htmlspecialchars(
    $product['price']
);
?>"
required
>


<!-- STOCK -->

<label>
Stock Quantity
</label>

<input
type="number"
name="stock_quantity"
value="<?php
echo htmlspecialchars(
    $product['stock_quantity']
);
?>"
required
>


<!-- EXPIRY -->

<label>
Expiry Date
</label>

<input
type="date"
name="expire_date"
value="<?php
echo htmlspecialchars(
    $product['expire_date']
);
?>"
required
>


<!-- CURRENT PHOTO -->

<label>
Current Medicine Photo
</label>

<div class="current-image">

<?php

if (
    !empty($product['image_url']) &&
    file_exists(
        "Images/product-icons/" .
        $product['image_url']
    )
) {

?>

<img
src="Images/product-icons/<?php
echo htmlspecialchars(
    $product['image_url']
);
?>"
alt="Medicine Photo"
>

<p>
Current Photo
</p>

<?php

}
else {

?>

<p>
No photo available
</p>

<?php

}

?>

</div>


<!-- NEW PHOTO -->

<label>
Upload New Medicine Photo
</label>

<input
type="file"
name="product_image"
accept=".jpg,.jpeg,.png,.webp"
>


<div class="file-info">

Leave this empty if you want to keep the current photo.

</div>


<!-- UPDATE -->

<button
type="submit"
name="update_product"
>

💾 Update Medicine

</button>


</form>


<a
href="manage_products.php"
class="back"
>

← Back to Manage Medicines

</a>


</div>


</body>

</html>