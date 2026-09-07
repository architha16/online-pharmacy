<?php
//Medhani W A P  IT23569522

session_start();
$result;
if(isset($_POST['Submit_frm']))


 
{
    $f_Name = $_POST['firstName'];
    $l_Name = $_POST['lastname'];
    $user_Name = $_POST['username'];
    $e_Mail = $_POST['email'];
    $P_word = $_POST['pword'];
    $re_entr_pwd = $_POST['confirmPassword'];
    $acc_status = "Active";
    $user_type = "Customer";

    require_once './db_Config/config.php';

    $sql = "SELECT user_name FROM user_info WHERE user_name = '$user_Name'; ";

    $result = mysqli_query($Connection, $sql);

    if(mysqli_num_rows($result) > 0)
    {
        echo "<script> alert('!Username not available'); </script>" ;
    }

    else if (empty($f_Name)|| empty($l_Name)|| empty($user_Name)||empty($e_Mail)||empty($P_word)||empty($re_entr_pwd))
    {   
        echo"<script> alert('Fill All Fields!'); </script>";
           
    }

    else if ($P_word !== $re_entr_pwd)
    {   
        echo "<script>alert('!Passwords do not match.');</script>";
       
    }

    else{

    $sql ="INSERT INTO user_info(user_name,first_name,last_name,email,password,acc_status,user_type)
    VALUES ('$user_Name','$f_Name','$l_Name','$e_Mail','$P_word' ,'$acc_status','$user_type');";
       

       $result = mysqli_query($Connection, $sql);

       if($result)
       {

            echo "<script> alert('Welcome to PhamacyX'); </script>";
            header('location: signin.php');
       }
       else
       {
            echo " <script> alert('Error!'); </script> ";
       }

    }

    
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="./CSS/index.css">
    <link rel="stylesheet" href="./CSS/register.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="./Images/Pharmacy X Icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./Images/Pharmacy X Icon.png">
</head>
<body>
    
<div class="container">
        <h2>Registration</h2>

        <form action="register.php" method="POST" id="registrationForm">
            <div class="form-row">
                <div class="form-column">
                    
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="firstName"  placeholder="Enter Your Fist Name" >
                    </div>

                    <div class="form-group">
                        <label for="username">User name</label>
                        <input type="text" id="username" name="username"  placeholder="Enter Your Username">
                    </div>
                    
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="pword"  placeholder="Minimum of 8 characters" >
                    </div>

                    
                </div>
                
                <div class="form-column">

                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname"  placeholder="Enter Your Last Name" >
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email"  name="email"  placeholder="mail@example.com" >
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Minimum of 8 characters" >
                    </div>
                </div>

            </div>


            <br>

            <button type="submit" name="Submit_frm" id="submit" >Create Account</button>   

        </form>
        
    </div>
    
</body>
</html>