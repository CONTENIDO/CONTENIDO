<?php

/**
 * This file contains tests for the mail validator.
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
 * Class to test email validator.
 *
 * Most of the code below s taken over from
 * http://code.google.com/p/php-email-address-validation/
 *
 * @package    Testing
 * @subpackage Test_Validator
 */
class cValidatorEmailTest extends cTestingTestCase
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
        global $cfg;

        $cfg['validator']['email'] = [
            // List of top level domains to disallow
            'disallow_tld'  => [
                '.test',
                '.example',
                '.invalid',
                '.localhost',
            ],
            // List of hosts to disallow
            'disallow_host' => [
                'example.com',
                'example.org',
                'example.net',
            ],
            // Flag to check DNS records for MX type
            'mx_check'      => false,
        ];

        $this->_validator = cValidatorFactory::getInstance('email');
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        global $cfg;
        unset($this->_validator, $cfg['validator']['email']);
    }

    public function dataIsValid()
    {
        return [
            'Null'  => [null, false],
            'Empty' => ['', false],
            'Int'   => [1, false],
            'Float' => [.1, false],
            'Bool'  => [true, false],
            'String'  => ['foobar', false],


            'Standard'                      => ['test@contenido.org', true],
            'UpperCaseLocalPart'            => ['TEST@contenido.org', true],
            'NumericLocalPart'              => ['1234567890@contenido.org', true],
            'TaggedLocalPart'               => ['test+test@contenido.org', true],
            'QmailLocalPart'                => ['test-test@contenido.org', true],
            'UnusualCharactersInLocalPart1' => ['t*est@contenido.org', true],
            'UnusualCharactersInLocalPart2' => ['+1~1+@contenido.org', true],
            'UnusualCharactersInLocalPart3' => ['{_test_}@contenido.org', true],
            // 'UnusualCharactersInLocalPart4' => ['test\"test@contenido.org', true],
            // 'UnusualCharactersInLocalPart5' => ['test\@test@contenido.org', true],
            // 'UnusualCharactersInLocalPart6' => ['test\test@contenido.org', true],
            'UnusualCharactersInLocalPart7' => ['"test\test"@contenido.org', true],
            'UnusualCharactersInLocalPart8' => ['"test.test"@contenido.org', true],
            // 'QuotedLocalPart'               => ['"[[ test ]]"@contenido.org', true],
            'AtomisedLocalPart'             => ['test.test@contenido.org', true],
            // 'ObsoleteLocalPart'             => ['test."test"@contenido.org', true],
            // 'QuotedAtLocalPart'             => ['"test@test"@contenido.org', true],
            // 'IpDomain'                      => ['test@123.123.123.123', true],
            'BracketIpDomain'               => ['test@[123.123.123.123]', true],
            'MultipleLabelDomain1'          => ['test@contenido.contenido.com', true],
            'MultipleLabelDomain2'          => ['test@contenido.contenido.contenido.com', true],
            'DisallowedTopLevelDomains1'    => ['user@contenido.test', false],
            'DisallowedTopLevelDomains2'    => ['user@contenido.example', false],
            'DisallowedTopLevelDomains3'    => ['user@contenido.invalid', false],
            'DisallowedTopLevelDomains4'    => ['user@contenido.localhost', false],
            'DisallowedHosts1'              => ['user@example.com', false],
            'DisallowedHosts2'              => ['user@example.org', false],
            'DisallowedHosts3'              => ['user@example.net', false],
            'TooLong'                       => [
                '12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345@contenido.org',
                false,
            ],
            'TooShort'                      => ['@a', false],
            'NoAtSymbol'                    => ['test.contenido.org', false],
            'BlankAtomInLocalPart1'         => ['test.@contenido.org', false],
            'BlankAtomInLocalPart2'         => ['test..test@contenido.org', false],
            'BlankAtomInLocalPart3'         => ['.test@contenido.org', false],
            'MultipleAtSymbols1'            => ['test@test@contenido.org', false],
            'MultipleAtSymbols2'            => ['test@@contenido.org', false],
            // No spaces allowed in local part
            'InvalidCharactersInLocalPart1' => ['-- test --@contenido.org', false],
            // Square brackets only allowed within quotes
            'InvalidCharactersInLocalPart2' => ['[test]@contenido.org', false],
            // Quotes cannot be nested
            'InvalidCharactersInLocalPart3' => ['"test"test"@contenido.org', false],
            // Disallowed Characters
            'InvalidCharactersInLocalPart4' => ['()[]\;:,<>@contenido.org', false],
            'DomainLabelTooShort1'          => ['test@.', false],
            'DomainLabelTooShort2'          => ['test@contenido.', false],
            'DomainLabelTooShort3'          => ['test@.org', false],
            // 64 characters is maximum length for local part. This is 65.
            'LocalPartTooLong'              => [
                '12345678901234567890123456789012345678901234567890123456789012345@contenido.org',
                false,
            ],
            // 255 characters is maximum length for domain. This is 256.
            'DomainLabelTooLong'            => [
                'test@123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012.com',
                false,
            ],
            'TooFewLabelsInDomain'          => ['test@contenido', false],
            'UnpartneredSquareBracketIp1'   => ['test@[123.123.123.123', false],
            'UnpartneredSquareBracketIp2'   => ['test@123.123.123.123]', false],
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
