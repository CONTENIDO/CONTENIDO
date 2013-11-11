<!-- form_assistant/templates/template.right_bottom_import.tpl -->

<form id="pifa-form-import" action="{$formAction}" method="post" enctype="multipart/form-data">

    <fieldset>

        <legend>{$trans.legend|escape}</legend>

        <div class="field-type">
            <label for="xml">{$trans.xml|escape}</label>
            <input type="file" id="xml" name="xml" />
        </div>

        <input type="image" src="images/but_ok.gif" alt="{$trans.import|escape}" title="{$trans.import|escape}" />

    </fieldset>

</form>

<!-- /form_assistant/templates/template.right_bottom_import.tpl -->
