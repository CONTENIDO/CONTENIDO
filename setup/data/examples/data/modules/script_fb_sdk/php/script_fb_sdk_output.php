<?php

/**
 * Description: Facebook Software Development Kit
 *
 * The Facebook SDK for JavaScript provides a rich set of client-side
 * functionality that:
 *
 * - Enables you to use the Like Button and other Social Plugins on your site.
 * - Enables you to use Facebook Login to lower the barrier for people to sign
 * up on your site.
 * - Makes it easy to call into Facebook's primary API, called the Graph API.
 * - Launch Dialogs that let people perform various actions like sharing
 * stories.
 * - Facilitates communication when you're building a game or an app tab on
 * Facebook.
 *
 * The SDK, social plugins and dialogs work on both desktop and mobile web
 * browsers.
 *
 * == How to use this module ==
 *
 * Define settings fb-sdk/app-id with the appropriate values from the App
 * Dashboard.
 *
 * If you want to use a channel file to prevent certain problems (please see
 * module script_fb_channel for more information), define settings
 * fb-sdk/idart-channel with the idart of an article displaying but the module
 * script_fb_channel.
 *
 * If your web site or online service, or a portion of your web site or service,
 * is directed to children under 13, please read
 * https://developers.facebook.com/docs/plugins/restrictions/ on how to
 * modify your initialization code.
 *
 * If the frontend uses jQuery, set the setting fb-sdk/template to 'jQuery' for
 * that a different template is used. By default 'async' is used.
 * See https://developers.facebook.com/docs/javascript/howto/jquery/.
 *
 * By default the en_US version of the SDK is initialized, which means that all
 * the dialogs and UI will be in US English. In order to localize the UI define
 * the setting fb-sdk/locale.
 *
 * All settings can be set as either as system- or (translatable)
 * client-setting.
 *
 * If you already include this snippet elsewhere on your page, you can remove it
 * if you wish, although it will not cause any issues if included twice.
 *
 * TODO See the FB.init documentation for a full list of available
 * initialization options.
 * https://developers.facebook.com/docs/reference/javascript/FB.init
 *
 * @package Module
 * @subpackage ScriptFbSdk
 * @version SVN Revision $Rev:$
 *
 * @author marcus.gnass
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

$settingType = 'fb-sdk';

// app ID from the app dashboard
$appId = getEffectiveSetting($settingType, 'app-id');

// channel file for x-domain comms
$idartChannel = getEffectiveSetting($settingType, 'idart-channel', 0);
$idartChannel = cSecurity::toInteger($idartChannel);
$channelUrl = '';
if (0 < $idartChannel) {
    $channelUrl = cUri::getInstance()->build(array(
        'idart' => $idartChannel,
        'lang' => cRegistry::getLanguageId()
    ), true);
}

// enable cookies to allow the server to access the session
$cookie = getEffectiveSetting($settingType, 'cookie');

// This indicates to Facebook that your site or service is directed towards
// under-13s.
$kidDirectedSite = getEffectiveSetting($settingType, 'kid-directed-site');

// locale to be used to identify FB all.js
$locale = getEffectiveSetting($settingType, 'locale');

// if none was defined
if (0 == strlen(trim($locale))) {
    // get current locale
    cApiPropertyCollection::reset();
    $propColl = new cApiPropertyCollection();
    $propColl->changeClient(cRegistry::getClientId());
    $languageCode = $propColl->getValue('idlang', cRegistry::getLanguageId(), 'language', 'code', '');
    $countryCode = $propColl->getValue('idlang', cRegistry::getLanguageId(), 'country', 'code', '');
    $locale = $languageCode . '_' . strtoupper($countryCode);
}

// if none
if (0 == strlen(trim($locale))) {
    // get default locale
    $locale = 'en_US';
}

// By setting status to true, the SDK will attempt to get information about
// the current user by hitting the OAuth endpoint. Setting status to false
// will improve page load times, but you'll need to manually check for login
// status to get an authenticated user. You can find out more about this
// process by looking at Facebook Login
// (https://developers.facebook.com/docs/javascript/gettingstarted/#login).
$status = getEffectiveSetting($settingType, 'status');

// get name of template to be used
$template = getEffectiveSetting($settingType, 'template', 'async');

// With xfbml set to true, the SDK will parse the DOM to find and initialize
// social plugins. If you're not using social plugins on the page, setting
// xfbml to false will improve page load times. You can find out more about
// this by looking at Social Plugins
// (https://developers.facebook.com/docs/javascript/gettingstarted/#plugins).
$xfbml = getEffectiveSetting($settingType, 'xfbml');

// display template
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('appId', $appId);
$tpl->assign('channelUrl', $channelUrl);
$tpl->assign('cookie', $cookie);
$tpl->assign('kidDirectedSite', $kidDirectedSite);
$tpl->assign('locale', $locale);
$tpl->assign('status', $status);
$tpl->assign('xfbml', $xfbml);
$tpl->display($template . '.tpl');

?>