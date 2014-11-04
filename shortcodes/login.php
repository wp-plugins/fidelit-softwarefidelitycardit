<?php

function fidelit_login($atts)
{
    global $WPFidElit;

    if (!$WPFidElit->Loaded())
        return "FidElit - Verifica la configurazione";

    if (isset($_SESSION['fidelit_login']))
        return '';

    $atts = shortcode_atts(array(
        'title' => null,
        'show_link_registrazione' => true,
        'link_registrazione' => null,
        'show_link_recupero_password' => true,
        'link_recupero_password' => null
    ), $atts);

    ob_start();

    if (empty($atts['link_registrazione']) && isset($_GET['fidelit_cliente_id']) && $_GET['fidelit_cliente_id'] > 0 && isset($_GET['fidelit_reg_secure_code']) && $_GET['fidelit_reg_secure_code'] != "")
    {
        $atts['show_link_registrazione'] = false;

        echo do_shortcode("[fidelit_registrazione show_link_login=false show_link_recupero_password=false popup=true]");
    }
    else
    {
        if (!is_null($atts['title']))
            echo $atts['title'];
        ?>
        <form name="fidelit-login" onsubmit="return false;">
            <table width="100%">
                <tr>
                    <td><input type="text" name="email" placeholder="Email, Codice Card" style="width: 100%"/></td>
                    <td><input type="password" name="passwd" placeholder="Password" style="width: 100%;"/></td>
                    <td>
                        <a name="btn_send_login" onclick="fidelit_submit_login()" class="button">Login</a>
                        <div align="center" class="fidelit-loading" style="margin-top: 10px;"><img src="<?= plugin_dir_url(FIDELIT_PLUGIN_FILE); ?>images/loader.gif" border="0" alt=""/></div></div>
                    </td>
                </tr>
                <? if ($atts['show_link_registrazione'] === true || $atts['show_link_recupero_password'] === true) { ?>
                    <tr>
                        <? if ($atts['show_link_registrazione'] === true) { ?>
                            <td align="right">
                                <a href="<?= !empty($atts['link_registrazione']) ? $atts['link_registrazione'] : "#fidelit-registrati-ora"; ?>" onclick="<? if (empty($atts['link_registrazione'])) echo 'show_fidelit_registrazione();'; ?>">Registrati ora</a>
                            </td>
                        <? } ?>
                        <? if ($atts['show_link_recupero_password'] === true) { ?>
                            <td align="right" colspan="<?= ($atts['show_link_registrazione'] === true) ? "2" : "3"; ?>">
                                <a href="<?= !empty($atts['link_recupero_password']) ? $atts['link_recupero_password'] : "#fidelit-recupero-password"; ?>" onclick="<? if (empty($atts['link_recupero_password'])) echo 'show_fidelit_recupero_password();'; ?>">Hai dimenticato la password?</a>
                            </td>
                        <? } ?>
                    </tr>
                <? } ?>
            </table>
        </form>
        <?
        if ($atts['show_link_recupero_password'] === true)
        {
            ?>
            <div id="fidelit-recupero-password">
                <? echo do_shortcode("[fidelit_recupero_password popup=true]"); ?>
            </div>
        <?
        }


        if ($atts['show_link_registrazione'] === true)
        {
            ?>
            <div id="fidelit-registrati-ora">
                <? echo do_shortcode("[fidelit_registrazione show_link_login=false show_link_recupero_password=false popup=true]"); ?>
            </div>
            <?
        }
        ?>
        <script type="text/javascript">
            <? if (empty($atts['link_registrazione'])) { ?>
                function show_fidelit_registrazione()
                {
                    var ov = jQuery('#fidelit-overlay');

                    if (ov.length == 0)
                    {
                        jQuery('<div id="fidelit-overlay"></div>').click(function() {
                            jQuery('#fidelit-overlay').remove();
                            jQuery('#fidelit-registrati-ora').hide();
                        }).appendTo('body');
                    }

                    jQuery('#fidelit-registrati-ora').detach().appendTo('body').show();
                }
            <? } ?>

            <? if (empty($atts['link_recupero_password'])) { ?>
                function show_fidelit_recupero_password()
                {
                    var ov = jQuery('#fidelit-overlay');

                    if (ov.length == 0)
                    {
                        jQuery('<div id="fidelit-overlay"></div>').click(function() {
                            jQuery('#fidelit-overlay').remove();
                            jQuery('#fidelit-recupero-password').hide();
                        }).appendTo('body');
                    }

                    jQuery('#fidelit-recupero-password').detach().appendTo('body').show();
                }
            <? } ?>

            function do_fidelit_submit_login_loading_stop()
            {
                var _form = jQuery('form[name="fidelit-login"]:eq(0)');
                _form.find(':input').attr("disabled", false);
                _form.find('[name="btn_send_login"]').show();
                _form.find('[class="fidelit-loading"]').hide();
            }

            function do_fidelit_submit_login_loading_start()
            {
                var _form = jQuery('form[name="fidelit-login"]:eq(0)');
                _form.find(':input').attr("disabled", true);
                _form.find('[name="btn_send_login"]').hide();
                _form.find('[class="fidelit-loading"]').show();
            }

            function fidelit_submit_login()
            {
                var _f = jQuery("form[name='fidelit-login']");
                var _dataSerialized = _f.serialize();

                if (_f.find('[name="email"]:eq(0)').val().trim() == "" || _f.find('[name="passwd"]:eq(0)').val().trim() == "")
                {
                    alert('E-mail e password sono campi obbligatori.');
                    return false;
                }

                do_fidelit_submit_login_loading_start();

                jQuery.ajax({
                    type: 'post',
                    url: '<?=plugins_url("/json/login.php", FIDELIT_PLUGIN_FILE);?>',
                    data: _dataSerialized,
                    success: function (result) {
                        result = jQuery.parseJSON(result);

                        if (result.success != true)
                            alert(result.msg);
                        else
                        {
                            location.reload();
                            return;
                        }

                        do_fidelit_submit_login_loading_stop();
                    }
                });

                return true;
            }
        </script>
        <?
    }

    return ob_get_clean();
}

add_shortcode("fidelit_login", "fidelit_login");