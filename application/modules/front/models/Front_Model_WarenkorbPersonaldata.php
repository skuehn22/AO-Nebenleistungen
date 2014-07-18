<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 09.05.12
 * Time: 12:23
 * To change this template use File | Settings | File Templates.
 */
 
class Front_Model_WarenkorbPersonaldata extends Pimple_Pimple{

    /**
     * Finden der Kunden ID aus der Session
     *
     * @return string
     */
    public function getKundenId(){
		$kundenId = translate('unbekannt');
		$warenkorb = new Zend_Session_Namespace('warenkorb');
		if(!empty($warenkorb->kundenId))
			$kundenId = $warenkorb->kundenId;

		return $kundenId;
	}

}
