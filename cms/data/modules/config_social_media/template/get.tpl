<!-- config_social_media -->

{foreach item=item from=$items}

	{if 0 lt $item.name|strlen}
		<label class="content-type-label">{$item.name}</label>
	{/if}

	{$item.link}

{/foreach}

<!-- /config_social_media -->
