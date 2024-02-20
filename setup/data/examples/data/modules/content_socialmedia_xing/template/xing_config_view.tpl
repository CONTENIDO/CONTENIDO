<label class="con_content_type_label">{$label_overview|escape}</label>
<form action="" method="POST">
    <table style="width: 100%;">
        <tr>
            <td>{$urlProfileLabel|escape}</td>
            <td><input type="text" name="profile" value="{$profile|escape}"></td>
        </tr>
        <tr>
            <td style="width: 20%;">
                {$lookLabel|escape}
            </td>
            <td style="width: 80%;">
                <input type="radio" name="look"
                       value="small" {if $look == "small" } checked="checked" {/if} />
                &nbsp;&nbsp;{$label_optionIcon|escape}
                <br/>
                <input type="radio" name="look"
                       value="big" {if $look =="big" } checked="checked" {/if} />
                &nbsp;&nbsp;{$label_optionButton|escape}
            </td>
        </tr>
        <tr>
            <td>{$nameLabel|escape}</td>
            <td><input type="text" name="name" value="{$name|escape}"></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td><input type="hidden" name="plugin_type" value="xing"/>
                <input type="submit" value="{$save|escape}"/>
            </td>
        </tr>
    </table>

