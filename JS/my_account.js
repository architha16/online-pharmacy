function checkPassword(){
    var password = document.querySelector('.newPassword').value;
    var passwordConfirm = document.querySelector('.confirmPassword').value;

    if(password === passwordConfirm){
        document.querySelector('.display-error').innerHTML = "Password Match";
        document.querySelector('.display-error').style.color = 'blue';
    }
    else{
        document.querySelector('.display-error').innerHTML = "Password mismatch";
        document.querySelector('.display-error').style.color = 'red';
    }
    if(passwordConfirm === '')
    {
        document.querySelector('.display-error').innerHTML = "";
    }

}



function confirmDelete() {
   if (confirm("Are you sure you want to delete this account?")) {
       // Code to delete the account
       alert("Account deleted");
   } else {
       // User canceled the deletion
       alert("Account not deleted");
   }
}