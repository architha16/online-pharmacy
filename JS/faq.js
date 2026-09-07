//By Medhani it23569522

document.addEventListener('DOMContentLoaded', function() {

    const faqQuestions = document.querySelectorAll('.faq-question');

//Loop through each question
    faqQuestions.forEach(question => { 
        question.addEventListener('click', function() {

            const answer = this.nextElementSibling;

// check answer is currently displayed
            if (answer.style.display === 'block') {
                answer.style.display = 'none';
            } else {
                answer.style.display = 'block';
            }
        });
    });
});

