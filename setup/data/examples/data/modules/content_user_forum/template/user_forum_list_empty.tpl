{*template for empty comment list*}
<div class="user_forum">
    <div class="form_status_message">
        {$MESSAGE|escape}
    </div>
    <br />
        <a href="front_content.php?userid={$LINK_NEW_FORUM}&user_forum_action=new_forum" class="new button red">{$LINKTEXT|escape}</a>
    <br />
    <div class="blog-data" id="user_forum" name="user_forum" style="margin-bottom:15px; display:block;">
        <h3 class="blogforum">0 {$FORUM_TEXT|escape}</h3>
        {$MESSAGE|escape}
    </div>
</div>