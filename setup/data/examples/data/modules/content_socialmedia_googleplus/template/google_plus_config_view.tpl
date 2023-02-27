<label class="con_content_type_label">{$label_overview|escape}</label>
<form action="" method="POST">
	<table style="width: 100%;">
	<tr>
        <td>{$urlLabel|escape}</td>
        <td><input type="text" name="url" value="{$url|escape}"></td>
    </tr>
	<tr>
        <td>{$automaticURLLabel|escape}</td>
        <td><input type="checkbox" name="currentArticleUrl" value="1" {if $currentArticleUrl == "1"} checked="1" {/if}/>&nbsp;&nbsp;{$urlHelp->render()}</td>
    </tr>
    <tr>
		<td style="width: 30%;">
            {$lookLabel|escape}
        </td>
		<td style="width: 70%;">
            <input type="radio" name="size" value="standard" {if $size == "standard" } checked="checked" {/if}/>
            {$normalLabel|escape}&nbsp;&nbsp;{$normalHelp->render()}
            <br/>
            <input type="radio" name="size" value="small"  {if $size == "small"} checked="checked" {/if} />
            {$smallLabel|escape}&nbsp;&nbsp;{$smallHelp->render()}
            <br/>
            <input type="radio" name="size" value="medium"  {if $size == "medium"} checked="checked" {/if} />
            {$mediumLabel|escape}&nbsp;&nbsp;{$mediumHelp->render()}
            <br/>
            <input type="radio" name="size" value="tall"  {if $size == "tall"} checked="checked" {/if} />
            {$tallLabel|escape}&nbsp;&nbsp;{$tallHelp->render()}
        </td>
    </tr>
    <tr>
        <td>{$displayCounterLabel|escape}</td>
        <td>
            <input type="checkbox" name="counter"  value="1" {if $counter} checked="checked" {/if} />&nbsp;&nbsp;{$counterHelp->render()}
        </td>
    </tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type="hidden" name="plugin_type" value="gplus" />
			<input type="submit" value="{$save|escape}" /></td>
	</tr>
</table>
</form>
