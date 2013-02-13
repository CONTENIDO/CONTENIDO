<!-- content_map_google -->

{if true eq $isBackendEditMode}
    <label class="content-type-label">{$trans.header}</label>
{/if}
<div>{$header}</div>

{if true eq $isBackendEditMode}
    <label class="content-type-label">{$trans.address}</label>
{/if}
<div id="address">{$address}</div>

{if false eq $isBackendEditMode}
    <script src="http://maps.google.com/maps/api/js?sensor=false" type="text/javascript"></script>
    <div id="googleMap"></div>
{/if}

{if true eq $isBackendEditMode}

    <label class="content-type-label">{$trans.latitude}</label>
    <div>{$lat}</div>

    <label class="content-type-label">{$trans.longitude}</label>
    <div>{$lng}</div>

    <label class="content-type-label">{$trans.markerTitle}</label>
    <div>{$markerTitle}</div>

    <label class="content-type-label">{$trans.way}</label>
    <div>{$way}</div>

{else}

    <div id="clearFloat">
        <input type="hidden" id="lat" value="{$lat}" />
        <input type="hidden" id="lon" value="{$lng}" />
        <input type="hidden" id="markerTitle" value="{$markerTitle|strip_tags}" />
    </div>

    <input type="button" id="btndialog" value="{$trans.wayDescription}"  class="button red"/>

    <div id="myDialog" title="{$trans.way}">
        <div id="dialogContent">{$way}</div>
    </div>

{/if}

<!-- /content_map_google -->
