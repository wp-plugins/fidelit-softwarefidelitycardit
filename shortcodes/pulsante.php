<?

function fidelit_pulsante($atts)
{
    $atts = shortcode_atts(array(
        'login' => false,
        'testo' => "BOTTONE SENZA TESTO",
        'class' => 'fidelit-btn',
        'style' => '',
        'href' => '',
        'onclick' => ''
    ), $atts);

    if (($atts['login'] && isset($_SESSION['fidelit_login'])) || (!$atts['login'] && !isset($_SESSION['fidelit_login'])) || empty($atts['login']))
        return '<a href="'. fHTML::encode($atts['href']) .'" onclick="'. fHTML::encode($atts['onclick']) .'" style="'. fHTML::encode($atts['style']) .'" class="'. fHTML::encode($atts['class']) .'">'. $atts['testo'] .'</a>';
}

add_shortcode("fidelit_pulsante", "fidelit_pulsante");

?>