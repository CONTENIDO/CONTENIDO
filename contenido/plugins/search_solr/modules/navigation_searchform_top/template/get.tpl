<!-- navigation_searchform_top/template/get.tpl -->

{if 0 eq $action|trim|strlen}
    <!--
        In order for the search form to be shown
        you have to define a search result page.
    -->
{else}
    <form action="{$action}" method="{$method}" id="navigation_searchform_top">
    {if $idart}<input type="hidden" name="idart" value="{$idart}" />{/if}
    {if $idlang}<input type="hidden" name="idlang" value="{$idlang}" />{/if}
        <input type="text" id="search_term" name="search_term" />
    </form>
{/if}

<!-- /navigation_searchform_top/template/get.tpl -->