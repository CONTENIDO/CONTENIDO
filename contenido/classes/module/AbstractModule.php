<?php
cInclude('classes', 'class.globals.config.php');
cInclude('classes', 'module/ModuleException.php');

abstract class AbstractModule extends ConfigGlobals {
    
    protected $cmsValue = array();
    
    const CMS_LABEL = 'label';
    
    const CMS_VALUE = 'value';
    
    public static function setConfig(array $configGlobals) {
        parent::setConfig($configGlobals);
        self::$_db->free();
    }

    public static function test() {
        self::debug('hello test');
        self::debug(self::$_idcatArt, true);
    }

    public static function debug($value, $dump = false) {
        echo '<pre>';
        $dump == false ? print_r($value) : var_dump($value);
        echo '</pre>';
    }
    
    public function setCMSValue(array $cmsValue) {
        if(isset($cmsValue[self::CMS_VALUE]) && isset($cmsValue[self::CMS_LABEL])) {
            
        } else {
            throw new ModulException('Given array should belongs ' . $cmsValue[self::CMS_VALUE] .' or '. $cmsValue[self::CMS_LABEL]);
        }
    }
    
    public function getCMSValue($label) {
        
    }

    public function _getTplCfgByCatId() {
      
        $db = new DB_Contenido();
        $sql = "SELECT idtplcfg FROM " . self::$_cfg['tab']['art_lang'] . " WHERE idart='" .
         Contenido_Security::toInteger(self::$_idart) . "'
			AND idlang='" . Contenido_Security::toInteger(self::$_lang) . "'";
        $db->query($sql);
        if ($db->next_record()) {
            return $db->f("idtplcfg");
        }
        return false;
    }
}

global $idart, $idcat, $sess, $lang, $perm, $auth, $cfg, $contenido, $client, $cfgClient, $encoding, $cCurrentModule, $idtplcfg,
       $idcatart, $edit;

$configGlobals = array(
    
    'contenido' => $contenido, 
    'sess' => $sess, 
    'idart' => $idart, 
    'idcat' => $idcat, 
    'cfg' => $cfg,
    'lang' => $lang, 
    'perm' => $perm, 
    'auth' => $auth, 
    'client' => $client, 
    'cfgClient' => $cfgClient, 
    'db' => new DB_Contenido(), 
    #'smarty' => Contenido_SmartyWrapper::getInstance(), 
    'idmod' => $cCurrentModule,
    'idcatArt' => $idcatart,
    'edit' => $edit,
);
//@TODO activate smarty 
AbstractModule::setConfig($configGlobals);

?>