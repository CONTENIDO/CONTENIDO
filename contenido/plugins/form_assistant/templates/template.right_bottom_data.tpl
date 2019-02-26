<!-- form_assistant/templates/template.right_bottom_data.tpl -->

<fieldset>
    <legend>{$trans.legend}</legend>
{if true neq $form->isLoaded()}
    <p>{$trans.pleaseSaveFirst}</p>
{else if not $fields|is_array}
    {* an error is a string instead of an array *}
    {$fields}
{else if not $data|is_array}
    {* an error is a string instead of an array *}
    {$data}
{else}
    {* If no $exportUrl is given user lacks rights to download CSV. *}
    {if 0 lt $exportUrl|trim|strlen}
    <a class="form-data-export" href="{$exportUrl}">{$trans.export}</a>
    {/if}

    <!-- table cellpadding="0" class="generic" -->
    <table class="generic" width="97%" cellspacing="0" cellpadding="2" border="0">
        <tr>
            <th nowrap="nowrap">id</th>
    {if $withTimestamp}
            <th nowrap="nowrap">timestamp</th>
    {/if}
    {foreach from=$fields item=field}
        {* skip columns that don't store data into DB *}
        {if NULL eq $field->getDbDataType()}{continue}{/if}
        {assign var=columnName value=$field->get('column_name')}
        {if 0 eq $columnName|strlen}
            <th nowrap="nowrap">&nbsp;</th>
        {else}
            <th nowrap="nowrap">{$columnName}</th>
        {/if}
    {/foreach}
        </tr>
    {if 0 eq $data|count}
        <tr>
            <td colspan="{$fields|count}">{$trans.nodata}</td>
        </tr>
    {else}
        {foreach from=$data item=row}
        <tr>
            <td nowrap="nowrap" class="bordercell">{$row.id}</td>
        {if $withTimestamp}
            <td nowrap="nowrap" class="bordercell">{$row.pifa_timestamp}</td>
        {/if}
        {foreach from=$fields item=field}
            {* skip columns that dont store data into DB *}
            {if NULL eq $field->getDbDataType()}{continue}{/if}
            {assign var=columnName value=$field->get('column_name')}
            {assign var=columnData value=$row.$columnName}
            {if 0 eq $columnData|strlen}
            <td nowrap="nowrap" class="bordercell">&nbsp;</td>
            {else if '9' eq $field->get('field_type')}
            {* display INPUTFILE values as link *}
            <td nowrap="nowrap" class="bordercell"><a href="{$getFileUrl}&name={$columnData|htmlentities}&file={$form->get('data_table')}_{$row.id}_{$columnName}">{$columnData|escape:htmlall}</a></td>
            {else}
            <td nowrap="nowrap" class="bordercell">{$columnData|escape:htmlall}</td>
            {/if}
        {/foreach}
        </tr>
        {/foreach}
    {/if}
    </table>
{/if}
</fieldset>

<!-- /form_assistant/templates/template.right_bottom_data.tpl -->
