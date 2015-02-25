<!-- content_map_google -->

{if true eq $isBackendEditMode}
    <label class="content_type_label">{$trans.header|escape}</label>
{/if}
<div>{$header}</div>



{if false eq $isBackendEditMode}
    <script src="http://maps.google.com/maps/api/js?sensor=false" type="text/javascript"></script>
    <div id="googleMap"></div>
{/if}

{if true eq $isBackendEditMode}
    <label class="content_type_label">{$trans.address|escape}</label>
    <div>{$address}</div>
{ELSE}
    <div id="address">{$address}</div>
{/if}


{if true eq $isBackendEditMode}

    <label class="content_type_label">{$trans.latitude|escape}</label>
    <div>{$lat}</div>

    <label class="content_type_label">{$trans.longitude|escape}</label>
    <div>{$lng}</div>

    <label class="content_type_label">{$trans.markerTitle|escape}</label>
    <div>{$markerTitle}</div>

    <label class="content_type_label">{$trans.way}</label>
    <div>{$way}</div>

{else}

    <div id="clearFloat">
        <input type="hidden" id="lat" value="{$lat}" />
        <input type="hidden" id="lon" value="{$lng}" />
        <input type="hidden" id="markerTitle" value="{$markerTitle|strip_tags}" />
    </div>

    <input type="button" id="btndialog" value="{$trans.wayDescription|escape}"  class="button red"/>

    <div id="myDialog" title="{$trans.way}">
        <div id="dialogContent">{$way}</div>
    </div>

{/if}

<!-- /content_map_google -->
