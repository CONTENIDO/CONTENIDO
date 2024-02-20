<!-- content_image -->

{if 0 lt $label|count_characters}
    <label class="con_content_type_label">{$label|escape}</label>
{/if}

{$editor}

{if NULL neq $image}
    <img src="{$image->src|escape}" alt="{$image->alt|escape}" width="{$image->width|escape}"
         height="{$image->height|escape}"/>
{/if}

<!-- /content_image -->
