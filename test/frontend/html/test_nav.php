<?PHP
class cHtmlNavTest extends PHPUnit_Framework_TestCase {


    public function testConstruct() {
        $nav = new cHTMLNav('testContent', 'testClass', 'testId');
        $this->assertSame('<nav id="testId" class="testClass">testContent</nav>', $nav->toHTML());
        $nav = new cHTMLNav();
        $this->assertSame('<nav id=""></nav>', $nav->toHTML());
    }

}
?>