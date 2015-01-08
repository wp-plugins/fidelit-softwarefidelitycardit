<?php

function fidelit_shortcode_registrazione($atts)
{
    global $WPFidElit;

    if (!$WPFidElit->Loaded())
        return "FidElit - Verifica la configurazione";

    if (isset($_SESSION['fidelit_login']))
        return "";

    $atts = shortcode_atts(array(
        'popup' => false,
        'title' => null,
        'show_link_login' => true,
        'link_login' => null,
        'show_link_recupero_password' => true,
        'link_recupero_password' => null
    ), $atts);

    ob_start();

    if (isset($_GET['fidelit_cliente_id']) && $_GET['fidelit_cliente_id'] > 0)
    {
        if ($atts['popup'] !== true && isset($_GET['fidelit_reg_secure_code']) && $_GET['fidelit_reg_secure_code'] != "")
        {
            $result = $WPFidElit->ConvalidaRegistrazione((int)$_GET['fidelit_cliente_id'], $_GET['fidelit_reg_secure_code']);

            ?>
            <div class="fidelit-msg-<?=($result['success']) ? "success" : "error";?>">
                <?=$result['msg'];?>
                <? if (!empty($_GET['fidelit_return_uri'])) { ?>
                    <br />
                    <br />
                    <button onclick="location.href='<?=urldecode($_GET['fidelit_return_uri']);?>';">Vai al login</button>
                <? } ?>
            </div>
            <?
        }
        elseif ($atts['popup'] !== true && isset($_GET['fidelit_registrazione_completata']) && $_GET['fidelit_registrazione_completata'] == "true")
        {
            ?>
            <div class="fidelit-msg-success">
                Grazie per esserti registrato: accedi subito nel mondo di offerte a te riservate!
            </div>
            <?
        }
    }
    else
    {
        if ($atts['popup'] != true)
        {
            ?><div id="fidelit-registrati-ora"><?
        }

        if (!is_null($atts['title']))
            echo $atts['title'];
        elseif ($atts['popup']) { ?>
            <h2>Registrazione</h2>
        <? } ?>
        <form name="fidelit-registrazione" method="post" onsubmit="return false;">
            <input type="hidden" name="return_uri" value="<?= $_SERVER['REQUEST_URI']; ?>">
            <table style="width: 95%;">
                <tr>
                    <td>
                        <b>N&deg; Card (*)</b>
                        <input type="text" name="codice" title="N" style="width: 100%" required/>
                    </td>
                    <td>

                    </td>
                </tr>
                <tr>
                    <td>
                        <b>E-mail (*)</b><br/>
                        <input type="text" name="email" style="width: 100%" required/>
                    </td>
                    <td>
                        <b>Cellulare <? if (get_option("fidelit_celluare_obbligario") == 1) { ?>(*)<? } ?></b><br/>
                        <input type="text" name="cellulare" style="width: 100%"<? if (get_option("fidelit_celluare_obbligario") == 1) { ?> required<? } ?> />
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Password (*)</b><br/>
                        <input type="password" name="passwd" style="width: 100%" required/>
                    </td>
                    <td>
                        <b>Conferma password (*)</b><br/>
                        <input type="password" name="conferma_passwd" style="width: 100%" required/>
                    </td>
                </tr>
            </table>
            <div style="padding-right: 10px; text-align: right;">
                <span>Ho letto ed accetto i termini di <a href="#fidelit-registrati-ora-privacy" onclick="jQuery('#fidelit-registrati-ora-privacy').show();">privacy</a></span>&nbsp;
                <input type="checkbox" name="privacy" id="fidelit-registrazione-privacy-agree" value="Y"/>
            </div>
            <div style="text-align: right">
                <input type="button" onclick="do_fidelit_registrazione();" name="btn_send_reg" value="Registrati" style="margin-top: 10px; margin-right: 10px;"/>
                <div align="center" class="fidelit-loading" style="margin-top: 10px;"><img src="<?= plugin_dir_url(FIDELIT_PLUGIN_FILE); ?>images/loader.gif" border="0" alt=""/></div>
            </div>
        </form>
        <div id="fidelit-registrati-ora-privacy">
            <h2>Termini di Privacy</h2>

            <div class="fidelit-testo-privacy">
                <? echo nl2br(get_option("fidelit_privacy")); ?>
            </div>
            <div class="fidelit-btn-privacy">
                <button type="button" style="float: left;" class="back" onclick="jQuery('#fidelit-registrati-ora-privacy').hide()">Indietro</button>
                <button type="button" style="float: right;" onclick="jQuery('#fidelit-registrazione-privacy-agree').attr('checked', 'checked');jQuery('#fidelit-registrati-ora-privacy').hide()">Accetto la privacy</button>
            </div>
        </div>
        <script type="text/javascript">
            function do_fidelit_registrazione_loading_stop() {
                var _form = jQuery('form[name="fidelit-registrazione"]:eq(0)');
                _form.find(':input').attr("disabled", false);
                _form.find('[name="btn_send_reg"]').show();
                _form.find('[class="fidelit-loading"]').hide();
            }

            function do_fidelit_registrazione_loading_start() {
                var _form = jQuery('form[name="fidelit-registrazione"]:eq(0)');
                _form.find(':input').attr("disabled", true);
                _form.find('[name="btn_send_reg"]').hide();
                _form.find('[class="fidelit-loading"]').show();
            }

            function do_fidelit_registrazione() {
                var _form = jQuery('form[name="fidelit-registrazione"]:eq(0)');
                var data = _form.serializeArray();
                var dataSerialized = _form.serialize();
                var error_campi_obb = "";

                if (document.forms['fidelit-registrazione'].passwd.value != document.forms['fidelit-registrazione'].conferma_passwd.value)
                {
                    alert('Le password inserite non coincidono. Verificale.');
                    return;
                }

                for (var i = 0; i < data.length; i++)
                {
                    var _field = _form.find('[name="' + data[i].name + '"]:eq(0)');

                    if (_field.attr('required') && data[i].value == "")
                        error_campi_obb += "  - " + _field.parent().find('b:eq(0)').html() + "\n";
                }

                if (error_campi_obb != "")
                {
                    alert("Alcuni campi obbligatori non sono stati compilati:\n" + error_campi_obb + "\nCompilali per proseguire!");
                    return;
                }

                if (!jQuery('#fidelit-registrazione-privacy-agree').is(':checked'))
                {
                    alert('Per proseguire devi leggere ed accettare i nostri termini di privacy.');
                    return;
                }

                delete error_campi_obb;

                do_fidelit_registrazione_loading_start();

                jQuery.ajax({
                    type: 'post',
                    url: '<?=plugins_url("/json/registrazione.php", FIDELIT_PLUGIN_FILE);?>',
                    data: dataSerialized,
                    success: function (result) {
                        result = jQuery.parseJSON(result);

                        <? if ($atts['popup']) { ?>
                        if (result.success) {
                            jQuery('#fidelit-registrati-ora').hide();
                            jQuery('#fidelit-overlay').remove();
                        }
                        <? } ?>

                        alert(result.msg);

                        do_fidelit_registrazione_loading_stop();
                    }
                });
            }
        </script>
        <?
        if ($atts['popup'] !== true)
        {
            ?></div><?
        }
    }

    return ob_get_clean();
}

add_shortcode("fidelit_registrazione", "fidelit_shortcode_registrazione");