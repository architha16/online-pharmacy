<?php 
//Deshan G G D IT23539372

session_start();

//check if the user is logged in
if(isset($_SESSION['username']))
{
    //check if the user is admin
    if($_SESSION['user_type']=='Admin')
    {
        require_once './db_Config/config.php';
    }
    else if($_SESSION['user_type']=='Manager')
    {
        header('location: ./manager_DB.php');
    }
    else
    {
        header('location: ./index.php');
    }
}
else
{
    header('location: ./index.php');
}


//Handle the form for creat and update
if($_SERVER['REQUEST_METHOD']==='POST')
{
    if(isset($_POST['submitbtn']))
    {
        $product_name = $_POST['product_name'];
        $price = $_POST['price'];
        $expire_date = $_POST['expire_date'];
        $quantity = $_POST['quantity'];
        $description = $_POST['description'];
        $img_url = "test.jpg";

        if(isset($_FILES['prdct_IMAGE']) && $_FILES['prdct_IMAGE']['error'] === UPLOAD_ERR_OK)
        {
            $upload_name = $_FILES['prdct_IMAGE']['name'];
            
            // target location
            $target_directory = "./Images/product-icons/";
            
            // Move file
            move_uploaded_file($_FILES['prdct_IMAGE']['tmp_name'],$target_directory . $upload_name);

            // save file name in database
            $img_url = $upload_name;
        }

        $sql = "INSERT INTO Products (product_name, product_description, price, stock_quantity, image_url, expire_date ) VALUES ('$product_name', '$description', '$price', '$quantity', '$img_url', '$expire_date')";
    
        $result = mysqli_query($Connection, $sql);

        if($result)
        {
            echo " <script> alert('Product added!'); </script> ";
            header('location: addDelete_Product.php');
        }
        else
        {
            echo " <script> alert('Error!'); </script> ";
            header('location: addDelete_Product.php');
        }

    }
}

//Handle the form for delete
if(isset($_POST['deletprdct']))
{
    $product_id = $_POST['product_id'];

    $sql = "DELETE FROM Products WHERE product_id = '$product_id'";
    $result = mysqli_query($Connection, $sql);

    if($result)
    {
        echo " <script> alert('Product deleted!'); </script> ";
        header('location: addDelete_Product.php');
    }
    else
    {
        echo " <script> alert('Error!'); </script> ";
        header('location: addDelete_Product.php');
    }
}

//Handle the form for edit
if(isset($_POST['editprdct']))
{
    $product_id = $_POST['product_id'];
    $qty = $_POST['qtyedit'];
    $price = $_POST['Priceedt'];

    $sql = "UPDATE Products SET stock_quantity = '$qty', price = '$price' WHERE product_id = '$product_id'";
    $result = mysqli_query($Connection, $sql);

    if($result)
    {
        echo " <script> alert('Product updated!'); </script> ";
        header('location: addDelete_Product.php');
    }
    else
    {
        echo " <script> alert('Error!'); </script> ";
        header('location: addDelete_Product.php');
    }
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="./CSS/index.css">
    <link rel="stylesheet" href="./CSS/addDelete_Product.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="./Images/Pharmacy X Icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./Images/Pharmacy X Icon.png">
    <link rel="stylesheet" href="../CSS/addDelete_Product.css">
    <link rel="stylesheet" href="./CSS/index.css">
</head>
<body>
    <?php include ("./header.php"); ?>

    <div class="product-form">
        <h2>Product Add</h2>

        <div class="add-product-form">
            <div class="add-product-form-container">
                <form action="addDelete_Product.php" method ="POST" enctype="multipart/form-data">
                    
                    <div class="add-porduct-row">
                        <input type="text" class="add-products-inputs product-name" placeholder="Product Name" name="product_name" onkeydown="checkProductDetails();">
                        <input type="text" class="add-products-inputs product-name-input product-price" placeholder="Price" name="price" onkeydown="checkProductDetails();">
                    </div>

                    <div class="add-product-row">
                        <input type="date" class="add-products-inputs" placeholder="Expire Date" name="expire_date" >
                        <input type="text" class="add-products-inputs" placeholder="Quantity" name="quantity" >
                    </div>
                    <div class="add-product row">
                        <input type="file" class="add-product-inputs choose-file-input" name="prdct_IMAGE">
                    </div>
                    <div class="add-product-row description_box">
                        <textarea name="description" placeholder="Description" cols="55" rows="4" ></textarea>
                    </div>
                    <div class="error-content"></div>
                    <div class="submit-button-container">
                        <button class="submit-button" name="submitbtn" >Submit</button>
                    </div>

                </form>
            </div>
        
        </div>
    </div>
    
    <div class="product-container">
        <div class="product-heading">
            <h2 class="product-list-heading">Products</h2>
        </div>

        <div class="products-list-container">
            <table>
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Action</th>
                    <th>Action</th>
                </tr>

                <?php 
            
                    $sql = "SELECT * FROM Products;";
                    $result = mysqli_query($Connection, $sql);

                    if(mysqli_num_rows($result) > 0)
                    {
                        while($row = mysqli_fetch_assoc($result))
                        {
                            $pID = $row['product_id'];
                            $pName = $row['product_name'];
                            $qty = $row['stock_quantity'];
                            $P_price = $row['price'];
                            
                            echo "<tr>
                                    <td>$pID</td>
                                    <td>$pName</td>
                                    <form action='addDelete_Product.php' method='POST'>
                                    <td><input type='number' value='$qty' min='0' name='qtyedit'></td>
                                    <td><input type='text' value='$P_price' name='Priceedt'></td>
                                    <input type='hidden' name='product_id' value='$pID'>
                                    <td><button type='submit' name='editprdct' class='edit-product-button'>Edit</button></td>
                                    <td><button type='submit' name='deletprdct' class='delete-product-button'>Delete</button></td>
                                    </form>
                                </tr>";
                        }
                    }
                    else
                    {
                        echo "<tr>";
                        echo "<td colspan='6'>No products found</td>";
                        echo "</tr>";
                    }
                    
                    $Connection->close();

                ?>

            </table>
        </div>
    </div>

    <?php include ("./footer.php"); ?>
    <script src="./JS/addDelete_Product.js"></script>
</body>
</html>