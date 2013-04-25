<!-- form_assistant/templates/template.ajax_field_row.tpl -->

<li id= "{$field->get('idfield')}" title="idfield {$field->get('idfield')}">
    <div class="descr-icon pifa-icon pifa_icon_{$field->get('field_type')}"></div>
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
        <a href="{$editField}&amp;idfield={$field->get('idfield')}#tabs-2" class="pifa_icon_edit_field" title="{$trans.edit}">
            <img alt="{$trans.edit}" title="{$trans.edit}" src="images/editieren.gif" />
        </a>
        <a href="{$deleteField}&amp;idfield={$field->get('idfield')}" class="pifa_icon_delete_field " title="{$trans.delete}">
            <img alt="{$trans.delete}" title="{$trans.delete}" src="images/delete.gif" />
        </a>
    </div>
</li>

<!-- /form_assistant/templates/template.ajax_field_row.tpl -->
