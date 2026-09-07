//Sankalpa M M G H C
//IT23538832


//Check Password is matched with the Re-enter password
function checkPassword(){

  var password = document.querySelector('.enter-password').value;
  var passwordConfirm = document.querySelector('.re-enter-password').value;

  if(password === passwordConfirm){
    document.querySelector('.display-error').innerHTML = "Password Match";
    document.querySelector('.display-error').style.color = 'green';
  }else{
    document.querySelector('.display-error').innerHTML = "Password does not match";
    document.querySelector('.display-error').style.color = 'red';
  }

  if(passwordConfirm === ''){
    document.querySelector('.display-error').innerHTML = "";
  }
}