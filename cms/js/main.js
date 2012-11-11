$(function(){
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
			 $(".galery .slider").append('<li><a href="'+$(this).children("a").attr("href")+'"><img src="'+$(this).children("a").text()+'" alt="" /></a></li>');
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
	
	$(".galery .slider li a").on("click", function(e){
		 e.preventDefault();
		$(".galery .lightbox").html('<img src="'+$(this).attr("href")+'" alt="" /><p>TEXT</p>').dialog({
			modal:true,
			width: 'auto',
			height: 'auto',
			closeText: "X",
			close:function(){
				$(".galery").prepend('<div class="lightbox"></div>');
			}
		}); 
	});
	
});