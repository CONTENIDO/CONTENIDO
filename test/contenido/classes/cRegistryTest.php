<?php

/**
 * This file contains tests for Contenido cRegistry.
 *
 * @package          Testing
 * @subpackage       Test_Registry
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
 */


/**
 * Class to test cRegistry.
 *
 * TODO Implement other unit tests for cRegistry.
 *
 * @package          Testing
 * @subpackage       Test_Registry
 */
class cRegistryTest extends cTestingTestCase
{

    /**
     * Test cRegistry::isBackendVisualEditMode()
     */
    public function testIsBackendVisualEditMode()
    {
        global $contenido, $area;

        $sessId = md5('some_value');

        // No 'contenido' & 'area'
        $result = cRegistry::isBackendVisualEditMode();
        $this->assertEquals(false, $result);

        // 'contenido' but no 'area'
        $contenido = $sessId;
        $result = cRegistry::isBackendVisualEditMode();
        $this->assertEquals(false, $result);
        $contenido = null;

        // 'contenido' but wrong 'area'
        $contenido = $sessId;
        $area = 'wrong_area';
        $result = cRegistry::isBackendVisualEditMode();
        $this->assertEquals(false, $result);
        $contenido = null;
        $area = null;

        // 'contenido' and 'area'
        $contenido = $sessId;
        $area = 'tpl_visual';
        $result = cRegistry::isBackendVisualEditMode();
        $this->assertEquals(true, $result);
        $contenido = null;
        $area = null;
    }
}
