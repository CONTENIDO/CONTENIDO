$(document).ready(function(){

    if ($('input[type="checkbox"]').attr('checked') === 'checked') {
            disableInput();
    }

    $('input[type="checkbox"]').on('change', function(){
        if ($(this).attr('checked') === 'checked') {
            disableInput();
        }
    });

    function disableInput() {
        $('input[type="text"]').attr('disabled', 'disabled');
    }
});