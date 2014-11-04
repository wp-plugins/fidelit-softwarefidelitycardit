<?php

class WPFidElit
{
    public $API = null;

    public function Loaded()
    {
        return (!is_null($this->API) && is_object($this->API));
    }

    public function __construct()
    {
        if (get_option("fidelit_api_key") != "" && get_option("fidelit_api_secret") != "")
        {
            try
            {
                $this->API = new FidApi(get_option("fidelit_api_key"), get_option("fidelit_api_secret"), dirname(__FILE__) . "/../cert/pbl.crt");

                $this->Init();
            }
            catch (fProgrammerException $e)
            {
                $this->API = null;

                if (true || WP_DEBUG)
                {
                    echo $e->getMessage() ."<br />";
                    echo $e->getTraceAsString() ."<br />";
                }
            }
        }
        else
            $this->API = null;
    }

    private function Init()
    {
        if (!session_id())
            add_action('init', 'session_start', 1);

        // Filters
        add_filter("fidelit_registrazione_mail_content_filter", array($this, "Registrazione_Mail_Content_Filter"));
        add_filter("fidelit_benvenuto_mail_content_filter", array($this, "Benvenuto_Mail_Content_Filter"));
        add_filter("fidelit_recupero_password_mail_content_filter", array($this, "Recupero_Password_Mail_Content_Filter"));
    }

    private function return_array($success, $msg, $data, $count, $other_array_items=null)
    {
        $ret = array(
            "success" => $success,
            "result" => $success,
            "msg" => $msg,
            "data" => $data,
            "count" => $count
        );

        if (!is_null($other_array_items) && is_array($other_array_items))
            $ret = array_merge($ret, $other_array_items);

        return $ret;
    }

    /**
     * Effettua il login sul sistema di fidelizzazione
     * @param $email
     * @param $passwd
     * @return array
     */
    public function Login($email, $passwd)
    {
        if (!$this->Loaded())
            return;

        if (isset($_SESSION['fidelit_login']))
            return $this->return_array(false, "Sei già loggato.", null, 0);

        $cliente = json_decode($this->API->route("Clienti/Get", array((stristr($email, "@") ? "cliente_email" : "card_codice") => $email)), true);

        if (!is_array($cliente) || !isset($cliente['data']) || !is_array($cliente['data']) || is_null($cliente['data']['dataora_attivazione_accesso_online']))
            return $this->return_array(false, "Login impossibile: prima di effettuare il login è necessario registrarsi al sito.", null, 0);

        $cliente = $cliente['data'];

        if (md5($passwd) == $cliente['passwd'])
        {
            if (is_null($cliente['dataora_attivazione_accesso_online']))
                return $this->return_array(false, "Login fallito: il tuo account non è ancora attivo. Puoi attivarlo cliccando sul link che ti abbiamo inviato in email quando ti sei registrato.", null, 0);

            unset($cliente['passwd']);

            @session_regenerate_id();

            $this->API->route("Clienti/Edit", array("id"=> $cliente['id'], "dataora_ultimo_accesso_online" => date("Y-m-d H:i:s")));
            $_SESSION['fidelit_login'] = serialize($cliente);

            return $this->return_array(true, "Login riuscito", $cliente, 1);
        }
        else
            return $this->return_array(false, "Login fallito: i dati inseriti non sono validi.", null, 0);
    }

    /**
     * Effettua il logout (sempre forzato)
     */
    public function Logout()
    {
        unset($_SESSION['fidelit_login']);
        session_destroy();
    }

    /**
     * Effettua la registrazione di un cliente, in base ai dati che arrivano tramite $_POST
     * @return array
     */
    public function Registrazione()
    {
        if (!$this->Loaded())
            return $this->return_array(false, "API FidElìt non inizializzate.", null, 0);

        if (isset($_SESSION['fidelit_login']))
            return $this->return_array(false, "Sei già registrato ed autenticato.", null, 0);

        $cliente = json_decode($this->API->route("Clienti/Get", array("card_codice" => $_POST['codice'])), true); // Cerco il profilo per codice card

        if (!is_array($cliente) || !isset($cliente['data']) || is_null($cliente['data']) || !is_array($cliente['data']))
            return $this->return_array(false, "Il numero di card inserito non esiste.", null, 0);

        $cliente = $cliente['data'];

        // VERIFICO CHE LA MAIL INSERITA NEL FORM NON APPARTENGA GIA' A QUALCHE ALTRO CLIENTE
        $cliente_mail = json_decode($this->API->route("Clienti/Get", array("email" => $_POST['email'])), true);
        $cliente_mail = (!is_array($cliente_mail) || !isset($cliente_mail['data']) || !is_array($cliente_mail['data'])) ? $cliente_mail['data'] : null;

        if (is_null($cliente_mail) || ($cliente_mail['email'] == "" || $cliente['id'] == $cliente_mail['id'])) // Verifica effettuata con successo
        {
            unset($cliente_mail);

            if ($cliente['dataora_attivazione_accesso_online'] != "")
                return $this->return_array(false, "Questo numero di card risulta già registrato. Se non ricordi i dati, prova ad utilizzare il recupero password.", null, 0);

            $cliente['nome'] = isset($_POST['nome']) ? $_POST['nome'] : $cliente['nome'];
            $cliente['cognome'] = isset($_POST['cognome']) ? $_POST['cognome'] : $cliente['cognome'];
            $cliente['email'] = $_POST['email'];
            $cliente['cellulare'] = $_POST['cellulare'];
            $cliente['avvisi_via_email'] = "Y";
            $cliente['passwd'] = md5($_POST['passwd']);

            $this->API->route("Clienti/Edit", $cliente);

            $cliente['passwd'] = $_POST['passwd'];

            wp_mail($cliente['email'], "Conferma registrazione - ". get_bloginfo("name"), apply_filters("fidelit_registrazione_mail_content_filter", $cliente));

            unset($email, $messaggio_email, $url_attivazione, $secure_code, $cliente['passwd']);

            return $this->return_array(true, "Registrazione riuscita. Ti abbiamo inviato un'email con un link per confermare la tua registrazione.", $cliente, 1);
        }
        else
            return $this->return_array(false, "L'email inserita è già in uso.", null, 0);
    }

    /**
     * Effettua la convalida di una registrazione in base al token inviato via email
     * @param $cliente_id L'id che identifica il cliente
     * @param $token Il token di convalida della registrazione
     * @return array
     */
    public function ConvalidaRegistrazione($cliente_id, $token)
    {
        if (!$this->Loaded())
            return $this->return_array(false, "API FidElìt non inizializzate.", null, 0);

        if (isset($_SESSION['fidelit_login']))
            return $this->return_array(false, "Sei autenticato.", null, 0);

        $cliente = json_decode($this->API->route("Clienti/Get", array("id" => $cliente_id)), true);

        if (!is_array($cliente) || !isset($cliente['data']) || !is_array($cliente['data']))
            return $this->return_array(false, "API FidElìt :: La funzione non ha risposto correttamente.", null, 0);

        $cliente = $cliente['data'];

        if (!empty($cliente['dataora_attivazione_accesso_online']))
            return $this->return_array(false, "Questo profilo &egrave; gi&agrave; registrato. Effettua il login oppure, se hai smarrito i dati, effettua un recupero password.", null, 0);
        elseif (md5($cliente['id']) . md5($cliente['card_id']) . md5($cliente['email']) == $token)
        {
            $this->API->route("Clienti/Edit", array("id" => $cliente['id'], "dataora_attivazione_accesso_online" => date("Y-m-d H:i:s")));
            wp_mail($cliente['email'], "Benvenuto - ". get_bloginfo("name"), apply_filters("fidelit_benvenuto_mail_content_filter", $cliente));

            return $this->return_array(true, "Registrazione completata con successo!", null, 0);
        }
        else
            return $this->return_array(false, "Non &egrave; stato possibile completare la registrazione poich&eacute; il codice di verifica non &egrave; corretto. Riprova ad effettuare la registrazione.", null, 0);
    }

    /**
     * Recupero password
     * @param $email
     * @param $codice_card
     * @return array
     */
    public function RecuperoPassword($email, $codice_card)
    {
        if (!$this->Loaded())
            return $this->return_array(false, "API FidElìt non inizializzate.", null, 0);

        if (isset($_SESSION['fidelit_login']))
            return $this->return_array(false, "Sei autenticato.", null, 0);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            return $this->return_array(false, "Il campo email è obbligatorio: verifica di aver inserito un'email valida.", null, 0);

        $codice_card_obbligatorio = (bool)get_option("fidelit_recupero_password_richiedi_codice_card");

        if ($codice_card_obbligatorio && empty($codice_card))
            return $this->return_array(false, "Il campo codice card è obbligatorio.", null, 0);

        $cliente = json_decode($this->API->route("Clienti/Get", array(($codice_card_obbligatorio ? "card_codice" : "cliente_email") => $codice_card)), true);

        if (!is_array($cliente) || !isset($cliente['data']) || is_null($cliente['data']) || !is_array($cliente['data']))
            return $this->return_array(false, "Account non trovato.", null, 0);

        $cliente = $cliente['data'];

        if ($codice_card_obbligatorio && $email != $cliente['email'])
            return $this->return_array(false, "ERRORE: per il recupero password devi inserire la stessa email usata in fase di registrazione.", null, 0);

        $nuova_password = fCryptography::randomString(8, 'numeric');

        if (is_null($cliente['dataora_attivazione_accesso_online']))
            $this->API->route("Clienti/Edit", array("id"=> $cliente['id'], "passwd" => md5($nuova_password), "dataora_attivazione_accesso_online" => date("Y-m-d H:i:s")));
        else
            $this->API->route("Clienti/Edit", array("id"=> $cliente['id'], "passwd" => md5($nuova_password)));

        $cliente['nuova_password'] = $nuova_password;

        wp_mail($cliente['email'], "Nuova password - ". get_bloginfo("name"), apply_filters("fidelit_recupero_password_mail_content_filter", $cliente));

        unset($cliente['nuova_password'], $cliente['passwd'], $cliente['password']);

        return $this->return_array(true, "La nuova password è stata inoltrata via email.", null, 0);
    }

    /**
     * Modifica un profilo in base ai dati che arrivano tramite $_POST
     * @return array
     */
    public function ModificaProfilo()
    {
        if (!$this->Loaded())
            return $this->return_array(false, "API FidElìt non inizializzate.", null, 0);

        if (!isset($_SESSION['fidelit_login']))
            return $this->return_array(false, "Non sei autenticato.", null, 0);

        $cliente = unserialize($_SESSION['fidelit_login']);

        $cliente['nome'] = !empty($_POST['nome']) ? $_POST['nome'] : $cliente['nome'];
        $cliente['cognome'] = !empty($_POST['cognome']) ? $_POST['cognome'] : $cliente['cognome'];
        $cliente['email'] = !empty($_POST['email']) ? $_POST['email'] : $cliente['email'];

        if (!empty($_POST['passwd']))
            $cliente['passwd'] = md5($_POST['passwd']);

        $cliente['cellulare'] = !empty($_POST['cellulare']) ? $_POST['cellulare'] : $cliente['cellulare'];
        $cliente['data_nascita'] = !empty($_POST['data_nascita']) ? date("Y-m-d", strtotime(str_replace("/", "-", $_POST['data_nascita']))) : $cliente['data_nascita'];
        $cliente['codice_fiscale'] = !empty($_POST['codice_fiscale']) ? $_POST['codice_fiscale'] : $cliente['codice_fiscale'];
        $cliente['indirizzo'] = !empty($_POST['indirizzo']) ? $_POST['indirizzo'] : $cliente['indirizzo'];
        $cliente['cap'] = !empty($_POST['cap']) ? $_POST['cap'] : $cliente['cap'];
        $cliente['citta'] = !empty($_POST['citta']) ? $_POST['citta'] : $cliente['citta'];
        $cliente['provincia'] = !empty($_POST['provincia']) ? $_POST['provincia'] : $cliente['provincia'];

        $result = json_decode($this->API->route("Clienti/Edit", $cliente), true);

        if (isset($result['success']))
        {
            if ($result['success'])
                $_SESSION['fidelit_login'] = serialize($cliente);

            return $this->return_array($result['success'], $result['msg'], $result['data'], $result['count']);
        }
        else
            return $this->return_array(false, "Il sistema API non ha risposto correttamente. Riprova tra qualche minuto.", null, 0);
    }

    /**
     * Recupera la situazione di una card loggata
     * @return array
     */
    public function SituazioneCard($card_id=null)
    {
        if (!$this->Loaded())
            return $this->return_array(false, "API FidElìt non inizializzate.", null, 0);

        if (!isset($_SESSION['fidelit_login']))
            return $this->return_array(false, "Non sei autenticato.", null, 0);

        $cliente = unserialize($_SESSION['fidelit_login']);

        if (is_null($card_id))
            $card_id = $cliente['card_id'];

        $result = json_decode($this->API->route("Card/Situazione", array("card_id" => $card_id)), true);

        if (isset($result['success']))
            return $this->return_array($result['success'], $result['msg'], $result['data'], $result['count']);
        else
            return $this->return_array(false, "Il sistema API non ha risposto correttamente. Riprova tra qualche minuto.", null, 0);
    }

    public function UltimiMovimentiCard($card_id=null, $limite=25, $tipologia="")
    {
        if (!$this->Loaded())
            return $this->return_array(false, "API FidElìt non inizializzate.", null, 0);

        if (!isset($_SESSION['fidelit_login']))
            return $this->return_array(false, "Non sei autenticato.", null, 0);

        $cliente = unserialize($_SESSION['fidelit_login']);

        if (is_null($card_id))
            $card_id = $cliente['card_id'];

        $result = json_decode($this->API->route("Card/Movimenti", array("card_id" => $card_id, "limit" => $limite, "tipologia"=> $tipologia)), true);

        if (isset($result['success']))
            return $this->return_array($result['success'], $result['msg'], $result['data'], $result['count']);
        else
            return $this->return_array(false, "Il sistema API non ha risposto correttamente. Riprova tra qualche minuto.", null, 0);
    }

    /**
     **
     * FILTERS
     **
     **/
    public function Registrazione_Mail_Content_Filter($cliente, $url_attivazione=null)
    {
        if (is_null($url_attivazione))
        {
            $url_attivazione = get_bloginfo("url") . add_query_arg(array(
                    "fidelit_cliente_id" => $cliente['id'],
                    "fidelit_reg_secure_code" => md5($cliente['id']) . md5($cliente['card_id']) . md5($_POST['email']),
                    "fidelit_return_uri" => $_POST['return_uri']
                ), $_REQUEST['return_uri']);
        }

        $messaggio_email = nl2br(str_ireplace(
            array("%%nome%%", "%%cognome%%", "%%email%%", "%%password%%", "%%passwd%%", "%%url_attivazione%%"),
            array(ucwords($cliente['nome']), ucwords($cliente['cognome']), $_POST['email'], $_POST['passwd'], $_POST['passwd'], $url_attivazione),
            stripslashes(get_option("fidelit_email_registrazione"))
        ));

        return $messaggio_email;
    }

    public function Benvenuto_Mail_Content_Filter($cliente)
    {
        $messaggio_email = nl2br(str_ireplace(
            array("%%nome%%", "%%cognome%%", "%%email%%"),
            array(ucwords($cliente['nome']), ucwords($cliente['cognome']), $_POST['email']),
            stripslashes(get_option("fidelit_email_benvenuto"))
        ));

        return $messaggio_email;
    }

    public function Recupero_Password_Mail_Content_Filter($cliente)
    {
        $messaggio_email = nl2br(str_ireplace(
            array("%%nome%%", "%%cognome%%", "%%email%%", "%%password%%"),
            array(ucwords($cliente['nome']), ucwords($cliente['cognome']), $cliente['email'], $cliente['nuova_password']),
            stripslashes(get_option("fidelit_email_recupero_password"))
        ));

        return $messaggio_email;
    }
}