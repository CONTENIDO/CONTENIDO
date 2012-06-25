<?php

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

# Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav_blank']);
?>