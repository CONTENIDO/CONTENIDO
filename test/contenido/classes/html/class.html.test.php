<?php
/**
 * This file contains tests for the class cHTML.
 *
 * @package    Testing
 * @subpackage GUI_HTML
 * @author     marcus.gnass
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

/**
 * This class tests the class methods of the cHTML class.
 *
 * @author marcus.gnass
 */
class cHTMLTest extends cTestingTestCase
{
    protected function setUp(): void
    {
        // create XHTML
        cHTML::setGenerateXHTML(true);
    }

    /**
     * Test trimming of items in empty array, nonempty array and nonempty array
     * with arbitrary character.
     */
    public function testConstruct()
    {
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
        $this->assertNull($html->getID());
    }

    public function testAdvanceID()
    {
        $html = new cHTML(['id' => 'testId']);
        $id   = $html->getID();
        $html->advanceID();
        $this->assertNotSame($id, $html->getID());
    }

    public function testGetNotSetID()
    {
        $html = new cHTML();
        $this->assertNull($html->getID());
    }

    public function testGetID()
    {
        $html = new cHTML(['id' => 'testId']);
        $this->assertSame('testId', $html->getID());
    }

    public function testSetTag()
    {
        $html = new cHTML();

        $html->setTag('foo');
        $this->assertSame('<foo />', $html->render());

        $html->setTag('bar');
        $this->assertSame('<bar />', $html->render());
    }

    public function testSetAlt()
    {
        // set alt w/ default setting for title
        $html = new cHTML();
        $html->setAlt('foobar');
        $this->assertSame('< alt="foobar" title="foobar" />', $html->render());

        // set alt w/ title
        $html = new cHTML();
        $html->setAlt('foobar', true);
        $this->assertSame('< alt="foobar" title="foobar" />', $html->render());

        // set alt w/o title
        $html = new cHTML();
        $html->setAlt('foobar', false);
        $this->assertSame('< alt="foobar" />', $html->render());

        // set alt w/ title & reset alt w/o title
        $html = new cHTML();
        $html->setAlt('foo', true);
        $html->setAlt('bar', false);
        $this->assertSame('< alt="bar" title="foo" />', $html->render());
    }

    public function testSetID()
    {
        $html = new cHTML();

        $html->setID('foobar');
        $this->assertSame('foobar', $html->getID());
        $this->assertSame('< id="foobar" />', $html->render());

        $html->setID('');
        $this->assertNull($html->getID());
        $this->assertSame('< />', $html->render());

        $html->setID(null);
        $this->assertNull($html->getID());
        $this->assertSame('< />', $html->render());
    }

    public function testSetClass()
    {
        $html = new cHTML();

        $html->setClass('foobar');
        $this->assertSame('< class="foobar" />', $html->render());

        $html->setClass('');
        $this->assertSame('< />', $html->render());

        $html->setClass(null);
        $this->assertSame('< />', $html->render());
    }

    public function testSetStyle()
    {
        $html = new cHTML();
        $html->setStyle('my style');
        $this->assertSame('< style="my style;" />', $html->render());
    }

    // public function testSetEvent() {
    //     $html = new cHTML();
    //     $html->setEvent();
    //     $this->assertSame('<  />', $html->render());
    // }
    //
    // public function testUnsetEvent() {
    //     $html = new cHTML();
    //     $html->unsetEvent();
    //     $this->assertSame('<  />', $html->render());
    // }
    //
    // public function testFillSkeleton() {
    //     $html = new cHTML();
    //     $html->fillSkeleton();
    //     $this->assertSame('<  />', $html->render());
    // }
    //
    // public function testFillCloseSkeleton() {
    //     $html = new cHTML();
    //     $html->fillCloseSkeleton();
    //     $this->assertSame('<  />', $html->render());
    // }
    //
    // public function testAppendStyleDefinition() {
    //     $html = new cHTML();
    //     $html->appendStyleDefinition();
    //     $this->assertSame('<  />', $html->render());
    // }
    //
    // public function testAppendStyleDefinitions() {
    //     $html = new cHTML();
    //     $html->();
    //     $this->assertSame('<  />', $html->render());
    // }
    //
    // public function testAddRequiredScript() {
    //     $html = new cHTML();
    //     $html->addRequiredScript();
    //     $this->assertSame('<  />', $html->render());
    // }
    //
    // public function testAttachEventDefinition() {
    //     $html = new cHTML();
    //     $html->attachEventDefinition();
    //     $this->assertSame('<  />', $html->render());
    // }

    public function testGetAttribute()
    {
        $html = new cHTML();
        $this->assertNull($html->getAttribute('id'));

        $html->setAttribute('id', 'my-id');
        $this->assertSame('my-id', $html->getAttribute('id'));
    }

    public function testGetAttributes()
    {
        $attr = ['id' => 'my-id', 'class' => 'my-class'];
        $html = new cHTML();

        $html->setAttributes($attr);
        $this->assertSame($attr, $html->getAttributes());
    }

    public function testSetAttribute()
    {
        $html = new cHTML();

        $html->setAttribute('id');
        $this->assertSame('< />', $html->render());

        $html->setAttribute('name');
        $this->assertSame('< />', $html->render());

        $html->setAttribute('class');
        $this->assertSame('< />', $html->render());

        $html->setAttribute('foo');
        $this->assertSame('< foo="foo" />', $html->render());
    }

    public function testSetAttributes()
    {
        $html = new cHTML();

        $html->setAttributes(['id', 'name', 'class', 'foo', 'bar' => 'baz']);
        $this->assertSame('< id="id" name="name" class="class" foo="foo" bar="baz" />', $html->render());
    }

    public function testUpdateAttribute()
    {
        $html = new cHTML();

        $html->setAttributes(['id' => 'my-id', 'class' => 'my-class', 'foo']);
        $this->assertSame('< id="my-id" class="my-class" foo="foo" />', $html->render());

        $html->updateAttribute('id', 'another-id');
        $html->updateAttribute('class', 'another-class');
        $html->updateAttribute('foo', 'another-foo');
        $this->assertSame('< id="another-id" class="another-class" foo="another-foo" />', $html->render());
    }

    public function testUpdateAttributes()
    {
        $html = new cHTML();

        $html->setAttributes(['id' => 'my-id', 'class' => 'my-class', 'foo']);
        $this->assertSame('< id="my-id" class="my-class" foo="foo" />', $html->render());

        $html->updateAttributes(['id' => 'another-id', 'class' => 'another-class', 'foo' => 'another-foo']);
        $this->assertSame('< id="another-id" class="another-class" foo="another-foo" />', $html->render());
    }

    public function testRemoveAttribute()
    {
        $html = new cHTML();

        $html->setAttributes(['id' => 'my-id', 'class' => 'my-class', 'foo']);
        $this->assertSame('< id="my-id" class="my-class" foo="foo" />', $html->render());

        $html->removeAttribute('class');
        $this->assertSame('< id="my-id" foo="foo" />', $html->render());

        $html->removeAttribute('foo');
        $this->assertSame('< id="my-id" />', $html->render());
    }

    // public function testToHtml() {
    //     $html = new cHTML();
    //     $html->toHtml();
    //     $this->assertSame('<  />', $html->render());
    // }
    //
    // public function testDisplay() {
    //     $html = new cHTML();
    //     $html->display();
    //     $this->assertSame('<  />', $html->render());
    // }
}
