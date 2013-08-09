<!-- form_assistant/templates/template.ajax_field_row.tpl -->

<li id= "{$field->get('idfield')}" title="idfield {$field->get('idfield')}">
    <div class="descr-icon pifa-icon pifa-icon-{$field->get('field_type')}"></div>
    <div class="textMiddle">
        <div class="li-label-name">
            {if 0 eq $field->get('display_label')}<i>{/if}
            {$field->get('label')}
            {if 0 eq $field->get('display_label')}</i>{/if}
        </div>
        <div class="li-column-name">
            {$field->get('column_name')}
        </div>
    </div>
    <div class="edit">
        {if 1 eq $field->get('obligatory')}
        {* should be another image instead of reminder/prio_high.gif *}
        <img alt="{$trans.obligatory}" title="{$trans.obligatory}" src="images/reminder/prio_high.gif" />
        {/if}
        <a href="{$editField}&amp;idfield={$field->get('idfield')}#tabs-2" class="pifa-icon-edit-field" title="{$trans.edit}">
            <img alt="{$trans.edit}" title="{$trans.edit}" src="images/editieren.gif" />
        </a>
        <a href="{$deleteField}&amp;idfield={$field->get('idfield')}" class="pifa-icon-delete-field " title="{$trans.delete}">
            <img alt="{$trans.delete}" title="{$trans.delete}" src="images/delete.gif" />
        </a>
    </div>
</li>

<!-- /form_assistant/templates/template.ajax_field_row.tpl -->
