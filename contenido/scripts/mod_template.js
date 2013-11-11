
//changes the "delete template" link to always match the currently selected template in the dropdown menu
//instead of the currently loaded one
$(function() {
    $(".fileChooser").change(function() {
        var link = document.getElementById("deleteLink").href;
        var newLink = link.substr(0, link.lastIndexOf("=") + 1) + $(".fileChooser option:selected").val();
        document.getElementById("deleteLink").href = newLink;
    });
});