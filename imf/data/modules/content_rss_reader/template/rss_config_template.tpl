<!-- content_rss_reader/template/rss_config_template -->
<label class="content_type_label">{$label_overview}</label>
<form action="" method="POST">
	<table>
		<tr>
			<td>{$urlLabel}:</td>
			<td><input type="text" name="url" value="{$url}"></td>
		</tr>
		<tr>
			<td>{$hostLabel}:</td>
			<td><input type="text" name="host" value="{$host}"></td>
		</tr>
		<tr>
			<td>{$templateLabel}:</td>
			<td>{$templates}</td>
		</tr>
		<tr>
			<td>{$countEntriesLabel}:</td>
			<td><input type="text" name="count_entries" value="{$countEntries}" /></td>
		</tr>
	</table>
	<input type="hidden" name="plugin_type" value="rss_reader" />
	<input id="submit" type="submit" value="{$save}" />
</form>
<!-- content_rss_reader/template/rss_config_template -->