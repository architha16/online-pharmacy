<?php
session_start();
// Medhani W A P IT23569522
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <link rel="stylesheet" href="./CSS/index.css">
    <link rel="stylesheet" href="./CSS/aboutUs.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="./Images/Pharmacy X Icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./Images/Pharmacy X Icon.png">
</head>
<body>
    <?php include ("./header.php"); ?>
    
    <div class="About">
    <div class="image">
      <img src="./Images/AboutUs.png" alt="About Us Image">
    </div>
    
    <div class="text">
      <h1>About Us</h1>
      <br>
      <p>Welcome to<b> PharmacyX</b>, your trusted partner in health and wellness. 
        Our mission is to provide you with high-quality, accessible, and affordable healthcare solutions tailored to meet your unique needs.
        PhamacyX founded in 2024 and it is committed to offering an extensive range of medications, healthcare products, and wellness services all from the comfort of your home.
         With a focus on safety, convenience, and customer care. We are dedicated to helping you live healthier and happier lives.</p>
         <br>
      <button id="learnMoreBtn">Learn More</button>
      
      <div id="hidden-content" class="hidden-content">
      
      <a href="products.php">Check our product</a><br>
      <a href="t&c.php">Check t&c</a><br>
      <a href = "privacyPolicy.php">Check Privacy Policy</a>
          
        </div>
        
    </div>
  </div>

  <?php include ("./footer.php"); ?>
  <script src="./JS/aboutUs.js"></script>
  
</body>
</html>