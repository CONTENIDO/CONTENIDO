<?PHP
class cHtmlAsideTest extends PHPUnit_Framework_TestCase {

    public function testArticle(){
        $cAside = new cHTMLAside('huhu','testclass','testid');
        $this->assertSame('<aside id="testid" class="testclass">huhu</aside>',$cAside->toHTML());
    }
}
?>