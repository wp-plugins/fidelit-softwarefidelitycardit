<?php

function fidelit_logout($atts)
{
    global $WPFidElit;

    if (!$WPFidElit->Loaded())
        return "FidElit - Verifica la configurazione";

    $atts = shortcode_atts(array(
        'style' => "",
        'class' => "fidelit-btn-logout"
    ), $atts);

    if (isset($_SESSION['fidelit_login']))
    {
        ob_start();
        ?>
        <a href="#" style="<?=fHTML::encode($atts['style']);?>" class="<?=fHTML::encode($atts['class']);?>" onclick="jQuery.ajax({ url: '<?=plugins_url("/json/logout.php", FIDELIT_PLUGIN_FILE);?>', success: function() { location.reload(); } });">Esci</a>
        <?
        return ob_get_clean();
    }

    return "";
}

add_shortcode("fidelit_logout", "fidelit_logout");