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
 * Some methods have data providers to keep test cases concise.
 * They return a list of data sets for several testcases.
 * e.g. [
 *     'name of data set' => [<data to be used as input>, <data to be expected as output>],
 *     ...
 * ]
 *
 * @link   https://phpunit.readthedocs.io/en/8.4/annotations.html#dataprovider
 *
 * @author marcus.gnass
 */
class cHTMLTest extends cTestingTestCase
{
    protected function setUp(): void
    {
        cHTML::setGenerateXHTML(true);
    }

    /**
     */
    public function testAttributes()
    {
        $html = new cHTML();
        $this->assertClassHasAttribute('_generateXHTML', 'cHTML');
        $this->assertClassHasAttribute('_skeletonOpen', 'cHTML');
        $this->assertClassHasAttribute('_skeletonSingle', 'cHTML');
        $this->assertClassHasAttribute('_skeletonClose', 'cHTML');
        $this->assertClassHasAttribute('_tag', 'cHTML');
        $this->assertClassHasAttribute('_requiredScripts', 'cHTML');
        $this->assertClassHasAttribute('_contentlessTag', 'cHTML');
        $this->assertClassHasAttribute('_eventDefinitions', 'cHTML');
        $this->assertClassHasAttribute('_styleDefinitions', 'cHTML');
        $this->assertClassHasAttribute('_attributes', 'cHTML');
        $this->assertClassHasAttribute('_content', 'cHTML');

        $this->assertNull($html->getID());
    }

    public function dataConstruct()
    {
        return [
            'null'                                       => [
                null,
                [],
            ],
            'empty'                                      => [
                [],
                [],
            ],
            'attribute as number without value'          => [
                [1],
                [1 => '1'], // <= TODO is this correct?
            ],
            'attribute as number with value'             => [
                [1 => '1'],
                [1 => '1'],
            ],
            'attribute as empty string with empty value' => [
                ['' => ''],
                ['' => ''], // <= TODO is this correct?
            ],
            'attribute as string without value'          => [
                ['id'],
                ['id' => 'id'],
            ],
            'attribute as string with value'             => [
                ['id' => 'my-id'],
                ['id' => 'my-id'],
            ],
        ];
    }

    /**
     * @dataProvider dataConstruct()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testConstruct(array $input = null, array $output = null)
    {
        $cHtml = new cHTML($input);
        $this->assertEquals($output, $cHtml->getAttributes());
    }

    public function testSetGenerateXHTML()
    {
        $this->markTestIncomplete();
    }

    public function testAdvanceID()
    {
        $html = new cHTML(['id' => 'testId']);
        $id   = $html->getID();
        $html->advanceID();
        $this->assertNotSame($id, $html->getID());
    }

    public function dataGetIDAndSetID()
    {
        return [
            'null'   => [null, null],
            'empty'  => ['', null],
            'foobar' => ['foobar', 'foobar'],
        ];
    }

    /**
     * @dataProvider dataGetIDAndSetID()
     *
     * @param string|null $input  data to be used as input
     * @param string|null $output data to be expected as output
     */
    public function testGetIDAndSetID(string $input = null, string $output = null)
    {
        // via constructor
        $html = new cHTML(['id' => $input]);
        $this->assertSame($output, $html->getID());

        // via setter
        $html = new cHTML();
        $html->setID($input);
        $this->assertSame($output, $html->getID());

        // check return value
        $this->assertInstanceOf('cHTML', $html->setID(''));
    }

    public function dataSetTag()
    {
        return [
            'null'   => [null, '< />'], // <= TODO is this correct?
            'empty'  => ['', '< />'], // <= TODO is this correct?
            'foobar' => ['foobar', '<foobar />'],
        ];
    }

    /**
     * @dataProvider dataSetTag()
     *
     * @param string|null $input  data to be used as input
     * @param string|null $output data to be expected as output
     */
    public function testSetTag(string $input = null, string $output = null)
    {
        $html = new cHTML();
        $html->setTag($input);
        $this->assertSame($output, $html->render());

        // check return value
        $this->assertInstanceOf('cHTML', $html->setTag(''));
    }

    public function dataSetAlt()
    {
        return [
            'null'   => [null, null, null, null],
            'empty'  => ['', '', null, null],
            'foobar' => ['foo', 'bar', 'foo', 'bar'],
        ];
    }

    /**
     * @dataProvider dataSetAlt()
     *
     * @param string|null $input     data to be used as input
     * @param string|null $inputSec  data to be used as secondary input
     * @param string|null $output    data to be expected as output
     * @param string|null $outputSec data to be expected as secondary output
     */
    public function testSetAlt(
        string $input = null,
        string $inputSec = null,
        string $output = null,
        string $outputSec = null
    ) {
        // set alt w/ title (by default)
        $html = new cHTML();
        $html->setAlt($input);
        $this->assertSame($output, $html->getAttribute('alt'));
        $this->assertSame($output, $html->getAttribute('title'));

        // set alt w/ title (explicitly)
        $html = new cHTML();
        $html->setAlt($input, true);
        $this->assertSame($output, $html->getAttribute('alt'));
        $this->assertSame($output, $html->getAttribute('title'));

        // set alt w/o title
        $html = new cHTML();
        $html->setAlt($input, false);
        $this->assertSame($output, $html->getAttribute('alt'));
        $this->assertSame(null, $html->getAttribute('title'));

        // set alt w/ title & reset w/o title
        $html = new cHTML();
        $html->setAlt($input, true);
        $html->setAlt($inputSec, false);
        $this->assertSame($outputSec, $html->getAttribute('alt'));
        $this->assertSame($output, $html->getAttribute('title'));

        // check return value
        $this->assertInstanceOf('cHTML', $html->setAlt(''));
    }

    public function dataSetClass()
    {
        return [
            'null'   => [null, null],
            'empty'  => ['', null],
            'foobar' => ['foobar', 'foobar'],
        ];
    }

    /**
     * @dataProvider dataSetClass()
     *
     * @param string|null $input  data to be used as input
     * @param string|null $output data to be expected as output
     */
    public function testSetClass(string $input = null, string $output = null)
    {
        $html = new cHTML();
        $html->setClass($input);
        $this->assertSame($output, $html->getAttribute('class'));

        // check return value
        $this->assertInstanceOf('cHTML', $html->setClass(''));
    }

    public function dataSetStyle()
    {
        return [
            'null'   => [null, null],
            'empty'  => ['', null],
            'foobar' => ['foobar', 'foobar'],
        ];
    }

    /**
     * @dataProvider dataSetStyle()
     *
     * @param string|null $input  data to be used as input
     * @param string|null $output data to be expected as output
     */
    public function testSetStyle(string $input = null, string $output = null)
    {
        $html = new cHTML();
        $html->setStyle($input);
        $this->assertSame($output, $html->getAttribute('style'));
    }

    public function testSetEvent()
    {
        $this->markTestIncomplete();
    }

    public function testUnsetEvent()
    {
        $this->markTestIncomplete();
    }

    public function dataGetAttribute()
    {
        return [
            'null' => [
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataGetAttribute()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testGetAttribute(array $input = null, array $output = null)
    {
        $html = new cHTML();
        $this->assertNull($html->getAttribute('id'));

        $html->setAttribute('id', 'my-id');
        $this->assertSame('my-id', $html->getAttribute('id'));

        $html = new cHTML();
        $html->setAttributes([1, 2, 3, 4, '']);
        $this->assertSame('1', $html->getAttribute('1'));
        $this->assertSame('2', $html->getAttribute('2'));
        $this->assertSame('', $html->getAttribute(''));

        $html = new cHTML();
        $html->setAttributes([]);
        $this->assertSame([], $this->_readAttribute($html, '_attributes'));
    }

    public function dataSetAttribute()
    {
        return [
            'null' => [
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataSetAttribute()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testSetAttribute(array $input = null, array $output = null)
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

        $html = new cHTML();
        $html->setAttribute('test0', 'test1');
        $ret = $this->_readAttribute($html, '_attributes');
        $this->assertSame('test1', $ret['test0']);
    }

    public function dataGetAttributes()
    {
        return [
            'null' => [
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataGetAttributes()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testGetAttributes(array $input = null, array $output = null)
    {
        $attr = ['id' => 'my-id', 'class' => 'my-class'];
        $html = new cHTML();

        $html->setAttributes($attr);
        $this->assertSame($attr, $html->getAttributes());

        $cHtml = new cHTML();
        $cHtml->setAttribute('test0', 'test1');
        $ret = ($cHtml->getAttributes(true));
        $this->assertSame(' test0="test1"', $ret);
    }

    public function dataSetAttributes()
    {
        return [
            'null' => [
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataSetAttributes()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testSetAttributes(array $input = null, array $output = null)
    {
        $html = new cHTML();

        $html->setAttributes(['id', 'name', 'class', 'foo', 'bar' => 'baz']);
        $this->assertSame('< id="id" name="name" class="class" foo="foo" bar="baz" />', $html->render());

        $ar   = [1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5',];
        $html = new cHTML();
        $html->setAttributes([1, 2, 3, 4, 5]);
        $this->assertSame($ar, $html->getAttributes());

        $html = new cHTML();
        $html->setAttributes([]);
        $this->assertSame([], $html->getAttributes());
    }

    public function dataUpdateAttribute()
    {
        return [
            'null' => [
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataUpdateAttribute()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testUpdateAttribute(array $input = null, array $output = null)
    {
        $html = new cHTML();

        $html->setAttributes(['id' => 'my-id', 'class' => 'my-class', 'foo']);
        $this->assertSame('< id="my-id" class="my-class" foo="foo" />', $html->render());

        $html->updateAttribute('id', 'another-id');
        $html->updateAttribute('class', 'another-class');
        $html->updateAttribute('foo', 'another-foo');
        $this->assertSame('< id="another-id" class="another-class" foo="another-foo" />', $html->render());

        $html = new cHTML();
        $html->updateAttribute('foo', '2');
        $ar = $this->_readAttribute($html, '_attributes');
        $this->assertSame('2', $ar['foo']);
    }

    public function dataUpdateAttributes()
    {
        return [
            'null' => [
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataUpdateAttributes()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testUpdateAttributes(array $input = null, array $output = null)
    {
        $html = new cHTML();

        $html->setAttributes(['id' => 'my-id', 'class' => 'my-class', 'foo']);
        $this->assertSame('< id="my-id" class="my-class" foo="foo" />', $html->render());

        $html->updateAttributes(['id' => 'another-id', 'class' => 'another-class', 'foo' => 'another-foo']);
        $this->assertSame('< id="another-id" class="another-class" foo="another-foo" />', $html->render());
    }

    public function dataRemoveAttribute()
    {
        return [
            'null' => [
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataRemoveAttribute()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testRemoveAttribute(array $input = null, array $output = null)
    {
        $html = new cHTML();

        $html->setAttributes(['id' => 'my-id', 'class' => 'my-class', 'foo']);
        $this->assertSame('< id="my-id" class="my-class" foo="foo" />', $html->render());

        $html->removeAttribute('class');
        $this->assertSame('< id="my-id" foo="foo" />', $html->render());

        $html->removeAttribute('foo');
        $this->assertSame('< id="my-id" />', $html->render());

        $ar   = [2 => '2', 3 => '3', 4 => '4', 5 => '5'];
        $html = new cHTML();
        $html->setAttributes([1, 2, 3, 4, 5]);
        $html->removeAttribute('1');
        $this->assertSame($ar, $html->getAttributes());

        $html = new cHTML();
        $html->removeAttribute('2');
        $html->removeAttribute('3');
        $html->removeAttribute('4');
        $html->removeAttribute('5');
        $html->removeAttribute('5');
        $this->assertSame([], $html->getAttributes());
    }

    public function testFillSkeleton()
    {
        $this->markTestIncomplete();
    }

    public function testFillCloseSkeleton()
    {
        $this->markTestIncomplete();
    }

    public function dataAppendStyleDefinition()
    {
        return [
            'empty array'                            => [
                [],
                [],
            ],
            'empty property and value'               => [
                ['' => ''],
                ['' => ''],
            ],
            'single valid style'                     => [
                ['prop' => 'value'],
                ['prop' => 'value'],
            ],
            'multiple valid styles'                  => [
                ['prop' => 'value', 'another-prop' => 'another-value'],
                ['prop' => 'value', 'another-prop' => 'another-value'],
            ],
            'invalid style with single semicolon'    => [
                ['prop' => 'value;'],
                ['prop' => 'value'],
            ],
            'invalid style with multiple semicolons' => [
                ['prop' => 'value;;'],
                ['prop' => 'value'],
            ],
        ];
    }

    /**
     * @dataProvider dataAppendStyleDefinition()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testAppendStyleDefinition(array $input = null, array $output = null)
    {
        $html = new cHTML();
        foreach ($input as $property => $value) {
            $html->appendStyleDefinition($property, $value);
        }
        $this->assertSame($output, $html->getStyleDefinition());
    }

    public function dataAppendStyleDefinitions()
    {
        return [
            'null' => [
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataAppendStyleDefinitions()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testAppendStyleDefinitions(array $input = null, array $output = null)
    {
        // $html = new cHTML();
        // $html->();
        // $this->assertSame('<  />', $html->render());

        $ar   = ['margin-top' => '5px !important', 'margin-bottom' => '5px !important'];
        $html = new cHTML();
        $html->appendStyleDefinitions(['margin-top' => '5px !important', 'margin-bottom' => '5px !important']);
        $this->assertEquals($ar, $html->getStyleDefinition());
    }

    public function dataAddRequiredScript()
    {
        return [
            'null' => [
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataAddRequiredScript()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testAddRequiredScript(array $input = null, array $output = null)
    {
        // $html = new cHTML();
        // $html->addRequiredScript();
        // $this->assertSame('<  />', $html->render());

        $ar   = [0 => 'test.js', 1 => '', 2 => 'test1.js', 3 => 'test2.js'];
        $html = new cHTML();
        $html->addRequiredScript('test.js');
        $html->addRequiredScript('test.js');
        $html->addRequiredScript('');
        $html->addRequiredScript('');
        $html->addRequiredScript('test.js');
        $html->addRequiredScript('test1.js');
        $html->addRequiredScript('test2.js');
        $html->addRequiredScript('test.js');
        $this->assertSame($ar, $this->_readAttribute($html, '_requiredScripts'));
    }

    public function testAttachEventDefinition()
    {
        $this->markTestIncomplete();
    }

    public function dataToHtml()
    {
        return [
            'null' => [
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataToHtml()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testToHtml(array $input = null, array $output = null)
    {
        // $html = new cHTML();
        // $html->toHtml();
        // $this->assertSame('<  />', $html->render());

        $html = new cHTML();
        $html->setTag('img');
        $html->setAttributes(['src', 'alt', 'title']);
        $this->assertSame('<img src="src" alt="alt" title="title" />', $html->toHtml());

        $cHtml = new cHTML(['id' => 'm19']);
        $this->assertSame('< id="m19" />', $cHtml->toHtml());

        $cHtml->setStyle('margin-left:100px;');
        $this->assertSame('< id="m19" style="margin-left:100px;" />', $cHtml->toHtml());
    }

    public function dataRender()
    {
        return [
            'null' => [
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataRender()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testRender(array $input = null, array $output = null)
    {
        $cHtml = new cHTML(['id' => 'm20']);
        $this->assertSame('< id="m20" />', $cHtml->render());
    }

    public function testDisplay()
    {
        $this->markTestIncomplete();
    }

    /*
     * Test of private and protected methods.
     * These are commented cause it's considered bad practice to test such methods.
     */

    // public function testGetAttrString()
    // {
    //     $cHtml           = new cHTML();
    //     $cHtmlReflection = new \ReflectionClass('cHTML');
    //
    //     $ar     = ['alt' => 'alt', 'title' => ''];
    //     $result = $this->_callMethod($cHtmlReflection, $cHtml, '_getAttrString', [$ar]);
    //     $this->assertSame(' alt="alt" title=""', $result);
    //
    //     $ar     = ['alt' => 'Kategorie löschen', 'title' => 'Kategorie löschen'];
    //     $result = $this->_callMethod($cHtmlReflection, $cHtml, '_getAttrString', [$ar]);
    //     $this->assertSame(' alt="Kategorie löschen" title="Kategorie löschen"', $result);
    //
    //     $ar     = ['alt' => '""\n', 'title' => '""\n'];
    //     $result = $this->_callMethod($cHtmlReflection, $cHtml, '_getAttrString', [$ar]);
    //     $this->assertSame(' alt="""\n" title="""\n"', $result);
    // }

    // public function testAppendContent()
    // {
    //     $cHtml           = new cHTML();
    //     $cHtmlReflection = new \ReflectionClass('cHTML');
    //
    //     $ar = ['alt' => 'alt', 'title' => ''];
    //
    //     $result = $this->_callMethod($cHtmlReflection, $cHtml, '_getAttrString', [$ar]);
    //     $this->assertSame(' alt="alt" title=""', $result);
    //     // $this->assertSame('0="test4711"',Util::callProtectedMethod($cHtml, '_getAttrString', array(array('test4711'))));
    // }

    // public function testSetContent()
    // {
    //     $cHtml           = new cHtml();
    //     $cHtmlReflection = new \ReflectionClass('cHTML');
    //
    //     $cHtml->appendStyleDefinition('margin-left', '10px !important');
    //     $cHtml->setTag('div');
    //     $cHtml->setAlt('title');
    //     $cHtml->fillSkeleton('div');
    //     $cHtml->fillCloseSkeleton();
    //     $this->_callMethod($cHtmlReflection, $cHtml, '_setContent', [['<a href="huhu.php">blabla</a>']]);
    //     $this->assertSame('<a href="huhu.php">blabla</a>', $this->_readAttribute($cHtml, '_content'));
    //
    //     // $cHtml = new cHTML();
    //     // $this->assertSame(
    //     //     '<div id="m26" alt="title" title="title" style="margin-left: 10px !important;" />',
    //     //     $this->_readAttribute($cHtml, '_content')
    //     // );
    // }

    // public function testPAppendContent()
    // {
    //     $cHtmlReflection = new \ReflectionClass('cHTML');
    //
    //     $cHtml = new cHtml();
    //     $cHtml->appendStyleDefinition('margin-left', '10px !important');
    //     $cHtml->setTag('div');
    //     $cHtml->setAlt('title');
    //
    //     $this->_callMethod($cHtmlReflection, $cHtml, '_appendContent', [['<a href="huhu.php">blabla</a>']]);
    //     $this->assertSame('<a href="huhu.php">blabla</a>', $this->_readAttribute($cHtml, '_content'));
    //
    //     $this->_callMethod($cHtmlReflection, $cHtml, '_appendContent', [['<a href="huhu.php">blabla</a>']]);
    //     $this->assertSame(
    //         '<a href="huhu.php">blabla</a><a href="huhu.php">blabla</a>',
    //         $this->_readAttribute($cHtml, '_content')
    //     );
    //
    //     //Util::callProtectedMethod($cHtml, '_appendContent', array($cHtml));
    //     //$this->assertSame('<a href="huhu.php">blabla</a><a href="huhu.php">blabla</a><div id="m28" alt="title" title="title" style="margin-left: 10px !important;" />',$this->_readAttribute($cHtml, '_content'));
    //
    //     //_appendContent array -> duplicate stlye informations
    //     // Util::callProtectedMethod($cHtml, '_appendContent', [[$cHtml]]);
    //     // $this->assertSame(
    //     //     '<a href="huhu.php">blabla</a><a href="huhu.php">blabla</a><div id="m28" alt="title" title="title" style="margin-left: 10px !important;" /><div id="m28" alt="title" title="title" style="margin-left: 10px !important;" />',
    //     //     $this->_readAttribute($cHtml, '_content')
    //     // );
    // }
}
