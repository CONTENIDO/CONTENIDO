
(function(Con, $) {
    $(function() {
        $('.tooltip').tipsy({
            gravity : $.fn.tipsy.autoWE,
            html : true
        });
        $('.tooltip-north').tipsy({
            gravity : 'n',
            html : true
        });
    });
})(Con, Con.$);

