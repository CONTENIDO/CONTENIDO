<?php

/**
 * AMR test controller
 *
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Content controller to run tests.
 *
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
class ModRewrite_ContentTestController extends ModRewrite_ControllerAbstract {

    /**
     * Number of max items to process
     * @var  int
     */
    protected $_iMaxItems = 0;

    /**
     * Initializer method, sets some view variables
     */
    public function init() {
        $this->_oView->content = '';
        $this->_oView->form_idart_chk = ($this->_getParam('idart')) ? ' checked="checked"' : '';
        $this->_oView->form_idcat_chk = ($this->_getParam('idcat')) ? ' checked="checked"' : '';
        $this->_oView->form_idcatart_chk = ($this->_getParam('idcatart')) ? ' checked="checked"' : '';
        $this->_oView->form_idartlang_chk = ($this->_getParam('idartlang')) ? ' checked="checked"' : '';
        $this->_oView->form_maxitems = (int) $this->_getParam('maxitems', 200);
        $this->_iMaxItems = $this->_oView->form_maxitems;
    }

    /**
     * Index action
     */
    public function indexAction() {
        $this->_oView->content = '';
    }

    /**
     * Test action
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function testAction() {
        $this->_oView->content = '';

        // Array for testcases
        $aTests = [];

        // Instance of mr test
        $oMRTest = new ModRewriteTest($this->_iMaxItems);

        $startTime = getmicrotime();

        // Fetch complete CONTENIDO page structure
        $aStruct = $oMRTest->fetchFullStructure();
        ModRewriteDebugger::add($aStruct, 'mr_test.php $aStruct');

        // Loop through the structure and compose testcases
        foreach ($aStruct as $idcat => $aCat) {
            // category
            $aTests[] = [
                'url' => $oMRTest->composeURL($aCat, 'c'),
                'level' => $aCat['level'],
                'name' => $aCat['name']
            ];

            foreach ($aCat['articles'] as $idart => $aArt) {
                // articles
                $aTests[] = [
                    'url' => $oMRTest->composeURL($aArt, 'a'),
                    'level' => $aCat['level'],
                    'name' => $aCat['name'] . ' :: ' . $aArt['title']
                ];
            }
        }

        // compose content
        $this->_oView->content = '<pre>';

        $oMRUrlStack = ModRewriteUrlStack::getInstance();

        // first loop to add urls to mr url stack
        foreach ($aTests as $p => $v) {
            $oMRUrlStack->add($v['url']);
        }

        $successCounter = 0;
        $failCounter = 0;

        // second loop to do the rest
        foreach ($aTests as $p => $v) {
            $url = mr_buildNewUrl($v['url']);
            $arr = $oMRTest->resolveUrl($url);
            $error = '';
            $resUrl = $oMRTest->getResolvedUrl();
            $color = 'green';

            if ($url !== $resUrl) {
                if ($oMRTest->getRoutingFoundState()) {
                    $successCounter++;
                    $resUrl = 'route to -&gt; ' . $resUrl;
                } else {
                    $color = 'red';
                    $failCounter++;
                }
            } else {
                $successCounter++;
            }

            // @todo: translate
            if (isset($arr['error'])) {
                switch ($arr['error']) {
                    case ModRewriteController::ERROR_CLIENT:
                        $error = 'client';
                        break;
                    case ModRewriteController::ERROR_LANGUAGE:
                        $error = 'language';
                        break;
                    case ModRewriteController::ERROR_CATEGORY:
                        $error = 'category';
                        break;
                    case ModRewriteController::ERROR_ARTICLE:
                        $error = 'article';
                        break;
                    case ModRewriteController::ERROR_POST_VALIDATION:
                        $error = 'validation';
                        break;
                }
            }

            $pref = str_repeat('    ', $v['level']);

            // render resolve information for current item
            $itemTpl = $this->_oView->lng_result_item_tpl;
            $itemTpl = str_replace('{pref}', $pref, $itemTpl);
            $itemTpl = str_replace('{name}', $v['name'], $itemTpl);
            $itemTpl = str_replace('{url_in}', $v['url'], $itemTpl);
            $itemTpl = str_replace('{url_out}', $url, $itemTpl);
            $itemTpl = str_replace('{color}', $color, $itemTpl);
            $itemTpl = str_replace('{url_res}', $resUrl, $itemTpl);
            $itemTpl = str_replace('{err}', $error, $itemTpl);
            $itemTpl = str_replace('{data}', $oMRTest->getReadableResolvedData($arr), $itemTpl);

            $this->_oView->content .= "\n" . $itemTpl . "\n";
        }
        $this->_oView->content .= '</pre>';

        $totalTime = sprintf('%.4f', (getmicrotime() - $startTime));

        // render information about current test
        $msg = $this->_oView->lng_result_message_tpl;
        $msg = str_replace('{time}', $totalTime, $msg);
        $msg = str_replace('{num_urls}', ($successCounter + $failCounter), $msg);
        $msg = str_replace('{num_success}', $successCounter, $msg);
        $msg = str_replace('{num_fail}', $failCounter, $msg);

        $this->_oView->content = $msg . $this->_oView->content;
    }

}
