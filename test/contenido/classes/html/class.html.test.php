<?php
/**
 * This file contains tests for the class cHTML.
 *
 * @package Testing
 * @subpackage GUI_HTML
 * @version SVN Revision $Rev:$
 *
 * @author marcus.gnass
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/*
 * wrapper class to set _idCounter
 */
class cHTMLProxy extends cHTML {
    /**
     * Setter for static $_idCounter property
     *
     * @param int $value
     */
    public static function setIdCounter($value) {
        self::$_idCounter = $value;
    }
}

/**
 * This class tests the class methods of the cHTML class.
 *
 * @author marcus.gnass
 */
class cHTMLTest extends cTestingTestCase {

    public function setUp() {
        // create XHTML
        cHTML::setGenerateXHTML(true);
        
        
        cHTMLProxy::setIdCounter(0);
    }

    /**
     * Test trimming of items in empty array, nonempty array and nonempty array
     * with arbitrary character.
     */
    public function testConstruct() {
        $html = new cHTMLProxy();
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
        $html = new cHTMLProxy();
        $this->assertSame('m1', $html->getID());
        $html->advanceID();
        $this->assertSame('m2', $html->getID());
    }

    public function testGetID() {
        $html = new cHTMLProxy();
        $this->assertSame('m1', $html->getID());
    }

    public function testSetTag() {
        $html = new cHTMLProxy();
        $html->setTag('foo');
        $this->assertSame('<foo id="m1" />', $html->render());
        $html->setTag('bar');
        $this->assertSame('<bar id="m1" />', $html->render());
    }

    public function testSetAlt() {

        // set alt w/ default setting for title
        $html = new cHTMLProxy();
        $html->setAlt('foobar');
        $this->assertSame('< id="m1" alt="foobar" title="foobar" />', $html->render());

        // set alt w/ title
        $html = new cHTMLProxy();
        $html->setAlt('foobar', true);
        $this->assertSame('< id="m2" alt="foobar" title="foobar" />', $html->render());

        // set alt w/o title
        $html = new cHTMLProxy();
        $html->setAlt('foobar', false);
        $this->assertSame('< id="m3" alt="foobar" />', $html->render());

        // set alt w/ title & reset alt w/o title
        $html = new cHTMLProxy();
        $html->setAlt('foo', true);
        $html->setAlt('bar', false);
        $this->assertSame('< id="m4" alt="bar" title="foo" />', $html->render());
    }

    public function testSetID() {
        $html = new cHTMLProxy();
        $html->setID('foobar');
        $this->assertSame('foobar', $html->getID());
        $this->assertSame('< id="foobar" />', $html->render());
        // an emtpy string should remove the id-tag but it does nothing!
        $html->setID('');
        $this->assertSame('', $html->getID());
        $this->assertSame('< id="" />', $html->render());
        $html->setID(NULL);
        $this->assertSame(NULL, $html->getID());
        $this->assertSame('< />', $html->render());
    }

    public function testSetClass() {
        $html = new cHTMLProxy();
        $html->setClass('foobar');
        $this->assertSame('< id="m1" class="foobar" />', $html->render());
        // set class emtpy should remove class-tag!!
        $html->setClass('');
        $this->assertSame('< id="m1" class="" />', $html->render());
        $html->setClass(NULL);
        $this->assertSame('< id="m1" />', $html->render());
    }
}
