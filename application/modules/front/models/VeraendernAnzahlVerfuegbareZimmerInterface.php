<?php
/**
 * Interface Front_Model_VeraendernAnzahlVerfuegbareZimmer
 *
 * @author stephan.krauss
 * @date 10.06.13
 * @file VeraendernAnzahlVerfuegbareZimmerInterface.php
 * @package front
 * @subpackage interface
 */

interface Front_Model_VeraendernAnzahlVerfuegbareZimmerInterface{
    public function __construct();
    public function setDatenHotelbuchung(array $datenHotelbuchung);
    public function startZimmerreduktion();
}