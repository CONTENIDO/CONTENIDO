<!-- content_link_list -->
{if 0 lt $label|strlen}
    <label class="content-type-label">{$label}</label>
{/if}


<h2>{$usable_links}</h2>

<ul class="linklist">
	<!-- BEGIN:BLOCK -->
	<li>
		{foreach $contents as $content}
			{$content}</br>
		{/foreach}
	</li>
	{$inputfield}
	{$button}
	<!-- END:BLOCK -->

</ul>
<!-- content_link_list -->