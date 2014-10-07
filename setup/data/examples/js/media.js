(function(){

	var header = $('#header');

	//init navigation
	var headerNav = $('#navigation_header').clone();
	headerNav.appendTo('#menu')
			 .addClass('hide_desktop')
			 .attr('id', headerNav.attr('id')+"_mobile");

	//create burger-menu
	$('<a />').addClass('burger_menu hide_desktop').appendTo($('<div />').addClass('burger_menu_wrapper')).appendTo(header);

	//init menu
	$('#menu')
		.data('oPosRight', $('#menu').css('right'))
		.css('top', header.position().top + header.outerHeight() - 1);

	//init burger-menu
	$('.burger_menu').on('click', function() {

		toggleMenu();
	});

	function toggleMenu() {

		if ($('.burger_menu').hasClass('open')) {

			closeMenu();
		} else {

			openMenu();
		}
	}

	function openMenu() {

		$('.burger_menu').addClass('open');
		$('body').addClass('menu_open');
		$('#menu').animate({'right': 0});
	}

	function closeMenu() {

		$('.burger_menu').removeClass('open');
		$('body').removeClass('menu_open');
		$('#menu').animate({'right': $('#menu').data('oPosRight')});
	}

	$('.galery').on('swipeRight', function(e, direction, distance, duration, fingerCount, fingerData) {
		console.log("lkl");
	});

})();