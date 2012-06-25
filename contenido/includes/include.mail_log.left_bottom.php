<?php

$tpl = new Template();

$allClients = array('all' => i18n('All'));
$allLangauges = array('all' => i18n('All'));
$allStatus = array('all' => i18n('all'), 'success' => i18n('Success'), 'faild' => i18n('Faild'), 'resend' => i18n('Resend'));

$status = generateSelect($allStatus, $_POST['mail_status'], 'mail_status');

//$languages = generateSelect($allLangauges, $_POST['mail_lang'], 'mail_lang');

$sql = "SELECT
        *
        FROM
        ".$cfg["tab"]["clients"];

$db->query($sql);

while ($db->next_record()) {
    $allClients[$db->f("idclient")] = $db->f("name");
}
$clients = generateSelect($allClients, $_POST['mail_client'], 'mail_client');

$tpl->set('s', 'LANGUAGES','');// $languages);
$tpl->set('s', 'CLIENTS', $clients);
$tpl->set('s', 'STATUS', $status);

//lables
$tpl->set('s', 'LABLE_STATUS', i18n('Status'));
$tpl->set('s', 'LABLE_CLIENTS', i18n('Client'));
$tpl->set('s', 'LABLE_LANGUAGES','');// i18n('Languages'));
$tpl->set('s', 'SUBMIT', i18n('Search'));
$tpl->set('s', 'HEADER_TEXT', i18n('E-Mail filter'));
$tpl->set('s', 'SESSID', $sess->id);
$tpl->set('s', 'URL', 'main.php?area=mail_log&frame=2');


//reload script
if(!empty($_POST['mail_client']) && !empty($_POST['mail_status'])) {
     $reloadScript = "<script type=\"text/javascript\">
                             var left_bottom = parent.parent.frames['right'].frames['right_bottom'];
                             if (left_bottom) {
                                 var href = left_bottom.location.href;
                                 href = href.replace(/&idlay[^&]*/, '');
                             left_bottom.location.href = href+'&mail_client=".$_POST['mail_client']."&mail_status=".$_POST['mail_status']."';

                             }
                    </script>";

    $tpl->set('s', 'RELOAD_SCRIPT', $reloadScript);
}else  {
    $tpl->set('s', 'RELOAD_SCRIPT', '');
}

$tpl->generate($cfg['path']['templates'] . 'template.mail_log.left_bottom.html');













function generateSelect($options, $selected, $name = '' , $id = '', $class = '') {

    $select = '';
    foreach($options as $key => $value) {

        if($selected == $key) {
            $select .= '<option selected="selected" value="'.$key.'">'.$value . '</option>';
        }else  {
            $select .= '<option  value="'.$key.'">'.$value . '</option>';
        }
    }

    return '<select name="'.$name.'" class="'.$class.'" id="'.$id.'" >'. $select .'</select>';
}
?>