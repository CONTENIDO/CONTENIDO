<!-- content_image -->

{if 0 lt $labelImage|strlen}
	<label class="content-type-label">{$labelImage}</label>
{/if}

{$editor}

{if NULL neq $image}
	<img src="{$image->src}" alt="{$image->alt}" width="{$image->width}" height="{$image->height}" />
	{$image->alt}
{/if}

<!-- /content_image -->
