{*template for new entry dialog*}
<div class="user_forum">
    <div class="form_status_message">{$MESSAGE}</div>
    <div class="replyment_handler">
    <div class="replyment">
    {$FORUM_REPLYMENT} </div></div>
    <form action="front_content.php" method="post" name="new_user_forum">
        <input type="hidden" name="idcat" value="{$IDCAT}" />
        <input type="hidden" name="idart" value="{$IDART}" />
        <input type="hidden" name="user_forum_action" value="save_new_forum" />
        <input type="hidden" name="user_forum_parent" value="{$USER_FORUM_PARENT}" />
        <div style="padding-top:10px;">
            <div class="inputs">
                <div class="email">
                    <label for="email"><strong>{$EMAIL} </strong></label>{$INPUT_EMAIL}
                </div>
                <div class="name">
                    <label for="realname"><strong>{$REALNAME} </strong></label>{$INPUT_REALNAME}
                </div>
            </div>
            <div style="padding-top:10px;"></div>
            <div class="yourcomment" style="padding-top:10px;{$DISPLAY}"><strong>{$FORUM_QUOTE}</strong></div>
            <div>
                <textarea class="input_forum_text" name="forum_quote" style="width:400px;height:90px;{$DISPLAY}" tabindex="3">{$INPUT_FORUM_QUOTE}</textarea>
            </div>
            <div class="yourcomment" style="padding-top:10px;"><strong>{$FORUM}</strong></div>
            <div>
                <textarea class="input_forum_text" name="forum" style="width:400px;height:90px;" tabindex="4">{$INPUT_FORUM}</textarea>
            </div>
            {if isset($MODEMODETEXT)}
                <div class="modtext"><p>{$MODEMODETEXT}</p></div>
            {/if}
            <div class="submitbuttons">
                <a href="{$CANCEL_LINK}" class='cancel button grey'>{$CANCEL_FORUM}</a>
                <a href="javascript:document.forms['new_user_forum'].submit();" class="submit button red">{$SAVE_FORUM}</a>
            </div>
        </div>
    </form>
</div>