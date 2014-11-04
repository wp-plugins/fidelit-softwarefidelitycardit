<?php

function fidelit_situazione_card($atts)
{
    global $WPFidElit;

    if (!$WPFidElit->Loaded())
        return "FidElit - Verifica la configurazione";

    if (!isset($_SESSION['fidelit_login']))
        return "";

    $cliente = unserialize($_SESSION['fidelit_login']);

    if (!isset($atts['card_id']) && empty($cliente['card_id']))
        return "";

    $atts = shortcode_atts(array(
        'card_id' => $cliente['card_id'],
        'punti' => true,
        'sconti' => true,
        'credito' => true,
        'acquisti_ripetuti' => true
    ), $atts);

    ob_start();

    $situazione = $WPFidElit->SituazioneCard();

    if ($situazione['success'] && is_array($situazione['data']))
    {
        $situazione = $situazione['data'];

        ?>
        <table width="100%" class="fidelit-situazione-card">
            <tr>
                <? if ($atts['punti'] === true) { ?>
                    <td>
                        <small>PUNTI</small><br />
                        <?=$situazione['punti'];?>
                    </td>
                <? } ?>
                <? if ($atts['sconti'] === true) { ?>
                    <td>
                        <small>SCONTI</small><br />
                        &euro;&nbsp;<?=number_format($situazione['sconti'], 2, ",", ".");?>
                    </td>
                <? } ?>
                <? if ($atts['credito'] === true) { ?>
                    <td>
                        <small>CREDITO</small><br />
                        &euro;&nbsp;<?=number_format($situazione['credito'], 2, ",", ".");?>
                    </td>
                <? } ?>
                <? if ($atts['acquisti_ripetuti'] === true) { ?>
                    <td>
                        <small>ACQUISTI RIPETUTI</small><br />
                        <?=$situazione['acquisti_ripetuti'];?>
                    </td>
                <? } ?>
            </tr>
        </table>
        <?
    }

    return ob_get_clean();
}

add_shortcode("fidelit_situazione_card", "fidelit_situazione_card");