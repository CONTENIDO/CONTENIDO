<?php

/**
 * This file contains tests for the IPv4 address validator.
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
 * Class to test IPv4 address validator.
 *
 * @package    Testing
 * @subpackage Test_Validator
 */
class cValidatorIpv4Test extends cTestingTestCase
{
    /**
     * @var cValidatorAbstract
     */
    protected $_validator = null;

    /**
     * @throws cInvalidArgumentException
     */
    protected function setUp(): void
    {
        $this->_validator = cValidatorFactory::getInstance('ipv4');
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        unset($this->_validator);
    }

    public function dataIPv4IsValid(): array
    {
        return [
            'Null' => [null, false],
            'Empty' => ['', false],
            'Int' => [1, false],
            'Float' => [.1, false],
            'Bool' => [true, false],
            'String' => ['foobar', false],
            'IP with 256' => ['256.256.256.256', false],
            'IP with invalid characters' => ['19.127.a.32', false],
            'IP with zeros' => ['0.0.0.0', true],
            'IP with ones' => ['1.1.1.1', true],
            'Localhost 127.0.0.1' => ['127.0.0.1', true],
            'Local network' => ['192.168.1.1', true],
            'IP with 255' => ['255.255.255.255', true],
        ];
    }

    /**
     * @dataProvider dataIPv4IsValid()
     *
     * @param string $input
     * @param bool $output
     */
    public function testIsValid($input, $output)
    {
        $this->assertEquals($output, $this->_validator->isValid($input));
    }

}
