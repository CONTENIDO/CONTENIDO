<?php
/**
 * Testsuite for Contenido chains related tests.
 *
 * Call this from cmd-line as follows:
 * ...>phpunit ChainsTestSuite
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        03.04.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  TestSuite
 */


require_once('bootstrap.php');
TestSuiteHelper::loadFeSuite('Chains');


class ContenidoChainsAllTest
{

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Contenido Chains');
        $suite->addTestSuite('cApiCECRegistryTest');
        $suite->addTestSuite('ContenidoFrontendAllowEditTest');
        $suite->addTestSuite('ContenidoFrontendCategoryAccessTest');
        $suite->addTestSuite('ContenidoFrontendBaseHrefGenerationTest');
        $suite->addTestSuite('ContenidoFrontendHTMLCodeOutputTest');
        $suite->addTestSuite('ContenidoFrontendPreprocessUrlBuildingTest');
        $suite->addTestSuite('ContenidoFrontendPostprocessUrlBuildingTest');
        $suite->addTestSuite('ContenidoArticleConMoveArticles_LoopTest');
        $suite->addTestSuite('ContenidoArticleConCopyArtLang_AfterInsertTest');
        $suite->addTestSuite('ContenidoArticleConSyncArticle_AfterInsertTest');
        $suite->addTestSuite('ContenidoContentCreateTitletagTest');
        $suite->addTestSuite('ContenidoContentConGenerateCodeTest');
        $suite->addTestSuite('ContenidoCategoryStrSyncCategory_LoopTest');
        $suite->addTestSuite('ContenidoCategoryStrCopyCategoryTest');
        $suite->addTestSuite('ExampleContenidoChainExecuteObjectTest');
        $suite->addTestSuite('ExampleContenidoChainExecuteStaticMethodTest');
        $suite->addTestSuite('ContenidoContentSaveContentEntryTest');
        $suite->addTestSuite('ContenidoContentDeleteArticleTest');
        $suite->addTestSuite('ContenidoContentCopyArticleTest');
        return $suite;
    }

}
