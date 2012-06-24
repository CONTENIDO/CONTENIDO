<?php

class View_MailLog {

	protected $_cfg = array();
	protected $_tplFile = '';
	protected $_action = '';
	protected $_tableHeader = array();
	protected $_tpl = null;
	protected $_sid = null;
	protected $_message = '';
	public function __construct($conVars) {
		
		$this->_cfg = $conVars['cfg'];
		$this->_tplFile = $this->_cfg['path']['templates'] . 'template.mail_log.right_bottom.html';
		$this->_action = $conVars['action'];
		//key (table col) 
		$this->_tableHeader = array('checkbox'=> i18n('Mark'), 'created' => i18n('Date'), 'from' => i18n('From'), 'to' => i18n('To'), 'action'=> i18n('Action'));
		
		$this->_tpl = new Template();
		$this->_tpl->set('s', 'SID', $conVars['sid']);
	}
	
	
	public function makeAction() {
		
		switch($this->_action) {
			
			case 'delete':
				$mailLogCollection = new cApiMailLogCollection();
				$where = '';
				if(!empty($_REQUEST['idmails'])) {
					
					$idmails = explode('+', $_REQUEST['idmails']);
					foreach($idmails as $idmail) {
						if(is_numeric($idmail)) {
							$where .= ' OR idmail='.$idmail;
						}
					}
				//delete 	
				$mailLogCollection->deleteByWhereClause('1=2 '. $where);
				}
				
			break;
			default:
			
		}
		
	}
	
	protected function getData() {
		$mailLogCollection = new cApiMailLogCollection();
		
		if(!empty($_REQUEST['mail_status'])) {
			
			switch($_REQUEST['mail_status']) {
				
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
		
		if(!empty($_REQUEST['mail_client'])) {
			if(is_numeric($_REQUEST['mail_client'])) {
				$mailLogCollection->setWhere('idclient', $_REQUEST['mail_client']);
			}
		}
		
		$mailLogCollection->query();
		
		while ($oItem = $mailLogCollection->next()) {
			$cells = '';
			foreach($this->_tableHeader as $key => $item) {
				if($key == 'checkbox') {
					$cells .= sprintf('<td class="bordercell"> <input class="mark_emails %s" type="checkbox" name="" value="%s"/ ></td>','id_'.$oItem->get('idmail'),  $oItem->get('idmail'));
				}elseif($key == 'action') {
					$cells .= sprintf('<td class="bordercell"> <a id="%s" class="get_info" href=""> <img src="images/info.gif" alt="" /> </a></td>', 'id_'.$oItem->get('idmail'));
				}else {
				$cells .= '<td class="bordercell"> ' .$oItem->get($key). '&nbsp;</td>';
				}
				
			}
			$this->_tpl->set('d', 'CELLS', $cells);
			$this->_tpl->next();
		}
		
		
	}
	
	public function display() {
	
	$this->makeAction();
	//set table header
	$headers = '';
	foreach($this->_tableHeader as $item) {
		$headers .= '<td class="headerbordercell">'. $item . '</td>';
	}
	$this->_tpl->set('s', 'HEADERS',  $headers);
	
	$this->_tpl->set('s', 'DELETE_TITLE', i18n('Delete emails log'));
	$this->_tpl->set('s', 'DELETE_TEXT', i18n('Do you realy wont to delete selected emails.'));
	
	$this->_tpl->set('s', 'MESSAGE', $this->_message);
	
	$this->_tpl->set('s', 'MAIL_STATUS', $_REQUEST['mail_status']);
	$this->_tpl->set('s', 'MAIL_CLIENT', $_REQUEST['mail_client']);
	//get Data
	$this->getData();
	$this->_tpl->generate($this->_tplFile);	
	}	
	
}

$params = array('cfg' => $cfg, 
				'action' => $action,
				'sid' => $sess->id);
echo "idmail: ".$_REQUEST['idmail'];				
$viewMailLog = new View_MailLog($params);
$viewMailLog->display();

?>