<?php 
 /**
 * Interface Kapazitäten der Programme
 *
 * @author Stephan.Krauss
 * @date 27.09.13
 * @file ProgrammdetailKapazitaetManagerInterface.php
 * @package front
 * @subpackage interface
 */
 
interface Front_Model_ProgrammdetailKapazitaetManagerInterface
{
    public function berechneProgrammkapazitaet();
    public function aendernTageskapatitaetEinesProgrammes();
    public function mapDatum($__datum);
}
