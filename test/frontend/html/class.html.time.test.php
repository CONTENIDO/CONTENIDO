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
class cHtmlTimeTest extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var cHTMLTime
     */
    private $_time;

    /**
     * Creates tables with values of different datatypes.
     */
    public function setUp() {
        $this->_time = new cHTMLTime('testContent', 'testClass', 'testId', 'testDateTime');
    }

    /**
     *
     */
    public function testConstruct() {

        $act = count($this->_time->getAttributes());
        $exp = 3;
        $this->assertSame($exp, $act);

        $act = PHPUnit_Framework_Assert::readAttribute($this->_time, '_tag');
        $exp = 'time';
        $this->assertSame($exp, $act);

        $act = $this->_time->getAttribute('class');
        $exp = 'testClass';
        $this->assertSame($exp, $act);

        $act = $this->_time->getAttribute('id');
        $exp = 'testId';
        $this->assertSame($exp, $act);

        $act = $this->_time->getAttribute('datetime');
        $exp = 'testDateTime';
        $this->assertSame($exp, $act);

        $act = $this->_time->toHTML();
        $exp = '<time id="testId" class="testClass" datetime="testDateTime">testContent</time>';
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testSetDatetime() {
        $this->_time->setDatetime('datetime');
        $act = $this->_time->getAttribute('datetime');
        $exp = 'datetime';
        $this->assertSame($exp, $act);
    }

}

?>