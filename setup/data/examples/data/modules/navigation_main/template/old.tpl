<div id="navi" class="clearfix">
	<ul>
{foreach from=$tree item=wrapper name=navi}
    {assign var="idcat" value=$wrapper.idcat}
    {assign var="url" value=$wrapper.item->getLink()}
    {assign var="name" value=$wrapper.item->get('name')}
    {assign var="class" value='navmainStandardLevel'}
    {*assign var="class" value='navmainStandardLevel_{css_level}'*}
    {if $idcat|in_array:$path}
        {assign var="class" value='active'}
    {/if}
    {if $smarty.foreach.navi.first}
        {assign var="class" value=$class|cat:' first'}
    {/if}
    {if $smarty.foreach.navi.last}
        {assign var="class" value=$class|cat:' last'}
    {/if}
    <li class="{$class}">
        <a class="{$class}" href="{$url}" title="{$name}"><span>{$name}</span></a>
    </li>
{/foreach}
    </ul>
</div>
