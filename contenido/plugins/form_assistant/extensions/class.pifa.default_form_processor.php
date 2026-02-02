<?php

/**
 *
 * @package    Plugin
 * @subpackage FormAssistant
 * @author     Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright  four for business AG
 * @link       https://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Default implementation of the abstract form processor that implements no
 * postprocessing at all.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class DefaultFormProcessor extends PifaAbstractFormProcessor
{

    /**
     * @inheritDoc
     */
    protected function _processReadData()
    {
    }

    /**
     * @inheritDoc
     */
    protected function _processValidatedData()
    {
    }

    /**
     * @inheritDoc
     */
    protected function _processStoredData()
    {
    }
}
