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

plugin_include(Pifa::getName(), 'classes/class.pifa.external_options_datasource_interface.php');

/**
 *
 * @author marcus.gnass
 */
class ProductGroupOptionsDatasource extends ExternalOptionsDatasourceInterface {

    /**
     *
     * @var array
     */
    protected $_data = NULL;

    /**
     *
     * @see ExternalOptionsDatasourceInterface::getOptionLabels()
     */
    public function getOptionLabels() {
        if (NULL === $this->_data) {
            $data = $this->_getData();
        }
        return array_values($data);
    }

    /**
     *
     * @see ExternalOptionsDatasourceInterface::getOptionValues()
     */
    public function getOptionValues() {
        if (NULL === $this->_data) {
            $data = $this->_getData();
        }
        return array_keys($data);
    }

    /**
     */
    protected function _getData() {

        cInclude('frontend', 'classes/class.structure_xml_interface.php');
        cInclude('frontend', 'classes/node/class.node.php');
        cInclude('frontend', 'classes/node/class.image.php');
        cInclude('frontend', 'classes/node/class.meta.php');
        cInclude('frontend', 'classes/node/class.category.php');
        cInclude('frontend', 'classes/node/class.structure.php');

        // get data
        $pixi = new StructureXmlInterface();
        $structure = $pixi->getXml('structure');

        if (NULL === $structure->categories) {
            throw new PifaException('missing categories');
        }

        // get options
        $options = array(
            'n/a' => mi18n("Please choose a category")
        );
        foreach ($structure->categories as $category) {
            foreach ($category->children as $subcategory) {
                $options[$subcategory->seo_name] = $subcategory->name;
            }
        }

        return $options;

    }

}

?>