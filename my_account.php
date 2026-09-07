<?php 
 
 session_start();

 require_once './db_Config/config.php';

 // check user login

 if(!isset($_SESSION['username']))
{
    header('location: ./signin.php');
}

// get user data from database
$query = "SELECT * FROM User_info WHERE user_name = '{$_SESSION['username']}';";
$result = mysqli_query($Connection, $query);

if($result)
{
  $row = mysqli_fetch_assoc($result);

  $firstName = $row['first_name'];
  $lastName = $row['last_name'];
  $username = $row['user_name'];
  $phone = $row['phone_no'];
  $email = $row['email'];
  $password = $row['password'];

}

// update changes
if(isset($_POST['saveBtn']))
{
  $firstName = $_POST['F_name'];
  $lastName = $_POST['L_name'];
  $phone = $_POST['phone'];
  $email = $_POST['email'];

  $sql = "UPDATE user_info SET first_name = '$firstName', last_name = '$lastName', email = '$email', phone_no = '$phone' WHERE user_name = '" . $_SESSION['username'] . "'";
  $result = mysqli_query($Connection, $sql);

  if($result && mysqli_affected_rows($Connection) > 0)
    {
      $_SESSION['firstname'] = $firstName;
      header("Location: my_account.php");
      exit();
    }

}

//update password
if(isset($_POST['changePwBtn']))
{
  if($_POST['pwd'] == $password )
  {
    if($_POST['newPwd'] == $_POST['cnfmPwd'])
    $newpassword = $_POST['newPwd'];

    {
      $sql = "UPDATE user_info SET password = '$newpassword' WHERE  user_name = '" . $_SESSION['username'] . "'";

      $result = mysqli_query($Connection, $sql);

      if($result && mysqli_affected_rows($Connection) > 0)
        {
            header("Location: my_account.php");
            exit();
        }
    }
  }
}

//delete account
if(isset($_POST['deleteBtn']))
{
  $sql = "DELETE FROM user_info WHERE user_name = '" . $_SESSION['username'] . "';";
  $result = mysqli_query($Connection, $sql);

  if($result && mysqli_affected_rows($Connection) > 0)
  {

    header("Location: logout.php");
    exit();
  }
}

//profile pic upload
if(isset($_POST['addProfilePic']))
{
  if(isset($_FILES['profilePIC']) && $_FILES['profilePIC']['error'] === UPLOAD_ERR_OK)
    {
      $upload_name = $_FILES['profilePIC']['name'];
        
      //propic location
      $target_directory = "./Images/Profile_Pics/";
        
      // Move pic to location
      move_uploaded_file($_FILES['profilePIC']['tmp_name'],$target_directory . $upload_name);

      // save file name in database
      $query = "UPDATE user_info SET profilepic_url = '$upload_name' WHERE user_name = '" . $_SESSION['username'] . "'";
      $result = mysqli_query($Connection, $query);
      if ($result)
      {
        $_SESSION['profilePic_url'] = $upload_name;
        header("Location: my_account.php");
      } 
      else 
      {
        // js alert ekk danna
      }
    }
}

//profile pic
$baseProfilePicUrl = './Images/Profile_Pics/'; 
$profilePicUrl = isset($_SESSION['profilePic_url']) ? htmlspecialchars($baseProfilePicUrl.$_SESSION['profilePic_url']) : './Images/Profile_Pics/student-avatar-illustratio.jpg'; // Default profile picture

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="./CSS/index.css">
    <link rel="stylesheet" href="./CSS/my_account.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="./Images/Pharmacy X Icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./Images/Pharmacy X Icon.png">
</head>
<body>
    <?php include ("./header.php"); ?>

      <div class="my-account-content">
            <div class="edit-container">
            <div class="profile-pic">
                  <img src="<?php echo $profilePicUrl; ?>">
                </div> 
                  <div class="upload-profile">
                    <form action="my_account.php" method="POST" enctype="multipart/form-data">
                        <input type="file" name="profilePIC" class="profilePIC" accept=".jpg,.jpeg,.png" >
                        <button type="submit" name="addProfilePic" class="addProfileBtn">Add Picture</button>
                    </form>
                  </div>
                   <div class="background-img">
                    <img src="./Images/myaccount-background-image.png" alt="background-image">
                   </div>
                     <div class="delete-account-container">
                      <form action="my_account.php" method="POST">
                             <button type="submit" onclick="confirmDelete()" name="deleteBtn" class="delete-account-button">Delete Account</button>
                      </form>
                      
                     </div>
                </div>
        <div class="box">
        <div class="account-information-form-container">
               <form action="my_account.php" method="POST">
                  <div class="my-account-form">
                  <h3>Account Information</h3>
                     <div class="account-edit">
                        <div class="input-container">
                           <label>First Name</label>
                           <input type="text" name="F_name" value="<?php echo $firstName  ?>">
                        </div>
                        <div class="input-container">
                           <label>Last Name</label>
                           <input type="text" name="L_name" value="<?php echo $lastName ?>">
                        </div>
                     </div>
                     <div class="account-edit">
                         <div class="input-container">
                           <label>Username</label>
                           <input type="text" name="username" value="<?php echo $username  ?>" disabled >
                         </div>
                         <div class="input-container">
                           <label>Phone Number</label>
                           <input type="text"  name="phone" value="<?php echo $phone ?>">
                         </div>
                     </div>
                     <div class="account-edit">
                         <div class="input-container">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo $email ?>" >
                         </div>
                     </div>
                     <div class="save-changes-container">
                         <button type="submit" onclick="alert('Successfully changed')" name="saveBtn" class="save-changes-button" >Save Changes</button>
                     </div>
                  </div>
               </form>
            </div>
            <div class="change-password-form-container">
            <form action="my_account.php" method="post">
                 <div id="change-password-form">
                 <h3>Change Password</h3>
                 <div class="account-edit">
                    <div class="input-container">
                      <label>Current Password</label>
                      <input type="password" name="pwd"required>
                    </div>
                 </div>
                 <div class="account-edit">
                   <div class="input-container">
                     <label>New Password</label>
                     <input type="password" name="newPwd" class="newPassword"  required>
                   </div>
                   <div class="input-container">

                     <label>Confirm Password</label>
                     <input type="password" name="cnfmPwd" class="confirmPassword" onkeyup="checkPassword()" required>
                  </div>
                 </div>
                 
                 <div class="display-error"></div>
                 <div class="save-changes-container">
                     <button type="submit" name="changePwBtn" class="save-changes-button" >Save Changes</button>
                     <p id="error-message" style="color: red;"> </p>
                 </div>
                 </div>
            </form>
          </div>
        </div>
     </div>
    
     
    <div class="inbox-container">
    <h2 class="inbox"><b>Inbox</b></h2>
            <div class="inbox-content">
                <table>
                  <tr>
                    <th>Message ID</th>
                    <th>Message</th>
                    <th>Reply</th>
                  </tr>
                  <?php 
                      $sql = "SELECT * FROM Messages WHERE response_text IS NOT NULL AND user_name='" . $_SESSION['username'] . "';";
                      $result = mysqli_query($Connection, $sql);

                      if(mysqli_num_rows($result) > 0)
                      {
                        while($row = $result->fetch_assoc()) 
                        {
                          $messageID = $row['message_id'];
                          $message = $row['message_text'];
                          $reply = $row['response_text'];

                        echo" <tr>
                              <td>$messageID</td>
                              <td>$message</td>
                              <td>$reply</td>
                         </tr>";

                        }
                      }
                      else
                      {
                        echo "<tr>";
                        echo "<td colspan='3'>No messages found</td>";
                        echo "</tr>";
                        echo "<tr>";
                        echo "<td colspan='3'>No messages found</td>";
                        echo "</tr>";
                        echo "<tr>";
                        echo "<td colspan='3'>No messages found</td>";
                        echo "</tr>";
                        echo "<tr>";
                        echo "<td colspan='3'>No messages found</td>";
                        echo "</tr>";
                      }

                  ?>
                  
                  
                </table>
            </div>
     </div>

    
    <script src="./js/my_account.js"></script>
    <?php include ("./footer.php"); ?>
</body>
</html>