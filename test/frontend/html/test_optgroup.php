<?PHP
class cHtmlOptGroupTest extends PHPUnit_Framework_TestCase {


    public function testConstruct() {
        $opt = new cHTMLOptgroup('testContent', 'testClass', 'testId');
        $this->assertSame('<optgroups id="testId" class="testClass">testContent</optgroups>', $opt->toHTML());
        $opt = new cHTMLOptgroup();
        $this->assertSame('<optgroups id=""></optgroups>', $opt->toHTML());
    }

}
?>