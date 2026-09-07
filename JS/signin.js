//by IT23539990 Marasinghe M A M N

function showPassword()
{
    var passwordField = document.getElementById("password");
    var showPasswordCheckbox = document.getElementById("showpw");
    if(showPasswordCheckbox.checked)
    {
        passwordField.type = "text";
    }
    else
    {
        passwordField.type = "password";
    }
}