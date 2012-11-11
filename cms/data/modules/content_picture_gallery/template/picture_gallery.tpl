<div class="galery">
	<div class="lightbox"></div>   
	<ul class="source">
		{foreach from=$pictures item=picture}
			<li>
				<a title="{$picture.description}" rel="{$picture.copyright}" href="{$picture.lightbox}">
					{$picture.thumb}
				</a>
			</li>
		{/foreach}
	</ul>
	
	<ul class="slider"></ul>
	
	<div class="clear"></div>
	<ul class="pagination">
		<li><a href="">Zurück</a></li>
	</ul>  
</div>