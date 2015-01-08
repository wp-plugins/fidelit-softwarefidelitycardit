<?php

function fidelit_recupero_password($atts)
{
    global $WPFidElit;

    if (!$WPFidElit->Loaded())
        return "FidElit - Verifica la configurazione";

    if (isset($_SESSION['fidelit_login']))
        return '';

    $atts = shortcode_atts(array(
        'popup' => false,
        'title' => null
    ), $atts);

    ob_start();

    if (!is_null($atts['title']))
        echo $atts['title'];
    ?>
    <form name="fidelit-recupero-password" onsubmit="return false;">
        <table width="100%">
            <tr>
                <td><input type="email" name="email" placeholder="Indirizzo e-mail" required style="width: 100%"/></td>
                <td><input type="text" name="codice_card" placeholder="Codice card" <? if (get_option("fidelit_recupero_password_richiedi_codice_card")) { ?>required<? } ?> style="width: 100%;"/></td>
                <td>
                    <a name="btn_send_recupero_password" onclick="fidelit_submit_recupero_password()" class="button">Recupera</a>
                    <div align="center" class="fidelit-loading" style="margin-top: 10px;"><img src="<?= plugin_dir_url(FIDELIT_PLUGIN_FILE); ?>images/loader.gif" border="0" alt=""/></div></div>
                </td>
            </tr>
        </table>
    </form>
    <script type="text/javascript">
        function do_fidelit_submit_recupero_password_loading_stop()
        {
            var _form = jQuery('form[name="fidelit-recupero-password"]:eq(0)');
            _form.find(':input').attr("disabled", false);
            _form.find('[name="btn_send_recupero_password"]').show();
            _form.find('[class="fidelit-loading"]').hide();
        }

        function do_fidelit_submit_recupero_password_loading_start()
        {
            var _form = jQuery('form[name="fidelit-recupero-password"]:eq(0)');
            _form.find(':input').attr("disabled", true);
            _form.find('[name="btn_send_recupero_password"]').hide();
            _form.find('[class="fidelit-loading"]').show();
        }

        function fidelit_submit_recupero_password()
        {
            var _f = jQuery("form[name='fidelit-recupero-password']");
            var _dataSerialized = _f.serialize();

            if (_f.find('[name="email"]:eq(0)').val().trim() == ""<? if (get_option("fidelit_recupero_password_richiedi_codice_card")) { ?> || _f.find('[name="codice_card"]:eq(0)').val().trim() == ""<? } ?>)
            {
                alert(<? if (get_option("fidelit_recupero_password_richiedi_codice_card")) { ?>'E-mail e codice card sono campi obbligatori.'<? } else { ?>'L\'email è obbligatoria.'<? } ?>);
                return false;
            }

            do_fidelit_submit_recupero_password_loading_start();

            jQuery.ajax({
                type: 'post',
                url: '<?=plugins_url("/json/recupero_password.php", FIDELIT_PLUGIN_FILE);?>',
                data: _dataSerialized,
                success: function (result) {
                    result = jQuery.parseJSON(result);

                    do_fidelit_submit_recupero_password_loading_stop();

                    alert(result.msg);

                    <? if ($atts['popup']) { ?>
                        if (result.success) {
                            jQuery('#fidelit-recupero-password').hide();
                            jQuery('#fidelit-overlay').remove();
                        }
                    <? } ?>
                }
            });

            return true;
        }
    </script>
    <?

    return ob_get_clean();
}

add_shortcode("fidelit_recupero_password", "fidelit_recupero_password");