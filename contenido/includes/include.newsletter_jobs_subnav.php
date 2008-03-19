<?php
/*****************************************
* File      :   $RCSfile$
* Project   :   Contenido
* Descr     :   Custom subnavigation for the newsletters
* Modified  :   $Date$
*
* © four for business AG, www.4fb.de
*
* $Id$
******************************************/

if (isset($_GET['idnewsjob']) && (int)$_GET['idnewsjob'] > 0)
{
    $sCaption = i18n("View");
    $tmp_area = "foo2";

    # Set template data
    $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt);
    $tpl->set("d", "CLASS",     '');
    $tpl->set("d", "OPTIONS",   '');
    $tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=news_jobs&frame=4&idnewsjob=$idnewsjob").'">'.$sCaption.'</a>');
    $tpl->next();

    $sCaption = i18n("Details");
    $tmp_area = "foo2";

    # Set template data
    $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt);
    $tpl->set("d", "CLASS",     '');
    $tpl->set("d", "OPTIONS",   '');
    $tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=news_jobs&action=news_job_details&frame=4&idnewsjob=$idnewsjob").'">'.$sCaption.'</a>');
    $tpl->next();
        
    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

    # Generate the third navigation layer
    $tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["subnav"]);
} else {
    include ($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);
}

?>