<?php

/**
 * Backend action file con_saveart
 *
 * @package    Core
 * @subpackage Backend
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cPermission $perm
 * @var cDb $db
 * @var string $area
 * @var int $idcat
 * @var int $idart
 * @var int $lang
 * @var array $cfg
 * @var cGuiNotification $notification
 *
 * @var mixed $admin
 * @var int $tmp_firstedit
 * @var int|int[] $idcatnew
 * @var int $is_start
 * @var string $summary
 * @var string $created
 * @var string $lastmodified
 * @var string $author
 * @var int $online
 * @var string $datestart
 * @var string $dateend
 * @var int $artsort
 * @var string $urlname
 */

$idtpl = $idtpl ?? false;
$artspec = $artspec ?? '';
$online = isset($online) || isset($timemgmt) ? $online : false;
$searchable = $searchable ?? false;
$publishing_date = $publishing_date ?? false;
$locked = $locked ?? 0;
$is_start = $is_start ?? 0;
$sitemapprio = $sitemapprio ?? '0.5';
$changefreq = $changefreq ?? '0';

// remember old values to be passed to listeners of Contenido.Action.con_saveart.AfterCall
$oldData = [];

if (isset($title) && ($perm->have_perm_area_action($area, "con_edit") || $perm->have_perm_area_action_item($area, "con_edit", $idcat))  && ((int) $locked === 0 || $admin )) {

	// get idartlang
	if (!isset($idartlang) || $idartlang == 0) {
        $sql = 'SELECT `idartlang` FROM `%s` WHERE `idart` = %d AND `idlang` = %d';
		$db->query($sql, $cfg["tab"]["art_lang"], $idart, $lang);
		$db->nextRecord();
		$idartlang = $db->f("idartlang");
	}

    if (1 == $tmp_firstedit) {
        // insert article
        $idart = conEditFirstTime(
            $idcat, $idcatnew, $idart, $is_start, $idtpl, $idartlang, $lang, $title, $summary, $artspec,
            $created, $lastmodified, $author, $online, $datestart, $dateend, $artsort, 0, $searchable
        );

        // Set startarticle status
        conSetStartArticleHandler($idcatnew, $idcat, $is_start, $idart, $lang, $idartlang);

        // build notification
        $tmp_notification = $notification->returnNotification("ok", i18n("Changes saved"));

        // if meta-tag 'robots' is available add value 'index, follow'
        $availableTags = conGetAvailableMetaTagTypes();
        if (array_key_exists('robots', $availableTags)) {
            conSetMetaValue($idartlang, 'robots', 'index, follow');
        }
    } else {
        // get old article data that will be passed to
        // Contenido.Action.con_saveart.AfterCall chain handler
        $oArtLang = new cApiArticleLanguage(cSecurity::toInteger($idartlang));
        if ($oArtLang->isLoaded()) {

            // get array of idcats this article was related to
            $oCatArtColl = new cApiCategoryArticleCollection();
            $idcatold = $oCatArtColl->getCategoryIdsByArticleId($oArtLang->get('idart'));

            // get category language objects this article was related to
            $oCatLangColl = new cApiCategoryLanguageCollection();
            $oCatLangColl->setWhere('idlang', cRegistry::getLanguageId());
            $oCatLangColl->setWhere('idcat', implode(',', $idcatold), 'IN');
            $oCatLangColl->query();

            // determine if this article was start article of any related category
            $wasStart = false;
            while (false !== $categoryLanguage = $oCatLangColl->next()) {
                $wasStart |= $oArtLang->get('idartlang') == $categoryLanguage->get('startidartlang');
                if ($wasStart) {
                    break;
                }
            }

            // set old article data
            $oldData['idart']        = $oArtLang->get('idart');
            $oldData['idartlang']    = $oArtLang->get('idartlang');
            $oldData['lang']         = $oArtLang->get('idlang');
            $oldData['title']        = $oArtLang->get('title');
            $oldData['urlname']      = $oArtLang->get('urlname');
            $oldData['summary']      = $oArtLang->get('summary');
            $oldData['artspec']      = $oArtLang->get('artspec');
            $oldData['created']      = $oArtLang->get('created');
            $oldData['lastmodified'] = $oArtLang->get('lastmodified');
            $oldData['author']       = $oArtLang->get('author');
            $oldData['online']       = $oArtLang->get('online');
            $oldData['searchable']   = $oArtLang->get('searchable');
            $oldData['sitemapprio']  = $oArtLang->get('sitemapprio');
            $oldData['changefreq']   = $oArtLang->get('changefreq');
            $oldData['published']    = $oArtLang->get('published');
            $oldData['datestart']    = $oArtLang->get('datestart');
            $oldData['dateend']      = $oArtLang->get('dateend');
            $oldData['artsort']      = $oArtLang->get('artsort');
            $oldData['idtpl']        = $idtpl;
            $oldData['idcat']        = $idcat;
            $oldData['idcatnew']     = $idcatold;
            $oldData['is_start']     = $wasStart;
        }

        // update article
        conEditArt(
            $idcat, $idcatnew, $idart, $is_start, $idtpl, $idartlang, $lang, $title, $summary, $artspec,
            $created, $lastmodified, $author, $online, $datestart, $dateend, $publishing_date, $artsort, 0, $searchable
        );

        // Set startarticle status
        conSetStartArticleHandler($idcatnew, $idcat, $is_start, $idart, $lang, $idartlang);

        // build notification
        $tmp_notification = $notification->returnNotification("ok", i18n("Changes saved"));
    }
}

// CON-1493
if (isset($_POST['redirect_mode']) && ($_POST['redirect_mode'] === 'permanently' || $_POST['redirect_mode'] === 'temporary')) {
    $article = new cApiArticleLanguage($idartlang);
    $article->set('redirect_mode', ($_POST['redirect_mode']));
    $article->store();
}

$newData = [
    'idcat'        => $idcat,
    'idcatnew'     => $idcatnew,
    'idart'        => $idart,
    'is_start'     => $is_start,
    'idtpl'        => $idtpl,
    'idartlang'    => $idartlang,
    'lang'         => $lang,
    'title'        => $title,
    'urlname'      => $urlname,
    'summary'      => $summary,
    'artspec'      => $artspec,
    'created'      => $created,
    'lastmodified' => $lastmodified,
    'author'       => $author,
    'online'       => $online,
    'searchable'   => $searchable,
    'sitemapprio'  => $sitemapprio,
    'changefreq'   => $changefreq,
    'datestart'    => $datestart,
    'dateend'      => $dateend,
    'published'    => $publishing_date,
    'artsort'      => $artsort,
];
cApiCecHook::execute('Contenido.Action.con_saveart.AfterCall', $newData, $oldData);
