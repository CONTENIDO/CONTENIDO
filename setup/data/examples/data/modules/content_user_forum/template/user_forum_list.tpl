{*template for listing all comments*}
<div class="user_forum">
    {if !empty($MESSAGES)}
        <div class="form_status_message">
            {foreach from=$MESSAGES item=msgItem}
                {$msgItem|escape}<br />
            {/foreach}
        </div>
    {/if}
    <br />
    <a href="front_content.php?userid={$LINK_NEW_FORUM|escape}&user_forum_action=new_forum"class='new button red'>{$LINKTEXT|escape}</a>
    <br />
    <table class="list_table">
    {foreach from=$POSTS item=POST}
        <tr>
            <td style="padding-left:{$POST.PADDING|escape}px">
                <div class="block">
                    <p>
                        <span class="number">
                            Nr.{$POST.NUMBER|escape}
                        </span>
                        <span class="wrote_on">
                            {$POST.AM} {$POST.TIMESTAMP|escape}
                        </span>
                        {$POST.FROM|escape} {$POST.LINKBEGIN|escape}{$POST.EMAIL|escape}
                        <strong>
                            {$POST.REALNAME|escape}
                        </strong>
                        {$POST.LINKEND|escape}
                    </p>
                    <div class="quote_handler">
                        <p>
                            {$POST.FORUM_QUOTE}
                        </p>
                    </div>
                    <p>
                        {$POST.FORUM}
                    </p>
                    {if !empty($POST.EDIT_INFORMATION)}
                        {$POST.EDIT_INFORMATION}
                    {/if}
                </div>
            </td>
            <td>
                <div class="block">
                    <p class="right">
                        {$POST.OPINION|escape}
                    </p>
                    <div class="dislike">
                        <a href="front_content.php?userid={$POST.DISLIKE|escape}&user_forum_action=dislike_forum&user_forum_id={$POST.FORMID|escape}" class="dislike">-{$POST.DISLIKE_COUNT|escape}</a>
                    </div>
                    <div class="like">
                        <a href="front_content.php?userid={$POST.LIKE|escape}&user_forum_action=like_forum&user_forum_id={$POST.FORMID|escape}" class="like">+{$POST.LIKE_COUNT|escape}</a>
                    </div>
                    {if isset($POST.REPLY)}
                         <a href="front_content.php?userid={$POST.REPLY_QUOTE|escape}&user_forum_action=new_forum&user_forum_parent={$POST.FORMID|escape}&user_forum_quote={$POST.FORMID|escape}" class="reply_quote">{$POST.QUOTETEXT|escape}</a>
                        <a href="front_content.php?userid={$POST.REPLY}&user_forum_action=new_forum&user_forum_parent={$POST.FORMID|escape}" class="reply"> {$POST.REPLYTEXT|escape}</a>
                    {/if}
            </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="post_hr"></td>
        </tr>
    {/foreach}
    </table>
</div>
