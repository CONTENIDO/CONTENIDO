<!-- form_assistant/templates/template.ajax_field_form.tpl -->

<input type="hidden" id="area" name="area" value="form_ajax" />
<input type="hidden" id="frame" name="frame" value="4" />
{* If no $contenido is given user lacks rights to save form. *}
{if 0 lt $contenido|trim|strlen}
<input type="hidden" id="contenido" name="contenido" value="{$contenido}" />
{/if}
{* If no $action is given user lacks rights to save form. *}
{if 0 lt $action|trim|strlen}
<input type="hidden" id="action" name="action" value="{$action}" />
{/if}

<input type="hidden" id="idform" name="idform" value="{$idform}" />
<input type="hidden" id="idfield" name="idfield" value="{$field->get('idfield')}" />
<input type="hidden" id="field_type" name="field_type" value="{$field->get('field_type')}" />
<input type="hidden" id="field_rank" name="field_rank" value="{$field->get('field_rank')}" />
{*

GENERAL SETTINGS

*}
<p class="pseudo-legend">{$trans.general}</p>

<div class="pseudo-fieldset">

    {* showField returns false cause ist not editable but it should be displayed nonetheless *}
    {*if $field->showField('field_type')*}
    <div class="field-type-dialog">
        <label>{$trans.fieldType}</label>
        <span id="field-type-text">{$field->getMyFieldTypeName()}</span>
    </div>
    {*/if*}

    {if $field->showField('label')}
    <div class="label">
        <label for="label">{$trans.label}</label>
        <input type="text" id="label" name="label" value="{$field->get('label')|escape}" maxlength="1023" />
    </div>
    {/if}

    {if $field->showField('label')}
    <div class="display_label">
        <label for="display_label">{$trans.displayLabel}</label>
        <input type="checkbox" id="display_label" name="display_label" {if 1 eq $field->get('display_label')} checked="checked"{/if}/>
    </div>
    {/if}

    {if $field->showField('uri')}
    <div class="uri">
        <label for="uri">{$trans.uri}</label>
        <input type="text" id="uri" name="uri" value="{$field->get('uri')|escape}" maxlength="1023" />
    </div>
    {/if}

    {if $field->showField('obligatory')}
    <div class="req-input">
        <label for="obligatory">{$trans.obligatory}</label>
        <input type="checkbox" name="obligatory" id="obligatory" {if 1 eq $field->get('obligatory')} checked="checked"{/if}/>
    </div>
    {/if}

    {if $field->showField('default_value')}
    <div class="default_value">
        <label for="default_value">{$trans.defaultValue}</label>
        <input type="text" id="default_value" name="default_value" value="{$field->get('default_value')|escape}" maxlength="1023" />
    </div>
    {/if}

    {if $field->showField('rule')}
    <div class="rule">
        <label for="rule">{$trans.rule}</label>
        <input type="text" id="rule" name="rule" value="{$field->get('rule')|escape}" maxlength="1023" />
    </div>
    {/if}

    {if $field->showField('error_message')}
    <div class="error_message">
        <label for="error_message">{$trans.errorMessage}</label>
        <textarea id="error_message" name="error_message" rows="5" cols="30">{$field->get('error_message')|escape}</textarea>
    </div>
    {/if}

    {if $field->showField('help_text')}
    <div class="help_text">
        <label for="help_text">{$trans.helpText}</label>
        <!--textarea id="help_text" name="help_text">{$field->get('help_text')}</textarea-->
        <textarea id="help_text" name="help_text" rows="5" cols="30">{$field->get('help_text')|escape}</textarea>
    </div>
    {/if}

</div>
{*

DATABASE SETTINGS

*}
{if $field->showField('column_name')}
<p class="pseudo-legend">{$trans.database}</p>

<div class="pseudo-fieldset">
    <label for="column_name">{$trans.columnName}</label>
    <input type="text" id="column_name" name="column_name" value="{$field->get('column_name')}" maxlength="64" />
</div>
{/if}
{*

OPTION VALUES

*}
{if $field->showField('option_labels') or $field->showField('option_values')}
<p class="pseudo-legend">{$trans.options}</p>

<div class="pseudo-fieldset">

    <div id="options-list">
    {assign var="options" value=$field->getOptions()}

    {if NULL neq $options}
        {foreach from=$options item=option name=option}
            {include
                file=$partialOptionRow
                option=$option
                i=$smarty.foreach.option.iteration
                trans=$trans}
        {/foreach}
    {/if}
    </div>

    {* If no $hrefAddOption is given user lacks rights to add option. *}
    {if 0 lt $hrefAddOption|trim|strlen}
    <div id="add-options-div">
        <a id="icon-add-option" href="{$hrefAddOption}">
            <img src="images/but_art_new.gif" />
            <span id="txt-add-options">{$trans.addOption}</span>
        </a>
    </div>
    {/if}

    {if NULL neq $optionClasses}
    <label for="option_class">{$trans.externalOptionsDatasource}</label>
    <select id="option_class" name="option_class">
        {foreach from=$optionClasses item=optionClass}
        <option value="{$optionClass.value}"{if $optionClass.value eq $field->get('option_class')} selected="selected"{/if}>{$optionClass.label}</option>
        {/foreach}
    </select>
    {/if}

</div>
{/if}
{*

CSS CLASSES

*}
{if $field->showField('css_class')}
<p class="pseudo-legend">{$trans.styling}</p>

<div class="pseudo-fieldset">
    <label for="css_class">{$trans.cssClass}</label>
    {assign var="myClasses" value=","|cat:$field->get('css_class'):","}
    <select id="css_class" name="css_class[]" multiple="multiple" size="5">
        {foreach from=$cssClasses item=cssClass}
        {assign var="thisClass" value=","|cat:$cssClass:","}
        {assign var="selected" value=$myClasses|strpos:$thisClass}
        <option{if false !== $selected} selected="selected"{/if}>{$cssClass}</option>
        {/foreach}
    </select>
    <label for="css_class"></label>
    <input type="button" id="deselectCss" value="{$trans.deleteAll}" >
</div>
{/if}

{* HINT: The save icon is part of the jQuery dialog. See JS if you want to modify it. *}

<!-- /form_assistant/templates/template.ajax_field_form.tpl -->
