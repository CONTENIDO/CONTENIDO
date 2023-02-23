<?php

/**
 * @package    Testing
 * @subpackage GUI_HTML
 * @author     claus.schunk@4fb.de
 * @author     marcus.gnass@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * @author claus.schunk@4fb.de
 * @author marcus.gnass@4fb.de
 */
class cHTMLSelectElementTest extends cTestingTestCase
{
    /**
     *
     * @var cHTMLSelectElement
     */
    private $_selectEmpty;

    /**
     *
     * @var cHTMLSelectElement
     */
    private $_selectData;

    /**
     *
     * @var cHTMLOptionElement
     */
    private $_foo;

    /**
     *
     * @var cHTMLOptionElement
     */
    private $_bar;

    /**
     *
     * @var cHTMLOptionElement
     */
    private $_baz;

    /**
     */
    protected function setUp(): void
    {
        $this->_selectEmpty = new cHTMLSelectElement('empty');
        $this->_selectData  = new cHTMLSelectElement('testName');
        $this->_foo         = new cHTMLOptionElement('', 'foo');
        $this->_bar         = new cHTMLOptionElement('', 'bar');
        $this->_baz         = new cHTMLOptionElement('', 'baz');
        $this->_selectData->addOptionElement('foo', $this->_foo);
        $this->_selectData->addOptionElement('bar', $this->_bar);
        $this->_selectData->addOptionElement('baz', $this->_baz);
    }

    /**
     */
    public function testConstruct()
    {
        // $section = new cHTMLSection('testContent', 'testClass', 'testId');
        $this->assertSame('select', $this->_readAttribute($this->_selectData, '_tag'));
        $this->assertSame(false, $this->_readAttribute($this->_selectData, '_contentlessTag'));
        $this->assertSame(1, count($this->_selectData->getAttributes()));
        $this->assertSame('testName', $this->_selectData->getAttribute('name'));
        $this->assertNull($this->_selectData->getAttribute('class'));
        $this->assertNull($this->_selectData->getAttribute('disabled'));

        $this->_selectData = new cHTMLSelectElement('testName', 100);
        $this->assertSame('select', $this->_readAttribute($this->_selectData, '_tag'));
        $this->assertSame(false, $this->_readAttribute($this->_selectData, '_contentlessTag'));
        $this->assertSame(1, count($this->_selectData->getAttributes()));
        $this->assertSame('testName', $this->_selectData->getAttribute('name'));
        $this->assertNull($this->_selectData->getAttribute('class'));
        $ret = $this->_readAttribute($this->_selectData, '_styleDefinitions');
        $this->assertSame(100, $ret['width']);

        $this->_selectData = new cHTMLSelectElement('testName', 100, 'testId');
        $this->assertSame('select', $this->_readAttribute($this->_selectData, '_tag'));
        $this->assertSame(false, $this->_readAttribute($this->_selectData, '_contentlessTag'));
        $this->assertSame(2, count($this->_selectData->getAttributes()));
        $this->assertSame('testName', $this->_selectData->getAttribute('name'));
        $this->assertNull($this->_selectData->getAttribute('class'));
        $ret = $this->_readAttribute($this->_selectData, '_styleDefinitions');
        $this->assertSame(100, $ret['width']);
        $this->assertSame('testId', $this->_selectData->getAttribute('id'));

        $this->_selectData = new cHTMLSelectElement('testName', 100, 'testId', true);
        $this->assertSame('select', $this->_readAttribute($this->_selectData, '_tag'));
        $this->assertSame(false, $this->_readAttribute($this->_selectData, '_contentlessTag'));
        $this->assertSame(3, count($this->_selectData->getAttributes()));
        $this->assertSame('testName', $this->_selectData->getAttribute('name'));
        $this->assertNull($this->_selectData->getAttribute('class'));
        $ret = $this->_readAttribute($this->_selectData, '_styleDefinitions');
        $this->assertSame(100, $ret['width']);
        $this->assertSame('testId', $this->_selectData->getAttribute('id'));
        $this->assertSame('disabled', $this->_selectData->getAttribute('disabled'));

        $this->_selectData = new cHTMLSelectElement('testName', 100, 'testId', false, null, '', 'testClass');
        $this->assertSame('select', $this->_readAttribute($this->_selectData, '_tag'));
        $this->assertSame(false, $this->_readAttribute($this->_selectData, '_contentlessTag'));
        $this->assertSame(3, count($this->_selectData->getAttributes()));
        $this->assertSame('testName', $this->_selectData->getAttribute('name'));
        $ret = $this->_readAttribute($this->_selectData, '_styleDefinitions');
        $this->assertSame(100, $ret['width']);
        $this->assertSame('testClass', $this->_selectData->getAttribute('class'));
    }

    /**
     */
    public function testAutoFill()
    {
        $this->_selectData = new cHTMLSelectElement('testName', 100, 'testId');
        $stuff             = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $this->_selectData->autoFill($stuff);
        $this->assertSame(3, count($this->_readAttribute($this->_selectData, '_options')));
        $ret = $this->_readAttribute($this->_selectData, '_options');
        $this->assertSame('value1', $this->_readAttribute($ret['key1'], '_title'));
        $this->assertSame('value2', $this->_readAttribute($ret['key2'], '_title'));
        $this->assertSame('value3', $this->_readAttribute($ret['key3'], '_title'));
    }

    /**
     */
    public function testAddOptionElement()
    {
        // test first element
        $this->_selectEmpty->addOptionElement(0, $this->_foo);
        $options = $this->_readAttribute($this->_selectEmpty, '_options');
        $this->assertSame(true, is_array($options));
        $this->assertSame(1, count($options));
        $this->assertSame($this->_foo, $options[0]);

        // test second element
        $this->_selectEmpty->addOptionElement(1, $this->_bar);
        $options = $this->_readAttribute($this->_selectEmpty, '_options');
        $this->assertSame(true, is_array($options));
        $this->assertSame(2, count($options));
        $this->assertSame($this->_foo, $options[0]);
        $this->assertSame($this->_bar, $options[1]);
    }

    /**
     */
    public function testAppendOptionElement()
    {
        // test first element
        $this->_selectEmpty->appendOptionElement($this->_foo);
        $options = $this->_readAttribute($this->_selectEmpty, '_options');
        $this->assertSame(true, is_array($options));
        $this->assertSame(1, count($options));
        $this->assertSame($this->_foo, $options[0]);

        // test second element
        $this->_selectEmpty->appendOptionElement($this->_bar);
        $options = $this->_readAttribute($this->_selectEmpty, '_options');
        $this->assertSame(true, is_array($options));
        $this->assertSame(2, count($options));
        $this->assertSame($this->_foo, $options[0]);
        $this->assertSame($this->_bar, $options[1]);
    }

    /**
     */
    public function testSetMultiSelect()
    {
        $this->assertNull($this->_selectData->getAttribute('multiple'));
        $this->_selectData->setMultiselect();
        $this->assertSame('multiple', $this->_selectData->getAttribute('multiple'));
    }

    /**
     */
    public function testSetSize()
    {
        $this->assertNull($this->_selectData->getAttribute('size'));
        $this->_selectData->setSize(100);
        $this->assertSame(100, $this->_selectData->getAttribute('size'));
    }

    /**
     */
    public function testSetDefault()
    {
        // no option is selected
        /** @var cHTMLOptionElement[] $options */
        $options = $this->_readAttribute($this->_selectData, '_options');
        $this->assertSame(true, is_array($options));
        foreach ($options as $key => $option) {
            $act = $option->isSelected();
            $exp = false;
            $this->assertSame($exp, $act);
        }

        // option foo is selected
        $this->_selectData->setDefault('foo');
        $options = $this->_readAttribute($this->_selectData, '_options');
        $this->assertSame(true, is_array($options));
        foreach ($options as $key => $option) {
            $act = $option->isSelected();
            $exp = 'foo' === $key ? true : false;
            $this->assertSame($exp, $act);
        }

        // option bar is selected
        $this->_selectData->setDefault('bar');
        $options = $this->_readAttribute($this->_selectData, '_options');
        $this->assertSame(true, is_array($options));
        foreach ($options as $key => $option) {
            $act = $option->isSelected();
            $exp = 'bar' === $key ? true : false;
            $this->assertSame($exp, $act);
        }

        // options foo & bar are selected
        $this->_selectData->setDefault(
            [
                'foo',
                'bar',
            ]
        );
        $options = $this->_readAttribute($this->_selectData, '_options');
        $this->assertSame(true, is_array($options));
        foreach ($options as $key => $option) {
            $act = $option->isSelected();
            $exp = in_array(
                $key,
                [
                    'foo',
                    'bar',
                ]
            ) ? true : false;
            $this->assertSame($exp, $act);
        }
    }

    /**
     */
    public function testGetDefault()
    {
        // w/o default
        $act = $this->_selectData->getDefault();
        $exp = false;
        $this->assertSame($exp, $act);

        // w/ default
        $this->_foo->setSelected(true);
        $act = $this->_selectData->getDefault();
        $exp = 'foo';
        $this->assertSame($exp, $act);

        // again w/o default
        $this->_foo->setSelected(false);
        $act = $this->_selectData->getDefault();
        $exp = false;
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testSetSelected()
    {
        // selected none
        $this->assertSame(false, $this->_foo->isSelected());
        $this->assertSame(false, $this->_bar->isSelected());

        // selected none
        $this->_selectData->setSelected([]);
        $this->assertSame(false, $this->_foo->isSelected());
        $this->assertSame(false, $this->_bar->isSelected());

        // selected foo
        $this->_selectData->setSelected(
            [
                'foo',
            ]
        );
        $this->assertSame(true, $this->_foo->isSelected());
        $this->assertSame(false, $this->_bar->isSelected());

        // selected bar
        $this->_selectData->setSelected(
            [
                'bar',
            ]
        );
        $this->assertSame(false, $this->_foo->isSelected());
        $this->assertSame(true, $this->_bar->isSelected());
    }

    /**
     */
    public function testToHtml()
    {
        $this->assertSame($this->_selectData->toHtml(), $this->_selectData->toHtml());
    }
}

