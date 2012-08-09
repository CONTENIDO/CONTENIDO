<?php
/**
 * Description: XING output
 *
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created unknown
 *   $Id$
 * }}
 */

// Profile url
$url = "CMS_VALUE[0]";

// Big button or small button?
$look = "CMS_VALUE[1]";

// Name of user
$name = "CMS_VALUE[2]";

if ($url != '' && $look != '') {
    $tpl = new cTemplate();

    $tpl->set('s', 'NAME', $name);
    $tpl->set('s', 'URL' , $url);

    if ($look == 'small') {
        $tpl->generate('xing_small.html');
    } elseif ($look == 'big') {
        $tpl->generate('xing_big.html');
    }
}

?>