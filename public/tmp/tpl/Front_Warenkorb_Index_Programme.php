<?php if(!class_exists('raintpl')){exit;}?><!-- Programme -->
<?php $counter1=-1; if( isset($shoppingCartNestedProgramme) && is_array($shoppingCartNestedProgramme) && sizeof($shoppingCartNestedProgramme) ) foreach( $shoppingCartNestedProgramme as $key1 => $value1 ){ $counter1++; ?>
<div class="span-24 block_oben block_unten block">
    <div class="span-12 prepend-1 ueberschrift_gruen"><?php echo $value1["stadtname"];?></div>
    <div style="width: 430px; float: left;">
        <table style="float: right;">
            <tr>
                <td>
                    <form class="form" style="margin-right: 40px;" action="<?php echo $alternativButtonProgrammbuchung;?>" method="post">
                        <?php if( $flag_show_buttons ){ ?>
                            <?php if( $flagStornierung == 1 ){ ?>
                                <input type="submit" class="programmbuchung_neu" value="<?php echo  translate('weitere Programme buchen'); ?>">
                            <?php } ?>
                        <?php } ?>
                    </form>
                </td>
            </tr>
        </table>
    </div>
</div>
    <?php $counter2=-1; if( isset($value1["programme"]) && is_array($value1["programme"]) && sizeof($value1["programme"]) ) foreach( $value1["programme"] as $key2 => $value2 ){ $counter2++; ?>

        <!-- bereits stornierte Programme ausblenden -->
        <?php if( $value2["anzahl"] > 0 ){ ?>
            <div class='block_gray block block_unten' style="float: left;">
                <div class="span-5 prepend-1">
                    <?php echo anzeigenBilder($value2["programmdetails_id"],1,150); ?>
                </div>
                <div class="span-12">
                    <p class="teilueberschrift_gruen"><?php echo $value2["progname"];?></p>

                    <p><?php echo $value2["preisvarianteName"];?></p>

                    <?php if( $value2["typOeffnungszeiten"] < 4 ){ ?>
                        <?php echo translate('Datum der Durchführung'); ?>: <?php echo $value2["datum"];?><br>
                    <?php } ?>

                    <?php if( $value2["typOeffnungszeiten"] < 4 ){ ?>
                        <?php if( $value2["typOeffnungszeiten"] > 1 ){ ?>
                            <?php echo translate('Zeit der Durchführung'); ?>: <?php echo formatiereZeit($value2["zeit"],2); ?> Uhr<br>
                        <?php } ?>
                    <?php } ?>

                    <?php if( $value2["gebuchteProgrammsprache"] > 0 ){ ?>
                        <?php echo translate('gebuchte Programmsprache'); ?>: <img src="/flags/<?php echo flagge($value2["gebuchteProgrammsprache"]); ?>.png"><br>
                    <?php } ?>

                    <?php echo translate('Preis'); ?>: <?php echo translatePricing($value2["preisvariantePreis"]); ?>  €<br>
                    <?php echo translate('Anzahl'); ?>: <?php echo $value2["anzahl"];?><br><br>
                    <?php echo translate('Gesamtpreis für diese Programmvariante'); ?>: <?php echo translatePricing($value2["preisvarianteGesamtpreis"]); ?>  €<br>

                    <?php if( $value2["vertragspartner"] ){ ?>
                        <br><?php echo translate('Vertragspartner für diese Leistung ist:'); ?><br>
                        <?php echo $value2["vertragspartner"]["company"];?>, <?php echo $value2["vertragspartner"]["street"];?>, <?php echo $value2["vertragspartner"]["zip"];?> <?php echo $value2["vertragspartner"]["city"];?> <br>
                        <?php echo $value2["vertragspartner"]["country"];?><br>
                    <?php } ?>

                    <?php if( $value2["tageStornofristen"] ){ ?>
                        <br><?php echo translate('Stornokosten:'); ?><br>

                        <?php $counter3=-1; if( isset($value2["tageStornofristen"]) && is_array($value2["tageStornofristen"]) && sizeof($value2["tageStornofristen"]) ) foreach( $value2["tageStornofristen"] as $key3 => $value3 ){ $counter3++; ?>
                            <?php if( $value3["tage"] == 999 ){ ?>
                                <?php echo translate('Keine Stornierung möglich'); ?>.
                            <?php }else{ ?>
                                <?php echo $value3["tage"];?> <?php echo translate('Tage vor Beginn der Buchung'); ?>, <?php echo $value3["prozente"];?> % <?php echo translate('des Preises der Buchung'); ?>. <br>
                            <?php } ?>
                        <?php } ?>
                        <br>
                    <?php } ?>

                    <?php if( $value2["hinweis"] ){ ?>
                        <?php echo translate('saisonaler Hinweis'); ?>: <?php echo $value2["hinweis"];?><p>
                    <?php } ?>

                    <?php if( $value2["oepnv"] ){ ?>
                        <?php echo translate('öffentlicher Nahverkehr'); ?>: <?php echo $value2["oepnv"];?><p>
                    <?php } ?>

                    <?php if( $value2["treffpunktText"] ){ ?>
                        <?php echo translate('Treffpunkt'); ?>: <?php echo $value2["treffpunktText"];?><p>
                    <?php } ?>

                    <?php if( $value2["oeffnungszeiten"] ){ ?>
                        <?php echo translate('Öffnungszeiten'); ?>:<br> <?php echo $value2["oeffnungszeiten"];?><p>
                    <?php } ?>
                </div>
                <div style="float: left; width: 228px;">
                    <table style="float: right; margin-right: 40px;">
                        <?php if( $flag_show_buttons ){ ?>
                            <!-- neues Programm -->
                            <?php if( $value2["status"] == 1 ){ ?>
                            <tr>
                                <td>
                                    <form class="form" action="#" method="post">
                                        <input type="submit" id="<?php echo $value2["id"];?>" class="programmbuchung_stornieren singleProgrammButton" value="<?php echo  translate('löschen'); ?>">
                                    </form>
                                </td>
                                <td>
                                    <form class="form" action="/front/programmdetail/edit-programmbuchung/idBuchungstabelle/<?php echo $value2["id"];?>" method="post">
                                        <input type="submit" class="programmbuchung_bearbeiten" value="<?php echo  translate('bearbeiten'); ?>">
                                    </form>
                                </td>
                            </tr>
                            <?php }else{ ?>

                            <!-- bereits gebuchtes Programm -->
                            <tr>
                                <td colspan="2" class="input_action">
                                    <?php echo translate('bereits gebuchtes Programm'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <form class="form" action="#" method="post">
                                        <?php if( $value2["inStornofrist"] == 1 ){ ?>
                                            <input type="submit" id="<?php echo $value2["id"];?>" class="programmbuchung_stornieren singleProgrammButton" value="<?php echo  translate('kostenpflichtig stornieren'); ?>">
                                        <?php }else{ ?>
                                            <input type="submit" id="<?php echo $value2["id"];?>" class="programmbuchung_stornieren stornoProgrammButton" value="<?php echo  translate('kostenfrei stornieren'); ?>">
                                        <?php } ?>
                                    </form>
                                </td>
                                <td>
                                    <form class="form" action="/front/programmdetail/edit-programmbuchung/idBuchungstabelle/<?php echo $value2["id"];?>" method="post">
                                        <?php if( $value2["preisvariantePreis"] > 0 ){ ?>
                                            <input type="submit" class="programmbuchung_bearbeiten" value="<?php echo  translate('bearbeiten'); ?>">
                                        <?php } ?>
                                    </form>
                                </td>
                            </tr>
                            <?php } ?>
                        <?php } ?>

                        <tr>
                            <td colspan="2">
                                <?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Buchungstyp") . ( substr("Front_Warenkorb_Buchungstyp",-1,1) != "/" ? "/" : "" );$tpl->assign( "key", $key2 ); $tpl->assign( "value", $value2 );$tpl->draw( basename("Front_Warenkorb_Buchungstyp") );self::$tpl_dir = $tpl_dir_temp;?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        <?php } ?>
    <?php } ?>
<?php } ?>

<!-- Alternativ Programme -->
<?php if( $flag_show_buttons ){ ?>
    <?php if( !$shoppingCartNestedProgramme ){ ?>
    <div class="span-24">&nbsp;</div>

    <div class="span-24 block_oben block block_unten">
        <div class="span-12 prepend-1 teilueberschrift"><?php echo translate('keine touristischen Programme gewählt'); ?>
        </div>
        <div style="width: 430px; float: left;">
            <form method='post' action='<?php echo $alternativButtonProgrammbuchung;?>' style="float: right; margin-right: 40px;">
                <input type='submit' class='programmbuchung_neu' src='' name='suchenProgramme' value="<?php echo translate('Programmangebote suchen'); ?>">
            </form>
        </div>
    </div>
    <?php } ?>
<?php } ?>