<!-- form_assistant/templates/template.right_bottom_export.tpl -->

<form id="pifa-form-export" action="{$formAction}" method="post">

    <input type="hidden" name="idform" value="{$idform}">

    <fieldset>

        <legend>{$trans.legend|escape}</legend>

        <div class="field-type">
            <label for="with_data">{$trans.withData|escape}</label>
            <input type="checkbox" id="with_data" name="with_data" checked="checked" />
        </div>

        <input type="image" src="images/but_ok.gif" alt="{$trans.export|escape}" title="{$trans.export|escape}" />

    </fieldset>

</form>

<!-- /form_assistant/templates/template.right_bottom_export.tpl -->
