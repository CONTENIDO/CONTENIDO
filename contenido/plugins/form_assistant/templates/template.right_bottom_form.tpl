<!-- form_assistant/templates/template.right_bottom_form.tpl -->

{*

Form to edit meta data for the given form.

If no $formAction is given user lacks the rights to store form.

*}
{if 0 lt $formAction|trim|strlen}
<form id="pifa-form" action="{$formAction}" method="post">
{else}
<form id="pifa-form">
{/if}

    <input type="hidden" name="idform" value="{$idform}">

    <fieldset>

        <legend>{$trans.legend}</legend>

        <div class="field-type">
            <label for="name">{$trans.name}</label>
            <input type="text" id="name" name="name" value="{$nameValue|escape}"
                {if 0 eq $formAction|trim|strlen}disabled="disabled"{/if} />
        </div>

        <div class="field-type">
            <label for="data_table">{$trans.dataTable}</label>
            <input type="text" id="data_table" name="data_table" value="{$dataTableValue|escape}" maxlength="64"
                {if 0 eq $formAction|trim|strlen}disabled="disabled"{/if} />
        </div>

        <div class="field-type">
            <label for="request_method">{$trans.method}</label>
            <select id="method" name="method" {if 0 eq $formAction|trim|strlen}disabled="disabled"{/if}>
                <option value="">{$trans.pleaseChoose}</option>
                <option value="GET"{if "GET" eq $methodValue|strtoupper} selected="selected"{/if}>GET</option>
                <option value="POST"{if "POST" eq $methodValue|strtoupper} selected="selected"{/if}>POST</option>
            </select>
        </div>

        {if $hasWithTimestamp}
        <div class="field-type">
            <label for="with_timestamp">{$trans.withTimestamp}</label>
            <input type="checkbox" id="with_timestamp" name="with_timestamp"
                {if $withTimestampValue}checked="checked"{/if}
                {if 0 eq $formAction|trim|strlen}disabled="disabled"{/if}/>
        </div>
        {/if}

        <span class="con_form_action_control">
            {if 0 lt $formAction|trim|strlen}
                <input type="image" id="image-new-form" src="images/but_ok.gif" alt="{$trans.saveForm|escape}" title="{$trans.saveForm|escape}" />
            {else}
                <img id="image-new-form" src="images/but_ok_off.gif" alt="{$trans.saveForm|escape}" title="{$trans.saveForm|escape}" />
            {/if}
        </span>

    </fieldset>

</form>

<!-- /form_assistant/templates/template.right_bottom_form.tpl -->
