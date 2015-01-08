<?php

function fidelit_ultimi_movimenti_card($atts)
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
        'limite' => 25,
        'tipologia' => "",
        'note' => true
    ), $atts);

    ob_start();

    $movimenti = $WPFidElit->UltimiMovimentiCard($atts['card_id'], $atts['limite'], $atts['tipologia']);

    if ($movimenti['success'] && is_array($movimenti['data']))
    {
        $movimenti = $movimenti['data'];
        ?>
        <table width="100%" class="fidelit-movimenti-card">
            <thead>
                <tr>
                    <th>Operazione</th>
                    <th>Data ed ora</th>
                </tr>
            </thead>
            <tbody>
                <? if (count($movimenti) > 0) { ?>
                    <? foreach ($movimenti as $movimento) { ?>
                        <?
                        $operazione = "";

                        if ($movimento['tipologia'] == "P")
                            $operazione = $movimento['segno'] . $movimento['punti'] ." punti";
                        elseif ($movimento['tipologia'] == "S")
                            $operazione = $movimento['segno'] . number_format($movimento['sconti_accumulati'], 2, ",", ".") ." &euro;";
                        elseif ($movimento['tipologia'] == "C")
                            $operazione = $movimento['segno'] . number_format($movimento['credito_prepagato'], 2, ",", ".") ." &euro;";
                        ?>
                        <tr>
                            <td>
                                <?=$movimento['testo'];?> (<?=$operazione;?>)
                                <? if ($atts['note'] && !empty($movimento['note'])) { ?>
                                    <br />
                                    <small><?=$movimento['note'];?></small>
                                <? } ?>
                            </td>
                            <td><?=date("d/m/Y H:i", strtotime($movimento['dataora']));?></td>
                        </tr>
                    <? } ?>
                <? } else { ?>
                    <tr>
                        <td colspan="2">Questa card non ha movimenti</td>
                    </tr>
                <? } ?>
            </tbody>
        </table>
    <?
    }

    return ob_get_clean();
}

add_shortcode("fidelit_ultimi_movimenti_card", "fidelit_ultimi_movimenti_card");