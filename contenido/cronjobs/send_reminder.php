<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Cronjob to send reminder items
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Cronjob
 * @version    1.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 *
 * {@internal
 *   created  2004-02-12
 *   modified 2008-06-16, H. Librenz - Hotfix: Added check for malicious script call
 *   modified 2008-06-30, Dominik Ziegler, fixed bug CON-143, added new header
 *   modified 2010-05-20, Murat Purc, standardized CONTENIDO startup and security check invocations, see [#CON-307]
 *   modified 2011-05-12, Dominik Ziegler, forced include of startup.php [#CON-390]
 *   modified 2011-10-12, Murat Purc, absolute path to startup [#CON-447] and some cleanup
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

global $cfg, $client;

$oldclient = $client;

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = cRegistry::getDb();

    $sql = 'SELECT idclient FROM '.$cfg['tab']['clients'];
    $db->query($sql);

    $clients = array();
    $clientNames = array();

    while ($db->next_record()) {
        $clients[] = $db->f('idclient');
    }

    foreach ($clients as $client) {
        $mydate = time();

        $props = new cApiPropertyCollection();
        $props->select("itemtype = 'idcommunication' AND type = 'todo' AND name = 'reminderdate' AND value < $mydate AND value != 0 AND idclient=$client");
        $pastreminders = array();

        while ($prop = $props->next()) {
            $pastreminders[] = $prop->get('itemid');
        }

        $todoitem = new TODOItem();

        foreach ($pastreminders as $reminder) {
            $todoitem->loadByPrimaryKey($reminder);

            if ($todoitem->get('idclient') == $client) {
                // Check if email noti is active
                if ($todoitem->getProperty('todo', 'emailnoti') == 1 && $todoitem->getProperty('todo', 'emailnoti-sent') == 0) {
                    //modified : 2008-07-03 - use php mailer class instead of mail()
                    $sMailhost = getSystemProperty('system', 'mail_host');
                    if ($sMailhost == '') {
                        $sMailhost = 'localhost';
                    }

                    $oMail = new PHPMailer();
                    $oMail->Host = $sMailhost;
                    $oMail->IsHTML(0);
                    $oMail->WordWrap = 1000;
                    $oMail->IsMail();

                    $user = new cApiUser($todoitem->get('recipient'));

                    $oMail->AddAddress($user->get('email'), '');
                    $realname = $user->get('realname');
                    $oMail->Subject = $todoitem->get('subject');

                    $client = $todoitem->get('idclient');
                    if (!isset($clientNames[$client])) {
                        $oClientColl = new cApiClientCollection();
                        $clientNames[$client] = $oClientColl->getClientname($idclient);
                    }
                    $clientname = $clientNames[$client];

                    $todoitem->setProperty('todo', 'emailnoti-sent', '1');
                    $todoitem->setProperty('todo', 'emailnoti', '0');

                    $message = i18n("Hello %s,\n\nyou've got a new reminder for the client '%s' at\n%s:\n\n%s");

                    $path = $cfg['path']['contenido_fullhtml'];

                    $message = sprintf($message, $realname, $clientname, $path, $todoitem->get('message'));
                    $oMail->Body = $message;
                    $oMail->Send();
                }

                $todoitem->setProperty('todo', 'reminderdate', '0');
            }
        }
    }
}

$client = $oldclient;

?>