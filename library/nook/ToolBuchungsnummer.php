<?php
/**
 * Ermittelt und handelt die Buchungsnummer
 *
 * + Ermittelt die Buchungsnummer und gibt diese zurück
 * + Ermittelt den Buchungshinweis einer Buchung
 * + Gibt den Buchungshinweis einer Session in Rohform / Raw zurück
 * + Ermittelt Gruppenname einer Buchung
 * + Findet die Anzahl der Programmbuchungen einer Buchungsnummer
 * + Findet die Anzahl der Hotelbuchungen einer Buchungsnummer
 * + Findet alle Buchungsnummern eines Kunden.
 * + Ermittelt die Session ID einer Buchungsnummer
 * + Ermittelt ob ein Superuser für diese Buchung vorhanden ist.
 * + Filtert aus dem Array die Id der Buchungsnummern
 * + Umkopieren der Buchungsnummern
 * + Registriert in der Session , Session Namespace 'buchungsnummer'
 *
 * @date 04.26.2013
 * @file ToolBuchungsnummer.php
 * @package tools
 */
class nook_ToolBuchungsnummer
{

    /**
     * Ermittelt die Buchungsnummer und gibt diese zurück
     *
     * + an Hand der Session ID.
     * + Wenn nicht vorhanden wird 'false' zurückgegeben
     *
     * @static
     * @return bool
     */
    public static function findeBuchungsnummer()
    {
        $sessionId = Zend_Session::getId();
        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        $select = $tabelleBuchungsnummer->select();
        $select->where("session_id = '" . $sessionId . "'");

        $query = $select->__toString();

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if (count($rows) == 0)
            $buchungsnummer = false;
        elseif (count($rows) > 1)
            $buchungsnummer = false;
        else
            $buchungsnummer = $rows[0]['id'];

        return $buchungsnummer;
    }

    /**
     * Ermittelt den Buchungshinweis einer Buchung
     *
     * @return mixed
     */
    public static function getBuchungshinweis($buchungsNummerId = false)
    {
        $sessionId = Zend_Session::getId();
        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        $select = $tabelleBuchungsnummer->select();

        if (!empty($buchungsNummerId) and !empty($zaehler)) {
            $select->where("id = " . $buchungsNummerId);
        } else {
            $select->where("session_id = '" . $sessionId . "'");
        }

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();
        $buchungshinweis = $rows[0]['buchungshinweis'];
        $buchungshinweis = trim($buchungshinweis);

        if (!empty($buchungsNummerId) and !empty($buchungshinweis)) {
            $teile = explode("\n", $buchungshinweis);

            return $teile;
        }

        return false;
    }

    /**
     * Gibt den Buchungshinweis einer Session in Rohform / Raw zurück
     *
     * + Zeilenumbrüche werden in Leerzeichen umgewandelt
     *
     * @return string
     */
    public static function getBuchungshinweisRaw()
    {
        $sessionId = Zend_Session::getId();
        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        $select = $tabelleBuchungsnummer->select();
        $select->where("session_id = '" . $sessionId . "'");

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();
        $buchungshinweis = $rows[0]['buchungshinweis'];
        $buchungshinweis = trim($buchungshinweis);
        $buchungshinweis = str_replace("\n", ' ', $buchungshinweis);

        return $buchungshinweis;
    }

    /**
     * Ermittelt Gruppenname einer Buchung
     *
     * aus Tabelle 'tbl_buchungsnummer'
     * der aktuellen Session.
     *
     * @see Application_Model_DbTable_buchungsnummer
     * @return string
     */
    public static function getGruppenname()
    {
        $sessionId = Zend_Session::getId();
        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        $cols = array(
            'gruppenname'
        );

        $select = $tabelleBuchungsnummer->select();
        $select->from($tabelleBuchungsnummer, $cols)->where("session_id = '" . $sessionId . "'");
        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        $gruppenname = trim($rows[0]['gruppenname']);

        return $gruppenname;

    }

    /**
     * Findet die Anzahl der Programmbuchungen einer Buchungsnummer
     *
     * @param $__buchungsnummer
     * @return bool
     * @throws Exception
     */
    public static function existierenProgrammbuchungen($__buchungsnummer)
    {

        $__buchungsnummer = (int) $__buchungsnummer;
        if (!is_int($__buchungsnummer) and $__buchungsnummer > 0) {
            throw new Exception('Buchungsnummer keine Int');
        }

        $where = "buchungsnummer_id = " . $__buchungsnummer;
        $cols = array(
            'programmdetails_id'
        );

        /** @var $tabelleProgrammbuchung */
        $tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();
        $select = $tabelleProgrammbuchung->select();
        $select->from($tabelleProgrammbuchung, $cols)->where($where);

        $rows = $tabelleProgrammbuchung->fetchAll($select)->toArray();
        if (count($rows) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Findet die Anzahl der Hotelbuchungen einer Buchungsnummer
     *
     * @param $__buchungsnummer
     * @return bool
     * @throws Exception
     */
    public static function existierenHotelbuchungen($__buchungsnummer)
    {

        $__buchungsnummer = (int) $__buchungsnummer;
        if (!is_int($__buchungsnummer) and $__buchungsnummer > 0) {
            throw new Exception('Buchungsnummer keine Int');
        }

        $where = "buchungsnummer_id = " . $__buchungsnummer;
        $cols = array(
            'propertyId'
        );

        /** @var $tabelleHotelbuchung */
        $tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        $select = $tabelleHotelbuchung->select();
        $select->from($tabelleHotelbuchung, $cols)->where($where);

        $rows = $tabelleHotelbuchung->fetchAll($select)->toArray();
        if (count($rows) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Findet alle Buchungsnummern eines Kunden.
     *
     * + Kunden ID muss vorhanden sein
     *
     * @param $__kundenId
     * @param bool $__datum
     * @return array
     */
    public static function findeAlleBuchungsnummernEinesKunden($__kundenId)
    {

        $cols = array(
            'id'
        );

        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer(array( 'db' => 'front' ));
        $select = $tabelleBuchungsnummer->select();
        $select->from($tabelleBuchungsnummer, $cols)->where("kunden_id = " . $__kundenId);

        $buchungsnummern = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        return $buchungsnummern;
    }

    /**
     * Ermittelt die Session ID einer Buchungsnummer
     *
     * @param $buchungsnummer
     * @return string
     */
    public static function findeSessionIdEinerBuchungsnummer($buchungsnummer)
    {
        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer(array( 'db' => 'front' ));
        $rows = $tabelleBuchungsnummer->find($buchungsnummer)->toArray();

        return $rows[0]['session_id'];
    }

    /**
     * Ermittelt ob ein Superuser für diese Buchung vorhanden ist.
     *
     * + Ist ein Superuser vorhanden ist der Rückgabewert 'false',
     * + ansonsten wird eine Onlinebuchung vorgenommen. Der Rückgabewert bei einer Onlinebuchung ist 'true'
     *
     * @param $__buchungsNummer
     * @return bool
     */
    public static function findeOnlineBuchung($__buchungsNummer)
    {
        $statusOnlineBuchung = true;

        $cols = array(
            'superuser_id'
        );

        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer(array( 'db' => 'front' ));
        $select = $tabelleBuchungsnummer->select();

        $select
            ->from($tabelleBuchungsnummer, $cols)
            ->where("id = " . $__buchungsNummer);

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if (is_null($rows[0]['superuser_id'])) {
            $statusOnlineBuchung = false;
        }

        return $statusOnlineBuchung;
    }

    /**
     * Filtert aus dem Array die Id der Buchungsnummern
     *
     * @param $buchungsnummernIdArray
     * @return array
     */
    public static function filternBuchungsnummern($buchungsnummernIdArray)
    {
        $buchungsnummern = array();

        for ($i = 0; $i < count($buchungsnummernIdArray); $i++) {
            $buchungsnummern[] = $buchungsnummernIdArray[$i]['id'];
        }

        return $buchungsnummern;
    }

    /**
     * Umkopieren der Buchungsnummern, es wurde eine neue Session ID bereits angelegt
     *
     * + momentane Session ID
     * + bestimme Daten der alte Buchungsnummer
     * + bestimmen neue Buchungsnummer
     * + alte Buchungsnummer löschen
     * + neuanlegen Buchungsnummer
     * + umkopierte Buchungsnummer
     *
     * @param $alteBuchungsnummer
     *
     */
    public static function umkopierenBuchungsnummer($alteBuchungsnummer)
    {
        // momentane Session ID
        $momentaneSessionId = Zend_Session::getId();

        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer(array( 'db' => 'front' ));

        // bestimme Daten der alte Buchungsnummer
        $alteBuchung = $tabelleBuchungsnummer->find($alteBuchungsnummer)->toArray();

        // wenn kein Eintrag in 'tbl_buchungsnummer'
        if (count($alteBuchung) <> 1)
            return $alteBuchungsnummer;

        // wenn keine neue Buchungsnummer
        if ($momentaneSessionId == $alteBuchung[0]['session_id'])
            return $alteBuchungsnummer;

        // bestimmen neue Buchungsnummer
        $cols = array(
            'id'
        );

        $select = $tabelleBuchungsnummer->select();
        $select
            ->from($tabelleBuchungsnummer, $cols)
            ->where("session_id = '".$momentaneSessionId ."'");

        $momentaneBuchung = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        // alte Buchungsnummer löschen
        $anzahlDelete = $tabelleBuchungsnummer->delete("id = " . $alteBuchungsnummer);

        // neuanlegen Buchungsnummer
        if (count($momentaneBuchung) == 0) {
            unset($alteBuchung[0]['id']);
            unset($alteBuchung[0]['zaehler']);
            unset($alteBuchung[0]['session_id']);
            $alteBuchung[0]['session_id'] = $momentaneSessionId;
            $neueBuchungsnummer = $tabelleBuchungsnummer->insert($alteBuchung[0]);

            return $neueBuchungsnummer;
        }
        // wiederherstellen alte Buchungsnummer
        elseif ($anzahlDelete <> 1) {
            $neueBuchungsnummer = $tabelleBuchungsnummer->insert($alteBuchung[0]);

            return $neueBuchungsnummer;
        }
        // umkopierte Buchungsnummer
        else {
            unset($alteBuchung[0]['session_id']);

            $tabelleBuchungsnummer->update($alteBuchung[0], "session_id = '" . $momentaneSessionId . "'");

            return $alteBuchungsnummer;
        }
    }

    /**
     * Registriert in der Session , Session Namespace 'buchungsnummer'
     *
     * + die 'buchungsnummer'
     * + den 'zaehler' der Buchungsnummer
     *
     * @param $buchungsnummer
     * @param $zaehler
     * @return $this
     */
    public function registriereSessionBuchungsnummerZaehler($buchungsnummer, $zaehler)
    {
        $sessionBuchungsnummer = new Zend_Session_Namespace('buchung');
        $sessionBuchungsnummer->buchungsnummer = $buchungsnummer;
        $sessionBuchungsnummer->zaehler = $zaehler;

        return $this;
    }

} // end class
