
/**
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
			if ($input.val() == $input.attr('title')) {
				$input.val('');
			}
		}).focusout(function() {
			// restore label if user has entered nothing
			if (0 === $input.val().trim().length) {
				$input.val($input.attr('title'));
			}
		}).val($input.attr('title'));
	};

})(jQuery);

$(function() {

	// initialize self labeled input fields
	$('.ffb-self-labeled-input').self_labeled_input();
	
	/* ----- GALERY ----- */

    var imgPerPage = 6,
    	activePage,
    	maxPage =0;

    //Setting Pagination
    function iniPagination(){
        var images=0;
         $(".galery .source li").each(function(){
             images++;
            });
            maxPage=Math.ceil(images/imgPerPage);
            for(x=1;x<=maxPage;x++){
                $(".galery .pagination").append('<li><a href="">'+x+'</a></li>');
            }
            $(".galery .pagination").append('<li><a href="">Weiter</a></li>');
    }
    iniPagination();


    //Loading function for every single page, with limitation
    function loadGaleryPage(page){
        activePage=page;

        var sliceFrom=(page-1)*imgPerPage,
        sliceTo =page*imgPerPage;
        $(".galery .slider").html("");
        $(".galery .source li").slice(sliceFrom,sliceTo).each(function(){
             $(".galery .slider").append('<li><a href="'+$(this).children("a").attr("href")+'" rel="'+$(this).children("a").attr("rel")+'" title="'+$(this).children("a").attr("title")+'"><img src="'+$(this).children("a").text()+'" alt="" /></a></li>');
        });
        $(".galery .slider li:odd").addClass("odd");

        //Setting active pagination element
        $(".galery .pagination li a.active").removeClass("active");
        $(".galery .pagination li:eq("+page+") a").addClass("active");

    }

    //initial loading the first page
    loadGaleryPage(1);


    $(".galery .pagination li a").not(".disabled").click(function(e){
        e.preventDefault();
        var page;
        if($(this).parent().index() == 0){
            page = activePage-1;
        }
        else if($(this).parent().index() == $(".galery .pagination li").length-1){
            page = activePage+1;
        }
        else{
            page = $(this).parent().index();
        }
        if(page !=0 && page <=maxPage){
         loadGaleryPage(page);
        }
    });

    $(".galery .slider").delegate("a", "click", function(e){
         e.preventDefault();
        $(".galery .lightbox").html('<img src="'+$(this).attr("href")+'" alt="" /><p>'+$(this).attr("rel")+': '+$(this).attr("title")+'</p>').dialog({
            modal:true,
            width: 'auto',
            height: 'auto',
            closeText: "X",
            close:function(){
                $(".galery").prepend('<div class="lightbox"></div>');
            }
        });
    });


    /* ----- SLIDER ----- */

    var slider = window.setInterval(function () {
         var index =$(".slider .images li.active").index();
             $(".slider .pagination li a").removeClass("active");
            $(".slider .images li:eq("+index+")").animate({"opacity": "0"}, 500, function(){
                    $(this).removeClass("active");
                });
             if((index +1) == $(".slider .images li").length ){
                $(".slider .images li:eq(0)").animate({"opacity": "1"}, 900, function(){
                      $(this).addClass("active");
                        $(".slider .pagination li:eq(0) a").addClass("active");
                });
             }
            else {
                $(".slider .images li:eq("+(index+1)+")").animate({"opacity": "1"}, 900, function(){
                      $(this).addClass("active");
                        $(".slider .pagination li:eq("+(index+1)+") a").addClass("active");
                });
            }
        }, 7000);

    $(".slider").mouseenter(function(){
        clearTimeout(slider);
    });

        //Create pagination
         if($(".slider .images li").length >1){
            for(x=1; x <= $(".slider .images li").length; x++){
                $(".slider .pagination").append('<li><a href="">'+x+'</a></li>');
            }
            $(".slider .pagination").css({"marginLeft":"-"+($(".slider .pagination").width()/2)+"px"});
            $(".slider .pagination li:eq(0) a").addClass("active");
        }

        //Navigate through pagination
        $(".slider .pagination li").delegate("a", "click", function(e){
            e.preventDefault();
              var index =$(".slider .images li.active").index(),
                    next = $(this).parent().index() ;
            $(".slider .images li:eq("+index+")").animate({"opacity": "0"}, 500, function(){
                    $(this).removeClass("active");
                });
                $(".slider .images li:eq("+(next)+")").animate({"opacity": "1"}, 900, function(){
                      $(this).addClass("active");
                        $(".slider .pagination li:eq("+(next)+") a").addClass("active");
                });

        });


});