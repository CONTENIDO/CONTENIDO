<ul class="navigation">
	{foreach from=$navigation_data item=treeItem}
		{assign var="data" value=$treeItem.tree_data}
		{assign var="item" value=$data.item}
		
		<li>
	        <a href="{$item->getLink()}" title="{$item->getField('name')}" class="nav_level_{$data.level}{if $data.active == true} active{/if}">{$item->getField('name')}</a>
	    </li>
	{/foreach}
</ul>