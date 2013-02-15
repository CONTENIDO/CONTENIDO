<!-- form_assistant/templates/template.right_bottom_form.tpl -->

{*

author: marcus.gnass

Form to edit meta data for the given form.

*}
<form id="pifa-form" action="{$formAction}" method="post">

    <input type="hidden" name="idform" value="{$idform}">

	<fieldset>

		<legend>{$trans.legend}</legend>

	    <div class="field-type">
	        <label for="name">{$trans.name}</label>
			<input type="text" id="name" name="name" value="{$nameValue}" />
	    </div>

		<div class="field-type">
	        <label for="data_table">{$trans.dataTable}</label>
			<input type="text" id="data_table" name="data_table" value="{$dataTableValue}" maxlength="64" />
		</div>

		<div class="field-type">
			<label for="request_method">{$trans.method}</label>
			<select id="method" name="method">
				<option value="">{$trans.pleaseChoose}</option>
				<option value="GET"{if "GET" eq $methodValue|strtoupper} selected="selected"{/if}>GET</option>
				<option value="POST"{if "POST" eq $methodValue|strtoupper} selected="selected"{/if}>POST</option>
			</select>
		</div>

		<input type="image" id="image-new-form" src="images/but_ok.gif" alt="{$trans.saveForm}" title="{$trans.createForm}" />

	</fieldset>

</form>

<!-- /form_assistant/templates/template.right_bottom_form.tpl -->
