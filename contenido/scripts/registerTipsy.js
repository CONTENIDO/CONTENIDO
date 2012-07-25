

function registerTipsy()
{
    $(".tooltip").tipsy({gravity: $.fn.tipsy.autoWE, html: true });
}



$(document).ready(registerTipsy);
