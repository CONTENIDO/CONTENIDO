(function(){

    $.browser.chrome = /chrome/.test(navigator.userAgent.toLowerCase());

    //detect chrome
    if($.browser.chrome){
    	//chrome reports as safari too
        $.browser.safari = false;
    }

	//if is mobile breakpoint
	function isMobile() {
		return ($(window).width() < 769) ? true : false;
	}

	var header = $('#header');

	//append extra nav to mobile nav
	var navigationHeader = $('#navigation_header');
	if (navigationHeader.length) {
		//clone nav
		var headerNav = navigationHeader.clone();

		//find and clone search form
		var searchLi = headerNav.find('#navigation_searchform_top').parent().clone();

		//create search icon
		var searchIcon = $('<span />').addClass('search-icon');

		//form submit on click
		searchIcon.on('click', function() {
			$(this).closest('form').submit();
		});

		//append search icon
		searchLi
			.addClass('hide_desktop')
			.find('form')
			.append(searchIcon);

		//prepend search form
		$('#menu > ul.navigation').prepend(searchLi);

		//append extra nav
		headerNav.appendTo('#menu')
				 .addClass('hide_desktop')
				 .attr('id', navigationHeader.attr('id')+"_mobile");
	}

	//create burger-menu
	$('<a />').addClass('burger_menu hide_desktop').appendTo($('<div />').addClass('burger_menu_wrapper')).appendTo(header);

	//init menu
	$('#menu')
		.data('oPosRight', $('#menu').css('right'))
		.css('top', header.position().top + header.outerHeight() - 1);

	//slider
    $(".slider ul.images li").swipe( {

        swipe:function(event, direction, distance, duration, fingerCount, fingerData) {

            if (direction == 'right') {

            	$(this).closest('.slider').trigger('slider.next');
            } else if (direction == 'left') {

            	$(this).closest('.slider').trigger('slider.prev');
            }
        },
        threshold: 75
    });

    //slider button next
    $('<a />')
    	.attr('href', 'next')
    	.addClass('action next hide_desktop')
    	.appendTo('.slider');

    //slider button prev
    $('<a />')
    	.attr('href', 'prev')
    	.addClass('action prev hide_desktop')
    	.appendTo('.slider');

    $('.slider').find('a.action').on('click', function(e) {
    	e.preventDefault();
    	$(this).closest('.slider').trigger('slider.'+$(this).attr('href'));
    });

   	//bind click on headline
    $('.slider .images h2').on('click', function() {

    	if (isMobile()) {

    		//click on 'more'
    		var a = $(this).parent().find('a');
    		if (a.length) {

    			var h = $.trim(a.attr('href'));
    			if (h && h != 'undefined' && h != '#') {
	    			//goto
	    			document.location.href = a.attr('href');
    			}
    		}
    	}
    });

    //init teaser_img
    $('.teaser_img').each(function() {

    	$(this).parent('.col').addClass('mobile-col-100');

    	//get background image url
		var bgUrl = $(this).css('background-image');
		bgUrl = bgUrl.replace('url(','').replace(/["'()]/g, "");

		if (bgUrl) {

			$('<img />')
				.attr('src', bgUrl)
				.addClass('teaser_img_mobile hide_desktop')
				.prependTo($(this).parent());
			$(this).addClass('initialized');
		}
    });

	//init burger-menu
	$('.burger_menu').on('click', function() {

		toggleMenu();
	});

	//toggle mobile menu
	function toggleMenu() {

		if ($('.burger_menu').hasClass('open')) {
			closeMenu();
		} else {
			openMenu();
		}
	}

	//open mobile menu
	function openMenu() {

		$('.burger_menu').addClass('open');
		$('body').addClass('menu_open');
		$('#menu').stop().animate({'right': 0}, 400, 'easeOutQuad');

		//bind click with namespace
		$(document).on('click.mobileMenu', function(e) {

			if (!$(e.target).closest('#menu').length &&
					!$(e.target).hasClass('burger_menu')
			) {
				e.preventDefault();
				closeMenu();
			}
		});
	}

	//close mobile menu
	function closeMenu() {

		var posRight = $('#menu').data('oPosRight');

		if (typeof posRight === 'string') {
			posRight = posRight.replace('px', '');
		}
		
		if (posRight > 0) {
			posRight = -posRight;
		}

		//shadow fix
		posRight -= 50;

		$('.burger_menu').removeClass('open');
		$('body').removeClass('menu_open');
		$('#menu').stop().animate({'right': posRight});

		//bind click with namespace
		$(document).off('click.mobileMenu');
	}

	//update slider
	function updateSlider() {

		var maxHeight = 0;
		$('.slider .images').each(function() {

			//find max height of li´s
			var maxHeight = Math.max.apply(null, $(this).find('li').map(function () {
			    return $(this).height();
			}).get());

			$(this).css('height', maxHeight);
		});
	}

	//update menu
	function updateMenu() {

		var header = $('#header'),
			m = $('#menu');

		var d = m.data('oPosRight'),
			w = m.outerWidth();

		//fallback if init failed and fix IE
		if (!d || d == 'auto' || d == 'undefined' || d < w) {
			m.data('oPosRight', w);
		}

		m.css('top', header.position().top + header.outerHeight() - 1);
	}

	//window resize
	$(window).on('resize', function() {

		if (isMobile()) {
			updateSlider();
			updateMenu();
		} else {
			$('.slider .images').removeAttr('style');

		}

		var u = $('.ui-dialog-content');

		if (u.length) {
			u.dialog("option", "position", {my: "center", at: "center", of: window});
		}
	});

	//document ready
	$(document).ready(function() {

		updateSlider();
		updateMenu();
	});

	//window live
	$(window).load(function() {

		//detect safari
		if($.browser.safari){
		
			$('ul.download_list').find('img').addClass('safari');
		}

		updateSlider();
		updateMenu();
	});

})();