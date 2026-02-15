<?php

/**
 * This file contains client language selection form GUI class.
 *
 * @package    Core
 * @subpackage GUI
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 * @since      CONTENIDO 4.10.2
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Client language selection form GUI class.
 * Renders a form to select the client language for a client.
 * The default send form variables are `area`, `frame`, `idclient`, and `idclientslang`.
 *
 * @package    Core
 * @subpackage GUI
 */
class cGuiClientLanguageForm
{

    /**
     * @var cApiClient
     */
    private $client;

    /**
     * @var cApiClientLanguage|null
     */
    private $clientLanguage;

    /**
     * @var string[]
     */
    private $formVar = [];

    /**
     * @var bool
     */
    private $addLanguageIndependentOption;

    /**
     * @var string
     */
    private $formClass = 'con_block';

    /**
     * @var string
     */
    private $formName = 'clientLanguageForm';

    /**
     * @var string
     */
    private $tableClass = 'generic col_sm';

    /**
     * @param cApiClient $client The client to select the language for.
     * @param cApiClientLanguage|null $clientLanguage The client language to select initially.
     * @param bool $addLanguageIndependentOption Flag to add a language-independent option to the select box.
     *      The send form var `idclientslang` will be set to 0 if this flag is set to true.
     */
    public function __construct(
        cApiClient $client,
        ?cApiClientLanguage $clientLanguage = null,
        bool $addLanguageIndependentOption = true
    ) {
        $this->client = $client;
        $this->clientLanguage = $clientLanguage;
        $this->setFormVar('area', cRegistry::getArea())
            ->setFormVar('frame', cRegistry::getFrame())
            ->setFormVar('idclient', $this->client->getId());
        $this->addLanguageIndependentOption = $addLanguageIndependentOption;
    }

    /**
     * Set arbitrary form variable.
     *
     * @param string $name The form variable name.
     * @param mixed $value The value to set.
     */
    public function setFormVar(string $name, $value): self
    {
        $this->formVar[$name] = $value;

        return $this;
    }

    public function setFormClass(string $formClass): self
    {
        $this->formClass = $formClass;

        return $this;
    }

    public function setTableClass(string $tableClass): self
    {
        $this->tableClass = $tableClass;

        return $this;
    }

    /**
     * Renders this cGuiClientLanguageForm and either returns ist markup or echoes it immediately.
     *
     * @param bool $return If true, then return markup, else echo immediately
     * @return ?string
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function render(bool $return = true): ?string
    {
        $clientLanguageColl = new cApiClientLanguageCollection();

        // Language selection form
        $tableForm = new cGuiTableForm($this->formName);
        $tableForm->setFormClass($this->formClass);
        $tableForm->setTableClass($this->tableClass);
        foreach ($this->formVar as $name => $value) {
            $tableForm->setVar($name, $value);
        }
        $tableForm->setHeader(i18n('Select language'));
        $tableForm->add(i18n('Language'), $this->buildLanguageSelect()->render());

        $rendered = $tableForm->render($return);

        if ($return) {
            return $rendered;
        } else {
            // Echo is already done in `$tableForm->render()`
            return null;
        }
    }

    private function buildLanguageSelect(): cHTMLSelectElement
    {
        $clientLanguageColl = new cApiClientLanguageCollection();

        $select = new cHTMLSelectElement('idclientslang');
        if ($this->addLanguageIndependentOption) {
            $select->addOptionElement(0, new cHTMLOptionElement(i18n("Language independent"), 0));
        }

        // Get all client languages and fill the language-dependent settings select box
        foreach ($clientLanguageColl->getAllLanguagesByClient($this->client->getId()) as $item) {
            $id = cSecurity::toInteger($item['idclientslang']);
            $languageName = conHtmlSpecialChars($item['name']);
            $select->addOptionElement(
                $id,
                new cHTMLOptionElement("$languageName ({$item['idlang']})", $id)
            );
        }
        if ($this->clientLanguage) {
            $select->setDefault($this->clientLanguage->getId());
        }
        $select->setEvent('onchange', "document.forms.$this->formName.submit();");

        return $select;
    }

}
