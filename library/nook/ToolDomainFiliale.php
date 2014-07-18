<?php
/**
 * Ermittlung der Domain oder der Filiale
 *
 * + Mit der URL / Subdomain wird die Filiale ID erkannt
 *
 * @author Stephan Krauss
 * @date 17.04.2014
 * @package tool
 */
class nook_ToolDomainFiliale{

    protected $subdomain = null;
    protected $filialeId = null;

    /**
     * @param $subdomain
     * @return nook_ToolDomainFiliale
     */
    public function setSubdomain($subdomain)
    {
        $this->subdomain = $subdomain;

        return $this;
    }

    /**
     * @param $filialeId
     * @return nook_ToolDomainFiliale
     */
    public function setFilialeId($filialeId)
    {
        $this->filialeId = $filialeId;

        return $this;
    }

    /**
     * Ermittelt mit der URL / Domain die Filiale oder mit der Filiale die Domain
     *
     * + Auflistung der Filialen in 'static.ini'
     * + Ermittlung Filiale oder Domain / URL der Filiale
     *
     * @return nook_ToolDomainFiliale
     * @throws Exception
     */
    public function steuerungErmittlungFilialeId()
    {
        try{
            if( (is_null($this->filialeId)) and (is_null($this->subdomain)) )
                throw new nook_Exception('Domain / URL oder Name der Filiale fehlt');

            $static = Zend_Registry::get('static');
            $filialenArray = $static->filiale->toArray();

            if(!is_null($this->subdomain))
                $this->filialeId = $this->ermittlungFiliale($filialenArray['filiale'], $this->subdomain);

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * Ermittlung die ID der Filiale mit der Subdomain der URL
     *
     * + gibt ID der Filiale zurück
     * + wenn keine Filiale gefunden wurde, dann 1 = Herden
     *
     * @param $domain
     * @return int
     */
    protected function ermittlungFiliale(array $filialenArray, $subdomain)
    {
        $gefundeneFilialeId = 1;

        foreach($filialenArray as $nameFiliale => $urlFiliale){

            $suchMuster = "#^".$subdomain."#i";
            $treffer = preg_match($suchMuster, $urlFiliale, $matches);

            if($treffer == 1){
                $gefundeneFilialeId = $nameFiliale;
                break;
            }
        }

        return $gefundeneFilialeId;
    }

    /**
     * @return string
     */
    public function getSubdomain()
    {
        return $this->subdomain;
    }

    /**
     * @return string
     */
    public function getFilialeId()
    {
        return $this->filialeId;
    }
}