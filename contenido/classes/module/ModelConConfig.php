<?php
class ModelConConfig {

    /**
     * 
     * Identifier of DB Row 
     * @var int
     */
    private $_id;

    /**
     * 
     * Idartlang
     * @var int
     */
    private $_idartlang;

    /**
     * 
     * Idcatlang
     * @var int
     */
    private $_idcatlang;

    /**
     * 
     * The current Modul id 
     * @var int
     */
    private $_idmod;

    /**
     * 
     * Configuration Name
     * @var string
     */
    private $_name;

    /**
     * 
     * Configuration key
     * @var string
     */
    private $_key;

    /**
     * 
     * The value of the configuration
     * @var string
     */
    private $_value;

    /**
     * 
     * The bundle unique identifier of the configuration
     * @var string
     */
    private $_index;
    
    /**
     * 
     * Category configuration 
     * @var boolean
     */
    private $_catConfig;
    

    /**
     * 
     * All configuration for one Modul
     * @var array
     */
    public $modulConfiguration = array();

    /**
     * 
     * Sets configuration 
     */
    public function __construct() {
    
    }

    /**
     * @return the $_cat_config
     */
    public function getCatConfig() {
        return $this->_cat_config;
    }

	/**
     * @param boolean $_cat_config
     */
    public function setCatConfig($_cat_config) {
        $this->_cat_config = $_cat_config;
    }

	/**
     * @return the $_id
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * @return the $_idartlang
     */
    public function getIdartlang() {
        return $this->_idartlang;
    }

    /**
     * @return the $_idcatlang
     */
    public function getIdcatlang() {
        return $this->_idcatlang;
    }

    /**
     * @return the $_idmod
     */
    public function getIdmod() {
        return $this->_idmod;
    }

    /**
     * @return the $_name
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * @return the $_key
     */
    public function getKey() {
        return $this->_key;
    }

    /**
     * @return the $_value
     */
    public function getValue() {
        return $this->_value;
    }

    /**
     * @return the $_index
     */
    public function getIndex() {
        return $this->_index;
    }

    /**
     * @return the $modulConfiguration
     */
    public function getModuleConfiguration() {
        return $this->modulConfiguration;
    }
    
    /**
     * @param int $_id
     */
    public function setId($_id) {
        $this->_id = $_id;
    }

    /**
     * @param int $_idartlang
     */
    public function setIdartlang($_idartlang) {
        $this->_idartlang = $_idartlang;
    }

    /**
     * @param int $_idcatlang
     */
    public function setIdcatlang($_idcatlang) {
        $this->_idcatlang = $_idcatlang;
    }

    /**
     * @param int $_idmod
     */
    public function setIdmod($_idmod) {
        $this->_idmod = $_idmod;
    }

    /**
     * @param string $_name
     */
    public function setName($_name) {
        $this->_name = $_name;
    }

    /**
     * @param string $_key
     */
    public function setKey($_key) {
        $this->_key = $_key;
    }

    /**
     * @param string $_value
     */
    public function setValue($_value) {
        $this->_value = $_value;
    }

    /**
     * @param string $_index
     */
    public function setIndex($_index) {
        $this->_index = $_index;
    }

    /**
     * Sets resultset to objects.
     * @param field_type $modulConfiguration
     */
    public function setModulConfiguration(DB_Contenido $db) {
        $collection = array();
        $reflection = new ReflectionClass($this);
        
        // only private members are table columns
        $vars = $reflection->getProperties(ReflectionProperty::IS_PRIVATE);
        
        while ($db->next_record()) {
            $modelConfig = new ModelConConfig();
            foreach ($vars as $name => $value) {
                $methodName = substr_replace($value->name, '', 0,1);
                $setMethod = 'set'. $methodName;
                $modelConfig->$setMethod($db->f($methodName));
            }
            $collection[] = $modelConfig;
        }
        
        $this->modulConfiguration = $collection;
    }

}