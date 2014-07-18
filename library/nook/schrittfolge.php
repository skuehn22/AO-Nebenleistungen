<?php
/**
 * Zeigt die Schrittfolge im Bestellprozes an
 *
 *
 *
*/
 
class nook_schrittfolge {

    static  public function setAktiveStepClass($__step){
		$aktiveStep = array();
		for($i=1; $i< 5; $i++){
			$aktiveStep['aktiveStep'.$i] = '';

			if($i == $__step)
				$aktiveStep['aktiveStep'.$i] = 'aktiveStep';
		}

		return $aktiveStep;
    }
}
