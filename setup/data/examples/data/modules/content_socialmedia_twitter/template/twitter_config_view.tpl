<label class="content_type_label">{$label_overview|escape}</label>
<form action="" method="POST">
	<table style="width: 100%;">
    <tr>
		<td style="width: 30%;">{$nameLabel|escape} </td>
		<td style="width: 70%;"><input type="text" name="twitter_name" value="{$twitterName|escape}"/></td>
    </tr>
	<tr>
        <td>{$themeLabel|escape}</td>
        <td>
            <input type="radio" name="theme" value="light" {if $twitterTheme == "light" } checked="checked" {/if} />
            <label>{$lightThemeLabel|escape}</label>
            <br/>
            <input type="radio" name="theme" value="dark"  {if $twitterTheme =="dark" } checked="checked" {/if} />
            <label>{$darkThemeLabel|escape}</label>
        </td>
    </tr>
    <tr>
        <td>{$showRepliesLabel|escape}</td>
        <td><input type="checkbox" name="show_replies" value="1" {if $twitterShowReplies} checked="checked" {/if} >&nbsp;&nbsp;{$showRepliesHelp->render()}</td>
    </tr>
	<tr>
        <td>{$labelRelated|escape}</td>
        <td><input type="text" name="related" placeholder="{$labelRelatedExplanation|escape}" value="{$twitterRelated|escape}">&nbsp;&nbsp;{$relatedExplanationHelp->render()}</td>
    </tr>
	<tr>
        <td>{$labelWidth|escape}</td>
        <td><input type="text" name="width" value="{$twitterWidth|escape}"></td>
    </tr>
    <tr>
        <td>{$labelHeight|escape}</td>
        <td><input type="text" name="height" value="{$twitterHeight|escape}"></td>
    </tr>
    <tr>
        <td>{$labelLinkColor|escape}</td>
        <td><input type="text" name="link_color" value="{$twitterLinkColor|escape}"/></td>
    </tr>
    <tr>
        <td>{$labelBorderColor|escape}</td>
        <td><input type="text" name="border_color" value="{$twitterBorderColor|escape}"></td>
    </tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type="hidden" name="plugin_type" value="twitter" />
			<input type="submit" value="{$save|escape}" />
		</td>
	</tr>
</table>
</form>

