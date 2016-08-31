/* ----- GALLERY ----- */

var imgPerPage = 6,
	activePage,
	maxPage = 0;

//Setting Pagination
function iniPagination() {
	var images = 0;
	$(".gallery .source li").each(function() {
		images++;
	});

	maxPage = Math.ceil(images / imgPerPage);
	// no pagination necessary if only 1 page is shown
	if (2 > maxPage) {
		// take back link, remove it from dom
		$(".gallery .pagination li a#back").parent().remove();

		// take forward link, remove it from dom
		$(".gallery .pagination li a#forward").parent().remove();

		return;
	}

	// take forward link, remove it from dom
	var forwardNode = $(".gallery .pagination li a#forward").parent();
	forwardNode.remove();
	// append links for each pagination page
	for (x = 1; x <= maxPage; x++) {
		$(".gallery .pagination").append('<li><a class="' + x + '" href="">' + x + '</a></li>');
	}
	// re-append forward link to the end of pagination elements
	$(".gallery .pagination").append(forwardNode);

	// remove back and forward buttons if no pages are on page at all
	if (0 === maxPage) {
		$(".gallery .pagination li").remove();
	}
}
iniPagination();

//Loading function for every single page, with limitation
function loadGalleryPage(page) {
	activePage = page;

	var sliceFrom = (page - 1) * imgPerPage,
		sliceTo = page * imgPerPage;
	$(".gallery .slider").html("");
	$(".gallery .source li").slice(sliceFrom, sliceTo).each(function() {
		var $a = $(this).children("a");
		$(".gallery .slider").append('<li><a href="' + $a.attr("href") + '" rel="' + $a.attr("rel") + '" title="' + $a.attr("title") + '"><img src="' + $a.text() + '" alt="" style="' + $a.attr("style") + '" /></a></li>');
	});
	$(".gallery .slider li:odd").addClass("odd");

	//Setting active pagination element
	$(".gallery .pagination li a.active").removeClass("active");
	$(".gallery .pagination li a." + page).addClass("active");
}

//initial loading the first page
loadGalleryPage(1);


$(".gallery .pagination li a").not(".disabled").click(function(e) {
	e.preventDefault();

	var page;
	if ($(this).parent().index() == 0) {
		page = activePage - 1;
	} else if ($(this).parent().index() == $(".gallery .pagination li").length - 1) {
		page = activePage + 1;
	} else {
		page = $(this).parent().index();
	}
	if (page != 0 && page <= maxPage) {
		loadGalleryPage(page);
	}
});
/* ----- GALLERY LIGHTBOX ----- */
jQuery(window).load(function() {
	var dialogPosition = {
		my: "center",
		at: "center",
		of: window,
		collision: "fit"
	};

	$(".gallery .slider").delegate("a", "click", function(e) {
		e.preventDefault();
		var left = "", right = "";
		// number of pictures on all pages in total
		var count = $(".gallery .source li").length;
		// currently shown page in gallery, first page is 1
		var curPage = parseInt($(".pagination .active").text());

		if (count > 1) {
			// index of picture, starts with 0
			var index = $(this).parent().index();
			// add current page offset to index
			index += (curPage -1) * imgPerPage;
			if (index > 0) {
				left = '<a href="' + (index - 1) + '" class="prev_image">&laquo;</a>';
			}
			if (index < (count - 1)) {
				right = '<a href="' + (index + 1) + '" class="next_image">&raquo;</a>';
			}
		}

		var colon = '';
		if ($(this).attr("rel") != '' && $(this).attr("title") != '') {
			colon = ':';
		}


                /* distinguish between different browsers
                 * needed for some stupid ie/chrome incomatibilities */
                function isIE () {
                  var myNav = navigator.userAgent.toLowerCase();
                  return (myNav.indexOf('msie') != -1) ? parseInt(myNav.split('msie')[1]) : false;
                }
                
                if (isIE() < 9) {
                    var timeout = 0;
                    var dialogClass = 'dialog-gallery'; 
                } else {
                    var timeout = 5;
                    var dialogClass = 'dialog-gallery invisible';
                }
                
		var lb = $(".gallery .lightbox").html(left + right + '<img src="' + $(this).attr("href") + '" alt="" /><p>' + $(this).attr("rel") + colon + $(this).attr("title") + '</p>').dialog({
			modal: true,
			width: "auto",
			height: "auto",
			closeText: "X",
                        dialogClass: dialogClass, 
                        position: dialogPosition,
			open: function(event) {
				$('.ui-widget-overlay').on('click', function() {
					$(".ui-dialog-content").dialog("destroy");
				});

				$(".ui-dialog img").swipe( {
					//Generic swipe handler for all directions
					swipe:function(event, direction, distance, duration, fingerCount, fingerData) {

						if (direction == 'right') {
							$(this).parent().find('.next_image').click();
						} else if (direction == 'left') {
							$(this).parent().find('.prev_image').click();
						}
					},
					threshold: 75
				});
			},
			close: function() {
				dialogPosition = {
					my: "center",
					at: "center",
					of: window
				};
				$(this).dialog('destroy').remove()
					$(".gallery").prepend('<div class="lightbox"></div>');
			}
		});

		setTimeout(function() {
			dialogPosition = lb.dialog( "option", "position" );
			lb.dialog( "option", "position", dialogPosition);
			$('.dialog-gallery.invisible').removeClass('invisible');
		}, timeout);
	});

	$("body").delegate(".lightbox a", "click", function(e) {
		e.preventDefault();

		// get position
		dialogPosition = $( ".lightbox" ).dialog( "option", "position" );

		// get next image and close dialog
		var index = parseInt($(this).attr("href"));
		$(".lightbox").dialog("destroy");

		// switch pages when image is on other page.
		if (index % imgPerPage == 0 && e.currentTarget.className == 'next_image') {
			$('#forward').click();
		} else if (index % imgPerPage == (imgPerPage -1) && e.currentTarget.className == 'prev_image') {
			$('#back').click();
		}

		// make sure all images are loaded before comming up with next dialog
		// if we omit the check then the dialog will be misplaced after the images are loaded
		var numImgOnPage = $(".gallery .slider li").length;
		var numLoadedImg = 0;
		$(".gallery .slider img").one("load", function() {
			numLoadedImg++;
			// are all images are loaded yet?
			if (numLoadedImg >= numImgOnPage) {
				// click on link of displayed imagage at newly loaded page
				$('.gallery .slider li:eq(' + index % imgPerPage + ') a').click();
			}
		}).each(function() {
			// fallback if images are loaded from cache
			if (this.complete) $(this).load();
		});

	});

});
