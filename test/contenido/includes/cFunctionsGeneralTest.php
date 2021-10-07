<?php

/**
 * This file contains tests for general CONTENIDO functions.
 *
 * @package    Testing
 * @subpackage Polyfill
 * @author     marcus.gnass
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

class cFunctionsGeneralTest extends cTestingTestCase
{
    public function testGetCanonicalMonth()
    {
        foreach ([null, 0, 13] as $month) {
            $this->assertNull(getCanonicalMonth($month));
        }
        foreach (range(1, 12) as $month) {
            $this->assertNotNull(getCanonicalMonth($month));
        }
    }

    public function testGetCanonicalDay()
    {
        foreach ([null, 0, 8] as $weekday) {
            $this->assertNull(getCanonicalDay($weekday));
        }
        foreach (range(1, 7) as $weekday) {
            $this->assertNotNull(getCanonicalDay($weekday));
        }
    }
}
