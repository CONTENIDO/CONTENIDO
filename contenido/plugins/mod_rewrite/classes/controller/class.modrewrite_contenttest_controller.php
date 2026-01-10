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
class ModRewrite_ContentTestController extends ModRewrite_ControllerAbstract
{

    /**
     * @var int Number of max items to process
     */
    protected $maxItems = 0;

    /**
     * Initializer method, sets some view variables
     */
    public function init()
    {
        $this->view->content = '';
        $this->view->form_idart_chk = $this->getRequestParam('idart') ? ' checked="checked"' : '';
        $this->view->form_idcat_chk = $this->getRequestParam('idcat') ? ' checked="checked"' : '';
        $this->view->form_idcatart_chk = $this->getRequestParam('idcatart') ? ' checked="checked"' : '';
        $this->view->form_idartlang_chk = $this->getRequestParam('idartlang') ? ' checked="checked"' : '';
        $this->view->form_maxitems = cSecurity::toInteger($this->getRequestParam('maxitems', 200));
        $this->maxItems = $this->view->form_maxitems;
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        $this->view->content = '';
    }

    /**
     * Test action
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function testAction()
    {
        $this->view->content = '';

        // Array for testcases
        $test = [];

        // Instance of mr test
        $oMRTest = new ModRewriteTest($this->maxItems);

        $startTime = getmicrotime();

        // Fetch complete CONTENIDO page structure
        $catArtStruct = $oMRTest->fetchFullStructure();
        ModRewriteDebugger::add($catArtStruct, 'mr_test.php $catArtStruct');

        // Loop through the structure and compose testcases
        foreach ($catArtStruct as $catData) {
            // category
            $test[] = [
                'url' => $oMRTest->composeURL($catData, 'c'),
                'level' => $catData['level'],
                'name' => $catData['name']
            ];

            foreach ($catData['articles'] as $artData) {
                // articles
                $test[] = [
                    'url' => $oMRTest->composeURL($artData, 'a'),
                    'level' => $catData['level'],
                    'name' => $catData['name'] . ' :: ' . $artData['title']
                ];
            }
        }

        // compose content
        $this->view->content = '<pre>';

        $mrUrlStack = ModRewriteUrlStack::getInstance();

        // first loop to add urls to mr url stack
        foreach ($test as $v) {
            $mrUrlStack->add($v['url']);
        }

        $successCounter = 0;
        $failCounter = 0;

        // second loop to do the rest
        foreach ($test as $v) {
            $url = mr_buildNewUrl($v['url']);
            $arr = $oMRTest->resolveUrl($url);
            $error = '';
            $resUrl = $oMRTest->getResolvedUrl();
            $color = 'green';

            if ($url !== $resUrl) {
                if ($oMRTest->isRoutingFound()) {
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
            $itemTpl = $this->view->lng_result_item_tpl;
            $itemTpl = str_replace('{pref}', $pref, $itemTpl);
            $itemTpl = str_replace('{name}', $v['name'], $itemTpl);
            $itemTpl = str_replace('{url_in}', $v['url'], $itemTpl);
            $itemTpl = str_replace('{url_out}', $url, $itemTpl);
            $itemTpl = str_replace('{color}', $color, $itemTpl);
            $itemTpl = str_replace('{url_res}', $resUrl, $itemTpl);
            $itemTpl = str_replace('{err}', $error, $itemTpl);
            $itemTpl = str_replace('{data}', $oMRTest->getReadableResolvedData($arr), $itemTpl);

            $this->view->content .= "\n" . $itemTpl . "\n";
        }
        $this->view->content .= '</pre>';

        $totalTime = sprintf('%.4f', (getmicrotime() - $startTime));

        // render information about the current test
        $msg = $this->view->lng_result_message_tpl;
        $msg = str_replace('{time}', $totalTime, $msg);
        $msg = str_replace('{num_urls}', ($successCounter + $failCounter), $msg);
        $msg = str_replace('{num_success}', $successCounter, $msg);
        $msg = str_replace('{num_fail}', $failCounter, $msg);

        $this->view->content = $msg . $this->view->content;
    }

}
