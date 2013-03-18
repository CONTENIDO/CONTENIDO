<?php
 print_r($_POST);
 print_r($_GET);

// echo unserialize($_POST['content']);
$idcat = $_POST['idcat'];
$idart = $_POST['idart'];
$collection = new ArticleForumCollection();
if (isset($_POST['realname'])) {

    $idcat = $_POST['idcat'];
    $idart = $_POST['idart'];

    $right = new ArticleForumRightBottom("content");

    if (isset($_POST['action']) && $_POST['action'] != NULL)
        switch ($_POST['action']) {

            case 'online_toggle':
                echo 'online_toggle';
                $right->toggleOnlineState($_POST['online'], $_POST['id_user_forum']);

                echo $_POST['online'];
                break;

            case 'update':
                $collection->updateValues($_POST['id_user_forum'], $_POST['realname'], $_POST['email'], $_POST['like'], $_POST['dislike'], $_POST['forum'], $_POST['online'], $_POST['onlineState']);
                break;
            default:
                throw new Exception('$_POST["action"] type ' . $_POST["action"] . ' not implemented');
        }
    if ($_POST['mode'] === 'list') {
        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();


        $test = $right->getForum($idcat, $idart, $lang);
    } else {
        $test = $right->getEditModeMenu($_POST);
    }
    $test->render();
}

if (isset($_GET['idart']) && $_GET['idart'] !== NULL) {

    $cfg = cRegistry::getConfig();
    $client = cRegistry::getClientId();
    $lang = cRegistry::getLanguageId();
    $idart = $_GET['idart'];
    $idcat = $_GET['idcat'];
 //   echo "$idart". "<br>";
 //   echo "$idcat". "<br>";
   // die();

    global $area;
    $right = new ArticleForumRightBottom("content");
    if (isset($_GET['id_user_forum']) && isset($_GET['action'])) {

        $action = $_GET["action"];
        switch ($action) {

            case 'online_toggle':
                $collection->toggleOnlineState($_GET['online'], $_GET['id_user_forum']);
                break;
            case 'deleteComment':
               // ArticleForumCollection::deleteHierarchie($_GET['key'], $_GET['level'], $idart, $idcat, $lang);
                $collection->deleteHierarchie($_GET['key'], $_GET['level'], $idart, $idcat, $lang);
                $right->render();
                break;
            default:
                throw new Exception('$_GET["action"] type ' . $_GET["action"] . ' not implemented');
        }
    }
    $cfg = cRegistry::getConfig();
    $client = cRegistry::getClientId();
    $lang = cRegistry::getLanguageId();


    $test = $right->getForum($idcat, $idart, $lang);
    $test->render();
}

?>