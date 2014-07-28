<?php if(!class_exists('raintpl')){exit;}?><?php if( $gruppenRabatt ){ ?>
    <div class="span-22 block_unten block_gray block" style="padding-left: 40px; padding-right: 40px;">
        <span class="teilueberschrift_gruen"><?php echo translate('gewährte Freiplätze'); ?></span>
        <table>
        <?php $counter1=-1; if( isset($gruppenRabatt) && is_array($gruppenRabatt) && sizeof($gruppenRabatt) ) foreach( $gruppenRabatt as $key1 => $value1 ){ $counter1++; ?>
            <?php if( $value1["freiplaetze"] > 0 ){ ?>
                <tr>
                    <td>
                        <?php if( $value1["freiplaetze"] > 1 ){ ?>
                            <?php echo $value1["freiplaetze"];?> <?php echo translate('Freiplätze im'); ?>
                        <?php }else{ ?>
                            <?php echo $value1["freiplaetze"];?> <?php echo translate('Freiplatz im'); ?>
                        <?php } ?>
                    </td>
                    <td><?php echo $value1["hotelName"];?>: </td>
                    <td><?php echo $value1["freiplaetze"];?> <?php echo translate('Person(en)'); ?>  * <?php echo $value1["categorieName"];?> </td>
                    <td><?php echo translate('am'); ?> <?php echo formatiereDatumIso($value1["datum"]); ?> à <?php echo translatePricing($value1["rabatt"]); ?> € </td>

                </tr>
            <?php } ?>
        <?php } ?>
        </table>
    </div>
<?php } ?>