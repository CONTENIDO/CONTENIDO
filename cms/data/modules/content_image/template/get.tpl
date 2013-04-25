<!-- content_image -->

{if 0 lt $label|strlen}
    <label class="content_type_label">{$label}</label>
{/if}

{$editor}

{if NULL neq $image}
    <img src="{$image->src}" alt="{$image->alt}" width="{$image->width}" height="{$image->height}" />
{/if}

<!-- /content_image -->
