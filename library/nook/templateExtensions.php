<?php
/**
 * Plugins für die Template Engine
 *
 * @author Stephan.Krauss
 * @date 18.48.2013
 * @file templateExtensions.php
 * @package tools
 */

/**
 * zentrales Übersetzungstool
 *
 * @param bool $platzhalter
 * @return string
 */
function translate($platzhalter = false)
{
    $db = Zend_Registry::get('front');

    $sql = "select count(platzhalter) from tbl_translate where BINARY platzhalter = '" . $platzhalter . "'";
    $count = $db->fetchOne($sql);

    $translateValue = array();
    $translate = new Zend_Session_Namespace('translate');
    $translateValue['module'] = $translate->module;
    $translateValue['controller'] = $translate->controller;

    if ($count == 0) {
        $translateValue['de'] = $platzhalter;
        $translateValue['platzhalter'] = $platzhalter;

        $db->insert('tbl_translate', $translateValue);
    }

    $language = Zend_Registry::get('language');
    $sql = "select " . $language . ", id from tbl_translate where BINARY platzhalter = '" . $platzhalter . "' order by id desc";
    $translatedValue = $db->fetchRow($sql);

    $translate = new Zend_Session_Namespace('translate');


    if ($translate->uebersetzungsmodus) {
        return "<span style='color: blue;'>" . $translatedValue['id'] . "</span> <span style='color: red;'>" . $translatedValue[$language] . "</span>";
    }

    return $translatedValue[$language];
}

// Darstellen der Flagge einer Sprache
function flagge($idFlagge)
{
    $nameFlagge = array(
        1 => 'ger',
        2 => 'eng',
        3 => 'fr',
        4 => 'it',
        5 => 'es',
        6 => 'cn',
        7 => 'dk',
        8 => 'fi',
        9 => 'gr',
        10 => 'il',
        11 => 'jp',
        12 => 'cat',
        13 => 'dut',
        14 => 'no',
        15 => 'pl',
        16 => 'pt',
        17 => 'ro',
        18 => 'ru',
        19 => 'sw',
        20 => 'cz',
        21 => 'tr'
    );

    return $nameFlagge[$idFlagge];
}

/**
 * Darstellung des Preises entsprechend der Anzeigesprache
 *
 * @param $__price
 * @return string
 */
function translatePricing($__price)
{
    $price = nook_Tool::commaCorrection($__price);

    return $price;
}

/** Datumsformatierung in das deutsche Datumsformat
 *
 * @param $__datum
 * @return string
 */
function formatiereDatum($__datum)
{
    $teileDatum = explode('-', $__datum);

    return $teileDatum[2] . "." . $teileDatum[1] . "." . $teileDatum[0];
}

/**
 * Formatieren der Zeit
 *
 * + $__laenge = 1 , nur Stunden
 * + $__laenge = 2 , Stunden und Minuten
 * + $__laenge = 3 , Stunde und Minute und Sekunde
 *
 * @param $__zeit
 * @param int $__laenge
 * @return mixed
 */
function formatiereZeit($__zeit, $__laenge = 2)
{
    if ($__laenge < 3) {
        $teileZeit = explode(":", $__zeit);

        if ($__laenge == 1) {
            return $teileZeit[0];
        } else {
            return $teileZeit[0] . ":" . $teileZeit[1];
        }
    } else {
        return $__zeit;
    }
}

/**
 * Formatiert ein Datum unter Berücksichtigung der Anzeigesprache
 *
 * @param $datum
 * @return string
 */
function formatiereDatumSprache($datum)
{

    $datum = trim($datum);
    $datumTeile = explode(' ', $datum);

    $spracheSession = new Zend_Session_Namespace('translate');
    $sprache = (array) $spracheSession->getIterator();

    // Datum in deutsch
    if ($sprache['language'] == 'de') {
        $datumDeutschTeile = explode('-', $datumTeile[0]);

        return $datumDeutschTeile[2] . "." . $datumDeutschTeile[1] . "." . $datumDeutschTeile[0];
    } // Datum in englisch
    else {
        return $datumTeile[0];
    }
}

/**
 * Wandelt ein Datum nach ISO8601 in ein Datum entsprechned der Anzeigesprache
 *
 * + Eingabedatum nach ISO8601 'YYYY-mm-dd'
 * + Ausgabe mit verkürzten Monatsnamen entsprechend der Landessprache
 *
 * @param $datum
 * @return string
 */
function formatiereDatumIso($datum)
{
    $spracheSession = new Zend_Session_Namespace('translate');
    $sprache = (array) $spracheSession->getIterator();

    $monatsNamenDeutschShort = array(
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mär',
        4 => 'Apr',
        5 => 'Mai',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Dez'
    );

    $monatsNamenEnglischShort = array(
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec'
    );

    $teileDatum = explode('-', $datum);
    $teileDatum[1] = (int) $teileDatum[1];

    if ($sprache['language'] == 'de')
        $datumKomplett = $teileDatum[2].". ".$monatsNamenDeutschShort[$teileDatum[1]].". ".$teileDatum[0];
    else
        $datumKomplett = $teileDatum[2].". ".$monatsNamenEnglischShort[$teileDatum[1]].". ".$teileDatum[0];

    return $datumKomplett;
}

/**
 * Stellt ein Datum entsprechend der gewählten Anzeigesprache dar.
 *
 * + Darstellung im deutschen Datumsformat dd.mm.YYYY
 * + Darstellung im englischen Datumsformat mm/dd/YYYY
 *
 * @param $datum
 * @return mixed
 */
function formatiereDatumMitMonat($datum)
{
    $monatsNamenDeutschShort = array(
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mär',
        4 => 'Apr',
        5 => 'Mai',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Dez'
    );

    $monatsNamenEnglischShort = array(
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec'
    );

    $spracheSession = new Zend_Session_Namespace('translate');
    $sprache = (array) $spracheSession->getIterator();

    if($sprache['language'] == 'de'){
        $teileDatum = explode('.', $datum);
        $teileDatum[1] = (int) $teileDatum[1];
        $veraendertesDatum = $teileDatum[0].". ".$monatsNamenDeutschShort[$teileDatum[1]].". ".$teileDatum[2];
    }
    else{
        $teileDatum = explode('/', $datum);
        $teileDatum[1] = (int) $teileDatum[1];
        $veraendertesDatum = $teileDatum[1].". ".$monatsNamenEnglischShort[$teileDatum[1]].". ".$teileDatum[2];
    }

    return $veraendertesDatum;
}

function formatiereDatumZeitSprache($datum)
{

    if (empty($datum)) {
        return '';
    }

    $datum = trim($datum);
    $datumTeile = explode(' ', $datum);

    $spracheSession = new Zend_Session_Namespace('translate');
    $sprache = (array) $spracheSession->getIterator();

    // Datum in deutsch
    if ($sprache['language'] == 'de') {
        $datumDeutschTeile = explode('-', $datumTeile[0]);

        return $datumDeutschTeile[2] . "." . $datumDeutschTeile[1] . "." . $datumDeutschTeile[0] . " " . $datumTeile[1];
    } else {
        return $datumTeile[0] . " " . $datumTeile[1];
    }
}

// Login - Button / Logout - Button
function buttonLoginLogout()
{

    $sprache = nook_ToolSprache::getAnzeigesprache();

    if ($sprache == 'de') {

        $button = array(
            "login" => "<input type='image' src='/buttons/button_login_de.png' name='front_login'>",
            "logout" => "<input type='image' src='/buttons/button_logout_de.png' name='front_logout'>"
        );
    } else {

        $button = array(
            "login" => "<input type='image' src='/buttons/button_login_en.png' name='front_login'>",
            "logout" => "<input type='image' src='/buttons/button_logout_en.png' name='front_logout'>"
        );
    }

    $sessionAuth = new Zend_Session_Namespace('Auth');
    if ($sessionAuth->role_id > 4) {
        return $button['logout'];
    } else {
        return $button['login'];
    }
}

/**
 * Generiert Login / Logout in der Navigation
 */
function navigationLoginLogout()
{

    $sessionAuth = new Zend_Session_Namespace('Auth');
    if ($sessionAuth->role_id > 1) {
        $button = "<td><a href='#' id='checkWarenkorb' style='color: white;' class='submit_rot'>".translate('abmelden')."</a></td>";
    } else {
        $button = "<td><a href='#' style='color: white;' class='showHide submit_navigation'>" . translate('zur Anmeldung') . "</a></td>";
    }

    return $button;
}

/**
 * Navigationslink Registrierung
 *
 * Erstellt den Navigationslink 'Registrierung'
 * wenn die Rolle < 2 ist.
 *
 * @return string
 */
function linkRegistrierung()
{
    $auth = new Zend_Session_Namespace('Auth');
    $roleId = $auth->role_id;

    if ($roleId <= 1) {
        $linkRegistrierung = translate('Registrierung');
        $linkRegistrierung = "<a href='/front/registrierung/index/'>" . $linkRegistrierung . "</a>";
    } else {
        $linkRegistrierung = '';
    }

    return $linkRegistrierung;
}

// Darstellung des Link zum Administrationssystem
function linkAdministration()
{
    $linkAdminBereich = '';

    $auth = new Zend_Session_Namespace('Auth');
    $roleId = $auth->role_id;

    // Zugang ab Rolle Superuser
    if ($roleId > 4) {
        $linkBezeichnung = translate('Verwaltung');
        $linkAdminBereich = "<a href='/admin/whiteboard/index/'>" . $linkBezeichnung . "</a>";
    }

    return $linkAdminBereich;
}

// Darstellung des Link zur Korrektur der Personendaten
function linkKundendaten($linkBezeichnung)
{
    $linkKundendaten = '';
    $auth = new Zend_Session_Namespace('Auth');
    $roleId = $auth->role_id;

    // Zugang ab Rolle Kunde
    if ($roleId >= 2) {
        $linkKundendaten = "<a href='/front/kundendaten/view/'>" . $linkBezeichnung . "</a>";
    }

    return $linkKundendaten;
}

function linkOfflinebucher()
{
    $linkOfflinebucher = '';

    $auth = new Zend_Session_Namespace('Auth');
    $roleId = $auth->role_id;

    if($roleId > 8){
        $linkOfflinebucher = "<a href='/front/offlinebuchung/read/'>".translate('Offlinebucher')."</a>";
    }

    return $linkOfflinebucher;
}

// Link zu den bereits erfolgten Buchungen
function linkBuchungen()
{

    /** @var $sessionAuth Zend_Session_Namespace */
    $sessionAuth = new Zend_Session_Namespace('Auth');
    $sessionAuthArray = (array) $sessionAuth->getIterator();

    if (!empty($sessionAuthArray['userId'])) {

        $frontModelBuchungen = new Front_Model_Buchungen();
        $buchungshistorie = $frontModelBuchungen
            ->setKundenId($sessionAuthArray['userId'])
            ->steuernErmittelnBuchungen()
            ->getBuchungsHistorie();

        if(count($buchungshistorie) > 0)
            $link = "<a href='/front/buchungen/'>" . translate('Buchungen') . "</a>";
        else
            $link = ' ';
    } else {
        $link = '';
    }

    return $link;

}

// Link zur Vormerkung
function linkVormerkung()
{
    /** @var $sessionAuth Zend_Session_Namespace */
    $sessionAuth = new Zend_Session_Namespace('Auth');
    $sessionAuthArray = (array) $sessionAuth->getIterator();

    if ( ($sessionAuthArray['role_id'] == 1) or ( empty($sessionAuthArray['role_id']) and empty($sessionAuthArray['userId']) )) {
        $link = "<a href='/front/vormerken-anmelden/'>" . translate('Login / Vormerkung') . "</a>";
    } else {
        $link = '';
    }

    return $link;

}

// Hotelbilder
function findPropertyImage($propertyId)
{
    if (file_exists(ABSOLUTE_PATH . "/images/propertyImages/midi/" . $propertyId . ".jpg")) {
        $propertyImageSrc = "/images/propertyImages/midi/" . $propertyId . ".jpg";
    } else {
        $propertyImageSrc = "/images/propertyImages/midi/noImage.gif";
    }

    return $propertyImageSrc . "?test=" . time();
}

// Raten Bilder
function findRateImage($__rateId)
{
    if (file_exists(ABSOLUTE_PATH . "/images/rateImages/midi/" . $__rateId . ".jpg")) {
        $rateImageSrc = "/images/rateImages/midi/" . $__rateId . ".jpg";
    } else {
        $rateImageSrc = "/images/rateImages/midi/standard.gif";
    }

    return $rateImageSrc . "?test=" . time();
}

// Kategorie Bilder
function findKategorieImage($kategorieId)
{
    if (file_exists(ABSOLUTE_PATH . "/images/kategorieImages/midi/" . $kategorieId . ".jpg")) {
        $kategorieImageSrc = "/images/kategorieImages/midi/" . $kategorieId . ".jpg";
    } else {
        $kategorieImageSrc = "/images/kategorieImages/midi/standard.gif";
    }

    return $kategorieImageSrc . "?test=" . time();
}

// Produkt Bilder
function findProductImage($__productId)
{
    if (file_exists(ABSOLUTE_PATH . "/images/product/" . $__productId . ".jpg")) {
        $productImageSrc = "/images/product/" . $__productId . ".jpg";
    } else {
        $productImageSrc = "/images/product/standard.gif";
    }

    return $productImageSrc . "?test=" . time();
}

function internetlink($__link)
{
    $internetlink = '';

    if (empty($__link)) {
        return $internetlink;
    }

    $internetlink = "<a href='http://" . $__link . "' target='_blank'>www</a>";

    return $internetlink;
}

function darstellungGruppe($__personen)
{
    if (empty($__personen)) {
        $__personen = translate('Gruppe');
    }

    return $__personen;
}

// Ermittlung des Controllernamen
function anzeigenStadtbilder()
{
    $translate = new Zend_Session_Namespace('translate');
    $translateItems = $translate->getIterator();

    if ($translateItems['controller'] == 'login') {
        return true;
    } else {
        return false;
    }
}

// anzeige der Stadtbeschreibung im Banner
function anzeigeStadtbeschreibung($__cityId)
{
    $stadtbeschreibung = '';
    $db = Zend_Registry::get('front');
    $sprache = nook_ToolSprache::ermittelnKennzifferSprache();

    $sql = "select stadtbeschreibung from tbl_stadtbeschreibung where city_id = " . $__cityId . " and sprache_id = '" . $sprache . "'";
    $stadtbeschreibung = $db->fetchOne($sql);

    $stadtbeschreibung = nook_Tool::trimLongTextStandard($stadtbeschreibung, 30);

    return $stadtbeschreibung;
}

function staedteNamenUndId()
{
    $stadtSeiten = '';

    $db = Zend_Registry::get('front');
    $select = $db->select();
    $select
        ->from('tbl_ao_city', array( 'AO_City_ID', 'AO_City' ))
        ->order('AO_City');

    $result = $db->fetchAll($select);

    foreach ($result as $key => $stadt) {
        $stadtSeiten .= "<li><a href='/front/stadt/index/city/" . $stadt['AO_City_ID'] . "'>" . $stadt['AO_City'] . "</a></li>";
    }

    return $stadtSeiten;
}

/**
 * Zeigt die Bilder mit Lightbox Effekt an.
 *
 * + Wenn das Bild nicht vorhanden ist, wird ein 'Leerbild' angezeigt.
 * + Es wirden die Zusatzinformationen des Bildes ermittelt.
 * Ein kompletter <img Tag wird zurückgegeben.
 *
 * @param $bildId , ID des Programmes
 * @param $bildTyp , Bereich 1 = Programme, 6 = Hotel, 10 = Stadt
 * @param int $bildBreite , zoom auf Bildbreite
 * @param int $copyrightAlign , anzeigen Copyright
 * @param int $lightboxEffekt, anzeigen Maxibild = 1
 *
 * @return string
 */
function anzeigenBilder($bildId, $bildTyp, $bildBreite = 0, $copyrightAlign = 0, $lightboxEffekt = 1)
{

    $toolBild = nook_ToolBild::factory();
    $zusatzinformationBild = $toolBild
        ->setBildId($bildId)
        ->setBildTyp($bildTyp)
        ->getZusatzinformationBild();

    // wenn Bild / Bildpfad nicht vorhanden ist
    if (empty($zusatzinformationBild['bildpfad'])) {
        return '';
    }

    $bild = "<table style='margin-bottom: 0px;'><tr><td>";

    // Lightbox Effekt
    if($lightboxEffekt == 1)
        $bild .= "<img src='" . $zusatzinformationBild['bildpfad'] . "' class='bild basic-modal' ";
    else
        $bild .= "<img src='" . $zusatzinformationBild['bildpfad'] . "' class='bild' ";

    // 'alt' Attribut
    if (!empty($zusatzinformationBild))
        $bild .= " alt='" . $zusatzinformationBild['bildname'] . "'";

    // Bild ID
    $bild .= " id='bild".$bildTyp."_".$bildId."'";

    // Bildbreite
    if ($bildBreite > 0) {
        $bild .= " width='" . $bildBreite . "'";
    }

    $bild .= ">";

    $bild .= "</td></tr>";

    // Copyright
    if ((array_key_exists('copyright', $zusatzinformationBild)) and ($zusatzinformationBild['copyright'] != false)) {
        $bild .= "<tr>";

        if ($copyrightAlign == 1) {
            $bild .= "<td> &copy; " . $zusatzinformationBild['copyright'] . "&nbsp;&nbsp;</td>";
        }
        else{
            $bild .= "<td align='right'> &copy; " . $zusatzinformationBild['copyright'] . "&nbsp;</td>";
        }

        $bild .= "</tr>";
    }

    $bild .= "</table>";

    return $bild;
}

function erstellenOptionAnzahl($anzahl, $memory = 1)
{
    $options = "";

    for ($i = 1; $i <= $anzahl; $i++) {
        if ($i == $memory) {
            $options .= "<option value='" . $i . "' selected>" . $i . "</option> \n";
        } else {
            $options .= "<option value='" . $i . "'>" . $i . "</option> \n";
        }
    }

    return $options;
}

/**
 * Select Box des Sprachenmanager der Programme
 *
 * @param array $programmsprachen
 * @return string
 */
function sprachenmanagerSelect(array $programmsprachen)
{
    $options = '';
    foreach ($programmsprachen as $sprache) {
        if ($sprache['gewaehlt'] == 1) {
            $options .= "<option value='" . $sprache['spracheId'] . "'>" . $sprache['beschreibung'] . "</option> \n";
        } else {
            $options .= "<option value='" . $sprache['spracheId'] . "' selected>" . $sprache['beschreibung'] . "</option> \n";
        }
    }

    return $options;
}

?>