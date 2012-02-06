<?php
/**
* $RCSfile$
*
* Description: Newslist / ArticleList. Module "Output".
*
* @version 1.1.0
* @author Andreas Lindner
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2005-08-12
* modified 2009-01-16 Rudi Bieller Added new Contenido_Url for creating URLs
* }}
*
* $Id$
*/

cInclude('includes', 'functions.api.string.php');
cInclude('includes', 'functions.api.images.php');

if (!isset($tpl) || !is_object($tpl)) {
    $tpl = new Template();
}

$tpl->reset();

$htmlpath = $cfgClient[$client]['path']['htmlpath'];
$frontendpath = $cfgClient[$client]['path']['frontend'];

// selected category
$selcat = "CMS_VALUE[1]";
$template = "teaser-standard.html";
// anzahl der zeichen text
$mxtext = 200;

$limit = "CMS_VALUE[15]";

$cms_sort_direction = "CMS_VALUE[16]";
if ($cms_sort_direction == '') {
    $cms_sort_direction = 'desc';
}

if ("CMS_VALUE[3]" == "sortdate") {
    $order = 'lastmodified';
} else {
    $order = 'artsort';
}

if ("CMS_VALUE[17]" != '') {
    $with_start = true;
} else {
    $with_start = false;
}

$newsheadline = "CMS_VALUE[4]";

$tpl->set('s', 'TITLE', $newsheadline);

if (strlen($selcat) > 0 && $selcat != '0') {
    $options = array(
        'idcat' => $selcat, 'start' => $with_start, 'order' => $order, 'direction' => $cms_sort_direction
    );

    $list = new ArticleCollection($options);

    $count = $list->count;

    if ($count > 0) {
        if (is_numeric($limit) && strlen($limit) > 0) {
            if ($limit < $list->count) {
                $limit_art = $limit;
            } else {
                $limit_art = $list->count;
            }
        } else {
            $limit_art = $list->count;
        }

        for ($i = 0; $i < $limit_art; $i ++) {

            $article = $list->nextArticle();

            $article_id = $article->getField('idart');

            $teaser_img = '';
            if ($noimg != 'true') {
                $text_html = $article->getContent('CMS_HTML', 1);

                $regEx = "/<img[^>]*?>.*?/i";
                $match = array ();
                preg_match($regEx, $text_html, $match);

                $regEx = "/(src)(=)(['\"]?)([^\"']*)(['\"]?)/i";
                $img = array ();
                preg_match($regEx, $match[0], $img);
                $img_src = preg_split("/\//", $img[0]);

                $img_name = $img_src[count($img_src) - 1];
                $img_name = preg_replace("/\"/", '', $img_name);
                $img_split = preg_split("/\./", $img_name);
                $img_type = $img_split[count($img_split) - 1];

                $img_split2 = preg_split("/_/", $img_split[0]);

                $name = $img_name;

                if (count($img_split2) > 1) {
                    $img_x = $img_split2[count($img_split2) - 1];
                    $img_y = $img_split2[count($img_split2) - 2];

                    if (is_numeric($img_x) && is_numeric($img_y)) {
                        $suffix = '_' . $img_x . '_' . $img_y . '.' . $img_type;
                        $name = preg_replace("/$suffix/", '', $img_name);
                        $name = $name . '.[a-zA-Z]{3}';
                    }
                }

                $img_teaser = '';

                if (strlen($name) > 0) {
                    $sql = "SELECT * FROM " . $cfg['tab']['upl'] . " WHERE filename REGEXP '$name'";
                    //echo "<pre>"; print_r($sql); echo "</pre>";
                    $db->query($sql);
                    if ($db->next_record()) {
                        $filename = $db->f('filename');
                        $dirname = $db->f('dirname');
                    }

                    $img_path = $cfgClient[$client]['upl']['path'] . $dirname . $filename;

                    $img_size = "CMS_VALUE[14]";

                    $img_teaser = capiImgScale($img_path, $img_size, $img_size, $crop = false, $expand = false, $cacheTime = 1000, $wantHQ = false);
                } // end if strlen

                if (strlen($img_teaser) > 0) {
                    $teaser_img = '<img src="'.$img_teaser.'" class="teaser_img">';
                } else {
                    $teaser_img = '';
                }

            } // end if noimg

            $headline = strip_tags($article->getContent('CMS_HTMLHEAD', 1));
            $headline = str_replace($replace, ' ', $headline);

            /*          $subheadline = strip_tags($article->getContent('CMS_HTMLHEAD', 2));
                        $subheadline = str_replace($replace, " ", $subheadline);*/

            $teaserheadline = /*$subheadline."&nbsp;-&nbsp;".*/
            $headline;
            // this is just for sample client - modify to your needs!
            if ($cfg['url_builder']['name'] == 'front_content' || $cfg['url_builder']['name'] == 'MR') {
                $aParams = array('lang' => $lang, 'idcat' => $selcat, 'idart' => $article_id);
            } else {
                $aParams = array('b' => array('lang' => $lang, 'idcat' => $selcat, 'idart' => $article_id),
                                'idcat' => $selcat, // needed to build category path
                                'lang' => $lang, // needed to build category path
                                'level' => 1); // needed to build category path
            }
            try {
                $href = Contenido_Url::getInstance()->build($aParams);
            } catch (InvalidArgumentException $e) {
                $href = $sess->url("front_content.php?idcat=$selcat&amp;idart=$article_id");
            }
            $teasertext = $article->getField('summary');

            if (strlen(trim($teasertext)) == 0) {
                $teasertext = strip_tags($article->getContent('CMS_HTML', 1));
                $teasertext2 = $teasertext;
                $teasertext = capiStrTrimAfterWord($teasertext, $mxtext);
                if ($teasertext != $teasertext2) {
                    $teasertext .= '...';
                }
            }

            $teasertext = $teasertext . '&nbsp;';

            $tpl->set('d', 'HEADLINE', $teaserheadline);
            $tpl->set('d', 'TEXT', $teasertext);
            $tpl->set('d', 'HREF', $href);
            $tpl->set('d', 'IMG', $teaser_img);
            $tpl->set('d', 'MORE', mi18n("mehr"));

            $tpl->next();

        } // end for

        $tpl->generate('templates/'.$template);

    }
}
?>