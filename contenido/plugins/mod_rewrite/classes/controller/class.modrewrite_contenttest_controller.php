<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Content test controller
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend plugins
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since Contenido release 4.8.15
 *
 * {@internal
 *   created  2011-04-11
 *
 *   $Id$:
 * }}
 *
 */


defined('CON_FRAMEWORK') or die('Illegal call');


plugin_include('mod_rewrite', 'classes/class.modrewritetest.php');
plugin_include('mod_rewrite', 'classes/controller/class.modrewrite_controller_abstract.php');


class ModRewrite_ContentTestController extends ModRewrite_ControllerAbstract
{
    protected $_iMaxItems = 0;

    public function init()
    {
        $this->_oView->content   = '';
        $this->_oView->form_idart_chk     = ($this->_getParam('idart')) ? ' checked="checked"' : '';
        $this->_oView->form_idcat_chk     = ($this->_getParam('idcat')) ? ' checked="checked"' : '';
        $this->_oView->form_idcatart_chk  = ($this->_getParam('idcatart')) ? ' checked="checked"' : '';
        $this->_oView->form_idartlang_chk = ($this->_getParam('idartlang')) ? ' checked="checked"' : '';
        $this->_oView->form_maxitems      = (int) $this->_getParam('maxitems', 200);
        $this->_iMaxItems = $this->_oView->form_maxitems;
    }

    /**
     * Execute index action
     */
    public function indexAction()
    {
        $this->_oView->content = '';

    }

    /**
     * Execute test action
     */
    public function testAction()
    {
        $this->_oView->content = '';

        // Array for testcases
        $aTests  = array();

        // Instance of mr test
        $oMRTest = new ModRewriteTest($this->_iMaxItems);

        $startTime = getmicrotime();

        // Fetch complete Contenido page structure
        $aStruct = $oMRTest->fetchFullStructure();
        ModRewriteDebugger::add($aStruct, 'mr_test.php $aStruct');

        // Loop through the structure and compose testcases
        foreach ($aStruct as $idcat => $aCat) {
            // category
            $aTests[] = array(
                'url'   => $oMRTest->composeURL($aCat, 'c'),
                'level' => $aCat['level'],
                'name'  => $aCat['name']
            );

            foreach ($aCat['articles'] as $idart => $aArt) {
                // articles
                $aTests[] = array(
                    'url'  => $oMRTest->composeURL($aArt, 'a'),
                    'level' => $aCat['level'],
                    'name' => $aCat['name'] . ' :: ' . $aArt['title']
                );
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
        $failCounter    = 0;

        // second loop to do the rest
        foreach ($aTests as $p => $v) {
            $url    = mr_buildNewUrl($v['url']);
            $arr    = $oMRTest->resolveUrl($url);
            $resUrl = $oMRTest->getResolvedUrl();
            $color  = 'green';

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

            $pref   = str_repeat('    ', $v['level']);

            $this->_oView->content .= "\n"
                . $pref . '<strong>' . $v['name'] . '</strong>' . "\n"
                . $pref . 'Builder Eingang:   ' . $v['url'] . "\n"
#                . $pref . 'Builder Ausgang:   <a href="' . $url . '" target="_blank">' . $url . '</a>' . "\n"
                . $pref . 'Builder Ausgang:   ' . $url . "\n"
                . $pref . '<span style="color:' . $color . '">Aufgel&ouml;ste URL:    ' . $resUrl . "</span>\n"
                . $pref . 'Aufgel&ouml;ste Daten:  ' . $oMRTest->getReadableResolvedData($arr) . "\n";
        }
        $this->_oView->content .= '</pre>';

        $totalTime = sprintf('%.4f', (getmicrotime() - $startTime));
        $msg = '<strong>Dauer des Testdurchlaufs: ' . $totalTime . ' Sekunden.<br />'
              . 'Anzahl verarbeiteter URLs: ' . ($successCounter + $failCounter) . '<br />'
              . '<span style="color:green">Erfolgreich aufgel&ouml;st: ' . ($successCounter) . '</span><br />'
              . '<span style="color:red">Fehler beim Aufl&ouml;sen: ' . ($failCounter) . '</span></strong><br /><br />';

        $this->_oView->content = $msg . $this->_oView->content;

    }

}
