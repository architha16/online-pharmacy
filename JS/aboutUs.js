//By Medhani W A P IT23569522

const learnMoreBtn = document.getElementById('learnMoreBtn');
const hiddenContent = document.getElementById('hidden-content');

//  content is not visible
hiddenContent.style.display = "none";

learnMoreBtn.addEventListener('click', function() {

    if (hiddenContent.style.display === "none") {
       
        hiddenContent.style.display = "block";
        
        learnMoreBtn.textContent = "Show Less";
    } else {
       
        hiddenContent.style.display = "none";
        
        learnMoreBtn.textContent = "Learn More";
    }
});
