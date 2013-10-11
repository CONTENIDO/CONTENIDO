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
class cHtmlImageTest extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var cHTMLImage
     */
    private $_imageEmpty;

    /**
     *
     * @var cHTMLImage
     */
    private $_imageData;

    /**
     *
     * @var cHTMLImage
     */
    private $_imageSrc;

    /**
     */
    public function setUp() {
        // image w/o data
        $this->_imageEmpty = new cHTMLImage();

        // image w/ data
        $this->_imageData = new cHTMLImage('source', 'class');

        // image w/ src
        $this->_imageSrc = new cHTMLImage();
        $this->_imageSrc->setSrc('http://google.jpg');
    }

    /**
     * Test constructor which sets the member $_tag.
     */
    public function testConstructTag() {
        $act = PHPUnit_Framework_Assert::readAttribute($this->_imageEmpty, '_tag');
        $exp = 'img';
        $this->assertSame($exp, $act);
    }

    /**
     * Test constructor which sets the member $_contentlessTag.
     */
    public function testConstructContentlessTag() {
        $act = PHPUnit_Framework_Assert::readAttribute($this->_imageEmpty, '_contentlessTag');
        $exp = true;
        $this->assertSame($exp, $act);
    }

    /**
     * Test constructor which sets the attribute src.
     */
    public function testConstructSource() {
        // src of image w/o data
        $act = $this->_imageEmpty->getAttribute('src');
        $exp = 'images/spacer.gif';
        $this->assertSame($exp, $act);

        // src of image w/ data
        $act = $this->_imageData->getAttribute('src');
        $exp = 'source';
        $this->assertSame($exp, $act);
    }

    /**
     * Test constructor which sets the attribute class.
     */
    public function testConstructClass() {
        // class of image w/o data
        $act = $this->_imageEmpty->getAttribute('class');
        $exp = '';
        $this->assertSame($exp, $act);

        // class of image w/ data
        $act = $this->_imageData->getAttribute('class');
        $exp = 'class';
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testSrc() {
        $act = $this->_imageSrc->getAttribute('src');
        $exp = 'http://google.jpg';
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testSetWidth() {
        // test setting string as width
        $this->_imageEmpty->setWidth('1');
        $this->assertSame('1', $this->_imageEmpty->getAttribute('width'));
        // test setting integer as width
        $this->_imageEmpty->setWidth(1);
        $this->assertSame(1, $this->_imageEmpty->getAttribute('width'));
        // test setting float as width
        $this->_imageEmpty->setWidth(1.5);
        $this->assertSame(1.5, $this->_imageEmpty->getAttribute('width'));
    }

    /**
     */
    public function testSetHeight() {
        // test setting string as height
        $this->_imageEmpty->setHeight('1');
        $this->assertSame('1', $this->_imageEmpty->getAttribute('height'));
        // test setting integer as height
        $this->_imageEmpty->setHeight(1);
        $this->assertSame(1, $this->_imageEmpty->getAttribute('height'));
        // test setting float as height
        $this->_imageEmpty->setHeight(1.5);
        $this->assertSame(1.5, $this->_imageEmpty->getAttribute('height'));

    }

    /**
     */
    public function testSetBorder() {
        // test setting string as border
        $this->_imageEmpty->setBorder('1');
        $this->assertSame('1', $this->_imageEmpty->getAttribute('border'));
        // test setting integer as border
        $this->_imageEmpty->setBorder(1);
        $this->assertSame(1, $this->_imageEmpty->getAttribute('border'));        // test setting integer as border
        // test setting float as border
        $this->_imageEmpty->setBorder(1.5);
        $this->assertSame(1.5, $this->_imageEmpty->getAttribute('border'));
    }

    /**
     * This test needs an image in the CONTENIDO backend and thus is difficult
     * to test.
     * BTW: Why is the image expected to be located in the backend folder?
     *
     * @todo This test has not been implemented yet.
     */
    public function testApplyDimensions() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}

?>