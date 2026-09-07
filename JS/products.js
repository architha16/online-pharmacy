// Sankalpa M M G H C
// IT23538832

//Create Image Slider
const images = [
  'Images/product-image-slider-1.jpg', 
  'Images/product-image-slider-2.jpg',
  'Images/product-image-slider-3.jpg' 
];

const heading = [
  'Quick, Easy, and Affordable <br> Healthcare Solutions.',
  'Your Digital Pharmacy for Modern Living',
  'Simplifying Your Health Journey, One Order at a Time'
];

let countIndex = 0;
const imageElement = document.getElementById('main-image');

function changeImage() {
    countIndex = (countIndex + 1) % images.length;
    imageElement.src = images[countIndex]; 
    document.querySelector('.main-heading').innerHTML = heading[countIndex];
}
setInterval(changeImage, 3000);
