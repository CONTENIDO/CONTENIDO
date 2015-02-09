{if true eq $isBackendEditMode}
<!-- content_teaser_config -->

    <label class="content_type_label">{$label}</label>

    {$editor}

    {if 0 lt $image|trim|strlen}
    <br /><img src="{$image}" alt=""/>
    {/if}

<!-- /content_teaser_config -->
{/if}
