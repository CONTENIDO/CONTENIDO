<!-- content_map_google -->

<div>
	{$gmapApiKey}
</div>

<div>
	{$header}
</div>
<br />
<div id="googleMap">

</div>

<div id="address">
 	{$address}
</div>
<div>
	<input type="hidden" id="lat" value="{$lat}" />
	<input type="hidden" id="lon" value="{$lon}" />
	<input type="hidden" id="markerTitle" value="{$markerTitle}" />
</div>
<div id="clearFloat"></div>
<br />
{if $wayDescription}
<input type="button" id="btndialog" value="{$wayDescription}" />
{/if}

<div id="myDialog" title="{$wayDescription}">
	<div id="dialogContent">{$way}</div>
</div>

<!-- /content_map_google -->

