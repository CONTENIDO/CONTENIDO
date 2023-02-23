/*
    jQuery Version:             jQuery 1.3.2
    Plugin Name:                aToolTip V 1.0
    Plugin by:                  Ara Abcarians: http://ara-abcarians.com
    License:                    aToolTip is licensed under a Creative Commons Attribution 3.0 Unported License
                                Read more about this license at --> http://creativecommons.org/licenses/by/3.0/
    Modified:                   Murat Purc <murat@purc>, 2010-01-28: Position clickable tooltip on right side,
                                                                     remove previous opened tooltip
    Modified:                   Murat Purc <murat@purc>, 2019-12-21: Option to close opened tooltip on outside
                                                                     click.

Creates following node:
-----------------------
<div class="aToolTip">
    <div class="aToolTipInner">
        <p class="aToolTipContent"></p>
        Content
        <a alt="close" href="#" class="aToolTipCloseBtn">close</a>
    </div>
</div>

*/

(function($, jQuery) {

(function($) {
    $.fn.aToolTip = function(options) {

        // setup default settings
        var defaults = {
            clickIt: false,
            closeTipBtn: 'aToolTipCloseBtn',
            fixed: false,
            inSpeed: 400,
            outSpeed: 100,
            tipContent: '',
            toolTipClass: 'aToolTip',
            xOffset: 0,
            yOffset: 0,
            removeOnOutsideClick: false
        },

        // This makes it so the users custom options overrides the default ones
        settings = $.extend({}, defaults, options);

        // If setting to remove tooltip on outside click is set, register proper event handler. but only once!
        if (settings.removeOnOutsideClick && !$.fn.aToolTip.documentClickEventHandlerRegistered) {
            $.fn.aToolTip.documentClickEventHandlerRegistered = true;
            $(document).click(function (e) {
                if ($(e.target).hasClass(settings.toolTipClass) || $(e.target).closest('.' + settings.toolTipClass).length > 0) {
                    return;
                }
                // Fade out
                $('.' + settings.toolTipClass).stop().fadeOut(settings.outSpeed, function(){$(this).remove();});
            });
        }

        return this.each(function() {
            var obj = $(this);
            // Decide weather to use a title attr as the tooltip content
            if (obj.attr('title') && !settings.tipContent) {
                // set the tooltip content/text to be the obj title attribute
                var tipContent = obj.attr('title');
            } else {
                // if no title attribute set it to the tipContent option in settings
                var tipContent = settings.tipContent;
            }

            // check if obj has a title attribute and if click feature is off
            if(tipContent && !settings.clickIt){
                // Activate on hover
                obj.hover(function(el){
                    obj.attr({title: ''});
                    $('body').append("<div class='"+ settings.toolTipClass +"'><div class='"+ settings.toolTipClass +"Inner'><p class='aToolTipContent'>"+ tipContent +"</p></div></div>");
                    $('.' + settings.toolTipClass).css({
                        position: 'absolute',
                        display: 'none',
                        zIndex: '50000',
                        top: (obj.offset().top - $('.' + settings.toolTipClass).outerHeight() - settings.yOffset) + 'px',
                        left: (obj.offset().left + obj.outerWidth() + settings.xOffset) + 'px'
                    })
                    .stop().fadeIn(settings.inSpeed);
                },
                function(){
                    // Fade out
                    $('.' + settings.toolTipClass).stop().fadeOut(settings.outSpeed, function(){$(this).remove();});
                });
            }

            // Follow mouse if fixed is false and click is false
            if(!settings.fixed && !settings.clickIt){
                obj.mousemove(function(el){
                    $('.' + settings.toolTipClass).css({
                        top: (el.pageY - $('.' + settings.toolTipClass).outerHeight() - settings.yOffset),
                        left: (el.pageX + settings.xOffset)
                    })
                });
            }

            // check if click feature is enabled
            if(tipContent && settings.clickIt){
                // Activate on click
                obj.click(function(el){
                    if (!settings.tipContent) {
                        obj.attr({title: ''});
                    }

//                    $('.' + settings.toolTipClass).remove();
                    $('.' + settings.toolTipClass).stop().fadeOut(settings.outSpeed, function(){$(this).remove();});

                    $('body').append("<div class='"+ settings.toolTipClass +"'><div class='"+ settings.toolTipClass +"Inner'><p class='aToolTipContent'>"+ tipContent +"</p><a class='"+ settings.closeTipBtn +"' href='#' alt='close'>close</a></div></div>");
                    $('.' + settings.toolTipClass).css({
                        position: 'absolute',
                        display: 'none',
                        zIndex: '50000',
//                        top: (obj.offset().top - $('.' + settings.toolTipClass).outerHeight() - settings.yOffset) + 'px',
//                        left: (obj.offset().left + obj.outerWidth() + settings.xOffset) + 'px'
                        top: (obj.offset().top - settings.yOffset) + 'px',
                        left: (obj.offset().left + obj.outerWidth() + settings.xOffset) + 'px'
                    })
                    .fadeIn(settings.inSpeed);
                    // Click to close tooltip
                    $('.' + settings.closeTipBtn).click(function(){
                        $('.' + settings.toolTipClass).fadeOut(settings.outSpeed, function(){$(this).remove();});
                        return false;
                    });
                    return false;
                });
            }

        }); // END: return this

        // returns the jQuery object to allow for chainability.
        return this;
    };
})(jQuery);

})(Con.$, Con.$);
