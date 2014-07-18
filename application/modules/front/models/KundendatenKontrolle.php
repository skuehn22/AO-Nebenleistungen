<?php
/**
 * Kontrolliert die Daten des Formulares Eingabe und Änderung Personendaten
 *
 * + Kontrolliert die Daten des Formulares Kundendaten
 * + Kontrolliert ob die mindestlänge eines String gegeben ist
 *
 * @date 09.09.13
 * @file KundendatenKontrolle.php
 * @package front
 * @subpackage model
 */
class Front_Model_KundendatenKontrolle
{
    // Fehler
    private $error_daten_unvollstaendig = 2060;
    private $error_daten_stimmen_nicht_ueberein = 2061;

    // Konditionen

    // Flags

    /**
     * Kontrolliert die Daten des Formulares Kundendaten
     *
     * @param array $params
     * @return array
     * @throws nook_Exception
     */
    public function kontrolleKundendaten(array $params)
    {
        // Kontrolle
        if ((empty($params['password'])) or ($params['password'] != $params['password_repeat'])) {
            throw new nook_Exception($this->error_daten_stimmen_nicht_ueberein);
        }

        // mappen und Kontrolle
        $daten = array(
            'title' => $this->validElement($params['title']),
            'firstname' => $this->validElement($params['firstname']),
            'lastname' => $this->validElement($params['lastname']),
            'company' => $this->validElement($params['company']),
            'country' => $params['country'],
            'street' => $this->validElement($params['street']),
            'zip' => $this->validElement($params['zip']),
            'city' => $this->validElement($params['city']),
            'password' => $this->validElement($params['password']),
            'phonenumber' => $params['phonenumber'],
            'schriftwechsel' => $params['schriftwechsel']
        );

        $filter = array(
            'schriftwechsel' => array(
                'filter' => FILTER_VALIDATE_INT
            )
        );

        $kontrollArray = filter_var_array($daten, $filter);

        return $daten;
    }

    /**
     * Kontrolliert ob die mindestlänge eines String gegeben ist
     *
     * + Länge des String kann individuell übergeben werden
     *
     * @param $element
     * @return bool
     */
    private function validElement($element, $laenge = 3)
    {
//        if (strlen($element) < $laenge) {
//            throw new nook_Exception($this->error_daten_unvollstaendig);
//        }

        $test = 123;

        return $element;
    }

}
