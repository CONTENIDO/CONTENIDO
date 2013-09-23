<div class="galery">
    <div class="lightbox"></div>
    <ul class="source">
        {foreach from=$pictures item=picture}
            <li>
                <a title="{$picture.description}" rel="{$picture.copyright}" href="{$picture.lightbox}" style="background: url({$picture.lightbox}) no-repeat -9999px -9999px;">
                    {$picture.thumb}
                </a>
            </li>
        {/foreach}
    </ul>

    <ul class="slider"></ul>

    <div class="clear"></div>
    <ul class="pagination">
        <li><a id="back" href="">Zurück</a></li>
    </ul>
</div>