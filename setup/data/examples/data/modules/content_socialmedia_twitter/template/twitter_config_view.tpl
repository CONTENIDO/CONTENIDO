<label class="content_type_label">{$label_overview}</label>
<form action="" method="POST">
	<table style="width: 100%;">
    <tr>
		<td style="width: 30%;">{$nameLabel} </td>
		<td style="width: 70%;"><input type="text" name="twitter_name" value="{$twitterName}"/></td>
    </tr>
	<tr>
        <td>{$themeLabel}</td>
        <td>
            <input type="radio" name="theme" value="light" {if $twitterTheme == "light" } checked="checked" {/if} />
            <label>{$lightThemeLabel}</label>
            <br/>
            <input type="radio" name="theme" value="dark"  {if $twitterTheme =="dark" } checked="checked" {/if} />
            <label>{$darkThemeLabel}</label>
        </td>
    </tr>
    <tr>
        <td>{$showRepliesLabel}</td>
        <td><input type="checkbox" name="show_replies" value="1" {if $twitterShowReplies} checked="checked" {/if} >&nbsp;&nbsp;{$showRepliesHelp->render()}</td>
    </tr>
	<tr>
        <td>{$labelRelated}</td>
        <td><input type="text" name="related" placeholder="{$labelRelatedExplanation}" value="{$twitterRelated}">&nbsp;&nbsp;{$relatedExplanationHelp->render()}</td>
    </tr>
	<tr>
        <td>{$labelWidth}</td>
        <td><input type="text" name="width" value="{$twitterWidth}"></td>
    </tr>
    <tr>
        <td>{$labelHeight}</td>
        <td><input type="text" name="height" value="{$twitterHeight}"></td>
    </tr>
    <tr>
        <td>{$labelLinkColor}</td>
        <td><input type="text" name="link_color" value="{$twitterLinkColor}"/></td>
    </tr>
    <tr>
        <td>{$labelBorderColor}</td>
        <td><input type="text" name="border_color" value="{$twitterBorderColor}"></td>
    </tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type="hidden" name="plugin_type" value="twitter" />
			<input type="submit" value="{$save}" />
		</td>
	</tr>
</table>
</form>

