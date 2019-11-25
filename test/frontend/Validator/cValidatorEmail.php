<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for the mail validator.
 *
 * @package Testing
 * @subpackage Test_Validator
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * Class to test email validator.
 *
 * Most of the code below s taken over from
 * http://code.google.com/p/php-email-address-validation/
 *
 * @package Testing
 * @subpackage Test_Validator
 */
class cValidatorEmailTest extends TestCase {

    /**
     *
     * @var cValidatorAbstract
     */
    protected $_validator = null;

    /**
     *
     */
    protected function setUp(): void {
        global $cfg;

        $cfg['validator']['email'] = array(
            // List of top level domains to disallow
            'disallow_tld' => array(
                '.test',
                '.example',
                '.invalid',
                '.localhost'
            ),
            // List of hosts to disallow
            'disallow_host' => array(
                'example.com',
                'example.org',
                'example.net'
            ),
            // Flag to check DNS records for MX type
            'mx_check' => false
        );

        $this->_validator = cValidatorFactory::getInstance('email');
    }

    /**
     *
     */
    protected function tearDown(): void {
        global $cfg;
        unset($this->_validator, $cfg['validator']['email']);
    }

    // #########################################################################
    // Valid addresses

    /**
     *
     */
    public function testValidAddress_Standard() {
        $this->assertEquals(true, $this->_validator->isValid('test@contenido.org'));
    }

    /**
     *
     */
    public function testValidAddress_UpperCaseLocalPart() {
        $this->assertEquals(true, $this->_validator->isValid('TEST@contenido.org'));
    }

    /**
     *
     */
    public function testValidAddress_NumericLocalPart() {
        $this->assertEquals(true, $this->_validator->isValid('1234567890@contenido.org'));
    }

    /**
     *
     */
    public function testValidAddress_TaggedLocalPart() {
        $this->assertEquals(true, $this->_validator->isValid('test+test@contenido.org'));
    }

    /**
     *
     */
    public function testValidAddress_QmailLocalPart() {
        $this->assertEquals(true, $this->_validator->isValid('test-test@contenido.org'));
    }

    /**
     *
     */
    public function testValidAddress_UnusualCharactersInLocalPart() {
        $this->assertEquals(true, $this->_validator->isValid('t*est@contenido.org'));
        $this->assertEquals(true, $this->_validator->isValid('+1~1+@contenido.org'));
        $this->assertEquals(true, $this->_validator->isValid('{_test_}@contenido.org'));
        // $this->assertEquals(true,
        // $this->_validator->isValid('test\"test@contenido.org'));
        // $this->assertEquals(true,
        // $this->_validator->isValid('test\@test@contenido.org'));
        // $this->assertEquals(true,
        // $this->_validator->isValid('test\test@contenido.org'));
        $this->assertEquals(true, $this->_validator->isValid('"test\test"@contenido.org'));
        $this->assertEquals(true, $this->_validator->isValid('"test.test"@contenido.org'));
    }

    // /**
    //  *
    //  */
    // public function testValidAddress_QuotedLocalPart() {
    //     $this->assertEquals(true, $this->_validator->isValid('"[[ test ]]"@contenido.org'));
    // }

    /**
     *
     */
    public function testValidAddress_AtomisedLocalPart() {
        $this->assertEquals(true, $this->_validator->isValid('test.test@contenido.org'));
    }


    // /**
    //  *
    //  */
    // public function testValidAddress_ObsoleteLocalPart() {
    //     $this->assertEquals(true,
    //     $this->_validator->isValid('test."test"@contenido.org'));
    // }

    // /**
    //  *
    //  */
    // public function testValidAddress_QuotedAtLocalPart() {
    //     $this->assertEquals(true,
    //     $this->_validator->isValid('"test@test"@contenido.org'));
    // }

    // /**
    //  *
    //  */
    // public function testValidAddress_IpDomain() {
    //     $this->assertEquals(true,
    //     $this->_validator->isValid('test@123.123.123.123'));
    // }

    /**
     *
     */
    public function testValidAddress_BracketIpDomain() {
        $this->assertEquals(true, $this->_validator->isValid('test@[123.123.123.123]'));
    }

    /**
     *
     */
    public function testValidAddress_MultipleLabelDomain() {
        $this->assertEquals(true, $this->_validator->isValid('test@contenido.contenido.com'));
        $this->assertEquals(true, $this->_validator->isValid('test@contenido.contenido.contenido.com'));
    }

    // #########################################################################
    // Invalid Addresses

    /**
     *
     */
    public function testInvalidAddress_disallowedTopLevelDomains() {
        $this->assertEquals(false, $this->_validator->isValid('user@contenido.test'));
        $this->assertEquals(false, $this->_validator->isValid('user@contenido.example'));
        $this->assertEquals(false, $this->_validator->isValid('user@contenido.invalid'));
        $this->assertEquals(false, $this->_validator->isValid('user@contenido.localhost'));
    }

    /**
     *
     */
    public function testInvalidAddress_disallowedHosts() {
        $this->assertEquals(false, $this->_validator->isValid('user@example.com'));
        $this->assertEquals(false, $this->_validator->isValid('user@example.org'));
        $this->assertEquals(false, $this->_validator->isValid('user@example.net'));
    }

    /**
     *
     */
    public function testInvalidAddress_TooLong() {
        $this->assertEquals(false, $this->_validator->isValid('12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345@contenido.org'));
    }

    /**
     *
     */
    public function testInvalidAddress_TooShort() {
        $this->assertEquals(false, $this->_validator->isValid('@a'));
    }

    /**
     *
     */
    public function testInvalidAddress_NoAtSymbol() {
        $this->assertEquals(false, $this->_validator->isValid('test.contenido.org'));
    }

    /**
     *
     */
    public function testInvalidAddress_BlankAtomInLocalPart() {
        $this->assertEquals(false, $this->_validator->isValid('test.@contenido.org'));
        $this->assertEquals(false, $this->_validator->isValid('test..test@contenido.org'));
        $this->assertEquals(false, $this->_validator->isValid('.test@contenido.org'));
    }

    /**
     *
     */
    public function testInvalidAddress_MultipleAtSymbols() {
        $this->assertEquals(false, $this->_validator->isValid('test@test@contenido.org'));
        $this->assertEquals(false, $this->_validator->isValid('test@@contenido.org'));
    }

    /**
     *
     */
    public function testInvalidAddress_InvalidCharactersInLocalPart() {
        // No spaces allowed in local part
        $this->assertEquals(false, $this->_validator->isValid('-- test --@contenido.org'));
        // Square brackets only allowed within quotes
        $this->assertEquals(false, $this->_validator->isValid('[test]@contenido.org'));
        // Quotes cannot be nested
        $this->assertEquals(false, $this->_validator->isValid('"test"test"@contenido.org'));
        // Disallowed Characters
        $this->assertEquals(false, $this->_validator->isValid('()[]\;:,<>@contenido.org'));
    }

    /**
     *
     */
    public function testInvalidAddress_DomainLabelTooShort() {
        $this->assertEquals(false, $this->_validator->isValid('test@.'));
        $this->assertEquals(false, $this->_validator->isValid('test@contenido.'));
        $this->assertEquals(false, $this->_validator->isValid('test@.org'));
    }

    /**
     * 64 characters is maximum length for local part. This is 65.
     */
    public function testInvalidAddress_LocalPartTooLong() {
        $this->assertEquals(false, $this->_validator->isValid('12345678901234567890123456789012345678901234567890123456789012345@contenido.org'));
    }

    /**
     * 255 characters is maximum length for domain. This is 256.
     */
    public function testInvalidAddress_DomainLabelTooLong() {
        $this->assertEquals(false, $this->_validator->isValid('test@123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012.com'));
    }

    /**
     *
     */
    public function testInvalidAddress_TooFewLabelsInDomain() {
        $this->assertEquals(false, $this->_validator->isValid('test@contenido'));
    }

    /**
     *
     */
    public function testInvalidAddress_UnpartneredSquareBracketIp() {
        $this->assertEquals(false, $this->_validator->isValid('test@[123.123.123.123'));
        $this->assertEquals(false, $this->_validator->isValid('test@123.123.123.123]'));
    }

}
