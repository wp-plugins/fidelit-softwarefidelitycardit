<?php
/*
Plugin Name: FidElìt - SoftwareFidelityCard.it
Plugin URI: http://www.softwarefidelitycard.it
Description: Software gestionale completo per la fidelizzazione dei clienti.
Author: Gruppo Ambita Società Cooperativa
Author URI: http://www.gruppoambita.com
Version: 1.0
*/

include_once(dirname(__FILE__)."/common.php");

wp_enqueue_script("jquery");

include_once(dirname(__FILE__)."/admin/init.php");

// Install
function fidelit_on_install() { include(dirname(__FILE__) .'/install/install.php'); }
register_activation_hook(__FILE__, 'fidelit_on_install');

function fidelit_on_uninstall() { include(dirname(__FILE__) .'/install/uninstall.php'); }
register_activation_hook(__FILE__, 'fidelit_on_uninstall');

if (!is_admin() && get_option("fidelit_custom_css") != 1)
{
    wp_register_style("fidelit-css", plugins_url("/css/style.css", __FILE__), false, 0.1);
    wp_enqueue_style("fidelit-css");
}

if (get_option("fidelit_enable_html_email"))
{
    function fidelit_set_html_content_type() { return 'text/html'; }
    add_filter('wp_mail_content_type', 'fidelit_set_html_content_type');
}

foreach (glob(dirname(__FILE__)."/shortcodes/*.php") as $_fidelit_shortcode_file)
    include_once($_fidelit_shortcode_file);
?>