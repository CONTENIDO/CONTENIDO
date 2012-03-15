<?php
cInclude('backend', 'classes/class.globals.config.php');
cInclude('backend', 'classes/module/ModuleException.php');
cInclude('backend', 'classes/module/ModuleInterface.php');
cInclude('backend', 'classes/module/ModelConConfig.php');

if (class_exists('AbstractModule')=== false) {
    
    abstract class AbstractModule extends ConfigGlobals implements ModuleInterface {

        protected $_cmsVariable = array();

        protected static $idMod = 0;

        protected static $_tableName = '';

        protected $_allModuleConfiguration = array();

        protected $_uniqIndex = '';

        protected $_isCatConfig = false;

        const DB_CONFIG_NAME = 'config_new';

        /**
         * @return the $idMod
         */
        public static function getIdMod() {
            return AbstractModule::$idMod;
        }

        protected function __construct() {
            // Sets Configuration for this module 
            $this->_setAllConfigForThisModule();
            $this->_uniqIndex = str_replace('.', '_', $this->_getUniqueId());
        }

        /** (non-PHPdoc)
         * @see ModuleInterface::renderOutput()
         */
        public function renderOutput() {
            // TODO Auto-generated method stub
        }

        /** Configuration in Edit mode. 
         * @see Module$postInterface::setConfigEditMode()
         */
        public function setConfigEditMode() {
            $configIndex = 0;
           
            self::$_smarty->assign('session', self::$_contenido);
            self::$_smarty->assign('idArtLang', self::$_idart);
            
            if (is_object($this->_allModuleConfiguration[0])) {
                $identifierOfModul = $this->_allModuleConfiguration[0]->getIndex();
                $this->_isCatConfig = strlen($this->_allModuleConfiguration[0]->getCatConfig()== 1) ? true : false;
            } else {
                $identifierOfModul = self::$_idmod;
            
     // config,
            }
            
            self::$_smarty->assign('id', $identifierOfModul);
            self::$_smarty->assign('catConfig', $this->_isCatConfig);
        }

        /**
         * AbstractModule::setConfig($configGlobals);
         * Set config globals, modul index and db issues
         * @param array $configGlobals
         */
        public static function setConfig(array $configGlobals) {
            parent::setConfig($configGlobals);
            if (! isset($configGlobals['idcatlang'])|| 0== $configGlobals['idcatlang']) {
                $sql = "SELECT idcatlang FROM ". self::$_cfg["tab"]["cat_lang"]. " WHERE idcat = '".
                 Contenido_Security::toInteger(self::$_idcat). "' AND idlang = '".
                 Contenido_Security::toInteger(self::$_lang). "'";
                self::$_db->free();
                self::$_db->query($sql);
                self::$_db->next_record();
                parent::setIdcatLang(self::$_db->f("idcatlang"));
            }
            
            self::$_db->free();
            self::$_tableName = self::$_cfg['sql']['sqlprefix']. '_'. self::DB_CONFIG_NAME;
            
            self::$idMod++;
        }

        public static function debug($value, $dump = false) {
        	cDebug(print_r($value, true));
        }

        /**
         * 
         * Sets cms value 
         * @param string $name
         * @param array $cmsVariable
         */
        protected function _setModuleValue($name, $cmsVariable) {
            $cmsInsertValue = array();
            $cmsVariable = ! is_array($cmsVariable) ? array(
                $cmsVariable
            ) : $cmsVariable;
            
            foreach ($cmsVariable as $key => $value) {
                
                if (strlen($value)> 0) {
                    $this->_insertOrUpdateCMSValue(
                    array(
                        
                    'name' => $name, 'key' => $key, 'value' => $value
                    ));
                }
            }
        }

        /**
         * 
         * This function does update or insert the cms value of modul settings.
         * @param array $cmsInsertValue
         * @return bool
         */
        private function _insertOrUpdateCMSValue(array $cmsInsertValue) {
            $sql = '';
            self::$_db->free();
            
            $sql = 'SELECT * from `con_config_new` 
        		WHERE 
            		`name` = "'. $cmsInsertValue['name']. '" AND 
            		`key`="'. $cmsInsertValue['key']. '" AND 
            		`idmod`="'. self::$_idmod. '" AND 
            		`idcatlang` ="'. self::$_idcatLang. '" AND 
            		`idartlang`="'. self::$_idartLang. '" limit 1';
            self::$_db->query($sql);
            
            if (self::$_db->next_record()) {
                self::$_db->free();
                // update
                $sql = 'UPDATE `con_config_new` set 
           		       `value` ="'. $cmsInsertValue['value']. '" 
        		   WHERE 
                		`name` = "'. $cmsInsertValue['name']. '" AND 
                		`key`="'. $cmsInsertValue['key']. '" AND 
                		`idmod` ="'. self::$_idmod. '" AND
                		`idcatlang` ="'. self::$_idcatLang. '" AND  
                		`idartlang`="'. self::$_idartLang. '" limit 1';
                return self::$_db->query($sql);
                self::debug(self::$_db->Error);
            } else {
                // insert
                self::$_db->free();
                $sql = 'INSERT INTO `con_config_new`(`id`, `idartlang`, `idcatlang`, `idmod`, `name`, `key`, `value`, `index`)      
            		VALUES 
            			(NULL, "'. self::$_idartLang. '", "'. self::$_idcatLang. '",
            			 "'. self::$_idmod. '",
            			 "'. $cmsInsertValue['name']. '",
            			 "'. $cmsInsertValue['key']. '",
            			 "'. $cmsInsertValue['value']. '",
            			 "'. $this->_uniqIndex. '")';
                
                return self::$_db->query($sql);
                self::debug(self::$_db->Error);
            }
        }

        /**
         * 
         * This function sets all information for this modul in ModelConConfig
         * 
         * return ModelConConfig objects
         */
        protected function _getAllConfigForThisModule() {
            return $this->_allModuleConfiguration;
        }

        /**
         * 
         * This function sets all information for this modul in ModelConConfig
         * 
         * return ModelConConfig objects
         */
        protected function _setAllConfigForThisModule() {
            //$conConfig = new ModelConConfig();
            self::$_db->free();
            $sql = 'SELECT * from `con_config_new` 
        			WHERE 
                		`idmod`="'. self::$_idmod. '" AND 
                		`idcatlang`="'. self::$_idcatLang. '" AND
                		`idartlang`="'. self::$_idartLang. '"';
            self::$_db->query($sql);
            
            $modelConfig = new ModelConConfig();
            $modelConfig->setModulConfiguration(self::$_db);
            $this->_allModuleConfiguration = $modelConfig->getModuleConfiguration();
        }

        /**
         * 
         * Get value for given name and key combination config.
         * @param array $cmsSelectLabel
         * @throws ModuleException
         * 
         * return mixed $cmsValue
         */
        protected function _getModuleValue(array $cmsSelectLabel) {
            $cmsValue = '';
            if (count($this->_allModuleConfiguration)> 0) {
                foreach ($this->_allModuleConfiguration as $modulConfiguration) {
                    if ($modulConfiguration->getName()== $cmsSelectLabel['name']&&
                     $modulConfiguration->getKey()== $cmsSelectLabel['key']) {
                        $cmsValue = $modulConfiguration->getValue();
                    }
                }
            } else {
                $cmsValue = $this->_getConfigFromCategory($cmsSelectLabel);
            }
            
            return $cmsValue;
        }

        protected function _getConfigFromCategory(array $cmsSelectLabel) {
            // Look if category configuration exists for this modul configuration
            $cmsValue = '';
            self::$_db->free();
            $sql = 'SELECT * from `con_config_new` 
            			WHERE 
                    		`idmod`="'. self::$_idmod. '" AND 
                    		`idcatlang`="'. self::$_idcatLang. '" AND
                    		`catConfig`=1';
            self::$_db->query($sql);
            $modelConfig = new ModelConConfig();
            $modelConfig->setModulConfiguration(self::$_db);
            $this->_allModuleConfiguration = $modelConfig->getModuleConfiguration();
            if (count($this->_allModuleConfiguration)> 0) {
                foreach ($this->_allModuleConfiguration as $modulConfiguration) {
                    if ($modulConfiguration->getName()== $cmsSelectLabel['name']&&
                     $modulConfiguration->getKey()== $cmsSelectLabel['key']) {
                        $cmsValue = $modulConfiguration->getValue();
                    }
                }
            }
            
            return $cmsValue;
        }

        public function getTplCfgByCatId() {
            
            $db = new DB_Contenido();
            $sql = "SELECT idtplcfg FROM ". self::$_cfg['tab']['art_lang']. " WHERE idart='".
             Contenido_Security::toInteger(self::$_idart). "'
			AND idlang='". Contenido_Security::toInteger(self::$_lang). "'";
            $db->query($sql);
            if ($db->next_record()) {
                return $db->f("idtplcfg");
            }
            return false;
        }

        /**
         * 
         * Singleton idmod
         * 
         * @return idmod
         */
        static public function incrementIdMode() {
            return self::$idMod++;
        }

        /**
         * Generates UniqueId
         */
        private function _getUniqueId() {
            return uniqid('', true);
        }

        protected function _checkIfConfigurationExists($configIndex) {
            $db = new DB_Contenido();
            $db->free();
            
            if (strlen($configIndex)> 0) {
                $sql = 'SELECT * FROM `'. self::$_tableName. '` where `index` ="'. $configIndex. '"';
                $db->query($sql);
                return $db->next_record();
            }
            
            return false;
        }
    }
}

global $idart, $idcat, $sess, $lang, $perm, $auth, $cfg, $contenido,$edit, $client, $cfgClient, $encoding, $cCurrentModule, $idtplcfg, $idcatart, $idartlang, $idcatlang;

$configGlobals = array(
    
'contenido' => $contenido,'edit'=>$edit, 'sess' => $sess, 'idart' => $idart, 'idcat' => $idcat, 'cfg' => $cfg, 'lang' => $lang, 
    'perm' => $perm, 'auth' => $auth, 'client' => $client, 'cfgClient' => $cfgClient, 'db' => new DB_Contenido(), 
    'smarty' => Contenido_SmartyWrapper::getInstance(), 'idmod' => $cCurrentModule, 'idcatArt' => $idcatart, 
    'idartLang' => $idartlang, 'idcatLang' => $idcatlang
);

AbstractModule::setConfig($configGlobals);
?>