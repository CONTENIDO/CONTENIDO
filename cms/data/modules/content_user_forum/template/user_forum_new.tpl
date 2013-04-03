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
            <div><strong>{$REALNAME}</strong></div>
            <div>{$INPUT_REALNAME}</div>
            <div style="padding-top:10px;"><strong>{$EMAIL}</strong></div>
            <div>{$INPUT_EMAIL}</div>
            <div style="padding-top:10px;{$DISPLAY}"><strong>{$FORUM_QUOTE}</strong></div>
            <div><textarea name="forum_quote" style="width:400px;height:90px;{$DISPLAY}">{$INPUT_FORUM_QUOTE}</textarea></div>
            <div style="padding-top:10px;"><strong>{$FORUM}</strong></div>
            <div><textarea name="forum" style="width:400px;height:90px;">{$INPUT_FORUM}</textarea></div>
                <a href="{$CANCEL_LINK}" class='cancel button grey'>{$CANCEL_FORUM}</a>
                <a href="javascript:document.forms['new_user_forum'].submit();" class="submit button red">{$SAVE_FORUM}</a>

        </div>
    </form>
</div>