<?php

/**
 * AMR abstract controller class
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
 * Abstract controller for all concrete mod_rewrite controller implementations.
 *
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
abstract class PiModRewriteControllerAbstract
{

    /**
     * @var PiModRewriteConfigurationService
     */
    protected $mrConfigurationService;

    /**
     * @var stdClass View object, holds all view variables
     */
    protected $view;

    /**
     * @var array Global CONTENIDO $cfg variable
     */
    protected $cfg;

    /**
     * @var int Global CONTENIDO $client variable (client id)
     */
    protected $clientId;

    /**
     * @var int|string Global CONTENIDO $area variable (area name/id)
     */
    protected $area;

    /**
     * @var string Global CONTENIDO $action variable (send by request)
     */
    protected $action;

    /**
     * @var int Global CONTENIDO $frame variable (current frame in backend)
     */
    protected $frame;

    /**
     * @var string Global CONTENIDO $contenido variable (session id)
     */
    protected $contenido;

    /**
     * @var string Plugin name
     */
    protected $pluginName;

    /**
     * @var ?string Template file or template string to render
     */
    protected $template = NULL;

    /**
     * @var array Additional properties list
     */
    protected $properties = [];

    /**
     * Sets some properties by assigning global variables to them.
     */
    public function __construct()
    {
        $this->view = new stdClass();
        $this->cfg = cRegistry::getConfig();
        $this->area = cRegistry::getArea();
        $this->action = cRegistry::getAction();
        $this->frame = cRegistry::getFrame();
        $this->clientId = cRegistry::getClientId();
        $this->contenido = cRegistry::getBackendSessionId();
        $this->pluginName = $this->cfg['pi_mod_rewrite']['pluginName'];
        $this->mrConfigurationService = PiModRewriteConfigurationService::getInstance();
        $sess = cRegistry::getSession();

        $this->view->area = $this->area;
        $this->view->frame = $this->frame;
        $this->view->contenido = $this->contenido;
        $this->view->sessid = $sess->id;
        $this->view->lng_more_information = i18n('More information', $this->pluginName);

        $this->init();
    }

    /**
     * Initializer method, it could be overwritten by children.
     * This method will be invoked in the constructor of PiModRewriteControllerAbstract.
     */
    public function init()
    {
    }

    /**
     * View property setter.
     */
    public function setView(?stdClass $view)
    {
        $this->view = $view;
    }

    /**
     * View getter.
     */
    public function getView(): stdClass
    {
        return $this->view;
    }

    /**
     * Property setter.
     *
     * @param mixed $value
     */
    public function setProperty(string $key, $value)
    {
        $this->properties[$key] = $value;
    }

    /**
     * Property getter.
     *
     * @param mixed $default
     * @return mixed
     */
    public function getProperty(string $key, $default = NULL)
    {
        return $this->properties[$key] ?? $default;
    }

    /**
     * Template setter.
     *
     * @param string $template Either the full path and name of the template file or a template string.
     */
    public function setTemplate(string $template)
    {
        $this->template = $template;
    }

    /**
     * Template getter.
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * Renders template by replacing all view variables in template.
     *
     * @param ?string $template Either the full path and name of the template file or a template string.
     *                If not passed, the previous set template will be used.
     * @throws cException if no template is set
     */
    public function render(?string $template = NULL)
    {
        if ($template == NULL) {
            $template = $this->getTemplate();
        }

        if ($template == NULL) {
            throw new cException('Missing template to render.');
        }

        $tplObj = new cTemplate();
        foreach ($this->view as $k => $v) {
            $tplObj->set('s', cString::toUpperCase($k), $v);
        }
        $tplObj->generate($template, 0, 0);
    }

    /**
     * @see PiModRewriteRequestUtil::getRequestParam()
     */
    protected function getRequestParam(string $key, $default = NULL)
    {
        return PiModRewriteRequestUtil::getRequestParam($key, $default);
    }

    /**
     * Returns rendered notification markup by using the global $notification variable.
     *
     * @param string $type One of cGuiNotification::LEVEL_* constants
     * @param string $message The message to display
     */
    protected function renderNotification(string $type, string $message): string
    {
        global $notification;
        return $notification->returnNotification($type, $message) . '<br>';
    }

}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteControllerAbstract instead.
 */
abstract class ModRewrite_ControllerAbstract extends PiModRewriteControllerAbstract
{}
