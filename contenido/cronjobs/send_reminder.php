<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Cronjob to send reminder items
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   $Id: send_reminder.php 1157 2010-05-20 14:10:43Z xmurrix $:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $cfg, $area, $client;

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

$oldclient = $client;

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = new DB_Contenido();

    $sql = 'SELECT idclient FROM ' . $cfg['tab']['clients'];
    $db->query($sql);

    $clients = array();
    $clientNames = array();

    while ($db->next_record()) {
        $clients[] = $db->f('idclient');
    }

    foreach ($clients as $client) {
        $mydate = time();

        $props = new PropertyCollection();
        $props->select("itemtype = 'idcommunication' AND type = 'todo' AND name = 'reminderdate' AND value < $mydate AND value != 0 AND idclient=$client");
        $pastreminders = array();

        while (($prop = $props->next()) !== false) {
            $pastreminders[] = $prop->get('itemid');
        }

        $todoitem = new TODOItem();

        foreach ($pastreminders as $reminder) {
            $todoitem->loadByPrimaryKey($reminder);

            if ($todoitem->get('idclient') == $client) {
                // Check if email noti is active
                if ($todoitem->getProperty('todo', 'emailnoti') == 1 && $todoitem->getProperty('todo', 'emailnoti-sent') == 0) {
                    $user = new User();
                    $user->loadUserByUserID($todoitem->get('recipient'));
                    $realname = $user->getField('realname');

                    $client = $todoitem->get('idclient');
                    if (!isset($clientNames[$client])) {
                        $clientNames[$client] = getClientName($client);
                    }
                    $clientname = $clientNames[$client];

                    $todoitem->setProperty('todo', 'emailnoti-sent', '1');
                    $todoitem->setProperty('todo', 'emailnoti', '0');

                    $message = i18n("Hello %s,\n\nyou've got a new reminder for the client '%s' at\n%s:\n\n%s");
                    $path = $cfg['path']['contenido_fullhtml'];
                    $message = sprintf($message, $realname, $clientname, $path, $todoitem->get('message'));

                    $sMailhost = getSystemProperty('system', 'mail_host');
                    if ($sMailhost == '') {
                        $sMailhost = 'localhost';
                    }

                    $oMail = new PHPMailer();
                    $oMail->Host = $sMailhost;
                    $oMail->IsHTML(0);
                    $oMail->WordWrap = 1000;
                    $oMail->IsMail();
                    $oMail->AddAddress($user->getField('email'), '');
                    $oMail->Subject = $todoitem->get('subject');
                    $oMail->Body = $message;
                    $oMail->Send();
                }

                $todoitem->setProperty('todo', 'reminderdate', '0');
            }
        }
    }
}

$client = $oldclient;
