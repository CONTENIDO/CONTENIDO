<!-- content_rss_creator -->
<h1>{$label_rss_h1|escape}</h1>

<br/>

<div>
    <label class="content_type_label">{$label_rss_source|escape}</label>
    <div><a href="{$rss_source}" target="_blank">{$label_rss_source|label}</a></div>
</div>

<br/>

<div>
    <label class="content_type_label">{$label_rss_title|escape}</label>
    <div>{$rss_title}</div>
</div>

<br/>

<div>
    <label class="content_type_label">{$label_rss_link|escape}</label>
    <div>{$rss_link}</div>
</div>

<br/>

<div>
    <label class="content_type_label">{$label_rss_description|escape}</label>
    {$rss_description}
</div>

<br/>

<div>
    <label class="content_type_label">{$label_rss_logo|escape}</label>
    <div>{$rss_logo}</div>
    <div><img src="{$rss_logo_display}" alt=""/></div>
</div>

<br/>

<div>
    <label class="content_type_label">{$label_rss_configuration|escape}</label>
    <div>{$rss_configuration}</div>
</div>

<!-- /content_rss_creator -->
