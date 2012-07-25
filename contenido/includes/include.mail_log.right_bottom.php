<?php

class View_MailLog {

    protected $_cfg = array();
    protected $_tplFile = '';
    protected $_action = '';
    protected $_tableHeader = array();
    protected $_tpl = null;
    protected $_sid = null;
    protected $_message = '';
    protected $_idmail = '';
    protected $_area = '';
    protected $_sess = null;

    public function __construct($conVars) {
        $this->_cfg = $conVars['cfg'];
        $this->_tplFile = $this->_cfg['path']['templates'] . 'template.mail_log.right_bottom.html';
        $this->_action = $conVars['action'];
        $this->_tableHeader = array('checkbox' => i18n('Mark'), 'created' => i18n('Date'), 'from' => i18n('From'), 'to' => i18n('To'), 'action' => i18n('Action'));
        $this->_sess = $conVars['sess'];
        $this->_area = $conVars['area'];
        $this->_tpl = new cTemplate();
        $this->_tpl->set('s', 'SID', $this->_sess->id);
        $this->_tpl->set('s', 'DELETE_TITLE', i18n('Delete email log'));
        $this->_tpl->set('s', 'DELETE_TEXT', i18n('Do you realy wont to delete selected emails.'));
        $this->_tpl->set('s', 'AREA', $conVars['area']);
        $this->_tableHeaderDetail = array('success' => i18n('Success'),
            'mailer' => i18n('Mailer'),
            'exception' => i18n('Exception'),
            'created' => i18n('Date'),
            'idmail_resend' => i18n('Resend'),
            'subject' => i18n('Subject'),
            'header' => i18n('Header'),
            'body' => i18n('Body'),
            'from' => i18n('From'),
            'to' => i18n('To'),
            'cc' => i18n('CC'),
            'bcc' => i18n('BCC'),
            'replay_to' => i18n('Replay to'),
        );
        //set idmail
        if (!empty($_REQUEST['idmail']) && is_numeric($_REQUEST['idmail'])) {
            $this->_idmail = $_REQUEST['idmail'];
        }
    }

    public function makeAction() {
        switch ($this->_action) {

            case 'delete':
                $mailLogCollection = new cApiMailLogCollection();
                $where = '';
                if (!empty($_REQUEST['idmails'])) {

                    $idmails = explode('+', $_REQUEST['idmails']);
                    foreach ($idmails as $idmail) {
                        if (is_numeric($idmail)) {
                            $where .= ' OR idmail=' . $idmail;
                            $itemCollection = new cApiMailLogCollection();
                            $itemCollection->deleteFiles($mailLogCollection->loadItem($this->_idmail));
                        }
                    }
                    //delete
                    $mailLogCollection->deleteByWhereClause('1=2 ' . $where);
                }
                $this->_defaultAction();
                break;
            case 'detail':
                $this->_tpl->set('s', 'HEADER_TEXT', i18n('Detail of Email log'));
                $this->_tpl->set('s', 'IDMAIL', $this->_idmail);
                $this->_tpl->set('s', 'SESSID', $this->_sid);
                $this->_tpl->set('s', 'URL', 'main.php');

                $mailLogCollection = new cApiMailLogCollection();
                $omailItem = $mailLogCollection->loadItem($this->_idmail);
                foreach ($this->_tableHeaderDetail as $key => $value) {

                    switch ($key) {
                        case 'body':
                            $this->_tpl->set('d', 'NAME', $value);
                            $this->_tpl->set('d', 'VALUE', $mailLogCollection->getBody($omailItem));
                            break;
                        case 'header':
                            $this->_tpl->set('d', 'NAME', $value);
                            $this->_tpl->set('d', 'VALUE', $mailLogCollection->getHeader($omailItem));
                            break;
                        case 'success':
                            $this->_tpl->set('d', 'NAME', $value);

                            if ($omailItem->get($key) == 1) {
                                $this->_tpl->set('d', 'VALUE', '<img src="images/but_ok.gif" />');
                                $this->_tpl->set('s', 'RESEND_EMAIL_LINK', '');
                            } else {
                                $this->_tpl->set('d', 'VALUE', '<img src="images/icon_fatalerror.gif" />');
                                $link = sprintf('<a  class="resend" onclick="resendEmail()" alt="%s"> <img src="images/but_refresh.gif">', i18n('Resend email'));
                                $this->_tpl->set('s', 'RESEND_EMAIL_LINK', $link);
                            }
                            break;
                        case 'idmail_resend':
                            $this->_tpl->set('d', 'NAME', $value);
                            $this->_tpl->set('d', 'VALUE', ($omailItem->get($key) == 0) ? i18n('No') : i18n('Yes'));
                            break;
                        case 'bcc':
                        case 'cc':
                            $this->_tpl->set('d', 'NAME', $value);
                            $this->_tpl->set('d', 'VALUE', str_replace('+', '<br/>', $omailItem->get($key)));
                            break;
                        default:
                            $this->_tpl->set('d', 'NAME', $value);
                            $this->_tpl->set('d', 'VALUE', $omailItem->get($key));
                    }
                    $this->_tpl->next();
                }

                $this->_tpl->generate($this->_cfg['path']['templates'] . 'template.mail_log.detail.html');

                break;
            case 'resend_email':
                echo "---resend_email debugg";
                $mailLogCollection = new cApiMailLogCollection();
                $omailItem = $mailLogCollection->loadItem($this->_idmail);

                if ($mailLogCollection->getBody($omailItem) == false) {

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

        while ($oItem = $mailLogCollection->next()) {
            $cells = '';
            foreach ($this->_tableHeader as $key => $item) {
                if ($key == 'checkbox') {
                    $cells .= sprintf('<td class="bordercell"> <input class="mark_emails %s" type="checkbox" name="" value="%s"/ ></td>', 'id_' . $oItem->get('idmail'), $oItem->get('idmail'));
                } elseif ($key == 'action') {
                    $cells .= sprintf('<td class="bordercell"> <a id="%s" class="get_info" href=""> <img src="images/info.gif" alt="" /> </a></td>', 'id_' . $oItem->get('idmail'));
                } else {
                    $cells .= '<td class="bordercell"> ' . $oItem->get($key) . '&nbsp;</td>';
                }
            }
            $this->_tpl->set('d', 'CELLS', $cells);
            $this->_tpl->next();
        }
    }

    private function _defaultAction() {
        //set table header
        $headers = '';
        foreach ($this->_tableHeader as $item) {
            $headers .= '<td class="headerbordercell">' . $item . '</td>';
        }
        $this->_tpl->set('s', 'HEADERS', $headers);

        $this->_tpl->set('s', 'MESSAGE', $this->_message);

        $this->_tpl->set('s', 'MAIL_STATUS', $_REQUEST['mail_status']);
        $this->_tpl->set('s', 'MAIL_CLIENT', $_REQUEST['mail_client']);
        //get Data
        $this->getData();
        $this->_tpl->generate($this->_tplFile);
    }

    public function display() {
        if ($this->_area == 'mail_log_detail') {
            if (is_numeric($_REQUEST['idmail'])) {
                //execute action
                $this->makeAction();
            } else {
                $contenidoNotification = new cGuiNotification();
                $contenidoNotification->displayNotification('error', i18n('No item selected!'));
            }
        } else {
            //execute action
            $this->makeAction();
        }
    }

}

$params = array(
    'cfg' => $cfg,
    'action' => $action,
    'area' => $area,
    'sess' => $sess
);

$viewMailLog = new View_MailLog($params);

if ($area == 'mail_log_detail') {
    if (is_numeric($_REQUEST['idmail'])) {
        $viewMailLog->display();
    } else {
        $contenidoNotification = new cGuiNotification();
        $contenidoNotification->displayNotification('error', i18n('No item selected!'));
    }
} else {
    $viewMailLog->display();
}

?>