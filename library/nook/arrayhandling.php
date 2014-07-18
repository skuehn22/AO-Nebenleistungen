<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 21.03.12
 * Time: 14:00
 * To change this template use File | Settings | File Templates.
 */
 
class nook_arrayhandling {

    /**
     * Splittet ein Array in 2 Variablenbereiche
     * Sortiert dabei nach dem Kriterium checked.
     * Fügt beide Arrays zusammen.
     * 
     * @param $__hotelProducts
     * @return array
     */
    public function sortHotelProductsByChecked($__hotelProducts){
        $hotelProductsSelected = array();
        $hotelProductsUnselected = array();

        foreach($__hotelProducts as $key => $product){
            if($product['checked'] === true)
                $hotelProductsSelected[] = $product;
            else
                $hotelProductsUnselected[] = $product;
        }

        $sortedHotelProducts = array_merge($hotelProductsSelected, $hotelProductsUnselected);

        return $sortedHotelProducts;
    }

}
