
/**
 * @param {jQuery} $
 */
(function($) {

    /**
     * Define behaviour for "self-labeled" input fields.
     *
     * Formfields in some places have no label on their own but contain their
     * label text as value. These label texts should vanish when the formfield
     * gains focus and should be restored if it looses its focus and there is no
     * user input.
     */
    $.fn.self_labeled_input = function() {
        var $input = this;
        $input.focus(function() {
            // remove label so user can enter value
            if ($input.val() == $input.attr("title")) {
                $input.val("");
            }
        }).focusout(function() {
            // restore label if user has entered nothing
            if (0 === $input.val().trim().length) {
                $input.val($input.attr("title"));
            }
        }).val($input.attr("title"));
    };

    /**
     * Define behaviour for "link_section" elements.
     *
     * In a link-section every child will be clickable and referes to its containing anchor
     * Whole list rows will become links this way and also will be marked as such by a pointing
     * cursor.
     *
     */
    $.fn.link_section = function() {
        this.children().css({"cursor": "pointer"}).click(function() {
            window.location = $(this).find("a").attr("href");
        });
    };

})(jQuery);

$(function() {

    /* ----- HEADER SEARCH FIELD ----- */

    $("#header #navigation_header #search_term").on("focus", function(e) {
        $search_container = $(this).parent();
        if (!$search_container.hasClass("active")) {
            $search_container.addClass("active")
                    .animate({"width": "135px", "paddingLeft": "30px"}, 500, "swing");
        }
    });
    $("#header #navigation_header #search_term").on("blur", function(e) {
        $search_container = $(this).parent();
        $(this).val("");
        if ($search_container.hasClass("active")) {
            $search_container.animate({"width": "24px", "paddingLeft": "2px"}, 500, "swing", function() {
                $search_container.removeClass("active");
            });
        }
    });


    /* ----- SEARCH RESULT PAGE ----- */

    $("#search_result").link_section();
    $("#searchterm.full").self_labeled_input();


    /* ----- SLIDER ----- */
    //fix for safer sliding in IE 7/8
    $(".slider .images li").not(".active").css({"opacity": "0"});

    var slider = window.setInterval(function() {
        
        if ($(window).width() < 769) {
            return false;
        }

        $('.slider').trigger('slider.next');
    }, 7000);

    $(".slider").on('slider.next', function() {

        var _ = $(this);

        if (_.hasClass('animate')) {
            return false;
        }

        _.addClass('animate');

        var index = $(".slider .images li.active").index();
        $(".slider .pagination li a").removeClass("active");
        $(".slider .images li:eq(" + index + ")").animate({"opacity": "0"}, 500, function() {
            $(this).removeClass("active");
        });
        if ((index + 1) == $(".slider .images li").length) {
            $(".slider .images li:eq(0)").animate({"opacity": "1"}, 900, function() {
                $(this).addClass("active");
                $(".slider .pagination li:eq(0) a").addClass("active");
                _.removeClass('animate');
            });
        } else {
            $(".slider .images li:eq(" + (index + 1) + ")").animate({"opacity": "1"}, 900, function() {
                $(this).addClass("active");
                $(".slider .pagination li:eq(" + (index + 1) + ") a").addClass("active");
                _.removeClass('animate');
            });
        }
    });

    $(".slider").on('slider.prev', function() {

        var _ = $(this);

        if (_.hasClass('animate')) {
            return false;
        }

        _.addClass('animate');

        var index = $(".slider .images li.active").index();
        $(".slider .pagination li a").removeClass("active");
        $(".slider .images li:eq(" + index + ")").animate({"opacity": "0"}, 500, function() {
            $(this).removeClass("active");
        });
        if ((index - 1) == $(".slider .images li").length) {
            $(".slider .images li:last").animate({"opacity": "1"}, 900, function() {
                $(this).addClass("active");
                $(".slider .pagination li:last a").addClass("active");
                _.removeClass('animate');
            });
        } else {
            $(".slider .images li:eq(" + (index - 1) + ")").animate({"opacity": "1"}, 900, function() {
                $(this).addClass("active");
                $(".slider .pagination li:eq(" + (index - 1) + ") a").addClass("active");
                _.removeClass('animate');
            });
        } 
    });

    $(".slider").mouseenter(function() {
        clearTimeout(slider);
    });

    //Create pagination
    if ($(".slider .images li").length > 1) {
        for (x = 1; x <= $(".slider .images li").length; x++) {
            $(".slider .pagination").append('<li><a href="">' + x + '</a></li>');
        }
        $(".slider .pagination").css({"marginLeft": "-" + ($(".slider .pagination").width() / 2) + "px"});
        $(".slider .pagination li:eq(0) a").addClass("active");
    }

    //Navigate through pagination
    $(".slider .pagination li").delegate("a", "click", function(e) {
        e.preventDefault();
        var old = $(".slider .pagination li a.active").parent().index(),
            next = $(this).parent().index();
        $(".slider .images li:eq(" + old + ")").animate({"opacity": "0"}, 500, function() {
            $(".slider .pagination li:eq(" + old + ") a").removeClass("active");
        });
        $(".slider .images li:eq(" + (next) + ")").animate({"opacity": "1"}, 900, function() {
            $(".slider .pagination li:eq(" + next + ") a").addClass("active");
            $(".slider .images li").removeClass('active');
            $(".slider .images li:eq(" + (next) + ")").addClass('active');
        });

    });

    var maxHeight = 0;
    $(".column_quarter.text div.col").each(function() {
        if (maxHeight < $(this).height()) {
            maxHeight = $(this).height();
        }
    });

    $(".column_quarter.text div.col").css('height', maxHeight + 20 + 'px');

    window.prevMaxColHeight = maxHeight - 20;

    $(window).on('resize', function() {
        if ($(window).width() > 768 && !$('body').hasClass('init-col-d')) {

            var maxHeight = 0;

            $(".column_quarter.text div.col").each(function() {
                var h = $(this).height();
                $(this).css('height', 'auto');

                if (maxHeight < $(this).height()) {
                    maxHeight = $(this).height();
                } else {
                    $(this).css('height', 'h');
                }
            });

            if (maxHeight > window.prevMaxColHeight) {
                $(".column_quarter.text div.col").css('height', maxHeight + 20 + 'px');
                $('body').addClass('init-col-d');
            }
        }
    });

    //make teaser image clickable
    $('.teaser_img').click(function() {
        var link = $(this).children("p").children("a").attr('href');
        document.location.href = link;
    });
});
