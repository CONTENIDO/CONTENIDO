<label class="content_type_label">{$label_overview}</label>
<form action="" method="POST">
	<table style="width: 100%;">
    <tr>
        <td>{$urlProfileLabel}</td>
        <td><input type="text" name="profile" value="{$profile}"></td>
    </tr>
    <tr>
		<td style="width: 20%;">
            {$lookLabel}
        </td>
		<td style="width: 80%;">
            <input type="radio" name="look" value="small" {if $look == "small" } checked="checked" {/if} />
			&nbsp;&nbsp;{$label_optionIcon}
            <br/>
            <input type="radio" name="look" value="big"  {if $look =="big" } checked="checked" {/if} />
			&nbsp;&nbsp;{$label_optionButton}
        </td>
    </tr>
    <tr>
        <td>{$nameLabel}</td>
        <td><input type="text" name="name" value="{$name}"></td>
    </tr>
	<tr>
		<td>&nbsp;</td>
		 <td><input type="hidden" name="plugin_type" value="xing" />
			<input type="submit" value="{$save}" />
		</td>
	</tr>
</table>
	
	