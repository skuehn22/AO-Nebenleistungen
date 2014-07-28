<?php if(!class_exists('raintpl')){exit;}?><script type="text/javascript" src='/tpl/Front_Hotelsearch_Index.js'></script>

<!-- Lightbox und Tooltip -->
<script type="text/javascript" src="/simpleModal/jquery.simplemodal.js"></script>
<script type="text/javascript" src="/simpleModal/steuerung.modal.js"></script>
<script type="text/javascript" src="/tooltipImage/tooltip.image.js"></script>

<script type="text/javascript">
    var hinweisMinimum = '<?php echo translate('mindestens 1 Übernachtung wählen'); ?>';
    var hinweisMaximum = '<?php echo translate('maximal 12 Übernachtungen möglich'); ?>';
    var hinweisDatumseingabe = '<?php echo translate('Bitte Datumsangaben kontrollieren'); ?>'
</script>

    <div>
        <div class="block block_oben block_unten" style="padding-left: 40px;"><?php echo $breadcrumb;?></div>
    </div>

    <div class="span-16 last">&nbsp;</div>

    <div class="span-16">
        <div class="ueberschrift_gruen block block_oben" style="padding-left: 40px;"><?php echo translate('Suche nach Übernachtungen in'); ?> <?php echo $ort;?></div>

        <div class="block block_unten" style="height: 300px; padding-left: 40px;">
                <form action="/front/hotellist/index/" method="post" id="formHotelsearch" class="form">
                    <input type="hidden" name="city" value="<?php echo $city;?>">

                    <div class="span-10">
                        <table border="1" cellspacing="10">
                            <tr>
                                <td>
                                    <span class="teilueberschrift_gruen"><?php echo translate('Anreise'); ?>: </span><br>
                                    <input id='startDatepicker' name="from" class="input" readonly>
                                </td>
                                <td>
                                    <span class="teilueberschrift_gruen"><?php echo translate('Abreise'); ?>: </span><br>
                                    <input id='endeDatepicker' name="to" class="input" readonly>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div id="datepickerStart"></div>
                                </td>
                                <td>
                                    <div id="datepickerEnde"></div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <?php echo translate('berechnete Anzahl Übernachtungen'); ?><br>
                                    <?php echo translate('maximal 12 Übernachtungen'); ?><br>
                                    <input name="days" id="days" type="hidden">
                                    <?php echo translate('Nächte'); ?>: <span id="anzeigeNächte">0</span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div style="float: left; width: 170px;margin-left: 10px;">
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="height: 60px;">
                                    &nbsp;
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="teilueberschrift_gruen"><?php echo translate('Personenzahl'); ?> :</span><br>
                                    <?php echo translate('(Minimum: 10 Personen)'); ?><br>
                                    <input type="text" name="adult" id="adult" value="10" class="validate[required,custom[integer],min[10]] input_action">
                                </td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td align="left">
                                    <input type="submit" class="submit" onclick="return kontrolleAnzahlNaechte();" value="<?php echo translate('Übernachtung suchen'); ?>" name="suchen">
                                </td>
                            </tr>
                        </table>
                    </div>

            </form>
        </div>
    </div>

    <!-- Beschreibung der Stadt -->
    <div style="float: left; width: 310px;">
        <div class="ueberschrift block_oben block block_unten" style="padding-left: 10px;">
            <?php echo $ort;?>
        </div>
        <div class="block_unten block_links_rechts" style="padding: 10px; min-height: 200px;">
            <div style="width: 256; padding: 5px 0px 0px 5px; margin: 0px 5px 10px 0px;" class="bildrahmen">
                <?php echo anzeigenBilder($city,10,244, 1); ?>
            </div>
            <?php echo $uebernachtungenStadt;?>
        </div>
    </div>