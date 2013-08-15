
<div class="file_manager"><form action="" method=""><div class="breadcrumb">
 <ul><li>Aktueller Pfad:</li>{if isset($root)}<li><a href="{$path}deutsch/downloadbereich/index.html">upload/</a></li>{/if}{foreach $breadcrumb as $key => $item}<li><a href="{$item->getLink()}">{$item->getText()}</a></li>{/foreach}
 
</ul>
        </div>
        <table class="download_classification">
            <thead>
                <tr>
                    <th class="product">Name <a href="{$sortLinkNameAsc}"><img border="0" src="images/absteigend.gif" alt="absteigend sortieren" /></a><a href="{$sortLinkNameDsc}"><img border="0" src="images/aufsteigend.gif" alt="aufsteigend sortieren" /></a></th>
                    <th class="desc">Beschreibung <a href="{$sortLinkDescriptionAsc}"><img border="0" src="images/absteigend.gif" alt="absteigend sortieren"></a><a href="{$sortLinkDescriptionDsc}"><img border="0" src="images/aufsteigend.gif" alt="aufsteigend sortieren"></a></th>
                    <th class="type">Typ <a href="{$sortLinkTypeAsc}"><img border="0" src="images/absteigend.gif" alt="absteigend sortieren"></a><a href="{$sortLinkTypeDsc}"><img border="0" src="images/aufsteigend.gif" alt="aufsteigend sortieren"></a></th>
                    <th class="date">Datum <a href="{$sortLinkDateAsc}"><img border="0" src="images/absteigend.gif" alt="absteigend sortieren"></a><a href="{$sortLinkDateDsc}"><img border="0" src="images/aufsteigend.gif" alt="aufsteigend sortieren"></a></th>
                    <th class="size">Größe <a href="{$sortLinkSizeAsc}"><img border="0" src="images/absteigend.gif" alt="absteigend sortieren"></a><a href="{$sortLinkSizeDsc}"><img border="0" src="images/aufsteigend.gif" alt="aufsteigend sortieren"></a></th>
                    <th class="download">Download</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {foreach $uplContent as $key => $cont}
<tr>{*check if directory or file*}{if $cont->getNaviLink()}<td class="product"><div class="icon_placeholder"></div><div  id = "productname" name="productname"><a href="{$cont->getNaviLink()}">{$cont->getName()}</a></div>
{else}
<td class="product"><div class="iconFile_placeholder"></div><div  id="productname" name="productname"><a href="{$cont->getDownloadLink()}" target=_blank >{$cont->getName()}</a></div>{/if}</td>
<td class="desc"><div id="description" name="description">{$cont->getDescription()}</div></td>
<td class="type">{$cont->getType()}</td>
<td class="date">{if $cont->getDate() != 0}{$cont->getDate()}{/if}</td>
<td class="size">{if $cont->getSize() != 0}{$cont->getSize()}{/if}</td>
<td class="download download-internal">{if !$cont->getNaviLink()}<a href="{$cont->getDownloadSecurity()}" class="icon-download">download</a></td>{/if}
<td class="download download-internal">{if !$cont->getNaviLink()}<span class="jqTransformCheckboxWrapper"><a href="#" class="jqTransformCheckbox"></a><input class="selectedBox" type="checkbox" name="files[]" value="{$cont->getDLink()}" class="jqTransformHidden"></span>{/if}
{if $cont->getDLink()}{/if}</td></tr>{/foreach}</tbody>
        </table>
        <div class="download_all">
            <span class="jqTransformCheckboxWrapper"><a href="#" class="jqTransformCheckbox"></a><input type="checkbox" class="all jqTransformHidden" name="all"></span> <label style="cursor: pointer; ">alle an/abwählen</label>
        </div>
        <button type="submit"><span>»</span> Ausgewählte Dateien als Zip herunterladen</button>
    </form>
</div>
