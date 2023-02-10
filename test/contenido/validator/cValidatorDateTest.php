<?php

/**
 * This file contains tests for the date validator.
 *
 * @package    Testing
 * @subpackage Test_Validator
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

/**
 * Class to test date validator.
 *
 * @package    Testing
 * @subpackage Test_Validator
 */
class cValidatorDateTest extends cTestingTestCase
{
    /**
     *
     * @var cValidatorAbstract
     */
    protected $_validator = null;

    /**
     * @throws cInvalidArgumentException
     */
    protected function setUp(): void
    {
        $this->_validator = cValidatorFactory::getInstance('date');
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        unset($this->_validator);
    }

    public function dataIsValid()
    {
        return [
            'Null' => [null, false],
            'Empty' => ['', false],
            'Int' => [1, false],
            'Float' => [.1, false],
            'Bool' => [true, false],
            'String' => ['foobar', false],
            'DateZeroValues' => ['0000-00-00', false],
            'DateZeroValuesTimeZeroValues' => ['0000-00-00 00:00:00', false],
            'InvalidDate' => ['2021-08-99', false],
            'InvalidDateInvalidTime' => ['2021-08-99 21:28:99', false],
            'DateInvalidTime' => ['2021-08-31 21:28:99', false],
            'TimeOnly' => ['21:28:11', false],
            'Date' => ['2021-08-31', true],
            'DateTime' => ['2021-08-31 21:28:11', true],
            'DateTimeZeroValues' => ['2021-08-31 00:00:00', true],
        ];
    }

    /**
     * @dataProvider dataIsValid()
     *
     * @param string $input
     * @param bool $output
     */
    public function testIsValid($input, $output)
    {
        $this->assertEquals($output, $this->_validator->isValid($input));
    }
}
