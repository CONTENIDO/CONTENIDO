<?php

/**
 * This file contains tests for the asset util.
 *
 * @package    Testing
 * @subpackage Util
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * Class to test asset util.
 *
 * @package    Testing
 * @subpackage Util
 */
class cAssetTest extends cTestingTestCase
{

    public function setUp(): void
    {
        $GLOBALS['cilent'] = 1;
    }

    public function tearDown(): void
    {
        unset($GLOBALS['cilent']);
    }

    /**
     * Tests {@see cAsset::backend()}
     */
    public function testBackend()
    {
        // Valid assets
        $file = 'scripts/contenido.js';
        $this->assertNotEquals($file, cAsset::backend($file));

        $file = 'styles/contenido.css';
        $this->assertNotEquals($file, cAsset::backend($file));

        $file = 'file_not_exist/contenido.css';
        $this->assertEquals($file, cAsset::backend($file));

        // Invalid assets
        $file = 'file_not_exist/contenido.css?some=parameter';
        $this->assertEquals($file, cAsset::backend($file));

        $file = 'images/contenido.svg';
        $this->assertEquals($file, cAsset::backend($file));

        $file = '//images/contenido.svg';
        $this->assertEquals($file, cAsset::backend($file));

        $file = 'https://www.foobar.con/baz.html';
        $this->assertEquals($file, cAsset::backend($file));
    }

    /**
     * Tests {@see cAsset::frontend()}
     */
    public function testFrontend()
    {
        // Valid assets
        $file = 'css/reset.css';
        $this->assertNotEquals($file, cAsset::frontend($file));

        $file = 'js/main.js';
        $this->assertNotEquals($file, cAsset::frontend($file));
    }

}