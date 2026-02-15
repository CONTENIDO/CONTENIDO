<?php

/**
 * This file contains the backend client helper class.
 *
 * @package    Core
 * @subpackage Backend_Client_Helper
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 * @since      CONTENIDO 4.10.2
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

class cBackendClientHelper
{
    /**
     * Checks if the client id is > 0 and loads the client item by the id.
     * Returns the client instance in case of a success.
     * Renders page with an error message in case of a failure.
     */
    public static function requireClient(int $clientId): ?cApiClient
    {
        try {
            if ($clientId < 1) {
                $page = new cGuiPage('require_client');
                $page->displayCriticalError(i18n("No Client selected"));
                $page->render();
                return null;
            }

            $client = new cApiClient();
            $client->loadByPrimaryKey($clientId);
            if (!$client->isLoaded()) {
                $page = new cGuiPage('require_client');
                $page->displayCriticalError(sprintf(i18n('Client with id %d not found'), $clientId));
                $page->render();
                return null;
            }

            return $client;
        } catch (Exception $e) {
            cLogError(sprintf(
                'Could not check for client id %d. Error: %s',
                $clientId,
                $e->getMessage()
            ));
            return null;
        }
    }

    /**
     * Checks if the passed client has any languages.
     * Renders page with an error message in case of a failure.
     */
    public static function requireClientHasLanguages(cApiClient $client): bool
    {
        try {
            if (!$client->hasLanguages()) {
                $page = new cGuiPage('client_has_languages');
                $page->displayCriticalError(sprintf(
                    i18n('Client %s (%s) has no languages'),
                    $client->get('name'),
                    $client->getId()
                ));
                $page->render();
                return false;
            }
        } catch (Exception $e) {
            cLogError(sprintf(
                'Could not check languages for client id %d. Error: %s',
                $client->getId(),
                $e->getMessage()
            ));
            return false;
        }

        return true;
    }

    /**
     * Checks, if client language id is > 0 and loads the client language item by the id.
     * Returns the client language instance in case of a success.
     * Renders page with an error message in case of a failure.
     */
    public static function requireClientLanguage(int $clientLanguageId): ?cApiClientLanguage
    {
        try {
            if ($clientLanguageId < 1) {
                $page = new cGuiPage('require_client_language');
                $page->displayCriticalError(i18n("No client language selected"));
                $page->render();
                return null;
            }

            $clientLanguage = new cApiClientLanguage();
            $clientLanguage->loadByPrimaryKey($clientLanguageId);
            if (!$clientLanguage->isLoaded()) {
                $page = new cGuiPage('require_client_language');
                $page->displayCriticalError(sprintf(i18n('Language with id %d not found'), $clientLanguageId));
                $page->render();
                return null;
            }

            return $clientLanguage;
        } catch (Exception $e) {
            cLogError(sprintf(
                'Could not check for client language id %d. Error: %s',
                $clientLanguageId,
                $e->getMessage()
            ));
            return null;
        }
    }
}
