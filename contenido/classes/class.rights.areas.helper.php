<?php

/**
 * This file contains the rights areas helper class.
 *
 * @package Core
 * @subpackage Backend
 * @author Murat Purç <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
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
class cRightsAreasHelper {

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
     */
    public function __construct(cApiUser $currentUser, cAuth $auth, array $contextPerms) {
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
    public function setContextPermissions(array $perms) {
        $this->_contextPerms = $perms;
    }

    /**
     * Checks if authenticated user is sysadmin
     *
     * @return bool
     */
    public function isAuthSysadmin() {
        return $this->_isAuthSysadmin;
    }

    /**
     * Checks if context (user or group) is sysadmin
     *
     * @param int $idClient
     * @return bool
     */
    public function isContextSysadmin() {
        return $this->_isContextSysadmin;
    }

    /**
     * Checks if authenticated user is a client admin
     *
     * @param int $idClient
     * @return bool
     */
    public function isAuthClientAdmin($idClient) {
        return cPermission::checkClientAdminPermission($idClient, $this->_auth->getPerms());
    }

    /**
     * Checks if context (user or group) is a client admin
     *
     * @return bool
     */
    public function isContextClientAdmin($idClient) {
        return cPermission::checkClientAdminPermission($idClient, $this->_contextPerms);
    }

    /**
     * Checks if authenticated user has rights for a client
     *
     * @param int $idClient
     * @return bool
     */
    public function hasAuthClientPerm($idClient) {
        return $this->_isAuthSysadmin
            || cPermission::checkClientPermission($idClient, $this->_auth->getPerms());
    }

    /**
     * Checks if context (user or group) has rights for a client
     *
     * @param int $idClient
     * @return bool
     */
    public function hasContextClientPerm($idClient) {
        return $this->_isContextSysadmin
            || cPermission::checkClientPermission($idClient, $this->_contextPerms);
    }

    /**
     * Checks if authenticated user has rights for a language
     *
     * @param int $idLang
     * @return bool
     */
    public function hasAuthLanguagePerm($idLang) {
        return $this->_isAuthSysadmin
            || cPermission::checkLanguagePermission($idLang, $this->_auth->getPerms());
    }

    /**
     * Checks if context (user or group) has rights for a language
     *
     * @param int $idLang
     * @return bool
     */
    public function hasContextLanguagePerm($idLang) {
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
    public function getAvailableClients() {
        $oClientsCollection = new cApiClientCollection();
        return $oClientsCollection->getAvailableClients();
    }

    /**
     * Renders the client admin checkboxes for the passed list of client ids.
     *
     * @param int[] $clients
     * @return string
     */
    public function renderClientAdminCheckboxes(array $clients) {
        $sCheckboxes = '';
        foreach ($clients as $idclient => $item) {
            $isAuthUserClientAdmin = $this->isAuthClientAdmin($idclient);
            if ($isAuthUserClientAdmin || $this->_authHasSysadminPerm) {
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
    public function renderClientPermCheckbox($idClient, $clientName) {
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
    public function renderLanguagePermCheckbox($idLanguage, $languageName, $clientName) {
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
    public function renderPropertiesTable(array $data, $typeFieldName, $nameFieldName, $valueFieldName) {
        $table = '
            <table class="generic" width="100%" cellspacing="0" cellpadding="2">
                <tr>
                    <th>' . i18n("Area/Type") . '</th>
                    <th>' . i18n("Property") . '</th>
                    <th>' . i18n("Value") . '</th>
                    <th>' . i18n("Delete") . '</th>
                </tr>
        ';

        foreach ($data as $entry) {
            $table .= '
                <tr class="text_medium">
                    <td>' . $entry['type'] . '</td>
                    <td>' . $entry['name'] . '</td>
                    <td>' . $entry['value'] . '</td>
                    <td>
                        <a href="' . $entry['href'] . '"><img src="images/delete.gif" alt="' . i18n('Delete') . '" title="' . i18n('Delete') . '"></a>
                    </td>
                </tr>'
            ;
        }

        $table .='
                <tr>
                    <td><input class="text_medium" type="text" size="16" maxlength="32" name="' . $typeFieldName . '"></td>
                    <td><input class="text_medium" type="text" size="16" maxlength="32" name="' . $nameFieldName . '"></td>
                    <td><input class="text_medium" type="text" size="32" name="'. $valueFieldName . '"></td>
                    <td>&nbsp;</td>
                </tr>
            </table>';

        return $table;
    }

}