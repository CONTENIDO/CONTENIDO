<?php
cInclude('backend', 'classes/module/AbstractModule.php', true);


if (!class_exists('GoogleAnalytics')) {
    class GoogleAnalytics extends AbstractModule {
		
		private $_authcode = '';
    	public function __construct() {
    		
    		$this->_authcode = getEffectiveSetting('googleanalytics', 'googleanalytics_authentication', '');
    	}
    	
    	
    	
    	public function renderOutput() {
		  
    		self::$_smarty->assign('auth', $this->_authcode);
           self::$_smarty->display('googleanalytics/template/googleanalytics.html');
        }
        
    	
    }
}


$googleAnalytics = new GoogleAnalytics();
$googleAnalytics->renderOutput();


?>


