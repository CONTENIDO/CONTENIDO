
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
	/* ----- table zebra stripes ----- */
	$(".table tbody tr:odd").addClass("even");
	
	

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
            $search_container.animate({"width": "24px", "paddingLeft": "0"}, 500, "swing", function() {
                $search_container.removeClass("active");
            });
        }
    });


    /* ----- SEARCH RESULT PAGE ----- */

    $("#search_result").link_section();
    $("#searchterm.full").self_labeled_input();

    /* ----- GALERY ----- */

    var imgPerPage = 6,
            activePage,
            maxPage = 0;

    //Setting Pagination
    function iniPagination() {
        var images = 0;
        $(".galery .source li").each(function() {
            images++;
        });
        maxPage = Math.ceil(images / imgPerPage);
        for (x = 1; x <= maxPage; x++) {
            $(".galery .pagination").append('<li><a href="">' + x + '</a></li>');
        }
        // @fixme Translate this
        $(".galery .pagination").append('<li><a href="">Weiter</a></li>');
    }
    iniPagination();

    //Loading function for every single page, with limitation
    function loadGaleryPage(page) {
        activePage = page;

        var sliceFrom = (page - 1) * imgPerPage,
                sliceTo = page * imgPerPage;
        $(".galery .slider").html("");
        $(".galery .source li").slice(sliceFrom, sliceTo).each(function() {
            var $a = $(this).children("a");
            $(".galery .slider").append('<li><a href="' + $a.attr("href") + '" rel="' + $a.attr("rel") + '" title="' + $a.attr("title") + '"><img src="' + $a.text() + '" alt="" style="' + $a.attr("style") + '" /></a></li>');
        });
        $(".galery .slider li:odd").addClass("odd");

        //Setting active pagination element
        $(".galery .pagination li a.active").removeClass("active");
        $(".galery .pagination li:eq(" + page + ") a").addClass("active");
    }

    //initial loading the first page
    loadGaleryPage(1);


    $(".galery .pagination li a").not(".disabled").click(function(e) {
        e.preventDefault();
        var page;
        if ($(this).parent().index() == 0) {
            page = activePage - 1;
        } else if ($(this).parent().index() == $(".galery .pagination li").length - 1) {
            page = activePage + 1;
        } else {
            page = $(this).parent().index();
        }
        if (page != 0 && page <= maxPage) {
            loadGaleryPage(page);
        }
    });
    /* ----- GALERY LIGHTBOX ----- */
    jQuery(window).load(function() {
        $(".galery .slider").delegate("a", "click", function(e) {
            e.preventDefault();
            var left = "", right = "",
                count = $(this).parent().parent().find("li").length;

            if (count > 1) {
                var index = $(this).parent().index();
                if (index > 0) {
                    left = '<a href="' + (index - 1) + '" class="prev_image">&laquo;</a>';
                }
                if (index < (count - 1)) {
                    right = '<a href="' + (index + 1) + '" class="next_image">&raquo;</a>';
                }
            }
            $(".galery .lightbox").html(left + right + '<img src="' + $(this).attr("href") + '" alt="" /><p>' + $(this).attr("rel") + ': ' + $(this).attr("title") + '</p>').dialog({
                modal: true,
                width: "auto",
                height: "auto",
                closeText: "X",
                position: {
                    my: "center",
                    at: "center",
                    of: window
                },
                close: function() {
                    $(".galery").prepend('<div class="lightbox"></div>');
                }
            });
        });

        $("body").delegate(".lightbox a", "click", function(e) {
            e.preventDefault();
            var index = parseInt($(this).attr("href"));
            $(".lightbox").dialog("close");
            $('.galery .slider li:eq(' + index + ') a').click();
        });

    });


    /* ----- SLIDER ----- */

    var slider = window.setInterval(function() {
        var index = $(".slider .images li.active").index();
        $(".slider .pagination li a").removeClass("active");
        $(".slider .images li:eq(" + index + ")").animate({"opacity": "0"}, 500, function() {
            $(this).removeClass("active");
        });
        if ((index + 1) == $(".slider .images li").length) {
            $(".slider .images li:eq(0)").animate({"opacity": "1"}, 900, function() {
                $(this).addClass("active");
                $(".slider .pagination li:eq(0) a").addClass("active");
            });
        } else {
            $(".slider .images li:eq(" + (index + 1) + ")").animate({"opacity": "1"}, 900, function() {
                $(this).addClass("active");
                $(".slider .pagination li:eq(" + (index + 1) + ") a").addClass("active");
            });
        }
    }, 7000);

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

    //make teaser image clickable
    $('.teaser_img').click(function() {
        var link = $(this).children("p").children("a").attr('href');
        document.location.href = link;

    });
});
