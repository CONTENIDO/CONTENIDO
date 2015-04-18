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
require_once(dirname(__FILE__) . '/Chains/cApiCecRegistry.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoArticleConCopyArtLang_AfterInsert.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoArticleConMoveArticles_Loop.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoArticleConSyncArticle_AfterInsert.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoCategoryStrCopyCategory.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoCategoryStrSyncCategory_Loop.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoContentConGenerateCode.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoContentCopyArticle.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoContentCreateTitletag.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoContentDeleteArticle.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoContentSaveContentEntry.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoFrontendAllowEdit.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoFrontendBaseHrefGeneration.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoFrontendCategoryAccess.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoFrontendHTMLCodeOutput.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoFrontendPostprocessUrlBuilding.php');
require_once(dirname(__FILE__) . '/Chains/ContenidoFrontendPreprocessUrlBuilding.php');
require_once(dirname(__FILE__) . '/Chains/ExampleContenidoChainExecuteObject.php');
require_once(dirname(__FILE__) . '/Chains/ExampleContenidoChainExecuteStaticMethod.php');

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
