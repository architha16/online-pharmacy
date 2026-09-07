function checkProductDetails(){
    const productName = document.querySelector('.product-name').value;
    const productPrice = document.querySelector('.product-price').value;

    if((productName === '') || (productPrice === '')){
        document.querySelector('.error-content').innerHTML = "Enter both Name and Price";
        document.querySelector('.error-content').style.color = "Red";
    }


    if(!(productName==='') && !(productPrice==='')){
        document.querySelector('.error-content').innerHTML = "Product Name and Price Added";
        document.querySelector('.error-content').style.color= "Green";
    }
}
