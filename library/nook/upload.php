<?php
 /**
  * Upload von Bildern
  *
  * + wandelt '.png' zu '.jpg'
  *
  * @author Stephan.Krauss
  * @date 07.01.2014
  * @file upload.php
  * @package tools
  */
class nook_upload {

    static private $_instance;

    private $_checkImageTyp = false;
    private $_imagePath;
    private $_image;
    private $_imageName;
    private $_imageType;

    static public function getInstance(){
       if(self::$_instance == null){
           self::$_instance = new nook_upload();
       }

       return self::$_instance;
    }

    public function setImage($__image){
        $this->_image = $__image;

        return $this;
    }

    public function setImagePath($__imagePath){
        $this->_imagePath = $__imagePath;

        return $this;
    }

    public function setImageName($__imageName){
        $this->_imageName = $__imageName;

        return $this;
    }

    public function checkImageTyp(){
        if(($this->_image['type'] == 'image/jpeg') or ($this->_image['type'] == 'image/pjpeg')){
            $this->_checkImageTyp = true;
        }
        elseif(($this->_image['type'] == 'image/x-png') or ($this->_image['type'] == 'image/png')){
            $kontrolleKonvertierung = $this->_convertPngToJpg();
            $this->_checkImageTyp = true;
        }

        return $this->_checkImageTyp;
    }

    public function moveImage(){
        $kontrolle = false;

        $newImage = $this->_imagePath . $this->_imageName . ".jpg";
        $kontrolle = move_uploaded_file($this->_image['tmp_name'], $newImage);

        return $kontrolle;
    }

    private function _convertPngToJpg(){
        $image = imagecreatefrompng($this->_image['tmp_name']);
        imagejpeg($image, $this->_image['tmp_name']);
        imagedestroy($image);

        return;
    }
}
