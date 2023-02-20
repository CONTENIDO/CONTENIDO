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
            'String "" (empty)' => ['', '00'],
            'String with whitespaces' => [' ', '00'],
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
            'String "" (empty)' => ['', '00'],
            'String with whitespaces' => [' ', '00'],
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

    public function dataStrftimeToDate(): array
    {
        return [
            'Year four digit' => ['%Y', 'Y'],
            'Year two digit' => ['%y', 'y'],
            'Year (ISO 8601 week-numbering year)' => ['%G', 'o'],

            'Month (January through December)' => ['%B', 'F'],
            'Month (01 to 12))' => ['%m', 'm'],
            'Month (Jan through Dec)' => ['%b', 'M'],
            'Month (Jan through Dec) 2' => ['%h', 'M'],
            'Month (1 through 12)' => ['%-m', 'n'],

            'Week (SO 8601 week number)' => ['%V', 'W'],

            'Day (Sun to Sat)' => ['%a', 'D'],
            'Day (Sunday to Saturday)' => ['%A', 'l'],
            'Day (01 to 31)' => ['%d', 'd'],
            'Day (1 to 31)' => ['%e', 'j'],
            'Day (1 to 366)' => ['%j', 'z'],
            'Day (day of week)' => ['%u', 'N'],
            'Day (numeric day of week)' => ['%w', 'w'],

            'Lower-case `am` or `pm`' => ['%P', 'a'],
            'Upper-case `AM` or `PM`' => ['%p', 'A'],
            'Hour (1 to 12)' => ['%l', 'g'],
            'Hour (01 to 12)' => ['%I', 'h'],
            'Hour (00 to 23)' => ['%H', 'H'],
            'Hour (0 to 23)' => ['%k', 'G'],
            'Minute (00 through 59)' => ['%M', 'i'],
            'Second (00 through 59)' => ['%S', 's'],

            'Day.Month.Year' => ['%d.%m.%Y', 'd.m.Y'],
            'Hour:Minute:Second' => ['%H:%M:%S', 'H:i:s'],
            'Month/Day/Year, Hour:Minute:Second' => ['%m/%d/%Y, %H:%M:%S', 'm/d/Y, H:i:s'],
            'Year-Month-Day, Hour:Minute:Second' => ['%Y-%m-%d, %H:%M:%S', 'Y-m-d, H:i:s'],

            // Time zone
            'Difference to Greenwich time (GMT) without colon between hours and minutes' => ['%z', 'O'],
            'Timezone abbreviation, if known; otherwise the GMT offset.' => ['%Z', 'T'],

            // Full date
            'Unix Epoch Time timestamp' => ['%s', 'U'],
        ];
    }

    /**
     * Test {@see cDate::strftimeToDate()}.
     *
     * @dataProvider dataStrftimeToDate()
     */
    public function testStrftimeToDate(string $input, string $output)
    {
        $this->assertEquals($output, cDate::strftimeToDate($input));
    }

}