<!-- content_link_list -->
{if 0 lt $label|strlen}
    <label class="content_type_label">{$label}</label>
{/if}

<div class="link_list">
    <h2>{$usable_links}</h2>

    <ul class="link_list">
        <!-- BEGIN:BLOCK -->
        <li>
            {foreach from=$contents item=content key=kcontent}
                {foreach from=$descriptions item=description key=kdesc }
                    {if $kcontent == $kdesc}
                            <div>
                                {$content}</br>
                                {$description}</br>
                            </div>
                        {if $breakForBackend === TRUE}
                            </br>
                        {/if}
                    {/if}
                {/foreach}
            {/foreach}
        </li>
        {$createLabel}<br />
        {$inputfield}
        {$button}
        <!-- END:BLOCK -->

    </ul>
</div>
<!-- content_link_list -->
