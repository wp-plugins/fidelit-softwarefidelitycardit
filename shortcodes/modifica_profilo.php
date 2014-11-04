<?php

function fidelit_modifica_profilo($atts)
{
    global $WPFidElit;

    if (!$WPFidElit->Loaded())
        return "FidElit - Verifica la configurazione";

    if (!isset($_SESSION['fidelit_login']))
        return "";

    $atts = shortcode_atts(array(
        'button_style' => "",
        'button_class' => "fidelit-btn"
    ), $atts);

    $cliente = unserialize($_SESSION['fidelit_login']);

    ob_start();
    ?>
    <form method="post" name="fidelit-modifica-profilo" onsubmit="return false;">
        <table style="width: 100%">
            <tr>
                <td style="font-weight: bold;">Nome (*)</td>
                <td><input name="nome" class="nome input_text" type="text" value="<?=ucwords($cliente['nome'])?>" maxlength="255" <? if (get_option("fidelit_celluare_obbligario") == 1) { ?>required="required"<? } ?> /></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Cognome (*)</td>
                <td><input name="cognome" class="cognome input_text" type="text" value="<?=ucwords($cliente['cognome'])?>" maxlength="255" <? if (get_option("fidelit_celluare_obbligario") == 1) { ?>required="required"<? } ?> /></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Email (*)</td>
                <td><input name="email" class="email input_text" type="text" value="<?=$cliente['email']?>" maxlength="255" <? if (get_option("fidelit_celluare_obbligario") == 1) { ?>required="required"<? } ?> /></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Password (*)</td>
                <td><input name="passwd" class="input_text" type="password" value="" maxlength="255" autocomplete="off" <? if (get_option("fidelit_celluare_obbligario") == 1) { ?>required="required"<? } ?> /></td>
            </tr>
            <tr>
                <td>Cellulare <? if (get_option("fidelit_celluare_obbligario") == 1) { ?>(*)<? } ?></td>
                <td><input name="cellulare" class="input_text" type="text" value="<?=$cliente['cellulare']?>" maxlength="255" <? if (get_option("fidelit_celluare_obbligario") == 1) { ?>required="required"<? } ?> /></td>
            </tr>
            <tr>
                <td>Data di nascita</td>
                <td><input name="data_nascita" class="input_text" type="text" placeholder="dd/mm/YYYY" value="<?=(!is_null($cliente['data_nascita'])) ? date("d/m/Y", strtotime($cliente['data_nascita'])) : ""?>" maxlength="255" /></td>
            </tr>
            <tr>
                <td>Codice fiscale</td>
                <td><input name="codice_fiscale" class="input_text" type="text" value="<?=strtoupper($cliente['codice_fiscale'])?>" maxlength="255" /></td>
            </tr>
            <tr>
                <td>Indirizzo</td>
                <td><input name="indirizzo" class="input_text" type="text" value="<?=$cliente['indirizzo']?>" maxlength="255" style="width: 100%;" /></td>
            </tr>
            <tr>
                <td>Cap</td>
                <td><input name="cap" class="input_text" type="text" value="<?=$cliente['cap']?>" maxlength="255" /></td>
            </tr>
            <tr>
                <td>Citt&agrave;</td>
                <td><input name="citta" class="input_text" type="text" value="<?=ucfirst($cliente['citta'])?>" maxlength="255" /></td>
            </tr>
            <tr>
                <td>Provincia</td>
                <td><input name="provincia" class="input_text" type="text" value="<?=strtoupper($cliente['provincia'])?>" maxlength="255" /></td>
            </tr>
        </table>
        <div align="center">
            <a style="<?=fHTML::encode($atts['button_style']);?>" class="<?=fHTML::encode($atts['button_class']);?>" onclick="fidelit_submit_modifica_profilo();" name="btn-modifica-profilo">Salva modifiche al profilo</a>
            <div align="center" class="fidelit-loading" style="margin-top: 10px;"><img src="<?= plugin_dir_url(FIDELIT_PLUGIN_FILE); ?>images/loader.gif" border="0" alt=""/></div>
        </div>
    </form>
    <script>
        function do_fidelit_modifica_profilo_loading_stop() {
            var _form = jQuery('form[name="fidelit-modifica-profilo"]:eq(0)');
            _form.find(':input').attr("disabled", false);
            _form.find('[name="btn-modifica-profilo"]').show();
            _form.find('[class="fidelit-loading"]').hide();
        }

        function do_fidelit_modifica_profilo_loading_start() {
            var _form = jQuery('form[name="fidelit-modifica-profilo"]:eq(0)');
            _form.find(':input').attr("disabled", true);
            _form.find('[name="btn-modifica-profilo"]').hide();
            _form.find('[class="fidelit-loading"]').show();
        }

        function fidelit_submit_modifica_profilo()
        {
            var _form = jQuery('form[name="fidelit-modifica-profilo"]:eq(0)');
            var data = _form.serializeArray();
            var dataSerialized = _form.serialize();
            var error_campi_obb = "";

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

            do_fidelit_modifica_profilo_loading_start();

            jQuery.ajax({
                type: 'post',
                url: '<?=plugins_url("/json/modifica_profilo.php", FIDELIT_PLUGIN_FILE);?>',
                data: dataSerialized,
                success: function(result)
                {
                    result = jQuery.parseJSON(result);

                    if (result.success)
                        location.reload();
                    else
                        alert(result.msg);

                    do_fidelit_modifica_profilo_loading_stop();
                }
            });
        }
    </script>
    <?
    return ob_get_clean();
}

add_shortcode("fidelit_modifica_profilo", "fidelit_modifica_profilo");