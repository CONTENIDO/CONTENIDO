<!-- form_assistant/templates/template.right_bottom_import.tpl -->

<form id="pifa-form-import" action="{$formAction}" method="post" enctype="multipart/form-data">

    <fieldset>

        <legend>{$trans.legend|escape}</legend>

        <div class="field-type">
            <label for="xml">{$trans.xml}</label>
            <input type="file" id="xml" name="xml" />
        </div>

    {if $showTableNameField}
        <div class="field-type">
            <p style="color:red">{$trans.used_table_name_error}</p>
        </div>
        <div class="field-type">
            <label for="table_name">{$trans.table_name}</label>
            <input type="text" id="table_name" name="table_name" />
        </div>
        {/if}

        <span class="con_form_action_control">
            <input type="image" class="con_img_button" src="images/but_ok.gif" alt="{$trans.import|escape}" title="{$trans.import|escape}" />
        </span>

    </fieldset>

</form>

<!-- /form_assistant/templates/template.right_bottom_import.tpl -->
