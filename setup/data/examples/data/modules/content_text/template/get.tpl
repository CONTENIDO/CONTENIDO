<!-- content_text -->

{if 0 lt $label|count_characters}
    <label class="con_content_type_label">{$label|escape}</label>
{/if}

{if 0 lt $label|count_characters}<span class="con_content_type_value">{/if}
    {$text}
    {if 0 lt $label|count_characters}</span>{/if}


<!-- /content_text -->
