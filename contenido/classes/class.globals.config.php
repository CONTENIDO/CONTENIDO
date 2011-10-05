<?php
/**
 * 
 * Abstract config class to initialise global variables.
 * 
 * 
 *
 */
abstract class ConfigGlobals {

    /**
     * 
     * contenido global variable
     * @var array
     */
    static protected $_encoding = null;
    
    /**
     * 
     * contenido global variable
     * @var array
     */
    static protected $_contenido = null;

    /**  
     * 
     * contenido global variable
     * @var int
     */
    static protected $_edit = null;

    /**
     * 
     * contenido global variable
     * @var array
     */
    static protected $_sess = null;

    /**
     * 
     * contenido global variable
     * @var array
     */
    static protected $_cfg = null;

    /**
     * 
     * contenido global variable
     * @var int
     */
    static protected $_lang = null;

    /**
     * 
     * contenido global variable
     * @var array
     */
    static protected $_perm = null;

    /**
     * 
     * contenido global variable
     * @var array
     */
    static protected $_auth = null;

    /**
     * 
     * contenido global variable
     * @var int
     */
    static protected $_client = null;

    /**
     * 
     * contenido global variable
     * @var array
     */
    static protected $_cfgClient = null;

    /**
     * 
     * contenido global database object variable
     * @var DB_Contenido
     */
    static protected $_db = null;

    /**
     * 
     * contenido global variable
     * @var int
     */
    static protected $_idart = null;

    /**
     * 
     * contenido global variable
     * @var int
     */
    static protected $_idcat = null;
    
    /**
     * 
     * contenido global variable
     * @var int
     */
    static protected $_idcatArt = null;
    
    /**
     * 
     * contenido global variable
     * @var int
     */
    static protected $_idcatLang = null;

    /**
     * 
     * Contenido__smartyWrapper plugin singleton object
     * @var Contenido_SmartyWrapper 
     */
    static protected $_smarty = null;
    
    /**
     * 
     * contenido global variable
     * @var int
     */
    static protected $_idmod = null;
    
    /**
     * 
     * contenido global variable
     * @var int
     */
    static protected $_idartLang = null;
	



	/**
     * @return the $_idcatArt
     */
    public static function getIdcatArt() {
        return ConfigGlobals::$_idcatArt;
    }

	/**
     * @param int $_idcatArt
     */
    public static function setIdcatArt($_idcatArt) {
        ConfigGlobals::$_idcatArt = $_idcatArt;
    }

	/**
     * @return the $_idcatLang
     */
    public static function getIdcatLang() {
        return ConfigGlobals::$_idcatLang;
    }

	/**
     * @return the $_idartLang
     */
    public static function getIdartLang() {
        return ConfigGlobals::$_idartLang;
    }

	/**
     * @param int $_idcatLang
     */
    public static function setIdcatLang($_idcatLang) {
        ConfigGlobals::$_idcatLang = $_idcatLang;
    }

	/**
     * @param int $_idartLang
     */
    public static function setIdartLang($_idartLang) {
        ConfigGlobals::$_idartLang = $_idartLang;
    }

	/**
     * @return the $_idmod
     */
    public static function getIdmod() {
        return ConfigGlobals::$_idmod;
    }

	/**
     * @param int $_idmod
     */
    public static function setIdmod($_idmod) {
        ConfigGlobals::$_idmod = $_idmod;
    }

	/**
     * @return the $_encoding
     */
    public static function getEncoding() {
        return ConfigGlobals::$_encoding;
    }

	/**
     * @param array $_encoding
     */
    public static function setEncoding($_encoding) {
        ConfigGlobals::$_encoding = $_encoding;
    }
    
    /**
     * 
     * Constructor of class: Sets all given globals
     * @param array $globals
     */
	public static function setConfig(array $globals) {
	    
	    if(count($globals) > 0) {
            foreach($globals as $name=>$value) {
                $methodName = strtolower($name);
                if ($value != null) {
                    $setupMethod = 'set' . $methodName;
                    self::$setupMethod($value);
                    
                }
            }
	    }
	}
	
	/**
     * @return the $_contenido
     */
    public static function getContenido() {
        return ConfigGlobals::$_contenido;
    }

	/**
     * @return the $_edit
     */
    public static function getEdit() {
        return ConfigGlobals::$_edit;
    }

	/**
     * @return the $_sess
     */
    public static function getSess() {
        return ConfigGlobals::$_sess;
    }

	/**
     * @return the $_cfg
     */
    public static function getCfg() {
        return ConfigGlobals::$_cfg;
    }

	/**
     * @return the $_lang
     */
    public static function getLang() {
        return ConfigGlobals::$_lang;
    }

	/**
     * @return the $_perm
     */
    public static function getPerm() {
        return ConfigGlobals::$_perm;
    }

	/**
     * @return the $_auth
     */
    public static function getAuth() {
        return ConfigGlobals::$_auth;
    }

	/**
     * @return the $_client
     */
    public static function getClient() {
        return ConfigGlobals::$_client;
    }

	/**
     * @return the $_cfgClient
     */
    public static function getCfgClient() {
        return ConfigGlobals::$_cfgClient;
    }

	/**
     * @return the $_db
     */
    public static function getDb() {
        return ConfigGlobals::$_db;
    }

	/**
     * @return the $_idart
     */
    public static function getIdart() {
        return ConfigGlobals::$_idart;
    }

	/**
     * @return the $_idcat
     */
    public static function getIdcat() {
        return ConfigGlobals::$_idcat;
    }

	/**
     * @return the $_smarty
     */
    public static function getSmarty() {
        return ConfigGlobals::$_smarty;
    }

	/**
     * @param array $_contenido
     */
    public static function setContenido($_contenido) {
        ConfigGlobals::$_contenido = $_contenido;
    }

	/**
     * @param array $_edit
     */
    public static function setEdit($_edit) {
        ConfigGlobals::$_edit = $_edit;
    }

	/**
     * @param array $_sess
     */
    public static function setSess($_sess) {
        ConfigGlobals::$_sess = $_sess;
    }

	/**
     * @param array $_cfg
     */
    public static function setCfg($_cfg) {
        ConfigGlobals::$_cfg = $_cfg;
    }

	/**
     * @param array $_lang
     */
    public static function setLang($_lang) {
        ConfigGlobals::$_lang = $_lang;
    }

	/**
     * @param array $_perm
     */
    public static function setPerm($_perm) {
        ConfigGlobals::$_perm = $_perm;
    }

	/**
     * @param array $_auth
     */
    public static function setAuth($_auth) {
        ConfigGlobals::$_auth = $_auth;
    }

	/**
     * @param array $_client
     */
    public static function setClient($_client) {
        ConfigGlobals::$_client = $_client;
    }

	/**
     * @param array $_cfgClient
     */
    public static function setCfgClient($_cfgClient) {
        ConfigGlobals::$_cfgClient = $_cfgClient;
    }

	/**
     * @param array $_db
     */
    public static function setDb($_db) {
        ConfigGlobals::$_db = $_db;
    }

	/**
     * @param array $_idart
     */
    public static function setIdart($_idart) {
        ConfigGlobals::$_idart = $_idart;
    }

	/**
     * @param array $_idcat
     */
    public static function setIdcat($_idcat) {
        ConfigGlobals::$_idcat = $_idcat;
    }

	/**
     * @param Contenido_smartyWrapper $_smarty
     */
    public static function setSmarty($_smarty) {
        ConfigGlobals::$_smarty = $_smarty;
    }
    
}