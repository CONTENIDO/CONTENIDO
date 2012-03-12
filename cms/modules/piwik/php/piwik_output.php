<?php 
cInclude('backend', 'classes/module/AbstractModule.php', true);


if (!class_exists('Piwik')) {
	class Piwik extends AbstractModule {

		private $_httpsUrl = '';
		private $_httpUrl = '';
		private $_idsite = '';
		public function __construct() {

			$this->_httpsUrl = getEffectiveSetting('piwik', 'https_url', '');
			$this->_httpUrl = getEffectiveSetting('piwik', 'http_url', '');
			$this->_idsite = getEffectiveSetting('piwik','idsite', '');
		}
		 
		 
		public function renderOutput() {

			self::$_smarty->assign('httpsUrl', $this->_httpsUrl);
			self::$_smarty->assign('httpUrl', $this->_httpUrl);
			self::$_smarty->assign('idsite', $this->_idsite);
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



