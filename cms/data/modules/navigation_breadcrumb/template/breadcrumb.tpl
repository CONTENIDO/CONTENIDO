<ul>
    <li>{$label_breadcrumb}:</li>
    {foreach from=$breadcrumb item=bread}
    	<li><a href="{$bread->getLink()}">{$bread->get('name')}</a></li>
    {/foreach}
    {if $headline != ''}
    	<li>{$headline}</li>
    {/if}
</ul>