<?php
/**
 * 28.09.2012
 * Darstellung / Kontrolle der Bilder eines Programmes
 *
 * @author Stephan Krauß
 */

class nook_ToolProgrammbilder {

    /**
     * Überprüft ob ein Programmbild unter
     * /images/program/ ... vorhanden ist.
     * Gibt wenn Bild vorhanden die Id des programmes zurück.
     *
     * @param $__details
     * @param string $typ
     * @return mixed
     */
    public static function findImageFromProgram($__programmId, $typ = 'midi'){

        if(is_array($__programmId))
            $id = $__programmId['id'];
        else
            $id = $__programmId;

        if(file_exists(ABSOLUTE_PATH."/images/program/".$typ."/".$id.".jpg"))
                $image = $__programmId;
            else
                $image = 0;

        return $image;
    }

    /**
     * Gibt den Bildpfad der Programmbilder zurück.
     * Gibt ein 'leer.gif' zurück wenn die 'bildId' = 0.
     * In Verwendung mit 'findImageFromProgram' zu nutzen
     *
     * @param $__bildId
     * @param string $__type
     * @return string
     */
    public static function getImagePathForProgram($__bildId, $__type = 'midi'){

        if($__bildId == 0)
            return "/img/leer.gif";
        else
            return "/images/program/".$__type."/".$__bildId.".jpg";
    }

} // end class
