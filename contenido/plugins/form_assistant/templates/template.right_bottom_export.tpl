<!-- form_assistant/templates/template.right_bottom_export.tpl -->

<form id="pifa-form-export" action="{$formAction}" method="post">

    <input type="hidden" name="idform" value="{$idform}">

    <fieldset>

        <legend>{$trans.legend|escape}</legend>

        <div class="field-type">
            <input type="checkbox" id="with_data" name="with_data" checked="checked" />
            <label for="with_data">{$trans.withData|escape}</label>
        </div>

        <span class="con_form_action_control">
            <input type="image" class="con_img_button" src="images/but_ok.gif" alt="{$trans.export|escape}" title="{$trans.export|escape}" />
        </span>

    </fieldset>

</form>

<!-- /form_assistant/templates/template.right_bottom_export.tpl -->
