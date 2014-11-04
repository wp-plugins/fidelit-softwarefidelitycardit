<?php

$plugin_version = '1.0';

define("FIDELIT_PLUGIN_FILE", __FILE__);
define("FIDELIT_PLUGIN_PATH", plugin_dir_path(__FILE__));

include_once(dirname(__FILE__)."/libs/GoogleMap.php");
include_once(dirname(__FILE__)."/libs/JSMin.php");

if (function_exists("spl_autoload_register"))
{
    spl_autoload_register(function($class_name)
    {
        $base_root = dirname(__FILE__) . "/FidApi_SDK/";

        if (file_exists($base_root . "/flourishlib/" . $class_name . ".php"))
            include_once($base_root . "/flourishlib/" . $class_name . ".php");
        elseif (file_exists($base_root . "/" . $class_name . ".php"))
            include_once($base_root . "/" . $class_name . ".php");
    });
}
else
{
    function __autoload($class_name)
    {
        $base_root = dirname(__FILE__) . "/FidApi_SDK/";

        if (file_exists($base_root . "/flourishlib/" . $class_name . ".php"))
            include_once($base_root . "/flourishlib/" . $class_name . ".php");
        elseif (file_exists($base_root . "/" . $class_name . ".php"))
            include_once($base_root . "/" . $class_name . ".php");
    }
}

include_once(dirname(__FILE__)."/FidApi_SDK/WPFidElit.class.php");

$WPFidElit = new WPFidElit();