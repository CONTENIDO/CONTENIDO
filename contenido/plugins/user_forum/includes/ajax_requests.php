<?php

    if(isset($_GET['action']) && $_GET['action']!= NULL)
    {
        $action = $_GET['action'];

        switch ($action) {

            case 'toggle_online':

            default:
                throw new Exception('$_GET["action"] type ' . $_GET["action"] . ' not implemented');
        }
    }

?>