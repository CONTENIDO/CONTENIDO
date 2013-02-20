<!-- form_assistant/templates/template.right_bottom_fields.tpl -->

{*

AUTHOR marcus.gnass@4fb.de

*}

{if NULL eq $idform}

<p>{$trans.pleaseSaveFirst}</p>

{else}

{* common ajax requests params *}
<input type="hidden" id="ajaxParams" value="{$ajaxParams}" />
{* params used for an AJAX call on sorting form fields *}
<input type="hidden" id="sortParams" value="{$sortParams}">

{* list of available form field types for selection *}
<fieldset id="field-buttons">
    <legend>{$trans.legend}</legend>
    <ul>
        {foreach from=$fieldTypes key=fieldTypeId item=fieldTypeName}
        <li>
        <a
            class="img-draggable pifa-field-type-{$fieldTypeId}"
            href="{$dragParams}&field_type={$fieldTypeId}"
            title="{$fieldTypeName}"
        ></a></li>
        {/foreach}
    </ul>
</fieldset>

{* list of this forms fields *}
<fieldset id="field-list-field">
    <ul id="pifa-form-field-list">
        {* $fields might be NULL, but the UL has to be displayed for dropping nonetheless *}
        {if NULL neq $fields}
            {foreach from=$fields item=field}
                {include
                    file=$partialFieldRow
                    field=$field
                    editField=$editField
                    deleteField=$deleteField
                    trans=$trans}
            {/foreach}
        {/if}
    </ul>
</fieldset>

{* dialog for field forms (form is posted via Ajax!) *}
<form id="pifa-form-field-dialog" title="{$trans.dialogTitle}"></form>

{/if}

<!-- /form_assistant/templates/template.right_bottom_fields.tpl -->
