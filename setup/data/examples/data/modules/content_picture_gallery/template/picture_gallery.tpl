<div class="gallery">
    <div class="lightbox"></div>
    <ul class="source">
        {foreach from=$pictures item=picture}
            <li>
                <a title="{$picture.description|escape}" rel="{$picture.copyright|escape}" href="{$picture.lightbox|escape}" style="background: url({$picture.lightbox|escape}) no-repeat -9999px -9999px;">
                    {$picture.thumb}
                </a>
            </li>
        {/foreach}
    </ul>

    <ul class="slider"></ul>

    <div class="clear"></div>
    <ul class="pagination">
        <li><a id="back" href="">{$back|escape}</a></li>
        <li><a id="forward" href="">{$forward|escape}</a></li>
    </ul>
</div>