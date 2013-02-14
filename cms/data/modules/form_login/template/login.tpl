<form action="{$form_action}" method="post" class="login">
    <fieldset>
        <label for="username">{$label_name}</label> <input type="text" id="username" name="username" />
        <br />
        <label for="password">{$label_pass}</label> <input type="password" id="password" name="password" />
        <br />
        <input id="loginBtn" type="submit" name="login" value="{$label_login}" />
    </fieldset>
</form>