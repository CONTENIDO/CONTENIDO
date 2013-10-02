<?PHP
class cHtmlArticleTest extends PHPUnit_Framework_TestCase {

    public function testArticle(){
        $cArticle = new cHTMLArticle('huhuhuhuhu','testclass','testid');
        $this->assertSame('<article id="testid" class="testclass">huhuhuhuhu</article>',$cArticle->toHTML());
    }
}
?>