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
					{$content}</br>
					{$description}</br>
				{/if}
			{/foreach}
		{/foreach}
	</li>
	{$inputfield}
	{$button}
	<!-- END:BLOCK -->

</ul>
<!-- content_link_list -->