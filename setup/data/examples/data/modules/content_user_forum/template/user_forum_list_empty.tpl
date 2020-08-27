{*template for empty comment list*}
<div class="user_forum">
    {if !empty($MESSAGE)}
        <div class="form_status_message">
            {$MESSAGE|escape}
        </div>
    {/if}
    <br />
        <a href="front_content.php?userid={$LINK_NEW_FORUM}&user_forum_action=new_forum" class="new button red">{$LINKTEXT|escape}</a>
    <br />
    <div class="blog_data" id="user_forum" name="user_forum">
        <h3 class="blogforum">0 {$FORUM_TEXT|escape}</h3>
        {$MESSAGE|escape}
    </div>
</div>
