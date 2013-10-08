<?php
/**
 * Template TestSuite
 *
 * @package Testing
 * @subpackage Test_Security
 * @version SVN Revision $Rev:$
 *
 * @author claus schunk <claus.schunk@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
require_once ('bootstrap.php');
// foldername of the test

TestSuiteHelper::loadFeSuite('html');

require_once ('util.php');

/**
 * Template Testsuite.
 */
class ContenidoHtmlAllTest {

    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('html test');
        // class name of the test
        $suite->addTestSuite('cHtmlTest');
        $suite->addTestSuite('cHtmlArticleTest');
        $suite->addTestSuite('cHtmlAsideTest');
        $suite->addTestSuite('cHtmlAudioTest');
        $suite->addTestSuite('cHtmlButtonTest');
        $suite->addTestSuite('cHtmlCanvasTest');
        $suite->addTestSuite('cHtmlCheckBoxTest');
        $suite->addTestSuite('cHtmlDivTest');
        $suite->addTestSuite('cHtmlFieldSetTest');
        $suite->addTestSuite('cHtmlFooterTest');
        $suite->addTestSuite('cHtmlHeaderTest');
        $suite->addTestSuite('cHtmlHeaderHgroupTest');
        $suite->addTestSuite('cHtmlHiddenFieldTest');
        $suite->addTestSuite('cHtmlIframeTest');
        $suite->addTestSuite('cHtmlImageTest');
        $suite->addTestSuite('cHtmlLabelTest');
        $suite->addTestSuite('cHtmlLegendTest');
        $suite->addTestSuite('cHtmlListTest');
        $suite->addTestSuite('cHtmlListItemTest');
        $suite->addTestSuite('cHtmlNavTest');
        $suite->addTestSuite('cHtmlOptGroupTest');
        $suite->addTestSuite('cHtmlParagraphTest');
        $suite->addTestSuite('cHtmlVideoTest');
        $suite->addTestSuite('cHtmlOptionElementTest');
        $suite->addTestSuite('cHtmlPasswordBoxTest');
        $suite->addTestSuite('cHtmlRadioButtonTest');
        $suite->addTestSuite('cHtmlScriptTest');
        $suite->addTestSuite('cHtmlSectionTest');
        $suite->addTestSuite('cHtmlSpanTest');
        $suite->addTestSuite('cHtmlUploadTest');
        $suite->addTestSuite('cHtmlTimeTest');
        $suite->addTestSuite('cHtmlTextAreaTest');
        $suite->addTestSuite('cHtmlFormTest');
        $suite->addTestSuite('cHtmlFormElementTest');
        $suite->addTestSuite('cHtmlLinkTest');
        $suite->addTestSuite('cHtmlTableTest');
        $suite->addTestSuite('cHtmlTableBodyTest');
        $suite->addTestSuite('cHtmlTableDateTest');
        $suite->addTestSuite('cHtmlTableHeadTest');
        $suite->addTestSuite('cHtmlTableHeaderTest');
        $suite->addTestSuite('cHtmlTableRowTest');
        $suite->addTestSuite('cHTMLSelectElementTest');



        // $suite->addTestSuite('');

        return $suite;
    }

}
