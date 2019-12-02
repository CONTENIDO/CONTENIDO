<?php

/**
 *
 * @author    claus.schunk@4fb.de
 * @author    marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */

/**
 *
 * @author claus.schunk@4fb.de
 * @author marcus.gnass@4fb.de
 */
class cHtmlCheckBoxTest extends cTestingTestCase
{
    /**
     *
     * @var cHTMLCheckbox
     */
    private $_checkbox;

    /**
     */
    protected function setUp(): void
    {
        $this->_checkbox = new cHTMLCheckbox('name', 'value');
        $this->_checkbox->setID('');
    }

    /**
     * Test constructor which sets the member $_tag.
     */
    public function testConstructTag()
    {
        $act = $this->_readAttribute($this->_checkbox, '_tag');
        $exp = 'input';
        $this->assertSame($exp, $act);
    }

    /**
     * Test constructor which sets the member $_value.
     */
    public function testConstructValue()
    {
        $act = $this->_readAttribute($this->_checkbox, '_value');
        $exp = 'value';
        $this->assertSame($exp, $act);
    }

    /**
     * Test constructor which sets the member $_contentlessTag.
     */
    public function testConstructContentlessTag()
    {
        $act = $this->_readAttribute($this->_checkbox, '_contentlessTag');
        $exp = true;
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testSetChecked()
    {
        $this->_checkbox->setChecked(true);
        $this->assertSame('checked', $this->_checkbox->getAttribute('checked'));

        $this->_checkbox->setChecked(false);
        $this->assertNull($this->_checkbox->getAttribute('checked'));
    }

    /**
     */
    public function testSetLabelText()
    {
        // set label
        $this->_checkbox->setLabelText('label');
        $act = $this->_readAttribute($this->_checkbox, '_labelText');
        $exp = 'label';
        $this->assertSame($exp, $act);
        // unset label
        $this->_checkbox->setLabelText('');
        $act = $this->_readAttribute($this->_checkbox, '_labelText');
        $exp = '';
        $this->assertSame($exp, $act);
    }

    /**
     * w/o label
     */
    public function testToHtmlFalse()
    {
        $act = $this->_checkbox->toHtml(false);
        $exp = '<input name="name" type="checkbox" value="value" />';
        $this->assertSame($exp, $act);
    }

    /**
     * w/ label
     */
    public function testToHtmlTrue()
    {
        $this->_checkbox->setID('testId'); // NOTE: We need to set the id here, otherwise it will be set dynamically and we can't predict it!
        $act = $this->_checkbox->toHtml(true);
        $exp = '<div class="checkbox_wrapper"><input name="name" type="checkbox" value="value" id="testId" /><label for="testId">value</label></div>';
        $this->assertSame($exp, $act);
    }

    /**
     * w/ label & text
     */
    public function testToHtml()
    {
        $this->_checkbox->setLabelText('label')->setId('testId');
        $act = $this->_checkbox->toHtml(true);
        $exp =
            '<div class="checkbox_wrapper"><input name="name" type="checkbox" value="value" id="testId" /><label for="testId">label</label></div>';
        $this->assertSame($exp, $act);
    }
}

