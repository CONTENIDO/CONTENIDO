<label class="content_type_label">{$label_overview}</label>
<form action="" method="POST">
<table>
    <tr>
        <td>{$urlProfileLabel}</td>
        <td><input type="text" name="profile" value="{$profile}"></td>
    </tr>
    <tr>
        <td>
            {$lookLabel}
        </td>
        <td>
            <input type="radio" name="look" value="small" {if $look == "small" } checked="checked" {/if} />
            <img src="http://www.xing.com/img/n/xing_icon_32x32.png" alt="profil bild" />
            <br/>
            <input type="radio" name="look" value="big"  {if $look =="big" } checked="checked" {/if} />
            <img src="http://www.xing.com/img/buttons/1_de_btn.gif" alt="profil bild" />
        </td>
    </tr>
    <tr>
        <td>{$nameLabel}</td>
        <td><input type="text" name="name" value="{$name}"></td>
    </tr>
</table>
 <input type="hidden" name="plugin_type" value="xing" />
<input type="submit" value="{$save}" />



