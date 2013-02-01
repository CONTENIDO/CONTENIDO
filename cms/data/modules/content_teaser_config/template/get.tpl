{if true eq $isBackendEditMode}
<!-- content_teaser_config -->

	{if 0 lt $label|strlen}
<label class="content-type-label">{$label}</label>
	{/if}

{$editor}

	{if {$image} != ''}
<br />
<img src="{$image}" alt="" />
	{/if}

<!-- /content_teaser_config -->
{/if}
