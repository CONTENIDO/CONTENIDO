<?php

/**
 *
 * @version SVN Revision $Rev:$
 *
 * @author claus.schunk@4fb.de
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 *
 * @author claus.schunk@4fb.de
 * @author marcus.gnass@4fb.de
 */
class cHtmlTextAreaTest extends cTestingTestCase {

    /**
     *
     * @var cHTMLTextarea
     */
    private $_textarea;

    /**
     */
    public function setUp() {
        $this->_textarea = new cHTMLTextarea('name');
    }

    /**
     */
    public function testConstruct() {
        $area = new cHTMLTextarea('testName');
        $this->assertSame(4, count($area->getAttributes()));
        $this->assertSame('testName', $area->getAttribute('name'));
        $this->assertSame(NULL, $area->getAttribute('value'));
        $this->assertSame('textarea', $this->_readAttribute($area, '_tag'));

        $area = new cHTMLTextarea('testName', 'testInitValue');
        $this->assertSame(4, count($area->getAttributes()));
        $this->assertSame('testName', $area->getAttribute('name'));
        $this->assertSame('testInitValue', $this->_readAttribute($area, '_value'));
        $this->assertSame('textarea', $this->_readAttribute($area, '_tag'));

        $area = new cHTMLTextarea('testName', 'testInitValue', 200);
        $this->assertSame(4, count($area->getAttributes()));
        $this->assertSame('testName', $area->getAttribute('name'));
        $this->assertSame('testInitValue', $this->_readAttribute($area, '_value'));
        $this->assertSame('textarea', $this->_readAttribute($area, '_tag'));
        $this->assertSame(200, $area->getAttribute('cols'));

        $area = new cHTMLTextarea('testName', 'testInitValue', 200, 100);
        $this->assertSame(4, count($area->getAttributes()));
        $this->assertSame('testName', $area->getAttribute('name'));
        $this->assertSame('textarea', $this->_readAttribute($area, '_tag'));
        $this->assertSame('testInitValue', $this->_readAttribute($area, '_value'));
        $this->assertSame(200, $area->getAttribute('cols'));
        $this->assertSame(100, $area->getAttribute('rows'));

        $area = new cHTMLTextarea('testName', 'testInitValue', 200, 100, 'testId');
        $this->assertSame(4, count($area->getAttributes()));
        $this->assertSame('testName', $area->getAttribute('name'));
        $this->assertSame('textarea', $this->_readAttribute($area, '_tag'));
        $this->assertSame('testInitValue', $this->_readAttribute($area, '_value'));
        $this->assertSame(200, $area->getAttribute('cols'));
        $this->assertSame(100, $area->getAttribute('rows'));

        $area = new cHTMLTextarea('testName', 'testInitValue', 200, 100, 'testId');
        $this->assertSame(4, count($area->getAttributes()));
        $this->assertSame('testName', $area->getAttribute('name'));
        $this->assertSame('textarea', $this->_readAttribute($area, '_tag'));
        $this->assertSame('testInitValue', $this->_readAttribute($area, '_value'));
        $this->assertSame(200, $area->getAttribute('cols'));
        $this->assertSame(100, $area->getAttribute('rows'));
        $this->assertSame('testId', $area->getAttribute('id'));

        $area = new cHTMLTextarea('testName', 'testInitValue', 200, 100, 'testId', false);
        $this->assertSame(4, count($area->getAttributes()));
        $this->assertSame('testName', $area->getAttribute('name'));
        $this->assertSame('textarea', $this->_readAttribute($area, '_tag'));
        $this->assertSame('testInitValue', $this->_readAttribute($area, '_value'));
        $this->assertSame(200, $area->getAttribute('cols'));
        $this->assertSame(100, $area->getAttribute('rows'));
        $this->assertSame('testId', $area->getAttribute('id'));

        $area = new cHTMLTextarea('testName', 'testInitValue', 200, 100, 'testId', true);
        $this->assertSame(5, count($area->getAttributes()));
        $this->assertSame('testName', $area->getAttribute('name'));
        $this->assertSame('textarea', $this->_readAttribute($area, '_tag'));
        $this->assertSame('testInitValue', $this->_readAttribute($area, '_value'));
        $this->assertSame(200, $area->getAttribute('cols'));
        $this->assertSame(100, $area->getAttribute('rows'));
        $this->assertSame('testId', $area->getAttribute('id'));
        $this->assertSame('disabled', $area->getAttribute('disabled'));

        $area = new cHTMLTextarea('testName', 'testInitValue', 200, 100, 'testId', false, NULL, '', 'testClass');
        $this->assertSame(5, count($area->getAttributes()));
        $this->assertSame('testName', $area->getAttribute('name'));
        $this->assertSame('textarea', $this->_readAttribute($area, '_tag'));
        $this->assertSame('testInitValue', $this->_readAttribute($area, '_value'));
        $this->assertSame(200, $area->getAttribute('cols'));
        $this->assertSame(100, $area->getAttribute('rows'));
        $this->assertSame('testId', $area->getAttribute('id'));
        $this->assertSame('testClass', $area->getAttribute('class'));
    }

    /**
     */
    public function testSetWidth() {
        $area = new cHTMLTextarea('testName', 'testInitValue', 200);
        $this->assertSame(4, count($area->getAttributes()));
        $this->assertSame('testName', $area->getAttribute('name'));
        $this->assertSame('testInitValue', $this->_readAttribute($area, '_value'));
        $this->assertSame('textarea', $this->_readAttribute($area, '_tag'));
        $this->assertSame(200, $area->getAttribute('cols'));

        $area->setWidth(-1);
        $this->assertSame(50, $area->getAttribute('cols'));
        $area->setWidth(0);
        $this->assertSame(50, $area->getAttribute('cols'));
        $area->setWidth(1);
        $this->assertSame(1, $area->getAttribute('cols'));
    }

    /**
     */
    public function testSetHeight() {
        $area = new cHTMLTextarea('testName', 'testInitValue');
        $this->assertSame(4, count($area->getAttributes()));
        $this->assertSame('testName', $area->getAttribute('name'));
        $this->assertSame('testInitValue', $this->_readAttribute($area, '_value'));
        $this->assertSame('textarea', $this->_readAttribute($area, '_tag'));

        $area->setValue('testTestInitValue');
        $this->assertSame('testTestInitValue', $this->_readAttribute($area, '_value'));
        $area->setValue('testInitValue');
        $this->assertSame('testInitValue', $this->_readAttribute($area, '_value'));
    }

    /**
     */
    public function testSetValue() {
        // test w/o value
        $act = $this->_readAttribute($this->_textarea, '_value');
        $exp = '';
        $this->assertSame($exp, $act);

        // test w/ value
        $this->_textarea->setValue('value');
        $act = $this->_readAttribute($this->_textarea, '_value');
        $exp = 'value';
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testToHtml() {
        $area = new cHTMLTextarea('testName', 'testInitValue');
        $this->assertSame($area->toHtml(), $area->toHTML());
    }
}

?>