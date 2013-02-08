<!-- content_search_results/template/get.tpl -->

{* headline *}
<h1>Suchergebnisse der Webseite</h1>

{* search form *}
<form action="" method="">
	<input type="text" class="full" id="search" title="Suche" />
	<input type="submit" class="submit search" value="Suchen &raquo;" />
</form>

{* message *}
<h3>{$message}</h3>

{* top pagination *}
{if 0 lt $prev|cat:$pages|cat:$next|trim|strlen}
<div class="pagination">
	<span>Ergebnisse 11-15 von 35</span>
	<ul>
		<li><a href="{$pre}">{$label.previous}</a></li>
		{*
		<li><a href="">1</a></li>
		<li><a href="" class="active">2</a></li>
		<li><a href="">3</a></li>
		*}
		<li>{$pages}</li>
		<li><a href="{$next}">{$label.next}</a></li>
	</ul>
</div>
{/if}

{* search result list *}
<ul id="search_result">
{foreach item=result from=$results}
	<li>
		<span>{$result.number}</span>
		<a href="{$result.url}">{$result.headline}</a>
		<p>{$result.paragraph}</p>
	</li>
{/foreach}
</ul>

{* bottom pagination *}
{if 0 lt $prev|cat:$pages|cat:$next|trim|strlen}
<div class="pagination">
	<span>Ergebnisse 11-15 von 35</span>
	<ul>
		<li><a href="{$pre}">{$label.previous}</a></li>
		{*
		<li><a href="">1</a></li>
		<li><a href="" class="active">2</a></li>
		<li><a href="">3</a></li>
		*}
		<li>{$pages}</li>
		<li><a href="{$next}">{$label.next}</a></li>
	</ul>
</div>
{/if}

<!-- /content_search_results/template/get.tpl -->
