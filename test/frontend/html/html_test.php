<?PHP

/**
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlTest extends PHPUnit_Framework_TestCase {
    /**
     * @var cHTML
     */
    protected $_cHtml;

    /**
     * @var ReflectionClass
     */
    protected $cHtmlReflection;

    public function setUp() {
        $this->_cHtml = new cHTML();
        $this->_cHtmlReflection = new ReflectionClass('cHTML');
    }

    public function tearDown() {
        unset($this->_cHtml);
        unset($this->_cHtmlReflection);
    }

    public function testConstruct() {

        // $this->_cHtml = new cHtml(array(1,2,3,4));
        // Util::methodCall($this->_cHtmlReflection, 'chtml', '__construct',
        // array(1));
        $this->assertSame(array(
            'id' => 'm1'
        ), $this->_cHtml->getAttributes());
        $this->_cHtml = new cHtml(array(
            1
        ));
        $ar = array();
        $ar[1] = '1';
        $ar['id'] = 'm2';
        $this->assertSame($ar, $this->_cHtml->getAttributes());

        $this->_cHtml = new cHtml(array(
            1,
            51,
            3,
            4,
            5
        ));
        $ar = array();
        $ar[1] = '1';
        $ar[51] = '51';
        $ar[3] = '3';
        $ar[4] = '4';
        $ar[5] = '5';
        $ar['id'] = 'm3';

        $this->assertSame($ar, $this->_cHtml->getAttributes());

        // var_dump(cString::toLowerCase('ÜÖÄL'));
        // $umlVal = cString::toLowerCase('äöül');
        $this->_cHtml = new cHtml(array(
            1,
            'hello',
            'hallo',
            4,
            ''
        ));
        $ar = array();
        $ar[1] = '1';
        $ar['hello'] = 'hello';
        $ar['hallo'] = 'hallo';
        $ar[4] = '4';
        $ar[''] = '';
        $ar['id'] = 'm4';

        $this->assertEquals($ar, $this->_cHtml->getAttributes());
        // $this->assertSame(array('id' => 'm1'),
        // PHPUnit_Framework_Assert::readAttribute($this->_cHtml,
        // '_attributes'));
        // $this->assertSame(array('id' => 'm1'),
        // PHPUnit_Framework_Assert::readAttribute($this->_cHtml,
        // '_attributes'));
        // $this->assertSame(array('id' => 'm1'),
        // PHPUnit_Framework_Assert::readAttribute($this->_cHtml,
        // '_attributes'));
        // $this->assertSame(array('id' => 'm1'),
        // PHPUnit_Framework_Assert::readAttribute($this->_cHtml,
        // '_attributes'));
        // $this->assertSame(array('id' => 'm1'),
        // PHPUnit_Framework_Assert::readAttribute($this->_cHtml,
        // '_attributes'));
    }

    public function testSetAttributes() {
        $ar = array();
        $ar[1] = '1';
        $ar[2] = '2';
        $ar[3] = '3';
        $ar[4] = '4';
        $ar[5] = '5';

        $this->_cHtml->setAttributes(array(
            1,
            2,
            3,
            4,
            5
        ));
        $this->assertSame($ar, $this->_cHtml->getAttributes());
        $this->_cHtml->setAttributes(array());

        $this->assertSame(array(), $this->_cHtml->getAttributes());
    }

    public function testRemoveAttribute() {
        $ar = array();

        $ar[2] = '2';
        $ar[3] = '3';
        $ar[4] = '4';
        $ar[5] = '5';

        $this->_cHtml->setAttributes(array(
            1,
            2,
            3,
            4,
            5
        ));
        $this->_cHtml->removeAttribute('1');
        $this->assertSame($ar, $this->_cHtml->getAttributes());
        $this->_cHtml->removeAttribute('2');
        $this->_cHtml->removeAttribute('3');
        $this->_cHtml->removeAttribute('4');
        $this->_cHtml->removeAttribute('5');
        $this->_cHtml->removeAttribute('5');
        $this->assertSame(array(), $this->_cHtml->getAttributes());
    }

    public function testGetAttribute() {
        $this->_cHtml->setAttributes(array(
            1,
            2,
            3,
            4,
            ''
        ));

        $this->assertSame('1', $this->_cHtml->getAttribute('1'));
        $this->assertSame('2', $this->_cHtml->getAttribute('2'));
        $this->assertSame('', $this->_cHtml->getAttribute(''));

        $this->_cHtml->setAttributes(array());
        $this->assertSame(array(), PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_attributes'));
    }

    public function testUpdateAttribute() {
        $this->_cHtml->updateAttribute('1', '2');
        $ar = PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_attributes');
        $this->assertSame('2', $ar[2]);
    }

    public function testToHtml() {
        $this->_cHtml->setAttributes(array(
            'img',
            'alt'
        ));
        // var_dump($this->_cHtml->toHtml());
    }

    public function testSetTag() {
        $this->_cHtml->setTag('img');
        $this->assertSame('img', PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_tag'));
        $this->_cHtml->setTag('a');
        $this->assertSame('a', PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_tag'));
        $this->_cHtml->setTag('div');
        $this->assertSame('div', PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_tag'));
        $this->_cHtml->setTag('');
        $this->assertSame('', PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_tag'));
    }

    public function testSetAlt() {
        $ar = array();
        $ar['alt'] = 'alt';
        $ar['title'] = 'alt';

        $this->_cHtml->setAlt('alt', 'title');
        $ret = PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_attributes');
        unset($ret['id']);
        $this->assertSame($ar, $ret);

        $ar = array();
        $ar['alt'] = 'alt';
        $ar['title'] = '';

        $this->_cHtml->setAlt('alt', false);
        $ret = PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_attributes');
        unset($ret['id']);
        // $this->assertSame($ar,$ret);
    }

    public function testSetId() {
        $this->_cHtml->setID('testId');
        $ret = PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_attributes');
        $this->assertSame('testId', $ret['id']);
    }

    public function testGetId() {
        $this->_cHtml->setID('testId');
        $ret = PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_attributes');
        $this->assertSame($this->_cHtml->getId(), $ret['id']);
    }

    public function testSetStyle() {
        $this->_cHtml->setStyle('margin-top:100px');
        $ret = PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_attributes');
        $this->assertSame('margin-top:100px', $ret['style']);
    }

    public function testSetClass() {
        $this->_cHtml->setClass('testClass');
        $ret = PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_attributes');
        $this->assertSame('testClass', $ret['class']);

        // $ret = PHPUnit_Framework_Assert::readAttribute($this->_cHtml,
        // '_attributes');
        $this->assertInstanceOf('cHTML', $this->_cHtml->setClass(''));
    }

    // public function testSetEvent() {
    // }

    // public function testUnsetEvent() {
    // }

    public function testSetAttribute() {
        $this->_cHtml->setAttribute('test0', 'test1');
        $ret = PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_attributes');
        $this->assertSame('test1', $ret['test0']);

        // $this->_cHtml = new cHTML(array('id'=>5));
        // var_dump($this->_cHtml->getAttributes());
    }

    public function testGetAttrString() {
        $ar = array();
        $ar['alt'] = 'alt';
        $ar['title'] = '';

        $this->assertSame(' alt="alt" title=""', Util::callProtectedMethod($this->_cHtml, '_getAttrString', array(
            $ar
        )));

        $ar = array();
        $ar['alt'] = 'Kategorie löschen';
        $ar['title'] = 'Kategorie löschen';

        $this->assertSame(' alt="Kategorie löschen" title="Kategorie löschen"', Util::callProtectedMethod($this->_cHtml, '_getAttrString', array(
            $ar
        )));

        $ar = array();
        $ar['alt'] = '""\n';
        $ar['title'] = '""\n';

        $this->assertSame(' alt="""\n" title="""\n"', Util::callProtectedMethod($this->_cHtml, '_getAttrString', array(
            $ar
        )));
    }

    public function testGetAttributes() {
        $this->_cHtml->setAttribute('test0', 'test1');
        $ret = ($this->_cHtml->getAttributes(true));
        $this->assertSame(' id="m18" test0="test1"', $ret);
    }

    public function testTooHtml() {
        $this->assertSame('< id="m19" />', $this->_cHtml->toHtml());
        $this->_cHtml->setStyle('margin-left:100px;');
        $this->assertSame('< id="m19" style="margin-left:100px;" />', $this->_cHtml->toHtml());
    }

    public function testToRender() {
        $this->assertSame('< id="m20" />', $this->_cHtml->render());
    }

    public function testAppendContent() {
        $ar = array();
        $ar['alt'] = 'alt';
        $ar['title'] = '';
        $this->assertSame(' alt="alt" title=""', Util::callProtectedMethod($this->_cHtml, '_getAttrString', array(
            $ar
        )));
        // $this->assertSame('
        // 0="test4711"',Util::callProtectedMethod($this->_cHtml, '_getAttrString',
        // array(array('test4711'))));
    }

    public function testAppendStyleDefinition() {
        $ar = array(
            'margin-top' => '5px !important'
        );

        $this->_cHtml->appendStyleDefinition('margin-top', '5px !important');

        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_styleDefinitions'));

        $this->_cHtml->appendStyleDefinition('margin-bottom', '5px !important;');
        $ar['margin-bottom'] = '5px !important';

        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_styleDefinitions'));

        // good idea to cut only the last ';' => in style definition should all ';' be removed !
        $this->_cHtml->appendStyleDefinition('margin-bottom', '5px !important;;;');
        $ar['margin-bottom'] = '5px !important;;';

        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_styleDefinitions'));

        // $this->_cHtml->appendStyleDefinition('', '');
        // $ar[''] = '';
        // $this->assertSame($ar,PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_styleDefinitions'));

        // echo $this->_cHtml->toHtml();
    }

    public function testAppendStyleDefinitions() {
        $ar = array(
            'margin-top' => '5px !important'
        );
        $ar['margin-bottom'] = '5px !important';

        $this->_cHtml->appendStyleDefinitions(array(
            'margin-top' => '5px !important',
            'margin-bottom' => '5px !important'
        ));
        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_styleDefinitions'));
    }

    public function testAddRequiredScript() {

        $ar = array();

        $ar[0] = 'test.js';
        $ar[2] = '';
        $ar[5] = 'test1.js';
        $ar[6]= 'test2.js';

        $this->_cHtml->addRequiredScript('test.js');
        $this->_cHtml->addRequiredScript('test.js');
        $this->_cHtml->addRequiredScript('');
        $this->_cHtml->addRequiredScript('');
        $this->_cHtml->addRequiredScript('test.js');
        $this->_cHtml->addRequiredScript('test1.js');
        $this->_cHtml->addRequiredScript('test2.js');
        $this->_cHtml->addRequiredScript('test.js');


        $this->assertSame($ar,PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_requiredScripts'));

    }

    // public function testSetContent()
    // {
    // }

    public function testSetContent() {

        $cHtml = new cHtml();
        $cHtml->appendStyleDefinition('margin-left', '10px !important');
        $cHtml->setTag('div');
        $cHtml->setAlt('title');

        $cHtml->fillSkeleton('div');
        $cHtml->fillCloseSkeleton();
        // echo $cHtml->toHtml();

        // var_dump(PHPUnit_Framework_Assert::readAttribute($cHtml, '_content'));

        Util::callProtectedMethod($this->_cHtml, '_setContent', array('<a href="huhu.php">blabla</a>'));
        $this->assertSame('<a href="huhu.php">blabla</a>',PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_content'));

        // $this->_cHtml = new cHTML();
        //
        // var_dump(PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_content'));
        //
        // var_dump(Util::callProtectedMethod($this->_cHtml, '_setContent', [$cHtml]));
        // $this->assertSame(
        //     '<div id="m26" alt="title" title="title" style="margin-left: 10px !important;" />',
        //     PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_content')
        // );
        //var_dump(PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_skeletonOpen'));
    }

    public function testPAppendContent() {

        $cHtml = new cHtml();
        $cHtml->appendStyleDefinition('margin-left', '10px !important');
        $cHtml->setTag('div');
        $cHtml->setAlt('title');


        Util::callProtectedMethod($this->_cHtml, '_appendContent', array('<a href="huhu.php">blabla</a>'));
        $this->assertSame('<a href="huhu.php">blabla</a>',PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_content'));

        Util::callProtectedMethod($this->_cHtml, '_appendContent', array('<a href="huhu.php">blabla</a>'));
        $this->assertSame('<a href="huhu.php">blabla</a><a href="huhu.php">blabla</a>',PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_content'));


        Util::callProtectedMethod($this->_cHtml, '_appendContent', array($cHtml));
        $this->assertSame('<a href="huhu.php">blabla</a><a href="huhu.php">blabla</a><div id="m28" alt="title" title="title" style="margin-left: 10px !important;" />',PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_content'));

        //_appendContent array -> duplicate stlye informations
        // Util::callProtectedMethod($this->_cHtml, '_appendContent', [[$cHtml]]);
        // $this->assertSame(
        //     '<a href="huhu.php">blabla</a><a href="huhu.php">blabla</a><div id="m28" alt="title" title="title" style="margin-left: 10px !important;" /><div id="m28" alt="title" title="title" style="margin-left: 10px !important;" />',
        //     PHPUnit_Framework_Assert::readAttribute($this->_cHtml, '_content')
        // );
    }
}

?>