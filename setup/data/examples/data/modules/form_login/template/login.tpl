<form action="{$form_action|escape}" method="post" class="login">
    <fieldset>
        <label for="username">{$label_name|escape}</label> <input type="text" id="username"
                                                                  name="username"/>
        <br/>
        <label for="password">{$label_pass|escape}</label> <input type="password" id="password"
                                                                  name="password"/>
        <br/>
        <input id="loginBtn" type="submit" name="login" value="{$label_login|escape}"/>
    </fieldset>
</form>