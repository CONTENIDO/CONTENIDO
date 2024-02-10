<div id="breadcrumb">
    {foreach from=$breadcrumb item=category key=i}
        &gt;
        <a href="{$category->getLink()}">{$category->get('name')}</a>
    {/foreach}
</div>
