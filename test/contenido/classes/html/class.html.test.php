<?php
/**
 * This file contains tests for the class cHTML.
 *
 * @package Testing
 * @subpackage GUI_HTML
 * @author marcus.gnass
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * This class tests the class methods of the cHTML class.
 *
 * @author marcus.gnass
 */
class cHTMLTest extends cTestingTestCase {

    public function setUp() {
        // create XHTML
        cHTML::setGenerateXHTML(true);
    }

    /**
     * Test trimming of items in empty array, nonempty array and nonempty array
     * with arbitrary character.
     */
    public function testConstruct() {
        $html = new cHTML();
        $this->assertClassHasAttribute('_generateXHTML', 'cHTML');
        $this->assertClassHasAttribute('_skeletonOpen', 'cHTML');
        $this->assertClassHasAttribute('_skeletonSingle', 'cHTML');
        $this->assertClassHasAttribute('_skeletonClose', 'cHTML');
        $this->assertClassHasAttribute('_tag', 'cHTML');
        $this->assertClassHasAttribute('_styleDefs', 'cHTML');
        $this->assertClassHasAttribute('_requiredScripts', 'cHTML');
        $this->assertClassHasAttribute('_contentlessTag', 'cHTML');
        $this->assertClassHasAttribute('_eventDefinitions', 'cHTML');
        $this->assertClassHasAttribute('_styleDefinitions', 'cHTML');
        $this->assertClassHasAttribute('_attributes', 'cHTML');
        $this->assertClassHasAttribute('_content', 'cHTML');
        $this->assertSame('m1', $html->getID());
    }

    public function testAdvanceID() {
        $html = new cHTML();
        $this->assertSame('m2', $html->getID());
        $html->advanceID();
        $this->assertSame('m3', $html->getID());
    }

    public function testGetID() {
        $html = new cHTML();
        $this->assertSame('m4', $html->getID());
    }

    public function testSetTag() {
        $html = new cHTML();
        $html->setTag('foo');
        $this->assertSame('<foo id="m5" />', $html->render());
        $html->setTag('bar');
        $this->assertSame('<bar id="m5" />', $html->render());
    }

    public function testSetAlt() {

        // set alt w/ default setting for title
        $html = new cHTML();
        $html->setAlt('foobar');
        $this->assertSame('< id="m6" alt="foobar" title="foobar" />', $html->render());

        // set alt w/ title
        $html = new cHTML();
        $html->setAlt('foobar', true);
        $this->assertSame('< id="m7" alt="foobar" title="foobar" />', $html->render());

        // set alt w/o title
        $html = new cHTML();
        $html->setAlt('foobar', false);
        $this->assertSame('< id="m8" alt="foobar" />', $html->render());

        // set alt w/ title & reset alt w/o title
        $html = new cHTML();
        $html->setAlt('foo', true);
        $html->setAlt('bar', false);
        $this->assertSame('< id="m9" alt="bar" />', $html->render());
    }

    public function testSetID() {
        $html = new cHTML();
        $html->setID('foobar');
        $this->assertSame('foobar', $html->getID());
        $this->assertSame('< id="foobar" />', $html->render());
        $html->setID('');
        $this->assertSame('', $html->getID());
        $this->assertSame('< id="" />', $html->render());
        $html->setID(NULL);
        $this->assertSame(NULL, $html->getID());
        $this->assertSame('< />', $html->render());
    }

    public function testSetClass() {
        $html = new cHTML();
        $html->setClass('foobar');
        $this->assertSame('< id="m11" class="foobar" />', $html->render());
        $html->setClass('');
        $this->assertSame('< id="m11" class="" />', $html->render());
        $html->setClass(NULL);
        $this->assertSame('< id="m11" />', $html->render());
    }
}
