<?php

/**
 * This file contains tests for the class cSqlTemplate.
 *
 * @package    Testing
 * @subpackage Database
 * @author     Murat Purç <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * This class tests functionality of the cSqlTemplate.
 *
 * @author Murat Purç <murat@purc.de>
 */
class cSqlTemplateTest extends cTestingTestCase
{

    /**
     * @var array Backup of global configuration array
     */
    private $cfg;

    protected function setUp(): void
    {
        // Use global, we'll modify it
        global $cfg;

        // Backup global configuration
        $this->cfg = $cfg;
        $cfg['db']['connection']['charset'] = CON_DB_CHARSET;
        $cfg['db']['engine'] = CON_DB_ENGINE;
        $cfg['sql']['sqlprefix'] = CON_DB_PREFIX;
        $cfg['db']['collation'] = CON_DB_COLLATION;
    }

    protected function tearDown(): void
    {
        // Use global, we'll modify it
        global $cfg;

        // Restore global configuration
        $cfg = $this->cfg;
    }

    /**
     * Test {@see cSqlTemplate::__construct()}.
     */
    public function testConstruct()
    {
        $sqlTemplate = new cSqlTemplate();
        $this->assetDefaultReplacements($sqlTemplate);
    }

    /**
     * Test {@see cSqlTemplate::reset()}.
     */
    public function testReset()
    {
        $sqlTemplate = new cSqlTemplate();
        $sqlTemplate->setReplacements([]);
        $sqlTemplate->reset();
        $this->assetDefaultReplacements($sqlTemplate);
    }

    /**
     * Test {@see cSqlTemplate::setReplacements()}.
     */
    public function testSetReplacements()
    {
        $sqlTemplate = new cSqlTemplate();

        // Empty replacements
        $sqlTemplate->setReplacements([]);
        $this->assertEmpty($sqlTemplate->getReplacements());

        // Add custom replacement
        $sqlTemplate->setReplacements(['!MY_PLACEHOLDER!' => 'My value']);
        $replacements = $sqlTemplate->getReplacements();
        $this->assertTrue(isset($replacements['!MY_PLACEHOLDER!']));
        $this->assertEquals('My value', $replacements['!MY_PLACEHOLDER!']);

        // Overwrite existing replacement
        $sqlTemplate = new cSqlTemplate();
        $sqlTemplate->setReplacements(['!CHARSET!' => 'ascii']);
        $replacements = $sqlTemplate->getReplacements();
        $this->assertEquals('ascii', $replacements['!CHARSET!']);
    }

    /**
     * Test {@see cSqlTemplate::addReplacements()}.
     */
    public function testAddReplacements()
    {
        $sqlTemplate = new cSqlTemplate();
        $count = count($sqlTemplate->getReplacements());

        // Add existing replacement
        $sqlTemplate->addReplacements(['!CHARSET!' => 'ascii']);
        $this->assertEquals($count, count($sqlTemplate->getReplacements()));

        // Add new replacement
        $sqlTemplate->addReplacements(['!MY_PLACEHOLDER!' => 'My value']);
        $this->assertEquals($count + 1, count($sqlTemplate->getReplacements()));
    }

    /**
     * Test {@see cSqlTemplate::getPlaceholderValue()}.
     */
    public function testGetPlaceholderValue()
    {
        // Value of existing placeholder
        $sqlTemplate = new cSqlTemplate();
        $value = $sqlTemplate->getPlaceholderValue(cSqlTemplate::PREFIX_PLACEHOLDER);
        $this->assertNotEmpty($value);
        $this->assertNotNull($value);

        // Value of not existing placeholder
        $sqlTemplate = new cSqlTemplate();
        $value = $sqlTemplate->getPlaceholderValue('!INVALID_PLACEHOLDER!');
        $this->assertEmpty($value);
        $this->assertIsNotString($value);
    }

    /**
     * Test {@see cSqlTemplate::parse()}.
     */
    public function testParse()
    {
        $prefix = CON_DB_PREFIX;
        $charset = CON_DB_CHARSET;
        $engine = CON_DB_ENGINE;
        $collation = CON_DB_COLLATION;

        // Create database
        $template = 'CREATE DATABASE `my_database` CHARACTER SET !CHARSET! COLLATE !COLLATION!';
        $expected = "CREATE DATABASE `my_database` CHARACTER SET {$charset} COLLATE {$collation}";
        $sqlTemplate = new cSqlTemplate();
        $this->assertEquals($expected, $sqlTemplate->parse($template));

        // Create table
        $template = 'CREATE TABLE `!PREFIX!_foo` (`id` int(2) NOT NULL AUTO_INCREMENT, '
            . '`t` varchar(255) NOT NULL, PRIMARY KEY (`url`)) ENGINE=!ENGINE! CHARSET=!CHARSET!;';
        $expected = "CREATE TABLE `{$prefix}_foo` (`id` int(2) NOT NULL AUTO_INCREMENT, "
            . "`t` varchar(255) NOT NULL, PRIMARY KEY (`url`)) ENGINE={$engine} CHARSET={$charset};";
        $sqlTemplate = new cSqlTemplate();
        $this->assertEquals($expected, $sqlTemplate->parse($template));

        // Custom replacement
        $template = "INSERT INTO `!PREFIX!_foo` (`t`) VALUES ('!MY_PLACEHOLDER!');";
        $expected = "INSERT INTO `{$prefix}_foo` (`t`) VALUES ('My value');";
        $sqlTemplate = new cSqlTemplate();
        $sqlTemplate->addReplacements(['!MY_PLACEHOLDER!' => 'My value']);
        $this->assertEquals($expected, $sqlTemplate->parse($template));

        // Custom replacement with escaping
        $escaped = "My escaped \'value\'";
        $template = "INSERT INTO `!PREFIX!_foo` (`t`) VALUES ('!MY_PLACEHOLDER2!');";
        $expected = "INSERT INTO `{$prefix}_foo` (`t`) VALUES ('{$escaped}');";
        $sqlTemplate = new cSqlTemplate();
        $sqlTemplate->addReplacements(['!MY_PLACEHOLDER2!' => "My escaped 'value'"]);
        $this->assertEquals($expected, $sqlTemplate->parse($template));

        // Multiline & multiple replacements
        $template = <<<SQL
CREATE TABLE `!PREFIX!_foo` (
    `id` int(2) NOT NULL AUTO_INCREMENT, 
    `t` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=!ENGINE! CHARSET=!CHARSET!;
CREATE TABLE `!PREFIX!_bar` (
    `id` int(2) NOT NULL AUTO_INCREMENT,
    `t` varchar(255) NOT NULL, PRIMARY KEY (`id`)
) ENGINE=!ENGINE! CHARSET=!CHARSET!;
SQL;
        $expected = <<<SQL
CREATE TABLE `{$prefix}_foo` (
    `id` int(2) NOT NULL AUTO_INCREMENT, 
    `t` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE={$engine} CHARSET={$charset};
CREATE TABLE `{$prefix}_bar` (
    `id` int(2) NOT NULL AUTO_INCREMENT,
    `t` varchar(255) NOT NULL, PRIMARY KEY (`id`)
) ENGINE={$engine} CHARSET={$charset};
SQL;
        $sqlTemplate = new cSqlTemplate();
        $this->assertEquals($expected, $sqlTemplate->parse($template));
    }

    /**
     * Test {@see cSqlTemplate::parseFile()}.
     * @TODO Implement this test.
     */
    public function testParseFile()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    private function assetDefaultReplacements(cSqlTemplate $sqlTemplate)
    {
        $replacements = $sqlTemplate->getReplacements();

        $this->assertTrue(isset($replacements['!CHARSET!']));
        $this->assertTrue(isset($replacements['!PREFIX!']));
        $this->assertTrue(isset($replacements['!ENGINE!']));
        $this->assertTrue(isset($replacements['!COLLATION!']));

        $this->assertEquals(CON_DB_CHARSET, $replacements['!CHARSET!']);
        $this->assertEquals(CON_DB_PREFIX, $replacements['!PREFIX!']);
        $this->assertEquals(CON_DB_ENGINE, $replacements['!ENGINE!']);
        $this->assertEquals(CON_DB_COLLATION, $replacements['!COLLATION!']);
    }

}