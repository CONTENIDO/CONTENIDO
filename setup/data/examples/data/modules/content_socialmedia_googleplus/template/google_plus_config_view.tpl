<label class="content_type_label">{$label_overview}</label>
<form action="" method="POST">
	<table style="width: 100%;">
	<tr>
        <td>{$urlLabel}</td>
        <td><input type="text" name="url" value="{$url}"></td>
    </tr>
	<tr>
        <td>{$automaticURLLabel}</td>
        <td><input type="checkbox" name="currentArticleUrl" value="1" {if $currentArticleUrl == "1"} checked="1" {/if}/>&nbsp;&nbsp;{$urlHelp->render()}</td>
    </tr>
    <tr>
		<td style="width: 30%;">
            {$lookLabel}
        </td>
		<td style="width: 70%;">
            <input type="radio" name="size" value="standard" {if $size == "standard" } checked="checked" {/if}/>
            {$normalLabel}&nbsp;&nbsp;{$normalHelp->render()}
            <br/>
            <input type="radio" name="size" value="small"  {if $size == "small"} checked="checked" {/if} />
            {$smallLabel}&nbsp;&nbsp;{$smallHelp->render()}
            <br/>
            <input type="radio" name="size" value="medium"  {if $size == "medium"} checked="checked" {/if} />
            {$mediumLabel}&nbsp;&nbsp;{$mediumHelp->render()}
            <br/>
            <input type="radio" name="size" value="tall"  {if $size == "tall"} checked="checked" {/if} />
            {$tallLabel}&nbsp;&nbsp;{$tallHelp->render()}
        </td>
    </tr>
    <tr>
        <td>{$displayCounterLabel}</td>
        <td>
            <input type="checkbox" name="counter"  value="1" {if $counter} checked="checked" {/if} />&nbsp;&nbsp;{$counterHelp->render()}
        </td>
    </tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type="hidden" name="plugin_type" value="gplus" />
			<input type="submit" value="{$save}" /></td>
	</tr>
</table>
</form>
