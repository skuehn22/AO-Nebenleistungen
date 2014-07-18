<?php 
 /**
 * Grundmodel im bereich 'front'
 *
 * @author Stephan.Krauss
 * @date 19.12.2013
 * @file Grundmodel.php
 * @package front
 * @subpackage model
 */
class Front_Model_Grundmodel
{
    protected $pimple = null;

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    protected function servicecontainer(Pimple_Pimple $pimple)
    {
        foreach($this->tools as $tool){
            if(!$pimple->offsetExists($tool))
                throw new nook_Exception('Tool fehlt');
            else
                $this->$tool = $pimple[$tool];
        }

        $this->pimple = $pimple;

        return;
    }

}
 