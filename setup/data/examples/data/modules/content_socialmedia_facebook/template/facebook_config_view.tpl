<label class="content_type_label">{$label_overview}</label>
<form action="" method="POST">
<table>
    <tr>
        <td>{$urlLabel}*</td>
        <td><input type="text" name="url" value="{$url}"></td>
    </tr>
    <tr>
        <td>{$automaticURLLabel}</td>
        <td><input type="checkbox" name="automaticURL" value="1" {if $useAutomaticURL == "1" } checked="1" {/if}></td>
    </tr>
    <tr>
        <td>
            {$pluginLabel}
        </td>
        <td>
            <input type="radio" name="plugin" value="like_button" {if $pluginvalue == "like_button" } checked="checked" {/if}>
            {$likeButtonLabel}
            <br/>
            <input type="radio" name="plugin" value="like_box" {if $pluginvalue == "like_box"} checked="checked"  {/if}">
            {$likeBoxLabel}
        </td>
    </tr>
    <tr>
        <td>
            {$layoutLabel}
        </td>
        <td>
            <input type="radio" name="layout" value="standard" {if $layoutvalue == "standard" || $value !="button_count" && $value !="box_count" } checked="checked" {/if}>
            {$standardLabel}
            <br/>
            <input type="radio" name="layout" value="button_count" {if $layoutvalue =="button_count"} checked="checked" {/if}>
            {$buttonCountLabel}
            <br/>
            <input type="radio" name="layout" value="box_count" {if $layoutvalue == "box_count"} checked="checked" {/if}>
            {$boxCountLabel}
        </td>
    </tr>
    <tr>
        <td>{$showFacesLabel}</td>
        <td>
            <input type="checkbox" name="faces" value="true" {if $facesvalue} checked="checked" {/if}>
        </td>
    </tr>
    <tr>
        <td>{$widthLabel}</td>
        <td><input type="text" name="width" value="{$width}"></td>
    </tr>
    <tr>
        <td>{$heightLabel}</td>
        <td><input type="text" name="height" value="{$height}"></td>
    </tr>
 </table>
 <input type="hidden" name="plugin_type" value="facebook" />
 <input type="submit" value="{$save}" />
</form>

