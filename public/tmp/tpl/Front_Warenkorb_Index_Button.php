<?php if(!class_exists('raintpl')){exit;}?><div style="width: 430px; float: left;">
    <?php if( $counterToOrder > 0 ){ ?>
    <?php if( $flag_show_buttons ){ ?>
        <?php if( $flagStornierung == 1 ){ ?>
            <table style="float: right; margin-right: 40px; margin-bottom: 0px;">
                <tr>
                    <td>
                        <form class="form" action="/front/stornierung/warenkorb-delete/" method="post">
                            <?php if( $blockGebuchteProgramme ){ ?>
                                <input type="submit" id="warenkorbLeerenOben" class="warenkorb_leeren" value="<?php echo  translate('Warenkorb stornieren'); ?>">
                            <?php }else{ ?>
                                <input type="submit" id="warenkorbLeerenOben" class="warenkorb_leeren" value="<?php echo  translate('Warenkorb leeren'); ?>" style="position: relative; top: -3px;">
                            <?php } ?>
                        </form>
                    </td>
                    <td>
                        <?php if( $anzahlNeuerArtikel > 0 ){ ?>
                            <?php if( $buchungsnummerZaehler == 0 ){ ?>
                                <form class="form" action="/front/vormerken-anmelden/index/" method="post">
                                    <input type="submit" class="warenkorb_vormerken" value="<?php echo translate('vormerken'); ?>">
                                </form>
                            <?php } ?>
                        <?php } ?>
                    </td>
                    <?php if( $kundenId > 0 ){ ?>
                    <!-- wenn Benutzer angemeldet und Rolle mindestens 'Kunde' == 3 -->
                    <?php if( $rolleId >= 3 ){ ?>
                        <td style="padding-right: 0px;">
                            <form class="form" action="/front/warenkorb/orderdata-warenkorb/" method="post">
                                <?php if( $flagBestandsbuchung ){ ?>
                                    <input type="submit" class="warenkorb_buchen" value="<?php echo  translate('Buchung anpassen'); ?>">
                                <?php }else{ ?>
                                    <input type="submit" class="warenkorb_buchen" value="<?php echo  translate('weiter zur Buchung'); ?>">
                                <?php } ?>
                            </form>
                        </td>
                    <!-- wenn Benutzer angemeldet, aber noch nicht vollständig authentifiziert -->
                    <?php }else{ ?>
                        <td style="padding-right: 0px;">
                            <form class="form" action="/front/personalien/view/" method="post">
                                <input type="submit" class="warenkorb_buchen" value="<?php echo  translate('weiter zur Buchung'); ?>">
                            </form>
                        </td>
                    <?php } ?>
                <!-- wenn Benutzer nicht angemeldet -->
                <?php }else{ ?>
                    <td style="padding-right: 0px;">
                        <form class="form" action="/front/personaldata/index/" method="post">
                            <input type="submit" class="warenkorb_buchen" value="<?php echo  translate('weiter zur Buchung'); ?>">
                        </form>
                    </td>
                <?php } ?>
                </tr>
            </table>
        <?php } ?>
    <?php } ?>
    <?php }else{ ?>
    &nbsp;
    <?php } ?>
</div>