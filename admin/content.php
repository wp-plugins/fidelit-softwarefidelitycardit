<?
if (isset($_GET['act']) && $_GET['act'] == "submit")
{
	if (isset($_GET['tab']))
	{
		if ($_GET['tab'] == "api")
		{
			update_option("fidelit_url_piattaforma", $_POST['url_piattaforma']);
			update_option("fidelit_api_secret", $_POST['api_secret']);
			update_option("fidelit_api_key", $_POST['api_key']);
			update_option("fidelit_api_azienda_id", $_POST['api_azienda_id']);
			update_option("fidelit_api_punto_vendita_id", $_POST['api_punto_vendita_id']);

            if (isset($_FILES['certificati']) && isset($_FILES['certificati']['name']) && pathinfo($_FILES['certificati']['name'], PATHINFO_EXTENSION) == "zip" && $_FILES['certificati']['size'] < 8000)
            {
                $crt_zip = new ZipArchive();
                if ($crt_zip->open($_FILES['certificati']['tmp_name']))
                {
                    if (!file_exists(dirname(__FILE__)."/../cert"))
                        @mkdir(dirname(__FILE__)."/../cert");
                    else
                    {
                        @unlink(dirname(__FILE__) ."/../cert/pbl.crt");
                        @unlink(dirname(__FILE__) ."/../cert/pbl.csr");
                    }

                    $crt_zip->extractTo(dirname(__FILE__)."/../cert", array("pbl.crt", "pbl.csr"));
                    $crt_zip->close();
                }

                @unlink($_FILES['certificati']['tmp_name']);
                unset($crt_zip);
            }
		}
        elseif ($_GET['tab'] == "configurazioni")
        {
            update_option("fidelit_enable_html_email", ($_POST['enable_html_email'] == "Y"));
            update_option("fidelit_custom_css", ($_POST['custom_css'] == "Y"));
            update_option("fidelit_celluare_obbligatorio", ($_POST['celluare_obbligatorio'] == "Y"));
            update_option("fidelit_recupero_password_richiedi_codice_card", ($_POST['recupero_password_richiedi_codice_card'] == "Y"));
        }
        elseif ($_GET['tab'] == "testi")
        {
            update_option("fidelit_privacy", $_POST['privacy']);
            update_option("fidelit_email_registrazione", $_POST['email_registrazione']);
            update_option("fidelit_email_benvenuto", $_POST['email_benvenuto']);
            update_option("fidelit_email_recupero_password", $_POST['email_recupero_password']);
        }
		elseif ($_GET['tab'] == "avanzate")
		{
			update_option("fidelit_db_hostname", $_POST['db_hostname']);
			update_option("fidelit_db_port", $_POST['db_port']);
			update_option("fidelit_db_name", $_POST['db_name']);
			update_option("fidelit_db_username", $_POST['db_username']);
			update_option("fidelit_db_password", $_POST['db_password']);
		}
	}
}

$fidelit_admin_active_tab = isset($_GET['tab']) ? urldecode($_GET['tab']) : "api";

if (!file_exists(dirname(__FILE__)."/../FidApi_SDK/FidApi.php") || !file_exists(dirname(__FILE__)."/../FidApi_SDK/flourishlib/"))
{
    if (!isset($_GET['download_sdk']))
    {
        ?>
        <div class="error" style="padding: 8px;">
            Prima di poter configurare il plugin, devi scaricare l'ultima versione del FidEl&iacute;t SDK.<br />
            <br />
            <a class="button" href="admin.php?page=fidelit&download_sdk=1">Clicca qui per scaricarlo automaticamente</a><br />
        </div>
        <?
    }
    else
    {
        ?>
        <div class="error" style="padding: 8px;">
            <ul>
                <li>Download avviato...</li>
                <?
                $sdk_dw_ch = curl_init();
                curl_setopt($sdk_dw_ch, CURLOPT_URL, "http://api.sistemafidelity.com/sdk/php_FidApi_SDK.zip");
                curl_setopt($sdk_dw_ch, CURLOPT_RETURNTRANSFER, 1);
                $data = curl_exec($sdk_dw_ch);
                curl_close ($sdk_dw_ch);
                unset($sdk_dw_ch);
                ?>
                <li>Download completato. Salvo il file...</li>
                <?
                $sdk_zip_file = fopen(dirname(__FILE__)."/../FidApi_SDK/php_FidApi_SDK.zip", "w+");
                fputs($sdk_zip_file, $data);
                fclose($sdk_zip_file);
                unset($sdk_zip_file);
                ?>
                <li>Scompatto l'archivio...</li>
                <?
                $sdk_zip = new ZipArchive();
                if ($sdk_zip->open(dirname(__FILE__)."/../FidApi_SDK/php_FidApi_SDK.zip"))
                {
                    $sdk_zip->extractTo(dirname(__FILE__)."/../");
                    $sdk_zip->close();
                    ?><li>Archivio scompattato con successo. Aggiornamento della pagina in corso...</li><?
                }
                else
                {
                    ?><li>Impossibile scompattare l'archivio. Riprova tra qualche minuto.</li><?
                }

                unlink(dirname(__FILE__)."/../FidApi_SDK/php_FidApi_SDK.zip");
                ?>
            </ul>
            <script>
                setTimeout(function() { window.location = 'admin.php?page=fidelit'; }, 1500);
            </script>
        </div>
        <?
    }
}
else
{
?>
<h2 class="nav-tab-wrapper">
    <a class="nav-tab<? if ($fidelit_admin_active_tab == "api") echo " nav-tab-active";?>" href="?page=fidelit&tab=api">API</a>
    <a class="nav-tab<? if ($fidelit_admin_active_tab == "configurazioni") echo " nav-tab-active";?>" href="?page=fidelit&tab=configurazioni">Configurazioni</a>
    <a class="nav-tab<? if ($fidelit_admin_active_tab == "testi") echo " nav-tab-active";?>" href="?page=fidelit&tab=testi">Testi</a>
    <a class="nav-tab<? if ($fidelit_admin_active_tab == "avanzate") echo " nav-tab-active";?>" href="?page=fidelit&tab=avanzate">Avanzate</a>
</h2>
<br />
<? if ($fidelit_admin_active_tab == "api") { ?>
    <form action="admin.php?page=fidelit&tab=api&act=submit" method="post" enctype="multipart/form-data">
        <fieldset style="border: 1px solid #ccc; padding: 10px;">
            <legend><b>API</b></legend>
            <table style="width: 100%;">
                <tr>
                    <td style="width: 180px;" valign="top"><label for="fidelit_api_secret">Certificati <span style="color: red">*</span></label></td>
                    <td>
                        <input type="file" name="certificati">
                        <? if (!file_exists(dirname(__FILE__)."/../cert/pbl.crt") || !file_exists(dirname(__FILE__)."/../cert/pbl.csr")) { ?>
                            <span style="color: red">(caricare il file zip generato dal software FidEl&iacute;t)</span>
                        <? } else { ?>
                            <span style="color: green">(aggiorna i certificati con un nuovo file zip generato dal software FidEl&iacute;t)</span>
                        <? } ?>
                    </td>
                </tr>
                <tr>
                    <td style="width: 180px;" valign="top"><label for="fidelit_api_secret">URL piattaforma <span style="color: red">*</span></label></td>
                    <td><textarea name="url_piattaforma" id="fidelit_url_piattaforma" style="width: 100%; height: 70px;" class="required"><?php echo get_option("fidelit_url_piattaforma"); ?></textarea></td>
                </tr>
                <tr>
                    <td style="width: 180px;" valign="top"><label for="fidelit_api_secret">API Secret <span style="color: red">*</span></label></td>
                    <td><textarea name="api_secret" id="fidelit_api_secret" style="width: 100%; height: 70px;" class="required"><?php echo get_option("fidelit_api_secret"); ?></textarea></td>
                </tr>
                <tr>
                    <td style="width: 180px;"><label for="fidelit_api_key">API Key <span style="color: red">*</span></label></td>
                    <td><input name="api_key" id="fidelit_api_key" type="text" style="width: 100%;" value="<?php echo get_option("fidelit_api_key"); ?>" class="required" /></td>
                </tr>
                <tr>
                    <td style="width: 180px;"><label for="fidelit_api_azienda_id">ID Azienda (default) <span style="color: red">*</span></label></td>
                    <td><input name="api_azienda_id" id="fidelit_api_azienda_id" type="text" style="width: 100%;" value="<?php echo get_option("fidelit_api_azienda_id"); ?>" class="required" /></td>
                </tr>
                <tr>
                    <td style="width: 180px;"><label for="fidelit_api_punto_vendita_id">ID Punto Vendita (default) <span style="color: red">*</span></label></td>
                    <td><input name="api_punto_vendita_id" id="fidelit_api_punto_vendita_id" type="text" style="width: 100%;" value="<?php echo get_option("fidelit_api_punto_vendita_id"); ?>" class="required" /></td>
                </tr>
            </table>
            <br />
            <div style="overflow: hidden;margin: 0 0 10px 0;">
                <div style="float:right;"><button type="submit" id="wpscf_form_save" class="button-primary">Salva</button></div>
            </div>
        </fieldset>
    </form>
    <br />
<? } ?>
<? if ($fidelit_admin_active_tab == "configurazioni") { ?>
    <form action="admin.php?page=fidelit&tab=configurazioni&act=submit" method="post">
        <fieldset style="border: 1px solid #ccc; padding: 10px;">
            <legend><b>Configurazione del plugin</b></legend>
            <table style="width: 100%;">
                <tr>
                    <td>
                        <input type="checkbox" name="enable_html_email" <? if (get_option("fidelit_enable_html_email")) echo ' checked="checked"'; ?> class="required" value="Y">
                        <label>Abilita su Wordpress l'invio di e-mail in HTML</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="custom_css" <? if (get_option("fidelit_custom_css")) echo ' checked="checked"'; ?> class="required" value="Y">
                        <label>Usa un CSS personalizzato (esclude quello di FidElìt)</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="celluare_obbligatorio" <? if (get_option("fidelit_celluare_obbligatorio")) echo ' checked="checked"'; ?> class="required" value="Y">
                        <label>Cellulare obbligatorio in fase di registrazione</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="recupero_password_richiedi_codice_card" <? if (get_option("fidelit_recupero_password_richiedi_codice_card")) echo ' checked="checked"'; ?> class="required" value="Y">
                        <label>Richiedi il codice card (obbligatorio), insieme all'email, nel recupero password</label>
                    </td>
                </tr>
            </table>
            <br />
            <div style="overflow: hidden;margin: 0 0 10px 0;">
                <div style="float:right;"><button type="submit" id="wpscf_form_save" class="button-primary">Salva</button></div>
            </div>
        </fieldset>
    </form>
    <br />
<? } ?>
<? if ($fidelit_admin_active_tab == "testi") { ?>
    <form action="admin.php?page=fidelit&tab=testi&act=submit" method="post">
        <fieldset style="border: 1px solid #ccc; padding: 10px;">
            <legend><b>E-mail predefinite che partiranno dal sistema in automatico</b></legend>
            <table style="width: 100%;">
                <tr>
                    <td style="width: 180px;" valign="top"><label>Privacy <span style="color: red">*</span></label></td>
                    <td><textarea name="privacy" style="width: 100%; height: 200px;" class="required"><?php echo htmlentities(stripslashes(get_option("fidelit_privacy"))); ?></textarea></td>
                </tr>
                <tr>
                    <td style="width: 180px;" valign="top"><label>E-mail registrazione <span style="color: red">*</span></label></td>
                    <td><textarea name="email_registrazione" style="width: 100%; height: 200px;" class="required"><?php echo htmlentities(stripslashes(get_option("fidelit_email_registrazione"))); ?></textarea></td>
                </tr>
                <tr>
                    <td style="width: 180px;" valign="top"><label>E-mail benvenuto <span style="color: red">*</span></label></td>
                    <td><textarea name="email_benvenuto" style="width: 100%; height: 200px;" class="required"><?php echo htmlentities(stripslashes(get_option("fidelit_email_benvenuto"))); ?></textarea></td>
                </tr>
                <tr>
                    <td style="width: 180px;" valign="top"><label>E-mail recupero password <span style="color: red">*</span></label></td>
                    <td><textarea name="email_recupero_password" style="width: 100%; height: 200px;" class="required"><?php echo htmlentities(stripslashes(get_option("fidelit_email_recupero_password"))); ?></textarea></td>
                </tr>
            </table>
            <br />
            <div style="overflow: hidden;margin: 0 0 10px 0;">
                <div style="float:right;"><button type="submit" id="wpscf_form_save" class="button-primary">Salva</button></div>
            </div>
        </fieldset>
    </form>
    <br />
<? } ?>
<? if ($fidelit_admin_active_tab == "avanzate") { ?>
    <form action="admin.php?page=fidelit&tab=avanzate&act=submit" method="post">
        <fieldset style="border: 1px solid #ccc; padding: 10px;">
            <legend><b>Accesso diretto al Database (funzionalit&agrave; opzionale che potrebbe non essere inclusa nel tuo abbonamento)</b></legend>
            <table>
                <tr>
                    <td style="width: 180px;" valign="top"><label for="fidelit_db_hostname">Host del server <span style="color: red">*</span></label></td>
                    <td><input name="db_hostname" id="fidelit_db_hostname" type="text" style="width: 100%;" value="<?php echo get_option("fidelit_db_hostname"); ?>" class="required" /></td>
                </tr>
                <tr>
                    <td style="width: 180px;"><label for="fidelit_db_port">Porta del server <span style="color: red">*</span></label></td>
                    <td><input name="db_port" id="fidelit_db_port" type="text" style="width: 100%;" value="<?php echo get_option("fidelit_db_port"); ?>" class="required" /></td>
                </tr>
                <tr>
                    <td style="width: 180px;"><label for="fidelit_db_name">Nome del database <span style="color: red">*</span></label></td>
                    <td><input name="db_name" id="fidelit_db_name" type="text" style="width: 100%;" value="<?php echo get_option("fidelit_db_name"); ?>" class="required" /></td>
                </tr>
                <tr>
                    <td style="width: 180px;"><label for="fidelit_db_username">Nome utente <span style="color: red">*</span></label></td>
                    <td><input name="db_username" id="fidelit_db_username" type="text" style="width: 100%;" value="<?php echo get_option("fidelit_db_username"); ?>" class="required" /></td>
                </tr>
                <tr>
                    <td style="width: 180px;"><label for="fidelit_db_password">Password <span style="color: red">*</span></label></td>
                    <td><input name="db_password" id="fidelit_db_password" type="password" style="width: 100%;" value="<?php echo get_option("fidelit_db_password"); ?>" class="required" /></td>
                </tr>
            </table>
            <br />
            <div style="overflow: hidden;margin: 0 0 10px 0;">
                <div style="float:right;"><button type="submit" id="wpscf_form_save" class="button-primary">Salva</button></div>
            </div>
        </fieldset>
    </form>
    <br />
<? } ?>
<? } ?>