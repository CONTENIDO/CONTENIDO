<?php
/**
 * This file contains the TestSuite for chains.
 *
 * @package          Testing
 * @subpackage       Test_Chains
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

require_once(dirname(dirname(__FILE__)) . '/bootstrap.php');
require_once(dirname(__FILE__) . '/cApiCecRegistry.php');
require_once(dirname(__FILE__) . '/ContenidoArticleConCopyArtLang_AfterInsert.php');
require_once(dirname(__FILE__) . '/ContenidoArticleConMoveArticles_Loop.php');
require_once(dirname(__FILE__) . '/ContenidoArticleConSyncArticle_AfterInsert.php');
require_once(dirname(__FILE__) . '/ContenidoCategoryStrCopyCategory.php');
require_once(dirname(__FILE__) . '/ContenidoCategoryStrSyncCategory_Loop.php');
require_once(dirname(__FILE__) . '/ContenidoContentConGenerateCode.php');
require_once(dirname(__FILE__) . '/ContenidoContentCopyArticle.php');
require_once(dirname(__FILE__) . '/ContenidoContentCreateTitletag.php');
require_once(dirname(__FILE__) . '/ContenidoContentDeleteArticle.php');
require_once(dirname(__FILE__) . '/ContenidoContentSaveContentEntry.php');
require_once(dirname(__FILE__) . '/ContenidoFrontendAllowEdit.php');
require_once(dirname(__FILE__) . '/ContenidoFrontendBaseHrefGeneration.php');
require_once(dirname(__FILE__) . '/ContenidoFrontendCategoryAccess.php');
require_once(dirname(__FILE__) . '/ContenidoFrontendHTMLCodeOutput.php');
require_once(dirname(__FILE__) . '/ContenidoFrontendPostprocessUrlBuilding.php');
require_once(dirname(__FILE__) . '/ContenidoFrontendPreprocessUrlBuilding.php');
require_once(dirname(__FILE__) . '/ExampleContenidoChainExecuteObject.php');
require_once(dirname(__FILE__) . '/ExampleContenidoChainExecuteStaticMethod.php');

/**
 * Testsuite for Contenido chains related tests.
 *
 * Call this from cmd-line as follows:
 * ...>phpunit ChainsTestSuite
 *
 * @package          Testing
 * @subpackage       Test_Chains
 */
class ContenidoChainsAllTest
{

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Contenido Chains');
        $suite->addTestSuite('cApiCecRegistryTest');
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
