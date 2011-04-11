<?php

defined('CON_FRAMEWORK') or die('Illegal call');


class ModRewriteDebugger
{
    protected static $_bEnabled = false;


    public static function setEnabled($bEnabled)
    {
        self::$_bEnabled = (bool) $bEnabled;
    }

    public static function add($mVar, $sLabel = '')
    {
        if (!self::$_bEnabled) {
            return;
        }
        $oDebugger = DebuggerFactory::getDebugger('visible_adv');
        $oDebugger->add($mVar, $sLabel);
    }

    public static function getAll()
    {
        if (!self::$_bEnabled) {
            return '';
        }
        $oDebugger = DebuggerFactory::getDebugger('visible_adv');
        ob_start();
        $oDebugger->showAll();
        $sOutput = ob_get_contents();
        ob_end_clean();
        return $sOutput;
    }

    public static function log($mVar, $sLabel = '')
    {
        if (!self::$_bEnabled) {
            return;
        }
        $oDebugger = DebuggerFactory::getDebugger('file');
        $oDebugger->show($mVar, $sLabel);
    }
}
