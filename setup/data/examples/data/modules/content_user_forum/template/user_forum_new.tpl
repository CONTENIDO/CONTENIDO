{*template for new entry dialog*}
<div class="user_forum">
    <div class="form_status_message">{$MESSAGE}</div>
    <div class="replyment_handler">
    <div class="replyment">
    {$FORUM_REPLYMENT} </div></div>
    <form action="front_content.php" method="post" name="new_user_forum">
        <input type="hidden" name="idcat" value="{$IDCAT|escape}" />
        <input type="hidden" name="idart" value="{$IDART|escape}" />
        <input type="hidden" name="user_forum_action" value="save_new_forum" />
        <input type="hidden" name="user_forum_parent" value="{$USER_FORUM_PARENT|escape}" />
        <div style="padding-top:10px;">
            <div class="inputs">
                <div class="email">
                    <label for="email"><strong>{$EMAIL|escape} </strong></label>{$INPUT_EMAIL|escape}
                </div>
                <div class="name">
                    <label for="realname"><strong>{$REALNAME|escape} </strong></label>{$INPUT_REALNAME|escape}
                </div>
            </div>
            <div style="padding-top:10px;"></div>
            <div class="yourcomment" style="padding-top:10px;{$DISPLAY}"><strong>{$FORUM_QUOTE|escape}</strong></div>
            <div>
                <textarea class="input_forum_text" name="forum_quote" style="width:400px;height:90px;{$DISPLAY}" tabindex="3">{$INPUT_FORUM_QUOTE|escape}</textarea>
            </div>
            <div class="yourcomment" style="padding-top:10px;"><strong>{$FORUM|escape}</strong></div>
            <div>
                <textarea class="input_forum_text" name="forum" style="width:400px;height:90px;" tabindex="4">{$INPUT_FORUM|escape}</textarea>
            </div>
            {if isset($MODEMODETEXT)}
                <div class="modtext"><p>{$MODEMODETEXT|escape}</p></div>
            {/if}
            <div class="submitbuttons">
                <a href="{$CANCEL_LINK|escape}" class='cancel button grey'>{$CANCEL_FORUM|escape}</a>
                <a href="javascript:document.forms['new_user_forum'].submit();" class="submit button red">{$SAVE_FORUM|escape}</a>
            </div>
        </div>
    </form>
</div>