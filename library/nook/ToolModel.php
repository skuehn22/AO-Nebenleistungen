<?php
/**
 * Erweiterung des Model
 * für ArrayAccess
 */
class nook_ToolModel
{

    protected $_modelData = array();

    /**
     * Setzt den Inhalt eines
     * Containers der Datenübergabe
     * zwischen den Models
     *
     * @param $__offset
     * @param $__value
     */
    public function offsetSet($__value, $__offset = false)
    {

        // wenn Bereich des Transfer Container leer
        if (empty($__offset)) {
            $this->_modelData[] = $__value;
        } else {
            $this->_modelData[$__offset] = $__value;
        }
    }

    /**
     * Kontrolliert das vorhandensein
     * einer Eigenschaft.
     *
     * @param $__offset
     * @return bool
     */
    public function offsetExists($__offset)
    {
        return isset($this->_modelData[$__offset]);
    }

    /**
     * Entfernt eine Eigenschaft
     *
     * @param $__offset
     */
    public function offsetUnset($__offset)
    {
        unset($this->_modelData[$__offset]);
    }

    /**
     * Gibt eine Eigenschaft zurück
     *
     * @param $__offset
     * @return bool
     */
    public function offsetGet($__offset)
    {
        if (array_key_exists($__offset, $this->_modelData)) {
            return $this->_modelData[$__offset];
        } else {
            return false;
        }
    }

    /**
     * Filtert alle Model Eigenschaften.
     * Schreibt diese in den '_modelData' - Container.
     * Eigenschaften des Array '_modelData' selbst werden nicht übernommen.

     */
    public function _exportModelData()
    {

        // löscht Transfer - Container
        unset($this->_modelData);
        $this->_modelData = array();

        foreach ($this as $key => $value) {
            if (strpos($key, "_modelData") === 0) {
                continue;
            }

            $this->_modelData[$key] = $value;
        }

        return $this->_modelData;
    }

    /**
     * Übernimmt Daten eines externen
     * Models
     */
    protected function _importModelData($__fremdModel, $__modelName = false)
    {
        $test = 123;

        // löscht Transfer - Container
        if (empty($__modelName)) {
            $this->_modelData = array();
            $this->_modelData = $__fremdModel->_exportModelData();
        } // neuer Datenbereich eines Model
        else {
            $this->_modelData[$__modelName] = $__fremdModel->_exportModelData();
        }

        return $this;
    }

    /**
     * Löscht einen Bereich der $_modelData
     *
     * @param $__modelDataBereich
     * @return nook_ToolModel
     */
    protected function _deleteBereichModelData($__modelDataBereich)
    {
        if (isset($this->_modelData[$__modelDataBereich])) {
            unset($this->_modelData[$__modelDataBereich]);
        }

        return $this;
    }

    /**
     * Übernahme einer Eigenschaft der Klasse
     *
     * @param $offset
     * @param $value
     * @return bool
     */
    public function setProperty($offset, $value)
    {
        if (isset($this->$offset)) {
            $this->$offset = $value;

            return true;
        }

        return false;
    }

}