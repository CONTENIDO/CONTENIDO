<!-- content_sitemap_html -->
{if $error}
    <h1>{$error}</h1>
{/if}

<ul class="sitemap">

    {* loop categories *}
    {foreach from=$tree item=wrapper}

        {assign var="idcat" value=$wrapper.idcat}
        {assign var="url" value=$wrapper.item->getLink()}
        {assign var="name" value=$wrapper.item->get('name')}

        <li>
            <a href="{$url}" title="{$name}">{$name}</a>
            {include file="content_sitemap_html/template/get.tpl"
                tree=$wrapper.subcats path=$path ulId=""}

        {* loop articles *}
        {if 0 lt $wrapper.articles|count}
            {foreach from=$wrapper.articles item=article}
            <li>
                <a href="{$url}" title="{$name}">{$name}</a>
            </li>
            {/foreach}
        {/if}

        </li>
     {/foreach}

{*
{foreach $categories as $key => $category}
    <li>
             <a href="">{$key}.{$category->get('name')}</a></p>
             <ul>
                 {foreach $articles as $keyArt => $article}
                     {if $key == $keyArt}
                     <ul>
                         <li>
                         {foreach $article as $keyArt => $art}

                             <a href="">{$art['title']}</a></p>

                         {/foreach}
                         </li>
                     </ul>
                     {/if}
                 {/foreach}
                 </ul>
    </li>
        {/foreach}
*}

</ul>
<!-- /content_sitemap_html -->
