<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

plugin_include(Pifa::getName(), 'classes/class.pifa.external_options_datasource_interface.php');

/**
 * This is an example for an external options data source.
 *
 * @author marcus.gnass
 */
class ExampleOptionsDatasource extends PifaExternalOptionsDatasourceInterface {

    /**
     * Stores the options as associative array which maps values to labels.
     *
     * @var array
     */
    protected $_options = NULL;

    /**
     * Gets options from an external data source and return them as associative
     * array which maps values to labels.
     *
     * @return array
     */
    protected function _getData() {
        $options = array(
            'n/a' => mi18n("CHOOSE_OPTION"),
            'foo' => mi18n("FOO"),
            'bar' => mi18n("BAR")
        );

        return $options;
    }

    /**
     *
     * @see ExternalOptionsDatasourceInterface::getOptionLabels()
     */
    public function getOptionLabels() {
        if (NULL === $this->_options) {
            $this->_options = $this->_getData();
        }
        return array_values($this->_options);
    }

    /**
     *
     * @see ExternalOptionsDatasourceInterface::getOptionValues()
     */
    public function getOptionValues() {
        if (NULL === $this->_options) {
            $this->_options = $this->_getData();
        }
        return array_keys($this->_options);
    }
}
