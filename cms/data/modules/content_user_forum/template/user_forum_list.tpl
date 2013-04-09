{*template for listing all comments*}
<div class="user_forum">
    <div class="form_status_message">{$MESSAGE}</div>
    <br />
        <a href="front_content.php?userid={$LINK_NEW_FORUM}&user_forum_action=new_forum"class='new button red'>{$LINKTEXT}</a>
    <br />
    <table id="calendarTable">
    <!-- BEGIN:BLOCK -->
    {foreach from=$POSTS item=POST}
        <tr>
            <td valign="top" style="padding-left:{$PADDING}px">
                <div class="block">
                	<p>
                		<span class="number">
                			Nr.{$POST.NUMBER}
                		</span> 
                		<span class="wrote_on">
                			{$POST.AM} {$POST.TIMESTAMP}
                		</span> 
                		{$POST.FROM} {$POST.LINKBEGIN}{$POST.EMAIL} 
                		<strong>
                			{$POST.REALNAME}
                		</strong>
                		{$POST.LINKEND}
                	</p>
                	<div class="quote_handler">
                		<p>
                			{$POST.FORUM_QUOTE}
                		</p>
                	</div>
                	<p>
                		{$POST.FORUM}
                	</p>
                	{$POST.EDIT_INFORMATION}
            	</div>
            </td>
            <td valign="top">
            	<div class="block">
            		<p class="right">
            			{$POST.OPINION}
            		</p>
            		<div class="dislike">
            			<a href="front_content.php?userid={$POST.DISLIKE}&user_forum_action=dislike_forum&user_forum_id={$POST.FORMID}" class="dislike">-{$POST.DISLIKE_COUNT}</a>
            		</div>
            		<div class="like">
            			<a href="front_content.php?userid={$POST.LIKE}&user_forum_action=like_forum&user_forum_id={$POST.FORMID}" class="like">+{$POST.LIKE_COUNT}</a>
            		</div>
            		{if isset($POST.REPLY)}
                     	<a href="front_content.php?userid={$POST.REPLY_QUOTE}&user_forum_action=new_forum&user_forum_parent={$POST.FORMID}&user_forum_quote={$POST.FORMID}" class="reply_quote">{$POST.QUOTETEXT}</a>
            			<a href="front_content.php?userid={$POST.REPLY}&user_forum_action=new_forum&user_forum_parent={$POST.FORMID}" class="reply"> {$POST.REPLYTEXT}</a>
            		{/if}
            </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style=" background-image: url(upload/zimages/pix_common.gif);background-position: bottom;background-repeat: repeat-x;padding-top: 5px;padding-bottom: 5px;"></td>
        </tr>
      
    {/foreach}
    </table>
</div>
