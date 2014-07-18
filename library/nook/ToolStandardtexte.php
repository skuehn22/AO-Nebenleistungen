<?php 
 /**
 * Tool zur Verwaltung der Standardtexte
 *
 * + Standardtexte aus 'tbl_textbausteine'
 *
 * @author Stephan.Krauss
 * @date 15.07.13
 * @file ToolStandardtexte.php
 * @package tools
 */
class nook_ToolStandardtexte
{
    // Tabellen / Views
    /** @var $tabelleTextbausteine Application_Model_DbTable_textbausteine */
    private $tabelleTextbausteine = null;

    // Fehler
    private $error_bausteinname_nicht_bekannt = 1890;
    private $error_anzahl_datensaetze_falsch = 1891;

    // Konditionen

    protected $blockname = null;
    protected $text = null;
    protected $pimple = null;
    protected $anzeigesprache = null;

    /**
     * Setzen DIC
     *
     * @param Pimple_Pimple $pimple
     * @return nook_ToolStandardtexte
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;
        $this->tabelleTextbausteine = $pimple['tabelleTextbausteine'];

        $this->bestimmenAnzeigesprache();

        return $this;
    }

    /**
     * @param $bausteinname
     * @return nook_ToolStandardtexte
     */
    public function setBlockname($bausteinname)
    {
        $this->blockname = $bausteinname;

        return $this;
    }

    /**
     * Steuert die Ermittlung eines Textbausteines der 'tbl_textbaustein'
     *
     * @return $this
     * @throws nook_Exception
     */
    public function steuerungErmittelnText()
    {
        if(empty($this->blockname))
            throw new nook_Exception($this->error_bausteinname_nicht_bekannt);

        $this->ermittelnText();

        return $this;
    }

    /**
     * Ermittelt ein Textbaustein aus 'tbl_textbausteine'
     *
     * + sucht nach Blockname
     * + sucht nach Anzeigesprache
     * + return false, wenn Textbaustein nicht vorhanden
     *
     */
    private function ermittelnText()
    {
        $whereBlockname = "blockname = '".$this->blockname."'";
        $whereAnzeigesprache = "sprache_id = ".$this->anzeigesprache;

        $select = $this->tabelleTextbausteine->select();
        $select
            ->where($whereBlockname)
            ->where($whereAnzeigesprache);

        $rows = $this->tabelleTextbausteine->fetchAll($select)->toArray();

        if(count($rows) == 0)
            $this->text = false;
        elseif(count($rows) == 1)
            $this->text = $rows[0]['text'];
        else
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        return;
    }

    /**
     * Ermitteln der Anzeigesprache
     */
    private function bestimmenAnzeigesprache()
    {
        $this->anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();

        return;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }


}
