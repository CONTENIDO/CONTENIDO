<label class="content_type_label">{$label_overview}</label>
<form action="" method="POST">
<table>
    <tr>
        <td>{$urlLabel}</td>
        <td><input type="text" name="url" value="{$url}"></td>
    </tr>
    <tr>
        <td>
            {$lookLabel}
        </td>
        <td>
            <input type="radio" name="size" value="standard" {if $size == "standard" } checked="checked" {/if}/>
            {$normalLabel}
            <br/>
            <input type="radio" name="size" value="small"  {if $size == "small"} checked="checked" {/if} />
            {$smallLabel}
            <br/>
            <input type="radio" name="size" value="medium"  {if $size == "medium"} checked="checked" {/if} />
            {$mediumLabel}
            <br/>
            <input type="radio" name="size" value="tall"  {if $size == "tall"} checked="checked" {/if} />
            {$tallLabel}
        </td>
    </tr>
    <tr>
        <td>{$displayCounterLabel}</td>
        <td>
            <input type="checkbox" name="counter"  value="1" {if $counter} checked="checked" {/if} />
        </td>
    </tr>
</table>
 <input type="hidden" name="plugin_type" value="gplus" />
 <input type="submit" value="{$save}" />
</form>
