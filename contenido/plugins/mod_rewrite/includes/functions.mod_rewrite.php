<?php

/**
 * NOTE:
 * Functions in this file are deprecated since Advanced Mod Rewrite 2.1.0.
 * They are split into separate classes in the new version as follows.
 * - @see PiModRewriteConfigurationService
 * - @see PiModRewriteDatabaseUtil
 * - @see PiModRewriteDebugger
 * - @see PiModRewriteRequestUtil
 * - @see PiModRewriteUtil
 *
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Stefan Seifarth / stese
 * @author     Murat Purc <murat@purc.de>
 * @copyright   www.polycoder.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::strNewTree()} instead.
 */
function mr_strNewTree(array $data): array
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::strNewTree() instead.');
    return PiModRewriteUtil::strNewTree($data);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::strNewCategory()} instead.
 */
function mr_strNewCategory(array $data): array
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::strNewTree() instead.');
    return PiModRewriteUtil::strNewCategory($data);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::strRenameCategory()} instead.
 */
function mr_strRenameCategory(array $data): array
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::strRenameCategory() instead.');
    return PiModRewriteUtil::strRenameCategory($data);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::strMoveUpCategory()} instead.
 */
function mr_strMoveUpCategory($categoryId)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::strMoveUpCategory() instead.');
    return PiModRewriteUtil::strMoveUpCategory($categoryId);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::strMovedownCategory()} instead.
 */
function mr_strMovedownCategory($categoryId)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::strMovedownCategory() instead.');
    return PiModRewriteUtil::strMovedownCategory($categoryId);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::strMoveSubtree()} instead.
 */
function mr_strMoveSubtree(array $data)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::strMoveSubtree() instead.');
    return PiModRewriteUtil::strMoveSubtree($data);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::strCopyCategory()} instead.
 */
function mr_strCopyCategory(array $data)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::strCopyCategory() instead.');
    return PiModRewriteUtil::strCopyCategory($data);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::strSyncCategory()} instead.
 */
function mr_strSyncCategory(array $data): array
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::strSyncCategory() instead.');
    return PiModRewriteUtil::strSyncCategory($data);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::conSaveArticle()} instead.
 */
function mr_conSaveArticle(array $data): array
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::conSaveArticle() instead.');
    return PiModRewriteUtil::conSaveArticle($data);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::conMoveArticles()} instead.
 */
function mr_conMoveArticles($data)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::conMoveArticles() instead.');
    return PiModRewriteUtil::conMoveArticles($data);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::conCopyArtLang()} instead.
 */
function mr_conCopyArtLang($data)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::conCopyArtLang() instead.');
    return PiModRewriteUtil::conCopyArtLang($data);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::conSyncArticle()} instead.
 */
function mr_conSyncArticle($data)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::conSyncArticle() instead.');
    return PiModRewriteUtil::conSyncArticle($data);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::buildNewUrl()} instead.
 */
function mr_buildNewUrl(string $url): string
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::buildNewUrl() instead.');
    return PiModRewriteUtil::buildNewUrl($url);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::buildGeneratedCode()} instead.
 */
function mr_buildGeneratedCode(string $code): string
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::buildGeneratedCode() instead.');
    return PiModRewriteUtil::buildGeneratedCode($code);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::setClientLanguageId()} instead.
 */
function mr_setClientLanguageId(int $clientId)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::setClientLanguageId() instead.');
    PiModRewriteUtil::setClientLanguageId($clientId);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteConfigurationService::loadConfiguration()} instead.
 */
function mr_loadConfiguration(int $clientId, bool $forceReload = false)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteConfigurationService::loadConfiguration() instead.');
    PiModRewriteConfigurationService::getInstance()->loadConfiguration($clientId, $forceReload);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteConfigurationService::getConfigurationFilePath()} instead.
 */
function mr_getConfigurationFilePath(int $clientId): string
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteConfigurationService::getConfigurationFilePath() instead.');
    return PiModRewriteConfigurationService::getInstance()->getConfigurationFilePath($clientId);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteConfigurationService::getConfiguration()} instead.
 */
function mr_getConfiguration(int $clientId): ?array
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteConfigurationService::getConfiguration() instead.');
    return PiModRewriteConfigurationService::getInstance()->getConfiguration($clientId);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteConfigurationService::setConfiguration()} instead.
 */
function mr_setConfiguration(int $clientId, array $config): bool
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteConfigurationService::setConfiguration() instead.');
    return PiModRewriteConfigurationService::getInstance()->setConfiguration($clientId, $config);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::runFrontendController()} instead.
 */
function mr_runFrontendController(): bool
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::runFrontendController() instead.');
    return PiModRewriteUtil::runFrontendController();
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::removeMultipleChars()} instead.
 */
function mr_removeMultipleChars(string $char, string $string): string
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::removeMultipleChars() instead.');
    return PiModRewriteUtil::removeMultipleChars($char, $string);
}

/**
 * @deprecated [2023-01-20] Since CONTENIDO 4.10.2, is not used anymore.
 */
function mr_i18n($key)
{
    global $lngAMR;
    return is_array($lngAMR) && isset($lngAMR[$key]) ? $lngAMR[$key] : 'n. a.';
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteDatabaseUtil::queryAndNextRecord()} instead
 */
function mr_queryAndNextRecord(string $query)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteDatabaseUtil::queryAndNextRecord() instead');
    return PiModRewriteDatabaseUtil::queryAndNextRecord($query);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::arrayValue()} instead
 */
function mr_arrayValue($array, $key, $default = NULL)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::arrayValue() instead');
    return PiModRewriteUtil::arrayValue($array, $key, $default);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteRequestUtil::cleanup()} instead
 */
function mr_requestCleanup(&$data, ?array $options = NULL)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteRequestUtil::cleanup() instead');
    return PiModRewriteRequestUtil::cleanup($data, $options);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteRequestUtil::getRequest()} instead
 */
function mr_getRequest(string $key, $default = NULL)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteRequestUtil::getRequest() instead');
    return PiModRewriteRequestUtil::getRequest($key, $default);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteUtil::responseHeader()} instead
 */
function mr_header(string $header)
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteUtil::responseHeader() instead');
    PiModRewriteUtil::responseHeader($header);
}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteDebugger::output()} instead
 */
function mr_debugOutput(bool $print = true): ?string
{
    cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewriteDebugger::output() instead');
    return PiModRewriteDebugger::output($print);
}
