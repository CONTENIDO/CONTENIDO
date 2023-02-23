<?php
/**
 * This file contains the cronjob to send the reminder items.
 *
 * @package    Core
 * @subpackage Cronjob
 *
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $cfg, $client;

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

$oldclient = $client;

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = cRegistry::getDb();

    $sql = 'SELECT idclient FROM '.$cfg['tab']['clients'];
    $db->query($sql);

    $clients     = [];
    $clientNames = [];

    while ($db->nextRecord()) {
        $clients[] = $db->f('idclient');
    }

    foreach ($clients as $client) {
        $mydate = time();

        $props = new cApiPropertyCollection();
        $props->select("itemtype = 'idcommunication' AND type = 'todo' AND name = 'reminderdate' AND value < $mydate AND value != 0 AND idclient=$client");

        $pastreminders = [];

        while (($prop = $props->next()) !== false) {
            $pastreminders[] = $prop->get('itemid');
        }

        $todoitem = new TODOItem();

        foreach ($pastreminders as $reminder) {
            $todoitem->loadByPrimaryKey($reminder);

            if ($todoitem->get('idclient') == $client) {
                // Check if email noti is active
                if ($todoitem->getProperty('todo', 'emailnoti') == 1 && $todoitem->getProperty('todo', 'emailnoti-sent') == 0) {
                    $user = new cApiUser($todoitem->get('recipient'));
                    $realname = $user->get('realname');

                    $client = $todoitem->get('idclient');
                    if (!isset($clientNames[$client])) {
                        $clientNames[$client] = cRegistry::getClient()->get("name");
                        if($clientNames[$client] == "") {
                            $clientNames[$client] = i18n("No client");
                        }
                    }
                    $clientname = $clientNames[$client];

                    $todoitem->setProperty('todo', 'emailnoti-sent', '1');
                    $todoitem->setProperty('todo', 'emailnoti', '0');

                    $message = i18n("Hello %s,\n\nyou've got a new reminder for the client '%s' at\n%s:\n\n%s");
                    $path = cRegistry::getBackendUrl();
                    $message = sprintf($message, $realname, $clientname, $path, $todoitem->get('message'));

                    $mailer = new cMailer();
                    $mailer->sendMail(getEffectiveSetting("system", "mail_sender", "info@contenido.org"), $user->get('email'), $todoitem->get('subject'), $message);
                }

                $todoitem->setProperty('todo', 'reminderdate', '0');
            }
        }
    }
}

$client = $oldclient;
