<!-- form_assistant/templates/template.right_bottom_data.tpl -->

<fieldset>
    <legend>{$trans.legend}</legend>
{if true neq $form->isLoaded()}
    <p>{$trans.pleaseSaveFirst}</p>
{elseif not $fields|is_array}
    {* an error is a string instead of an array *}
    {$fields}
{elseif not $data|is_array}
    {* an error is a string instead of an array *}
    {$data}
{else}
    {* If no $exportUrl is given user lacks rights to download CSV. *}
    {if isset($exportUrl) && 0 lt $exportUrl|trim|strlen}
    <a class="form-data-export" href="{$exportUrl}">{$trans.export}</a>
    {/if}
    {if $data|count}
        {$lnkDel}
    {/if}

    <!-- table cellpadding="0" class="generic" -->
    <table class="generic" width="97%" cellspacing="0" cellpadding="2" border="0">
        <tr>
            <th class="no_wrap">mark</th>
            <th class="no_wrap">id</th>
    {if $withTimestamp}
            <th class="no_wrap">timestamp</th>
    {/if}
    {foreach from=$fields item=field}
        {* skip columns that don't store data into DB *}
        {if NULL eq $field->getDbDataType()}{continue}{/if}
        {assign var=columnName value=$field->get('column_name')}
        {if 0 eq $columnName|strlen}
            <th class="no_wrap">&nbsp;</th>
        {else}
            <th class="no_wrap">{$columnName}</th>
        {/if}
    {/foreach}
            <th class="no_wrap">{$trans.delete}</th>
        </tr>
    {if 0 eq $data|count}
        <tr>
            <td colspan="{$fields|count + 1}">{$trans.nodata}</td>
        </tr>
    {else}
        {foreach from=$data item=row}
        <tr data-form-data-id="{$row.id}">
            <td><input type="checkbox" name="mark" class="mark_data" value="{$row.id}" /></td>
            <td class="no_wrap bordercell">{$row.id}</td>
        {if $withTimestamp && isset($row.pifa_timestamp)}
            <td class="no_wrap bordercell">{$row.pifa_timestamp}</td>
        {/if}
        {foreach from=$fields item=field}
            {* skip columns that dont store data into DB *}
            {if NULL eq $field->getDbDataType()}{continue}{/if}
            {assign var=columnName value=$field->get('column_name')}
            {assign var=columnData value=$row.$columnName}
            {if 0 eq $columnData|strlen}
            <td class="no_wrap bordercell">&nbsp;</td>
            {elseif '9' eq $field->get('field_type')}
            {* display INPUTFILE values as link *}
            <td class="no_wrap bordercell"><a href="{$getFileUrl}&name={$columnData|htmlentities}&file={$form->get('data_table')}_{$row.id}_{$columnName}">{$columnData|escape:htmlall}</a></td>
            {else}
            <td class="no_wrap bordercell">{$columnData|escape:htmlall}</td>
            {/if}
        {/foreach}
            <td class="no_wrap bordercell">
                <img class="delete" src="images/delete.gif" data-action="delete_form_data" />
            </td>
        </tr>
        {/foreach}
    {/if}
    </table>
    {if $data|count}
    <table>
        <tr>
            <th>
                <input type="hidden" name="deleteUrl" class="deleteUrl" value="{$deleteUrl}">
                <img class="delete" src="images/delete.gif" />
            </th>
        </tr>
    </table>
    {/if}
{/if}
</fieldset>

<!-- /form_assistant/templates/template.right_bottom_data.tpl -->
