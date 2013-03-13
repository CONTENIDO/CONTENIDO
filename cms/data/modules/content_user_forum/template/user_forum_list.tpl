<div class="user_forum">
    <div class="form_status_message">{$MESSAGE}</div>
    <br />
    {$LINK_NEW_FORUM}
    <br />
    <table id="calendarTable">
    <!-- BEGIN:BLOCK -->
    {foreach from=$POSTS item=POST}
        <tr>
            <td valign="top" style="padding-left:{$PADDING}px">
                <div class="block">
                <p><span class="number">Nr.{$POST.NUMBER}</span> <span class="day">{$POST.DAY}</span> <span class="wrote_on">{$POST.TIMESTAMP}</span> {$POST.FROM} <strong>{$POST.REALNAME}</strong></p>
                <div class="quote_handler"><p>{$POST.FORUM_QUOTE}</p></div>
                <p>{$POST.FORUM}</p>
                {$POST.EDIT_INFORMATION}
                </div>
            </td>
            <td valign="top">
            <div class="block">
            <p class="right">{$POST.OPINION}</p>
            <div class="dislike">-{$POST.DISLIKE}</div>
            <div class="like">+{$POST.LIKE}</div>
                {$POST.REPLY}
                {$POST.REPLY_QUOTE}

               </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style=" background-image: url(upload/zimages/pix_common.gif);background-position: bottom;background-repeat: repeat-x;padding-top: 5px;padding-bottom: 5px;"></td>
        </tr>
    {/foreach}
    </table>
</div>