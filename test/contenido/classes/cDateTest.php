<?php

/**
 * This file contains tests for the class cDate.
 *
 * @package    Testing
 * @subpackage Util
 * @author     Murat Purç <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

/**
 * This class tests the static class methods of the cDate util class.
 *
 * @author     Murat Purç <murat@purc.de>
 */
class cDateTest extends cTestingTestCase
{

    public function dataPadDay(): array
    {
        return [
            'Null' => [null, null],
            'Empty string' => ['', '00'],
            'Empty string with whitespaces' => [' ', '00'],
            'Number -1' => [-1, -1],
            'Number 0 (min possible value)' => [0, '00'],
            'Number 9' => [9, '09'],
            'Number 31 (max possible value)' => [31, '31'],
            'Number 32' => [32, '32'],
            'String "-1"' => ['-1', -1],
            'String "0" (min possible value)' => ['0', '00'],
            'String "9"' => ['9', '09'],
            'String "31" (max possible value)' => ['31', '31'],
            'String "32"' => ['32', '32'],
        ];
    }

    /**
     * Test {@see cDate::padDay()}.
     *
     * @dataProvider dataPadDay()
     *
     * @param mixed $input
     * @param mixed $output
     */
    public function testPadDay($input, $output)
    {
        $this->assertEquals($output, cDate::padDay($input));
    }

    public function dataPadMonth(): array
    {
        return [
            'Null' => [null, null],
            'Empty string' => ['', '00'],
            'Empty string with whitespaces' => [' ', '00'],
            'Number -1' => [-1, -1],
            'Number 0 (min possible value)' => [0, '00'],
            'Number 9' => [9, '09'],
            'Number 12 (max possible value)' => [12, '12'],
            'Number 13' => [13, '13'],
            'String "-1"' => ['-1', -1],
            'String "0" (min possible value)' => ['0', '00'],
            'String "9"' => ['9', '09'],
            'String "12" (max possible value)' => ['12', '12'],
            'String "13"' => ['13', '12'],
        ];
    }

    /**
     * Test {@see cDate::padMonth()}.
     *
     * @dataProvider dataPadDay()
     *
     * @param mixed $input
     * @param mixed $output
     */
    public function testPadMonth($input, $output)
    {
        $this->assertEquals($output, cDate::padMonth($input));
    }


    /**
     * Test {@see cDate::padDayOrMonth()}.
     *
     * @dataProvider dataPadDay()
     *
     * @param mixed $input
     * @param mixed $output
     */
    public function testPadDayOrMonth($input, $output)
    {
        $this->assertEquals($output, cDate::padDayOrMonth($input));
    }

    /**
     * Test {@see cDate::getCanonicalMonth()}.
     */
    public function testGetCanonicalMonth()
    {
        foreach ([null, 0, 13] as $month) {
            $this->assertNull(cDate::getCanonicalMonth($month));
        }
        foreach (range(1, 12) as $month) {
            $this->assertNotNull(cDate::getCanonicalMonth($month));
        }
    }

    /**
     * Test {@see cDate::getCanonicalDay()}.
     */
    public function testGetCanonicalDay()
    {
        foreach ([null, 0, 8] as $weekday) {
            $this->assertNull(cDate::getCanonicalDay($weekday));
        }
        foreach (range(1, 7) as $weekday) {
            $this->assertNotNull(cDate::getCanonicalDay($weekday));
        }
    }

    /**
     * Test {@see cDate::formatDatetime()}.
     * @TODO Implement this test.
     */
    public function testFormatDatetime()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

}