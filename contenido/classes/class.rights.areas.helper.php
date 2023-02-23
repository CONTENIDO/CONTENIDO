<?php

/**
 * This file contains the rights areas helper class.
 *
 * @package Core
 * @subpackage Backend
 * @author Murat Purç <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for the rights areas helper in CONTENIDO.
 *
 * NOTE:
 * This class is for internal usage in CONTENIDO core, therefore it is not meant
 * for public usage. Its interface and functionality may change in the future.
 *
 * @since CONTENIDO 4.10.2
 * @package Core
 * @subpackage Backend
 */
class cRightsAreasHelper
{

    /**
     * @var cApiUser
     */
    protected $_currentUser = null;

    /**
     * @var cAuth
     */
    protected $_auth = null;

    /**
     * @var array
     */
    protected $_contextPerms;

    /**
     * @var bool
     */
    protected $_isAuthSysadmin;

    /**
     * @var bool
     */
    protected $_isContextSysadmin;

    /**
     * Constructor.
     *
     * @param cApiUser $currentUser
     * @param cAuth $auth
     * @param array $contextPerms
     */
    public function __construct(cApiUser $currentUser, cAuth $auth, array $contextPerms)
    {
        $this->_currentUser = $currentUser;
        $this->_auth = $auth;
        $this->_contextPerms = $contextPerms;
        $this->_isAuthSysadmin = cPermission::checkSysadminPermission($this->_auth->getPerms());
        $this->_isContextSysadmin = cPermission::checkSysadminPermission($this->_contextPerms);
    }

    /**
     * Sets the permissions for a specific context (user or group)
     *
     * @param array $perms
     * @return void
     */
    public function setContextPermissions(array $perms)
    {
        $this->_contextPerms = $perms;
    }

    /**
     * Checks if authenticated user is sysadmin
     *
     * @return bool
     */
    public function isAuthSysadmin(): bool
    {
        return $this->_isAuthSysadmin;
    }

    /**
     * Checks if context (user or group) is sysadmin
     *
     * @return bool
     */
    public function isContextSysadmin(): bool
    {
        return $this->_isContextSysadmin;
    }

    /**
     * Checks if authenticated user is a client admin
     *
     * @param int $idClient
     * @return bool
     */
    public function isAuthClientAdmin(int $idClient): bool
    {
        return cPermission::checkClientAdminPermission($idClient, $this->_auth->getPerms());
    }

    /**
     * Checks if context (user or group) is a client admin
     *
     * @param int $idClient
     * @return bool
     */
    public function isContextClientAdmin(int $idClient): bool
    {
        return cPermission::checkClientAdminPermission($idClient, $this->_contextPerms);
    }

    /**
     * Checks if authenticated user has rights for a client
     *
     * @param int $idClient
     * @return bool
     */
    public function hasAuthClientPerm(int $idClient): bool
    {
        return $this->_isAuthSysadmin
            || cPermission::checkClientPermission($idClient, $this->_auth->getPerms());
    }

    /**
     * Checks if context (user or group) has rights for a client
     *
     * @param int $idClient
     * @return bool
     */
    public function hasContextClientPerm(int $idClient): bool
    {
        return $this->_isContextSysadmin
            || cPermission::checkClientPermission($idClient, $this->_contextPerms);
    }

    /**
     * Checks if authenticated user has rights for a language
     *
     * @param int $idLang
     * @return bool
     */
    public function hasAuthLanguagePerm(int $idLang): bool
    {
        return $this->_isAuthSysadmin
            || cPermission::checkLanguagePermission($idLang, $this->_auth->getPerms());
    }

    /**
     * Checks if context (user or group) has rights for a language
     *
     * @param int $idLang
     * @return bool
     */
    public function hasContextLanguagePerm(int $idLang): bool
    {
        return $this->_isContextSysadmin
            || cPermission::checkLanguagePermission($idLang, $this->_contextPerms);
    }

    /**
     * Returns list of available clients.
     *
     * @return array
     * @throws cDbException
     * @throws cException
     */
    public function getAvailableClients(): array
    {
        $oClientsCollection = new cApiClientCollection();
        $result = $oClientsCollection->getAvailableClients();
        $clients = [];
        foreach ($result as $clientId => $entry) {
            $clients[cSecurity::toInteger($clientId)] = $entry;
        }
        return $clients;
    }

    /**
     * Returns a list with all clients and languages, alias for
     * {@see getAllClientsAndLanguages()}
     *
     * @return array
     * @throws cDbException
     */
    public function getAllClientsAndLanguages(): array
    {
        $result = getAllClientsAndLanguages();
        // Cast the values to their proper types
        foreach ($result as $pos => $entry) {
            $result[$pos]['idlang'] = cSecurity::toInteger($entry['idlang']);
            $result[$pos]['langname'] = cSecurity::toString($entry['langname']);
            $result[$pos]['idclient'] = cSecurity::toInteger($entry['idclient']);
            $result[$pos]['clientname'] = cSecurity::toString($entry['clientname']);
        }
        return $result;
    }

    /**
     * Renders the client admin checkboxes for the passed list of client ids.
     *
     * @param int[] $clients
     * @return string
     */
    public function renderClientAdminCheckboxes(array $clients): string
    {
        $sCheckboxes = '';
        foreach ($clients as $idclient => $item) {
            $isAuthUserClientAdmin = $this->isAuthClientAdmin($idclient);
            if ($isAuthUserClientAdmin || $this->_isAuthSysadmin) {
                $oCheckbox = new cHTMLCheckbox(
                    "madmin[" . $idclient . "]",
                    $idclient,
                    "madmin[" . $idclient . "]" . $idclient,
                    cPermission::checkClientAdminPermission($idclient, $this->_contextPerms)
                );
                $oCheckbox->setLabelText(conHtmlSpecialChars($item['name']) . " (" . $idclient . ")");
                $sCheckboxes .= $oCheckbox->toHtml();
            }
        }
        return $sCheckboxes;
    }

    /**
     * Renders a single client permission checkbox for the passed client.
     *
     * @param int $idClient
     * @param string $clientName
     * @return string
     */
    public function renderClientPermCheckbox(int $idClient, string $clientName): string
    {
        $oCheckbox = new cHTMLCheckbox(
            "mclient[" . $idClient . "]",
            $idClient,
            "mclient[" . $idClient . "]" . $idClient,
            $this->hasContextClientPerm($idClient)
        );
        $oCheckbox->setLabelText(conHtmlSpecialChars($clientName) . " (" . $idClient . ")");
        return $oCheckbox->toHtml();
    }

    /**
     * Renders a single client permission checkbox for the passed language.
     *
     * @param int $idLanguage
     * @param string $languageName
     * @param string $clientName
     * @return string
     */
    public function renderLanguagePermCheckbox(
        int $idLanguage, string $languageName, string $clientName
    ): string
    {
        $oCheckbox = new cHTMLCheckbox(
            "mlang[" . $idLanguage . "]",
            $idLanguage,
            "mlang[" . $idLanguage . "]" . $idLanguage,
            $this->hasContextLanguagePerm($idLanguage)
        );
        $oCheckbox->setLabelText(conHtmlSpecialChars($languageName) . " (" . $clientName . ")");
        return $oCheckbox->toHtml();
    }

    /**
     * Renders the properties table for user or group.
     *
     * @param array $data Data to render
     * @param string $typeFieldName Name of property type input field
     * @param string $nameFieldName  Name of property name input field
     * @param string $valueFieldName Name of property value input field
     * @return string
     * @throws cException
     */
    public function renderPropertiesTable(
        array $data, string $typeFieldName, string $nameFieldName, string $valueFieldName
    ): string
    {
        $table = new cHTMLTable();
        $table->setClass('generic');

        $contents = [];

        // Table head (tr > th)
        $tableRow = new cHTMLTableRow(); // tr
        $contents[] = $tableRow->setClass('text_medium')
            ->setContent([
                new cHTMLTableHead(i18n("Area/Type")),
                new cHTMLTableHead(i18n("Property")),
                new cHTMLTableHead(i18n("Value")),
                new cHTMLTableHead(i18n("Delete")),
            ])
            ->render();

        // Table rows (tr > td) for data
        foreach ($data as $entry) {
            $anchor = '<a href="' . $entry['href'] . '"><img src="images/delete.gif" alt="' . i18n('Delete') . '" title="' . i18n('Delete') . '"></a>';
            $tableRow = new cHTMLTableRow(); // tr
            $contents[] = $tableRow->setClass('text_medium')
                ->setContent([
                    new cHTMLTableData($entry['type']),
                    new cHTMLTableData($entry['name']),
                    new cHTMLTableData($entry['value']),
                    new cHTMLTableData($anchor),
                ])
                ->render();
        }

        // Table row (tr > td) for fom fields
        $tableRow = new cHTMLTableRow(); // tr
        $contents[] = $tableRow->setClass('text_medium')
            ->setContent([
                new cHTMLTableData('<input class="text_medium" type="text" size="16" maxlength="32" name="' . $typeFieldName . '">'),
                new cHTMLTableData('<input class="text_medium" type="text" size="16" maxlength="32" name="' . $nameFieldName . '">'),
                new cHTMLTableData('<input class="text_medium" type="text" size="32" name="'. $valueFieldName . '">'),
                new cHTMLTableData('&nbsp;'),
            ])
            ->render();

        return $table->setContent($contents)
            ->render();
    }

}