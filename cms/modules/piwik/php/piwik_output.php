<?php 
cInclude('backend', 'classes/module/AbstractModule.php', true);


if (!class_exists('Piwik')) {
	class Piwik extends AbstractModule {

		private $_httpsUrl = '';
		private $_httpUrl = '';
		public function __construct() {

			$this->_httpsUrl = getEffectiveSetting('piwik', 'https_url', '');
			$this->_httpUrl = getEffectiveSetting('piwik', 'http_url', '');
		}
		 
		 
		public function renderOutput() {

			self::$_smarty->assign('httpsUrl', $this->_httpsUrl);
			self::$_smarty->assign('httpUrl', $this->_httpUrl);
			
			$noScriptUrl = $this->_httpUrl;
			if(isset($_SERVER["https"])) {
				$noScriptUrl = $this->_httpsUrl;
				
			}
			self::$_smarty->assign('noScriptUrl', $noScriptUrl);
			self::$_smarty->display('piwik/template/piwik.html');
		}

		 
	}
}


$piwik = new Piwik();
$piwik->renderOutput();


?>



