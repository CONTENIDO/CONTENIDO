<?php

/**
 * Adding a Channel File greatly improves the performance of the JS SDK by
 * addressing issues with cross-domain communication in certain browsers.
 *
 * The channel file should be set to be cached for as long as possible. When
 * serving this file, you should send valid Expires headers with a long
 * expiration period. This will ensure the channel file is cached by the browser
 * and not reloaded with each page refresh. Without proper caching, users will
 * suffer a severely degraded experience.
 *
 * The channelUrl parameter within FB.init() is optional, but strongly
 * recommended. Providing a channel file can help address three specific known
 * issues.
 *
 * - Pages that include code to communicate across frames may cause Social
 * Plugins to show up as blank without a channelUrl.
 * - if no channelUrl is provided and a page includes auto-playing audio or
 * video, the user may hear two streams of audio because the page has been
 * loaded a second time in the background for cross domain communication.
 * - a channel file will prevent inclusion of extra hits in your server-side
 * logs. If you do not specify a channelUrl, you should remove page views
 * containing fb_xd_bust or fb_xd_fragment parameters from your logs to ensure
 * proper counts.
 *
 * The channelUrl must be a fully qualified URL matching the page on which you
 * include the SDK. In other words, the channel file domain must include www if
 * your site is served using www, and if you modify document.domain on your page
 * you must make the same document.domain change in the channel.html file as
 * well. The protocols must also match. If your page is served over https, your
 * channelUrl must also be https. Remember to use the matching protocol for the
 * script src as well. The sample code above uses protocol-relative URLs which
 * should handle most https cases properly.
 *
 * @package Module
 * @subpackage ScriptFbChannel
 * @author marcus.gnass
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

$cache_expire = 60 * 60 * 24 * 365; // one year

header('Pragma: public');
header('Cache-Control: max-age=' . $cache_expire);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_expire) . ' GMT');

// locale to be used to identify FB all.js
$settingType = 'fb-sdk';
$locale = getEffectiveSetting($settingType, 'locale', 'en_US');

echo '<script src="//connect.facebook.net/' . conHtmlSpecialChars($locale) . '/all.js"></script>';

?>