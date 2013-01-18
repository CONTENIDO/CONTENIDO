<!-- navigation_searchform_top/template/get.tpl -->

{if 0 eq $action|trim|strlen}
	<!--
		In order for the search form to be shown
		you have to define a search result page.
	-->
{else}
	<form action="{$action}" method="{$method}" id="navigation_searchform_top">
		{* The CSS class ffb-self-labeled-input adds a behaviour to
		the input field that is described in $().self_labeled_input(). *}
		<input type="text" id="search_term" name="search_term"
			class="ffb-self-labeled-input" title="{$label}" />
		<input type="submit" id="search_submit" value="{$submit}" />
	</form>
{/if}

<!-- /navigation_searchform_top/template/get.tpl -->