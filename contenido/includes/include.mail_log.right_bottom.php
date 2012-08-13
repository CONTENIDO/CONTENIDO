<?php
class View_MailLog {

    protected $_cfg;

    protected $_action = '';

    protected $_tableHeader;

    protected $_tpl;

    protected $_idmail = '';

    protected $_area = '';

    /**
     * Constructor initialises class attributes and prepares template.
     *
     * @param string $action the action which should be processed: delete,
     *        detail, resend_mail
     * @param string $area mail_log_overview or mail_log_detail depending on
     *        which area should be shown
     */
    public function __construct($action, $area) {
        $this->_cfg = cRegistry::getConfig();
        $this->_action = $action;
        $this->_tableHeader = array(
            'checkbox' => i18n('Mark'),
            'created' => i18n('Date'),
            'from' => i18n('From'),
            'to' => i18n('To'),
            'action' => i18n('Action')
        );
        $this->_area = $area;
        $this->_tpl = new cTemplate();
        $session = cRegistry::getSession();
        $this->_tpl->set('s', 'SID', $session->id);
        $this->_tpl->set('s', 'DELETE_TITLE', i18n('Delete email log'));
        $this->_tpl->set('s', 'DELETE_TEXT', i18n('Do you realy wont to delete selected emails.'));
        $this->_tpl->set('s', 'AREA', $area);
        $this->_tableHeaderDetail = array(
            'from' => i18n('From'),
            'to' => i18n('To'),
            'reply_to' => i18n('Reply to'),
            'cc' => i18n('CC'),
            'bcc' => i18n('BCC'),
            'subject' => i18n('Subject'),
            'body' => i18n('Body'),
            'created' => i18n('Date')
        );
        // set idmail
        if (!empty($_REQUEST['idmail']) && is_numeric($_REQUEST['idmail'])) {
            $this->_idmail = $_REQUEST['idmail'];
        }
    }

    public function makeAction() {
        switch ($this->_action) {
            case 'delete':
                if (!empty($_REQUEST['idmails'])) {
                    $mailLogCollection = new cApiMailLogCollection();
                    $where = '';
                    $idmails = json_decode($_REQUEST['idmails']);
                    foreach ($idmails as $idmail) {
                        if (is_numeric($idmail)) {
                            $where .= ' OR `idmail`=' . $idmail;
                        }
                    }
                    // cut the first " OR "
                    $where = substr($where, 4);
                    // delete all entries with the given idmails
                    $mailLogCollection->deleteByWhereClause($where);
                    // also delete the entries in mail_log_success
                    $mailLogSuccessCollection = new cApiMailLogSuccessCollection();
                    $mailLogSuccessCollection->deleteByWhereClause($where);
                }
                $this->_defaultAction();
                break;
            case 'detail':
                $this->_tpl->set('s', 'HEADER_TEXT', i18n('Detail of Email log'));
                $this->_tpl->set('s', 'IDMAIL', $this->_idmail);

                $mailItem = new cApiMailLog($this->_idmail);
                foreach ($this->_tableHeaderDetail as $key => $value) {
                    switch ($key) {
                        // TODO success and resend mails now have to be shown a
                        // different way - we can have multiple recipients per
                        // mail! in the overview, each mail is shown only once.
                        // the different recipients and success messages should
                        // be shown separately
                        case 'success':
                            $this->_tpl->set('d', 'NAME', $value);
                            if ($mailItem->get($key) == 1) {
                                $this->_tpl->set('d', 'VALUE', '<img src="images/but_ok.gif">');
                                $this->_tpl->set('s', 'RESEND_EMAIL_LINK', '');
                            } else {
                                $this->_tpl->set('d', 'VALUE', '<img src="images/icon_fatalerror.gif">');
                                $link = sprintf('<a  class="resend" onclick="resendEmail()" alt="%s"> <img src="images/but_refresh.gif">', i18n('Resend email'));
                                $this->_tpl->set('s', 'RESEND_EMAIL_LINK', $link);
                            }
                            break;
                        case 'idmail_resend':
                            $this->_tpl->set('d', 'NAME', $value);
                            $this->_tpl->set('d', 'VALUE', ($mailItem->get($key) == 0)? i18n('No') : i18n('Yes'));
                            break;
                        case 'from':
                        case 'to':
                        case 'reply_to':
                        case 'cc':
                        case 'bcc':
                            $this->_tpl->set('d', 'NAME', $value);
                            $addresses = $this->_decodeAddresses($mailItem->get($key));
                            $this->_tpl->set('d', 'VALUE', $addresses);
                            break;
                        default:
                            $this->_tpl->set('d', 'NAME', $value);
                            $this->_tpl->set('d', 'VALUE', $mailItem->get($key));
                    }
                    $this->_tpl->next();
                }
                $this->_tpl->generate($this->_cfg['path']['templates'] . 'template.mail_log.detail.html');
                break;
            case 'resend_mail':
                $mailLogCollection = new cApiMailLogCollection();
                $mailItem = $mailLogCollection->loadItem($this->_idmail);

                if ($mailLogCollection->getBody($mailItem) == false) {
                } else {
                }

                $this->_defaultAction();
                break;
            default:
                $this->_defaultAction();
        }
    }

    protected function getData() {
        $mailLogCollection = new cApiMailLogCollection();

        if (!empty($_REQUEST['mail_status'])) {
            switch ($_REQUEST['mail_status']) {
                case 'faild':
                    $mailLogCollection->setWhere('success', 0);
                    break;
                case 'success':
                    $mailLogCollection->setWhere('success', 1);
                    break;
                case 'resend':
                    $mailLogCollection->setWhere('idmail_resend', 0, '>');
                    break;
                default:
            }
        }

        if (!empty($_REQUEST['mail_client'])) {
            if (is_numeric($_REQUEST['mail_client'])) {
                $mailLogCollection->setWhere('idclient', $_REQUEST['mail_client']);
            }
        }

        $mailLogCollection->query();

        while (($item = $mailLogCollection->next()) !== false) {
            $cells = '';
            foreach ($this->_tableHeader as $key => $value) {
                switch ($key) {
                    case 'checkbox':
                        $cells .= sprintf('<td class="bordercell"> <input class="mark_emails %s" type="checkbox" name="" value="%s"/ ></td>', 'id_' . $item->get('idmail'), $item->get('idmail'));
                        break;
                    case 'action':
                        $cells .= sprintf('<td class="bordercell"> <a id="%s" class="get_info" href=""> <img src="images/info.gif" alt=""> </a></td>', 'id_' . $item->get('idmail'));
                        break;
                    case 'from':
                    case 'to':
                        $addresses = $item->get($key);
                        $addresses = $this->_decodeAddresses($addresses);
                        $cells .= '<td class="bordercell"> ' . $addresses . '&nbsp;</td>';
                        break;
                    default:
                        $cells .= '<td class="bordercell"> ' . $item->get($key) . '&nbsp;</td>';
                }
            }
            $this->_tpl->set('d', 'CELLS', $cells);
            $this->_tpl->next();
        }
    }

    /**
     * Takes an associative array where the keys represent the mail addresses
     * and the values optionally represent the mailer name and returns an HTML
     * representation in the following form:
     * Vorname Nachname <vorname.nachname@domain.tld>
     * Vorname2 Nachname2 <vorname2.nachname2@domain2.tld>
     *
     * @param array $addresses associative array containing the mail addresses
     *        as keys and the mailer names as values
     * @return string HTML code showing the given mail addresses and names
     */
    private function _decodeAddresses($addresses) {
        $result = '';
        $addresses = json_decode($addresses, true);
        foreach ($addresses as $mail => $name) {
            $result .= $name . ' &lt;' . $mail . '&gt;<br>';
        }
        $result = substr($result, 0, strlen($result) - 6);

        return $result;
    }

    private function _defaultAction() {
        // set table header
        $headers = '';
        foreach ($this->_tableHeader as $header) {
            $headers .= '<td class="headerbordercell">' . $header . '</td>';
        }
        $this->_tpl->set('s', 'HEADERS', $headers);

        $this->_tpl->set('s', 'MESSAGE', $this->_message);

        $this->_tpl->set('s', 'MAIL_STATUS', $_REQUEST['mail_status']);
        $this->_tpl->set('s', 'MAIL_CLIENT', $_REQUEST['mail_client']);
        // get Data
        $this->getData();
        $this->_tpl->generate($this->_cfg['path']['templates'] . 'template.mail_log.right_bottom.html');
    }

    /**
     * Displays the right content in dependency of $this->_area.
     */
    public function display() {
        // show notification if mail logging is disabled
        $log = getSystemProperty('system', 'mail_log');
        if ($log === 'false') {
            $contenidoNotification = new cGuiNotification();
            $contenidoNotification->displayNotification('warning', i18n('Mail logging was disabled!'));
        } else if ($this->_area == 'mail_log_detail') {
            if (is_numeric($_REQUEST['idmail'])) {
                // execute action
                $this->makeAction();
            } else {
                $contenidoNotification = new cGuiNotification();
                $contenidoNotification->displayNotification('error', i18n('No item selected!'));
            }
        } else {
            // execute action
            $this->makeAction();
        }
    }

}

$viewMailLog = new View_MailLog($action, $area);
$viewMailLog->display();
