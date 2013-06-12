<label class="content_type_label">{$label_overview}</label>
<form action="" method="POST">
<table>
    <tr>
        <td>{$nameLabel} </td>
        <td><input type="text" name="twitter_name" value="{$twitterName}"/></td>
    </tr>
    <tr>
        <td>{$lookLabel}</td>
        <td>
            <input type="radio" name="look" value="small" {if $look == "small" } checked="checked" {/if} />
            <label>{$smallIconLabel}</label>
            <br/>
            <input type="radio" name="look" value="big"  {if $look =="big" } checked="checked" {/if} />
            <label>{$bigIconLabel}</label>
        </td>
    </tr>
    <tr>
        <td>{$showTweetsLabel}</td>
        <td><input type="checkbox" name="show_tweets" value="1" {if $show_tweets} checked="checked" {/if} ></td>
    </tr>
    <tr>
        <td>{$countTweetsLabel}</td>
        <td><input type="text" name="count_tweets" value="{$count_tweets}"/></td>
    </tr>
    <tr>
        <td>{$showFollowButtonLabel}</td>
        <td><input type="checkbox" name="show_follow_button" value="1" {if $show_follow_button} checked="checked" {/if} ></td>
    </tr>
    <tr>
        <td>{$showTweetButtonLabel}</td>
        <td><input type="checkbox" name="show_tweet_button" value="1" {if $show_tweet_button} checked="checked" {/if} ></td>
    </tr>

     <tr>
        <td>{$defaultTextLabel}</td>
        <td><input type="text" name="default_text" value="{$default_text}"/></td>
    </tr>
    <tr>
        <td>{$urlToShareLabel}</td>
        <td><input type="text" name="url_to_share" value="{$url_to_share}"/></td>
    </tr>
    <tr>
        <td>{$showCountLabel}</td>
        <td><input type="checkbox" name="show_count" value="1" {if $show_count} checked="checked" {/if} ></td>
    </tr>
</table>
<input type="hidden" name="plugin_type" value="twitter" />
<input type="submit" value="{$save}" />
</form>

