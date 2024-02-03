<!-- content_text -->

{if 0 lt $label|strlen}
    <label class="con_content_type_label">{$label|escape}</label>
{/if}

{if 0 lt $label|strlen}<span class="con_content_type_value">{/if}
    {$text}
    {if 0 lt $label|strlen}</span>{/if}


<!-- /content_text -->
