<?php
require_once "../../HTML/Form.php";

$form = new HTML_Form($_SERVER['PHP_SELF']);

$form->addText("name", "What's your name?");
$form->addText("email", "What's your email address?");
$form->addPassword("password", "Please enter the desired password");
$form->addPlaintext("Tip", "Your password should be hard to guess");
$form->addSubmit("submit", "Submit");

$form->display();

$form = new HTML_Form($_SERVER['PHP_SELF']);

$form->displayText("name", "What's your name?");
$form->displayText("name", "What's your name?");
$form->displayText("name", "What's your name?");
$form->displayText("name", "What's your name?");
$form->displayText("name", "What's your name?");

#$form = new HTML_Form($_SERVER['PHP_SELF']);
#$str = $form->returnPassword("password", "Choose a password");
#var_dump($str);

?>
