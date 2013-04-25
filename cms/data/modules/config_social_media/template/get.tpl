<!-- config_social_media -->

<label class="content_type_label">{$label}</label>

{foreach item=item from=$items}

    {if 0 lt $item.name|strlen}
        <label class="content_type_label_secondary">{$item.name}</label>
    {/if}

    {$item.link}

{/foreach}

<!-- /config_social_media -->
