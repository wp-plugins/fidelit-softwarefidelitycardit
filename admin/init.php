<?php

function fidelit_admin()
{
	if (!is_admin())
		return;
	
	ob_start();
	?>
	<div class="wrap" style="padding-top: 10px;">
		<?php include (dirname(__FILE__).'/header.php');?>
		<?php include (dirname(__FILE__).'/content.php');?>
		<?php include (dirname(__FILE__).'/footer.php');?>
	</div>
	<?
	echo ob_get_clean();
}

function fidelit_admin_init_menu()
{
	if (is_admin()) {
        add_menu_page('FidElit', 'FidElit', 'manage_options', 'fidelit', 'fidelit_admin', plugins_url('/images/favicon-bianca-16.png', __FILE__));
    }
}

function fidelit_admin_init() 
{

}

add_action('admin_menu', 'fidelit_admin_init_menu');
add_action('admin_init', 'fidelit_admin_init');
?>
