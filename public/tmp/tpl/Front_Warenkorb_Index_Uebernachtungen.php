<?php if(!class_exists('raintpl')){exit;}?><!-- Übernachtungen -->
<?php $counter1=-1; if( isset($shoppingCartUebernachtungNested) && is_array($shoppingCartUebernachtungNested) && sizeof($shoppingCartUebernachtungNested) ) foreach( $shoppingCartUebernachtungNested as $key1 => $value1 ){ $counter1++; ?>
    <div class='span-24 block block_oben teilueberschrift'>
        <div class="span-12 prepend-1">
            <div class="ueberschrift_gruen span-3"><?php echo $value1["city"];?>:</div>
            <?php echo translate('Übernachtung'); ?>: <span class="teilueberschrift_gruen"><?php echo $value1["hotelUeberschrift"];?></span>
        </div>
        <div style="width: 430px; float: left;">

            <table style="float: right;">
                <tr>
                    <td>
                        <?php if( $flag_show_buttons ){ ?>
                            <a class="button_link" href="/front/hotelreservation-delete/hotelbuchung/teilrechnungenId/<?php echo $value1["teilrechnung_id"];?>"><?php echo translate('Hotelbuchung löschen'); ?></a>
                        <?php } ?>
                    </td>
                    <td>
                        <form class="form" style="margin-right: 40px;" action="/front/hotelreservation/update-show" method="post">
                            <input type="hidden" name="propertyId" value="<?php echo $value1["propertyId"];?>">
                            <input type="hidden" name="suchdatum" value="<?php echo $value1["anreisedatum"];?>">
                            <input type="hidden" name="adult" value="<?php echo $value1["adult"];?>">
                            <input type="hidden" name="days" value="<?php echo $value1["nights"];?>">
                            <input type="hidden" name="to" value="<?php echo $value1["to"];?>">
                            <input type="hidden" name="from" value="<?php echo $value1["anreisedatum"];?>">
                            <input type="hidden" name="cityId" value="<?php echo $value1["cityId"];?>">
                            <input type="hidden" name="flagUpdate" value="2">
                            <input type="hidden" name="teilrechnungen_id" value="<?php echo $value1["teilrechnung_id"];?>">

                            <?php if( $flag_show_buttons ){ ?>
                                <input type="submit" class="hotelbuchung_bearbeiten" value="<?php echo translate('Hotelbuchung bearbeiten'); ?>">
                            <?php } ?>

                        </form>
                    </td>
                </tr>
            </table>

        </div>
    </div>

    <?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Uebernachtungen_Raten") . ( substr("Front_Warenkorb_Index_Uebernachtungen_Raten",-1,1) != "/" ? "/" : "" );$tpl->assign( "key", $key1 ); $tpl->assign( "value", $value1 );$tpl->draw( basename("Front_Warenkorb_Index_Uebernachtungen_Raten") );self::$tpl_dir = $tpl_dir_temp;?>

    <?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Uebernachtungen_Produkte") . ( substr("Front_Warenkorb_Index_Uebernachtungen_Produkte",-1,1) != "/" ? "/" : "" );$tpl->assign( "key", $key1 ); $tpl->assign( "value", $value1 );$tpl->draw( basename("Front_Warenkorb_Index_Uebernachtungen_Produkte") );self::$tpl_dir = $tpl_dir_temp;?>

    <div class="span-24">&nbsp;</div>
<?php } ?>

<!-- Alternativ Übernachtungen -->
<?php if( !$shoppingCartUebernachtungNested ){ ?>
<div class="span-24 block_oben block block_unten">
    <div class="span-12 prepend-1 teilueberschrift"><?php echo translate('keine Übernachtungen gewählt'); ?></div>
    <div style="width: 430px; float: left;">
        <?php if( $flagStornierung == 1 ){ ?>
            <form method='post' action='<?php echo $alternativButtonHotelbuchung;?>' style="float: right; margin-right: 40px;">
                <input type='submit' class='submit_weiter' src='' name='suchen' value="<?php echo translate('Übernachtung suchen'); ?>">
            </form>
        <?php } ?>
    </div>
</div>
<?php } ?>