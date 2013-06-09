<div id="contact_form">
    <form method="post" action="{$FORM_ACTION}" name="newsletterform"{$FORM_TARGET}>
        <fieldset>
            <div class="contact_row">
                <label for="emailname">{$EMAILNAME}</label>
                <input id="emailname" type="text" name="emailname" value="" class="eingabe" maxlength="100" />
            </div>
            <div class="contact_row">
                <label for="email">{$EMAIL}</label>
                <input id="email" type="text" name="email" value="" class="eingabe" maxlength="100" />
            </div>
            <div class="contact_row contact_rowNlOptions">
                <label for="action">&nbsp;</label>
                <select name="action" class="column1" maxlength="100">
                    <option value="subscribe" selected>{$SUBSCRIBE}</option>
                    <option value="delete">{$DELETE}</option>
                </select>
                {$EXTRAHTML}
            </div>
            <div class="contact_row policy">
                <input class="checkbox" type="checkbox" value="1" name="privacy" />
                <label class="label" for="email"> {$PRIVACY_TEXT_PART1} {$LINKEDITOR}  {$PRIVACY_TEXT_PART2}</label>
            </div>

            <div class="hr" /><hr /></div>
            <div id="contact_form_submit" class="clearfix">
                <div id="contact_form_submit_left">
                    <input type="reset" value="{$LOESCHEN}" class="button grey"/>
                </div>
                <div id="contact_form_submit_right">
                    <input type="submit" value="{$ABSCHICKEN}" class="button red"/>
                </div>
            </div>
        </fieldset>
    </form>
</div>