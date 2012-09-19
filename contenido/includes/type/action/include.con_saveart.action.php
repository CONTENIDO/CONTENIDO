<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * con_saveart action
 *
 * @package    CONTENIDO Backend Includes
 * @version    0.0.1
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

if (!isset($idtpl)) {
  $idtpl = false;
}

if (!isset($artspec)) {
  $artspec = "";
}

if (!isset($online)) {
  $online = false;
}

if (!isset($searchable)) {
  $searchable = false;
}

if (isset($title)) {
    if (1 == $tmp_firstedit) {
        $idart = conEditFirstTime($idcat, $idcatnew, $idart, $is_start, $idtpl, $idartlang, $lang, $title, $summary, $artspec, $created, $lastmodified, $author, $online, $datestart, $dateend, $artsort, 0, $searchable, $sitemapprio, $changefreq);
        $tmp_notification = $notification->returnNotification("info", i18n("Changes saved"));

        if (!isset($idartlang)) {
            $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart = $idart AND idlang = $lang";
            $db->query($sql);
            $db->next_record();
            $idartlang = $db->f("idartlang");
        }

        if (in_array($idcat, $idcatnew)) {
            $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat = '".$idcat."' AND idart = '".$idart."'";

            $db->query($sql);
            $db->next_record();

            $tmp_idcatart = $db->f("idcatart");

            if ($is_start == 1) {
                conMakeStart($tmp_idcatart, $is_start);
            }

            if (!isset($is_start)) {
                $sql = "SELECT * FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '$idcat' AND idlang = '$lang' AND startidartlang != '0' ";
                $db->query($sql);
                if ($db->next_record()) {
                    $tmp_startidartlang = $db->f('startidartlang');
                    if ($idartlang == $tmp_startidartlang) {
                        conMakeStart($tmp_idcatart, 0);
                    }
                } else {
                    conMakeStart($tmp_idcatart, 0);
                }
            }
        }

        if (is_array($idcatnew)) {
            foreach ($idcatnew as $idcat) {
                $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat = $idcat AND idart = $idart";

                $db->query($sql);
                $db->next_record();

                conSetCodeFlag( $db->f("idcatart") );
            }
        }

    } else {
        conEditArt($idcat, $idcatnew, $idart, $is_start, $idtpl, $idartlang, $lang, $title, $summary, $artspec, $created, $lastmodified, $author, $online, $datestart, $dateend, $artsort, 0, $searchable, $sitemapprio, $changefreq);

        $tmp_notification = $notification->returnNotification("info", i18n("Changes saved"));

        if (!isset($idartlang)) {
            $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart = $idart AND idlang = $lang";
            $db->query($sql);
            $db->next_record();
            $idartlang = $db->f("idartlang");
        }

        if (is_array($idcatnew)) {
            if (in_array($idcat, $idcatnew)) {
                $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat = '".$idcat."' AND idart = '".$idart."'";

                $db->query($sql);
                $db->next_record();

                $tmp_idcatart = $db->f("idcatart");

                if ($is_start == 1) {
                    conMakeStart($tmp_idcatart, $is_start);
                }

                if (!isset($is_start)) {
                    $sql = "SELECT * FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '$idcat' AND idlang = '$lang' AND startidartlang != '0' ";
                    $db->query($sql);
                    if ($db->next_record()) {
                        $tmp_startidartlang = $db->f('startidartlang');
                        if ($idartlang == $tmp_startidartlang) {
                            conMakeStart($tmp_idcatart, 0);
                        }
                    } else {
                        conMakeStart($tmp_idcatart, 0);
                    }
                }
            }
        }

        if (is_array($idcatnew)) {
            foreach ($idcatnew as $idcat) {
                $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat = $idcat AND idart = $idart";

                $db->query($sql);
                $db->next_record();

                conSetCodeFlag($db->f("idcatart"));
            }
        }
    }
}

cApiCecHook::execute("Contenido.Action.con_saveart.AfterCall", array(
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
    'sitemapprio'   => $sitemapprio,
    'changefreq'   => $changefreq,
    'datestart'    => $datestart,
    'dateend'      => $dateend,
    'artsort'      => $artsort
));
