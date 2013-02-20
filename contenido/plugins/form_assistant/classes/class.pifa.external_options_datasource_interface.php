<?php

/**
 *
 * @package Plugin
 * @subpackage PIFA Form Asistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') or die('Illegal call');

/**
 * Abstract class to read labels and values to be used for PIFA field options
 * from an external datasource.
 *
 * @author marcus.gnass
 */
abstract class PifaExternalOptionsDatasourceInterface {

    /**
     *
     * @return array of labels to be used for PIFA field options
     */
    public abstract function getOptionLabels();

    /**
     *
     * @return array of values to be used for PIFA field options
     */
    public abstract function getOptionValues();

}
