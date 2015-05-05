<label class="content_type_label">{$label_overview|escape}</label>
<form action="" method="POST">
    <table class="moduleTable" style="width: 100%;">
        <tr>
            <td>{$urlLabel|escape}*</td>
            <td><input style="margin-left: 4px;" type="text" name="url" value="{$url|escape}"></td>
        </tr>
        <tr>
            <td>{$automaticURLLabel}</td>
            <td><input type="checkbox" name="automaticURL" value="1" {if $useAutomaticURL == "1" } checked="1" {/if}>&nbsp;{$autoUrlHelp->render()}
            </td>
        </tr>
        <tr>
            <td style="width: 30%;">
                {$pluginLabel|escape}
            </td>
            <td style="width: 70%;">
                <input style="margin-left: 4px;" type="radio" name="plugin"
                       value="like_button" {if $pluginvalue == "like_button" } checked="checked" {/if}>
                {$likeButtonLabel|escape}&nbsp;&nbsp;{$likeButtonHelp->render()}
                <br />
                <input style="margin-left: 4px;" type="radio" name="plugin"
                       value="like_box" {if $pluginvalue == "like_box"} checked="checked"  {/if}">
                {$likeBoxLabel|escape}&nbsp;&nbsp;{$likeBoxHelp->render()}
            </td>
        </tr>
        <tr>
            <td>
                {$layoutLabel|escape}
            </td>
            <td>
                <input style="margin-left: 4px;" type="radio" name="layout"
                       value="standard" {if $layoutvalue == "standard" || $value !="button_count" && $value !="box_count" } checked="checked" {/if}>
                {$standardLabel|escape}&nbsp;&nbsp;{$standardHelp->render()}
                <br />
                <input style="margin-left: 4px;" type="radio" name="layout"
                       value="box_count" {if $layoutvalue == "box_count"} checked="checked" {/if}>
                {$boxCountLabel|escape}&nbsp;&nbsp;{$boxCountHelp->render()}
                <br />
                <input style="margin-left: 4px;" type="radio" name="layout"
                       value="button_count" {if $layoutvalue =="button_count"} checked="checked" {/if}>
                {$buttonCountLabel|escape}&nbsp;&nbsp;{$buttonCountHelp->render()}
                <br />
                <input style="margin-left: 4px;" type="radio" name="layout"
                       value="button" {if $layoutvalue == "button"} checked="checked" {/if}>
                {$buttonLabel|escape}&nbsp;&nbsp;{$buttonHelp->render()}
            </td>
        </tr>
        <tr>
            <td>{$showFacesLabel|escape}</td>
            <td>
                <input type="checkbox" name="faces"
                       value="true" {if $facesvalue} checked="checked" {/if}>&nbsp;{$showFacesHelp->render()}
            </td>
        </tr>
        <tr>
            <td>{$showPostsLabel|escape}</td>
            <td>
                <input type="checkbox" name="posts"
                       value="true" {if $postsvalue} checked="checked" {/if}>&nbsp;{$showPostsHelp->render()}
            </td>
        </tr>
        <tr>
            <td>{$widthLabel}</td>
            <td><input style="margin-left: 4px;" type="text" name="width" value="{$width}"></td>
        </tr>
        <tr>
            <td>{$heightLabel}</td>
            <td><input style="margin-left: 4px;" type="text" name="height" value="{$height}"></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="hidden" name="plugin_type" value="facebook"/>
                <input type="submit" value="{$save|escape}"/>
            </td>
        </tr>
    </table>
</form>