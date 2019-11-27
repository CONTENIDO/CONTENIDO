<?PHP

/**
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */
class cHtmlTest extends cTestingTestCase
{
    /**
     * @var cHTML
     */
    protected $_cHtml;

    /**
     * @var ReflectionClass
     */
    protected $_cHtmlReflection;

    protected function setUp(): void
    {
        $this->_cHtml           = new cHTML();
        $this->_cHtmlReflection = new \ReflectionClass('cHTML');
    }

    protected function tearDown(): void
    {
        unset($this->_cHtml);
        unset($this->_cHtmlReflection);
    }

    public function testConstruct()
    {
        $cHtml = new cHTML();
        $this->assertSame([], $cHtml->getAttributes());

        $cHtml = new cHtml([1 => '1']);
        $ar    = [1 => '1'];
        $this->assertSame($ar, $cHtml->getAttributes());

        $cHtml = new cHtml([1 => '1', 'id' => 'testId']);
        $ar    = [1 => '1', 'id' => 'testId'];
        $this->assertSame($ar, $cHtml->getAttributes());

        $cHtml = new cHtml([1, 51, 3, 4, 5]);
        $ar    = [1 => '1', 51 => '51', 3 => '3', 4 => '4', 5 => '5'];
        $this->assertSame($ar, $cHtml->getAttributes());

        $cHtml = new cHtml([1, 'hello', 'hallo', 4, '']);
        $ar    = [1 => '1', 'hello' => 'hello', 'hallo' => 'hallo', 4 => '4', '' => ''];
        $this->assertEquals($ar, $cHtml->getAttributes());
    }

    public function testSetAttributes()
    {
        $ar = [1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5',];
        $this->_cHtml->setAttributes([1, 2, 3, 4, 5]);
        $this->assertSame($ar, $this->_cHtml->getAttributes());

        $this->_cHtml->setAttributes([]);
        $this->assertSame([], $this->_cHtml->getAttributes());
    }

    public function testRemoveAttribute()
    {
        $ar = [2 => '2', 3 => '3', 4 => '4', 5 => '5'];
        $this->_cHtml->setAttributes([1, 2, 3, 4, 5]);
        $this->_cHtml->removeAttribute('1');
        $this->assertSame($ar, $this->_cHtml->getAttributes());

        $this->_cHtml->removeAttribute('2');
        $this->_cHtml->removeAttribute('3');
        $this->_cHtml->removeAttribute('4');
        $this->_cHtml->removeAttribute('5');
        $this->_cHtml->removeAttribute('5');
        $this->assertSame([], $this->_cHtml->getAttributes());
    }

    public function testGetAttribute()
    {
        $this->_cHtml->setAttributes([1, 2, 3, 4, '']);
        $this->assertSame('1', $this->_cHtml->getAttribute('1'));
        $this->assertSame('2', $this->_cHtml->getAttribute('2'));
        $this->assertSame('', $this->_cHtml->getAttribute(''));

        $this->_cHtml->setAttributes([]);
        $this->assertSame([], $this->_readAttribute($this->_cHtml, '_attributes'));
    }

    public function testUpdateAttribute()
    {
        $this->_cHtml->updateAttribute('foo', '2');
        $ar = $this->_readAttribute($this->_cHtml, '_attributes');
        $this->assertSame('2', $ar['foo']);
    }

    public function testToHtml()
    {
        $this->_cHtml->setTag('img');
        $this->_cHtml->setAttributes(['src', 'alt', 'title']);
        $this->assertSame('<img src="src" alt="alt" title="title" />', $this->_cHtml->toHtml());
    }

    public function testSetTag()
    {
        $this->_cHtml->setTag('img');
        $this->assertSame('img', $this->_readAttribute($this->_cHtml, '_tag'));
        $this->_cHtml->setTag('a');
        $this->assertSame('a', $this->_readAttribute($this->_cHtml, '_tag'));
        $this->_cHtml->setTag('div');
        $this->assertSame('div', $this->_readAttribute($this->_cHtml, '_tag'));
        $this->_cHtml->setTag('');
        $this->assertSame('', $this->_readAttribute($this->_cHtml, '_tag'));
    }

    public function testSetAlt()
    {
        $ar = ['alt' => 'alt', 'title' => 'alt'];
        $this->_cHtml->setAlt('alt', 'title');
        $ret = $this->_readAttribute($this->_cHtml, '_attributes');
        unset($ret['id']);
        $this->assertSame($ar, $ret);

        $this->_cHtml->setAlt('alt', false);
        $ret = $this->_readAttribute($this->_cHtml, '_attributes');
        unset($ret['id']);
        // $this->assertSame($ar, $ret);
    }

    public function testSetId()
    {
        $this->_cHtml->setID('testId');
        $ret = $this->_readAttribute($this->_cHtml, '_attributes');
        $this->assertSame('testId', $ret['id']);

        $this->_cHtml->setID('');
        $ret = $this->_readAttribute($this->_cHtml, '_attributes');
        $this->assertSame(null, $ret['id']);
    }

    public function testGetId()
    {
        $this->_cHtml->setID('testId');
        $ret = $this->_readAttribute($this->_cHtml, '_attributes');
        $this->assertSame($this->_cHtml->getId(), $ret['id']);

        $this->_cHtml->setID('');
        $ret = $this->_readAttribute($this->_cHtml, '_attributes');
        $this->assertSame($this->_cHtml->getId(), $ret['id']);
    }

    public function testSetStyle()
    {
        $this->_cHtml->setStyle('margin-top:100px');
        $ret = $this->_readAttribute($this->_cHtml, '_attributes');
        $this->assertSame('margin-top:100px', $ret['style']);
    }

    public function testSetClass()
    {
        $this->_cHtml->setClass('testClass');
        $ret = $this->_readAttribute($this->_cHtml, '_attributes');
        $this->assertSame('testClass', $ret['class']);

        // $ret = $this->_readAttribute($this->_cHtml, '_attributes');
        $this->assertInstanceOf('cHTML', $this->_cHtml->setClass(''));
    }

    // public function testSetEvent() {
    // }

    // public function testUnsetEvent() {
    // }

    public function testSetAttribute()
    {
        $this->_cHtml->setAttribute('test0', 'test1');
        $ret = $this->_readAttribute($this->_cHtml, '_attributes');
        $this->assertSame('test1', $ret['test0']);

        // $this->_cHtml = new cHTML(array('id'=>5));
        // var_dump($this->_cHtml->getAttributes());
    }

    public function testGetAttrString()
    {
        $ar     = ['alt' => 'alt', 'title' => ''];
        $result = $this->_callMethod($this->_cHtmlReflection, $this->_cHtml, '_getAttrString', [$ar]);
        $this->assertSame(' alt="alt" title=""', $result);

        $ar     = ['alt' => 'Kategorie löschen', 'title' => 'Kategorie löschen'];
        $result = $this->_callMethod($this->_cHtmlReflection, $this->_cHtml, '_getAttrString', [$ar]);
        $this->assertSame(' alt="Kategorie löschen" title="Kategorie löschen"', $result);

        $ar     = ['alt' => '""\n', 'title' => '""\n'];
        $result = $this->_callMethod($this->_cHtmlReflection, $this->_cHtml, '_getAttrString', [$ar]);
        $this->assertSame(' alt="""\n" title="""\n"', $result);
    }

    public function testGetAttributes()
    {
        $cHtml = new cHTML();
        $cHtml->setAttribute('test0', 'test1');
        $ret = ($cHtml->getAttributes(true));
        $this->assertSame(' test0="test1"', $ret);
    }

    public function testTooHtml()
    {
        $cHtml = new cHTML(['id' => 'm19']);
        $this->assertSame('< id="m19" />', $cHtml->toHtml());
        $cHtml->setStyle('margin-left:100px;');
        $this->assertSame('< id="m19" style="margin-left:100px;" />', $cHtml->toHtml());
    }

    public function testToRender()
    {
        $cHtml = new cHTML(['id' => 'm20']);
        $this->assertSame('< id="m20" />', $cHtml->render());
    }

    public function testAppendContent()
    {
        $ar = ['alt' => 'alt', 'title' => ''];

        $result = $this->_callMethod($this->_cHtmlReflection, $this->_cHtml, '_getAttrString', [$ar]);
        $this->assertSame(' alt="alt" title=""', $result);
        // $this->assertSame('0="test4711"',Util::callProtectedMethod($this->_cHtml, '_getAttrString', array(array('test4711'))));
    }

    public function testAppendStyleDefinition()
    {
        $ar = ['margin-top' => '5px !important'];
        $this->_cHtml->appendStyleDefinition('margin-top', '5px !important');
        $this->assertSame($ar, $this->_readAttribute($this->_cHtml, '_styleDefinitions'));

        $this->_cHtml->appendStyleDefinition('margin-bottom', '5px !important;');
        $ar['margin-bottom'] = '5px !important';
        $this->assertSame($ar, $this->_readAttribute($this->_cHtml, '_styleDefinitions'));

        // good idea to cut only the last ';' => in style definition should all ';' be removed !
        $this->_cHtml->appendStyleDefinition('margin-bottom', '5px !important;;;');
        $ar['margin-bottom'] = '5px !important;;';
        $this->assertSame($ar, $this->_readAttribute($this->_cHtml, '_styleDefinitions'));

        // $this->_cHtml->appendStyleDefinition('', '');
        // $ar[''] = '';
        // $this->assertSame($ar,$this->_readAttribute($this->_cHtml, '_styleDefinitions'));
    }

    public function testAppendStyleDefinitions()
    {
        $ar = ['margin-top' => '5px !important', 'margin-bottom' => '5px !important'];
        $this->_cHtml->appendStyleDefinitions(['margin-top' => '5px !important', 'margin-bottom' => '5px !important']);
        $this->assertSame($ar, $this->_readAttribute($this->_cHtml, '_styleDefinitions'));
    }

    public function testAddRequiredScript()
    {
        $ar = [0 => 'test.js', 1 => '', 2 => 'test1.js', 3 => 'test2.js'];
        $this->_cHtml->addRequiredScript('test.js');
        $this->_cHtml->addRequiredScript('test.js');
        $this->_cHtml->addRequiredScript('');
        $this->_cHtml->addRequiredScript('');
        $this->_cHtml->addRequiredScript('test.js');
        $this->_cHtml->addRequiredScript('test1.js');
        $this->_cHtml->addRequiredScript('test2.js');
        $this->_cHtml->addRequiredScript('test.js');
        $this->assertSame($ar, $this->_readAttribute($this->_cHtml, '_requiredScripts'));
    }

    public function testSetContent()
    {
        $cHtml = new cHtml();
        $cHtml->appendStyleDefinition('margin-left', '10px !important');
        $cHtml->setTag('div');
        $cHtml->setAlt('title');
        $cHtml->fillSkeleton('div');
        $cHtml->fillCloseSkeleton();
        $this->_callMethod($this->_cHtmlReflection, $this->_cHtml, '_setContent', [['<a href="huhu.php">blabla</a>']]);
        $this->assertSame('<a href="huhu.php">blabla</a>', $this->_readAttribute($this->_cHtml, '_content'));

        // $this->_cHtml = new cHTML();
        // $this->assertSame(
        //     '<div id="m26" alt="title" title="title" style="margin-left: 10px !important;" />',
        //     $this->_readAttribute($this->_cHtml, '_content')
        // );
    }

    public function testPAppendContent()
    {
        $cHtml = new cHtml();
        $cHtml->appendStyleDefinition('margin-left', '10px !important');
        $cHtml->setTag('div');
        $cHtml->setAlt('title');

        $this->_callMethod($this->_cHtmlReflection, $cHtml, '_appendContent', [['<a href="huhu.php">blabla</a>']]);
        $this->assertSame('<a href="huhu.php">blabla</a>', $this->_readAttribute($cHtml, '_content'));

        $this->_callMethod($this->_cHtmlReflection, $cHtml, '_appendContent', [['<a href="huhu.php">blabla</a>']]);
        $this->assertSame(
            '<a href="huhu.php">blabla</a><a href="huhu.php">blabla</a>',
            $this->_readAttribute($cHtml, '_content')
        );

        //Util::callProtectedMethod($this->_cHtml, '_appendContent', array($cHtml));
        //$this->assertSame('<a href="huhu.php">blabla</a><a href="huhu.php">blabla</a><div id="m28" alt="title" title="title" style="margin-left: 10px !important;" />',$this->_readAttribute($this->_cHtml, '_content'));

        //_appendContent array -> duplicate stlye informations
        // Util::callProtectedMethod($this->_cHtml, '_appendContent', [[$cHtml]]);
        // $this->assertSame(
        //     '<a href="huhu.php">blabla</a><a href="huhu.php">blabla</a><div id="m28" alt="title" title="title" style="margin-left: 10px !important;" /><div id="m28" alt="title" title="title" style="margin-left: 10px !important;" />',
        //     $this->_readAttribute($this->_cHtml, '_content')
        // );
    }
}
