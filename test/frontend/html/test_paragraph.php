<?PHP
class cHtmlParagraphTest extends PHPUnit_Framework_TestCase {


    public function testConstruct() {
        $p = new cHTMLParagraph('testContent', 'testClass');
        $this->assertSame('<p id="" class="testClass">testContent</p>', $p->toHTML());
        $p = new cHTMLParagraph();
        $this->assertSame('<p id=""></p>', $p->toHTML());
        $p = new cHTMLParagraph('testContent', 'testClass');
        $p->setID('testId');
        $this->assertSame('<p id="testId" class="testClass">testContent</p>', $p->toHTML());
    }

}
?>