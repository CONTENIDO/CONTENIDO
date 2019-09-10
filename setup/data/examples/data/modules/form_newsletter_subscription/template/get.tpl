<div id="contact_form">
    <form method="post" action="{$FORM_ACTION|escape}" name="newsletterform"{$FORM_TARGET}>
        <fieldset>
            <div class="contact_row">
                <label for="emailname">{$EMAILNAME|escape}</label>
                <input id="emailname" type="text" name="emailname" value="" class="eingabe" maxlength="100" />
            </div>
            <div class="contact_row">
                <label for="email">{$EMAIL|escape}</label>
                <input id="email" type="text" name="email" value="" class="eingabe" maxlength="100" />
            </div>
            <div class="contact_row contact_rowNlOptions">
                <label for="action">&nbsp;</label>
                <select name="action" class="column1">
                    <option value="subscribe" selected>{$SUBSCRIBE|escape}</option>
                    <option value="delete">{$DELETE|escape}</option>
                </select>
            </div>
            {if !empty($ADDITIONAL_ROWS) && is_array($ADDITIONAL_ROWS)}
                {foreach from=$ADDITIONAL_ROWS item=row}
                    <div class="contact_row{if !empty($row.cssClass)} {$row.cssClass|escape}{/if}">
                        <label for="{$row.id|escape}">{$row.label|strip_tags}</label>
                        {$row.elementHtml}
                    </div>
                {/foreach}
            {/if}
            <div class="contact_row policy">
                <input class="checkbox" type="checkbox" value="1" name="privacy" />
                <label class="label" for="email"> {$PRIVACY_TEXT_PART1|escape} {$LINKEDITOR}  {$PRIVACY_TEXT_PART2|escape}</label>
            </div>

            <div class="hr"><hr /></div>
            <div id="contact_form_submit" class="clearfix">
                <div id="contact_form_submit_left">
                    <input type="reset" value="{$LOESCHEN|escape}" class="button grey"/>
                </div>
                <div id="contact_form_submit_right">
                    <input type="submit" value="{$ABSCHICKEN|escape}" class="button red"/>
                </div>
            </div>
        </fieldset>
    </form>
</div>