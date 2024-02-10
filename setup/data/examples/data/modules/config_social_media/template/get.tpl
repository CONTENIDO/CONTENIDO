<!-- config_social_media -->

<label class="con_content_type_label">{$label|escape}</label>

{foreach item=item from=$items}

    {if 0 lt $item.name|count_characters}
        <label class="con_content_type_label_secondary">{$item.name|escape}</label>
    {/if}

    {$item.link}

{/foreach}

<!-- /config_social_media -->
