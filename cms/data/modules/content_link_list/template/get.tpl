<!-- content_link_list -->
{if 0 lt $label|strlen}
    <label class="content-type-label">{$label}</label>
{/if}

<h2>{$usable_links}</h2>

<ul class="linklist">
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
<!-- content_link_list -->