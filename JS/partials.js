//By Moditha IT23539990

function dropdownmenu() 
{
    var dropdown = document.getElementById("prof_dropdown");
    if (dropdown.style.display === "block") 
    {
        dropdown.style.display = "none";
    } 
    else 
    {
        dropdown.style.display = "block";
    }
}

//Close the dropdown if clicks outside
window.onclick = function(event) 
{
    if (!event.target.matches('.username')) 
    {
        var dropdown = document.getElementById("prof_dropdown");
        if (dropdown.style.display === "block") 
        {
            dropdown.style.display = "none";
        }
    }
}
