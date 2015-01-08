<?php

include(dirname(__FILE__) . '/../../../../wp-load.php');
require_once(dirname(__FILE__)."/../common.php");

if (!isset($_POST['email']))
    $_POST['email'] = "";

if (!isset($_POST['codice_card']))
    $_POST['codice_card'] = "";

echo json_encode($WPFidElit->RecuperoPassword($_POST['email'], $_POST['codice_card']));