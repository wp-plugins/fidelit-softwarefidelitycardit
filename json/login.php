<?php

include(dirname(__FILE__) . '/../../../../wp-load.php');
require_once(dirname(__FILE__)."/../common.php");

echo json_encode($WPFidElit->Login($_POST['email'], $_POST['passwd']));