<?php

/**
 * Verringetr die Anzahl der Zeilen in einem mehrdimensionalen Array
 *
 * + sucht nach Unique / einheitlichen Datensätzen
 * + Vorgabe eines Array der Spalten mit dem verglichen werden soll
 * + Gibt Anzahl der Zeilen nach erfolgter Verringerung zurück
 *
 * @author Stephan Krauss
 * @date 24.02.14
 * @package front
 * @subpackage model
 */

class Front_Model_UniqueRowsArray
{
    // Array welches reduziert werden soll
    protected $ausgangsArray = array();
    // Spalten des Array
    protected $uniqueArray = array();

    // Array mit unique Datensätzen
    protected $reduzierteArray = array();

    protected $flagSuchParameter = null;

    /**
     * @param $ausgangsArray
     * @return Front_Model_UniqueRowsArray
     */
    public function setAusgangsArray($ausgangsArray)
    {
        $this->ausgangsArray = $ausgangsArray;

        return $this;
    }

    /**
     * @param $reduceArray
     * @return Front_Model_UniqueRowsArray
     */
    public function setUniqueArray($reduceArray)
    {
        $this->uniqueArray = $reduceArray;

        return $this;
    }

    /**
     * @param $suchparameter
     * @return Front_Model_UniqueRowsArray
     */
    public function setFlagSuchparameter($suchparameter)
    {
        $this->flagSuchParameter = $suchparameter;

        return $this;
    }

    /**
     * Steuerung der Ermittlung identischer Rows
     *
     * + baut Unique Array mit md5 Key
     * + vereinfacht Unique Array, Keys nummerisch
     *
     * @return Front_Model_UniqueRowsArray
     * @throws Exception
     */
    public function steuerungErmittlungUniqueRows()
    {
        try{
            if(count($this->ausgangsArray) < 1)
                throw new nook_Exception('Ausgangs Array fehlt');

            if(count($this->uniqueArray) < 1)
                throw new nook_Exception('Vergleichs Array fehlt');

            // baut Unique Array
            for($i = 0; $i < count($this->ausgangsArray); $i++){
                $row = $this->ausgangsArray[$i];

                $this->ermittlungUniqueRow($row, $this->uniqueArray);
            }

            // vereinfacht Unique Array, Keys nummerisch
            $this->reduzierteArray = $this->arrayMergeUniqueArray($this->reduzierteArray);

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * Verändert den Key des Array
     *
     * @param array $uniqueArray
     * @return array
     */
    protected function arrayMergeUniqueArray(array $reduzierteArray)
    {
        $neueKeyArray = array();

        foreach($reduzierteArray as $key => $value){
            $neueKeyArray[] = $value;
        }

        return $neueKeyArray;
    }

    /**
     * Ermitteln von Unique Row mittels Vergleichs - Array
     *
     * + es kann ein Suchparameter vorgegeben werden. Ist dieser Suchparameter leer,
     * + dann wird der Datensatz übergangen
     *
     * @param array $row
     * @param array $uniqueArray
     * @return array
     */
    protected function ermittlungUniqueRow(array $row, array $uniqueArray)
    {
        // bricht ab, wenn der Suchparameter leer ist
        if($this->flagSuchParameter){
            if(empty($row[$this->flagSuchParameter]))
                return;
        }

        // fügt die Spalten zusammen
        $suchstring = '';
        foreach($row as $key => $value){
            if(in_array($key, $uniqueArray)){
                $value = trim($value);
                $suchstring .= $value;
            }
        }

        $spaltenMd5 = md5($suchstring);
        $this->reduzierteArray[$spaltenMd5] = $row;

        return $this->reduzierteArray;
    }

    /**
     * @return array
     */
    public function getAnzahlRows()
    {
        return count($this->reduzierteArray);
    }

    /**
     * @return array
     */
    public function getReduzierteArray()
    {
        return $this->reduzierteArray;
    }
}

/*******/

//$ausgangsArray = array();
//
//$ausgangsArray[] = array(
//    'bla' => 111,
//    'foo' => 'aaa',
//    'search' => true
//);
//
//$ausgangsArray[] = array(
//    'bla' => 111,
//    'foo' => 'aaa',
//    'search' => true
//);
//
//$ausgangsArray[] = array(
//    'bla' => 222,
//    'foo' => 'aaa',
//    'search' => true
//);
//
//$reduceArray = array(
//    'bla',
//    'foo'
//);
//
//$frontModelUniqueRowsArray = new Front_Model_UniqueRowsArray();
//
//$frontModelUniqueRowsArray
//    ->setAusgangsArray($ausgangsArray)
//    ->setUniqueArray($reduceArray)
//    ->setFlagSuchparameter('search')
//    ->steuerungErmittlungUniqueRows();
//
//$anzahlRows = $frontModelUniqueRowsArray->getAnzahlRows();
//$neuesArray = $frontModelUniqueRowsArray->getReduzierteArray();