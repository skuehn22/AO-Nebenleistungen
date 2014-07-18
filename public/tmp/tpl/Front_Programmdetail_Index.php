<?php if(!class_exists('raintpl')){exit;}?><script type="text/javascript" src="/simpleModal/jquery.simplemodal.js"></script>
<script type="text/javascript" src="/simpleModal/steuerung.modal.js"></script>

<?php if( $showBlock ){ ?>

<script type="text/javascript" src='/tpl/Front_Programmdetail_Index.js'></script>

<script type='text/javascript'>
    var fromJahr = <?php echo $bookingdetails["fromJahr"];?>;
    var fromMonat = <?php echo $bookingdetails["fromMonat"];?>;
    var fromTag = <?php echo $bookingdetails["fromTag"];?>;

	var toJahr = <?php echo $bookingdetails["toJahr"];?>;
	var toMonat = <?php echo $bookingdetails["toMonat"];?>;
	var toTag = <?php echo $bookingdetails["toTag"];?>;

    var sperrtage = new Array(<?php echo $sperrtage;?>);
    var geschaeftstage = new Array(<?php echo $geschaeftstage;?>);
    var programmId = <?php echo $bookingdetails["ProgrammId"];?>;
    var preiseProgrammVarianten = new Array(<?php echo $preiseProgrammVarianten;?>);

    var sprache = '<?php echo $sprache;?>';
    var anzahlPreisvarianten = <?php echo $anzahlPreisvarianten;?>;

    var gebuchtesDatum = '<?php echo $gebuchtesDatum;?>';
    var gebuchtePreisvariante = '<?php echo $gebuchtePreisvariante;?>';

    var zeitmanagerSelect = '<?php echo $zeitmanagerSelect;?>';
    var zeitmanagerStunde = '<?php echo $zeitmanagerStunde;?>';
    var zeitmanagerMinute = '<?php echo $zeitmanagerMinute;?>';

    var buchungspauschale = '<?php echo $bookingdetails["buchungspauschale"];?>';
    var preisBuchungspauschale = '<?php echo $bookingdetails["preisBuchungspauschale"];?>';
    var hinweisAuswahlPreisvatiante = "<?php echo translate('Bitte Eingabe überprüfen'); ?>";
    var hinweisOeffnungszeiten = "<?php echo translate('Bitte Öffnungszeiten beachten'); ?>";
</script>
<?php } ?>




<div class="span-24 last">&nbsp;</div>

<div class='span-24' id='programmdetail'>

    <?php if( $buchungsDetailShow ){ ?>

        <form id="formID" class="form formular" method="post" action="/front/programmdetail/update-programmbuchung/">
    <?php }else{ ?>

        <form id="formID" class="form formular" method="post" action="/front/programmdetail/insert-programmbuchung/">
    <?php } ?>


    <?php if( $blockInformationBestandsbuchungProgramme ){ ?>

    <div class='block block_oben block_unten span-24 last'>
        <div style="margin-left: 40px;" class="text">
            <?php echo $blockInformationBestandsbuchungProgramme;?>

        </div>
    </div>

    <div class='span-24 last'>&nbsp;</div>
    <?php } ?>



    <div class='block block_oben ueberschrift block_unten span-24 last'>
        <div style="margin-left: 40px;"><?php echo $bookingdetails["Stadtname"];?> - <span class="stupidcat" editor='textfield' id='progname' label='Programmname'><?php echo $programmdetails["progname"];?></span></div>
    </div>

    <div class="span-17">&nbsp;</div>

    <!-- neue Programmbuchung -->
    <div id='programmbeschreibung' class="block block_oben block_unten" style="padding: 40px; width: 410px; float: left;">
        <span class="stupidcat" id="miniBild" label='Bild' editor='upload'></span>

        <?php if( $showBlock ){ ?>

            <?php if( $sprachenManager ){ ?>

                <?php echo translate('Sprachen'); ?>: <?php $counter1=-1; if( isset($sprachen) && is_array($sprachen) && sizeof($sprachen) ) foreach( $sprachen as $key1 => $value1 ){ $counter1++; ?> <img src='/flags/<?php echo $value1["flag"];?>.png'> <?php } ?>

            <?php } ?>

        <?php } ?>


        <span class="stupidcat" label='Programmbeschreibung' editor='htmleditor' id='txt'><?php echo $programmdetails["txt"];?></span><p>

        <?php if( $showBlock ){ ?>

            <?php if( $programmdetails["treffpunkt"] ){ ?>

                <?php echo translate('Treffpunkt'); ?>: <?php echo $programmdetails["treffpunkt"];?><br>
            <?php } ?>


            <?php if( $programmdetails["opnv"] ){ ?>

                <?php echo translate('öffentlicher Nahverkehr'); ?>: <?php echo $programmdetails["opnv"];?><br>
            <?php } ?>


            <?php if( $bookingdetails["Dauer"] > 0 ){ ?>

                <?php echo translate('voraussichtliche Dauer'); ?>: <?php echo $bookingdetails["Dauer"];?> <?php echo translate('Stunden'); ?>

            <?php } ?>

        <?php } ?>


        <p></p>
        <span class="teilueberschrift_grau"><?php echo translate('Buchungsinformation'); ?>:</span>
        <?php if( $programmdetails["buchungstext"] ){ ?>

            <br><?php echo $programmdetails["buchungstext"];?>

        <?php } ?>

        <?php if( $bookingdetails["buchungspauschale"] == 2 ){ ?>

            <br><?php echo translate('Für die Buchung dieses Programmes erheben wir eine einmalige Buchungspauschale in Höhe von 30,00 €.'); ?>

        <?php } ?>


        <?php if( $showBlock ){ ?>

            <?php if( $stornofristen ){ ?>

                <br><br><?php echo translate('Stornokosten:'); ?><br>

                <?php $counter1=-1; if( isset($stornofristen) && is_array($stornofristen) && sizeof($stornofristen) ) foreach( $stornofristen as $key1 => $value1 ){ $counter1++; ?>

                    <?php if( $value1["tage"] == 999 ){ ?>

                        <?php echo translate('Keine Stornierung möglich'); ?><br>
                    <?php }elseif( $value1["prozente"] == 0 and count($stornofristen) == 1 ){ ?>

                        <?php echo translate('kostenfrei'); ?><br>
                    <?php }else{ ?>

                        <?php if( count($stornofristen) == ($key1 + 1) ){ ?>

                            <br><?php echo translate('ab'); ?>

                        <?php }else{ ?>

                            <br><?php echo translate('bis'); ?>

                        <?php } ?>


                        <?php echo $value1["tage"];?> <?php echo translate('Tag(e) vor Beginn der Buchung'); ?>, <?php echo $value1["prozente"];?> % <?php echo translate('des Preises der Buchung'); ?>.

                    <?php } ?>

                <?php } ?>

            <?php } ?>

        <?php } ?>


        <?php if( $vertragspartner ){ ?>

            <br><br><?php echo translate('Vertragspartner für diese Leistung ist:'); ?><br>
                <?php echo $vertragspartner["company"];?>, <?php echo $vertragspartner["street"];?>, <?php echo $vertragspartner["zip"];?> <?php echo $vertragspartner["city"];?> <br>
                <?php echo $vertragspartner["country"];?><br>
        <?php } ?>


        <?php if( $buchungsTyp == 1 ){ ?>

            <br>
            <img src="/buttons/t.png"> <?php echo translate('Dieses Programm ist noch in der Testphase.Die Buchung wird erst rechtsverbindlich, wenn wir Ihnen innerhalb von max. 3 Werktagen eine seperate Bestätigung per E-Mail schicken'); ?><br>
        <?php } ?>

        <br>



        <?php if( $showBlock ){ ?>

            <?php if( $typOeffnungszeit == 2 ){ ?>

                <?php if( $oeffnungszeiten ){ ?>

                    <br>
                    <table border="0">
                    <tr>
                        <td colspan="3" class="teilueberschrift_grau"><?php echo translate('Öffnungszeiten'); ?>:</td>
                    </tr>
                    <?php $counter1=-1; if( isset($oeffnungszeiten) && is_array($oeffnungszeiten) && sizeof($oeffnungszeiten) ) foreach( $oeffnungszeiten as $key1 => $value1 ){ $counter1++; ?>

                        <tr>
                            <td><?php echo $value1["name"];?>: </td>
                            <td><?php echo $value1["von"];?> &nbsp;&nbsp; <?php echo translate('bis'); ?></td>
                            <td><?php echo $value1["bis"];?> <?php echo translate('Uhr'); ?></td>
                        </tr>
                    <?php } ?>

                    </table>
                <?php } ?>

            <?php } ?>

        <?php } ?>

    </div>

        <!-- Datumswahl -->
        <?php if( $showBlock ){ ?>

        <div class="block block_oben block_unten ueberschrift" style="float: right; width: 420px; padding-left: 20px;">
            <?php echo translate('Bitte wählen'); ?>:
        </div>
        <div id='buchung' style="padding: 10px; float: right; width: 420px;" class="block">

            <?php if( $typOeffnungszeit < 4 ){ ?>

                <div id='datepicker'></div>
            <?php } ?>


            <table>
                <?php if( $typOeffnungszeit < 4 ){ ?>

                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>
                        <?php echo translate('Datum'); ?> <input id='alternateDatum' class="span-3 input" readonly>
                    </td>
                </tr>
                <?php } ?>



                <?php if( $typOeffnungszeit == 3 ){ ?>

                <tr>
                    <td id="zeitmanager">
                        <?php echo translate('Zeit'); ?> &nbsp;&nbsp;
                        <select id="zeitmanagerSelect" name="zeitmanager" class=" input_action">
                            <?php $counter1=-1; if( isset($zeitmanager) && is_array($zeitmanager) && sizeof($zeitmanager) ) foreach( $zeitmanager as $key1 => $value1 ){ $counter1++; ?>

                                <option value="<?php echo $value1["zeit"];?>"><?php echo $value1["zeit"];?></option>
                            <?php } ?>

                        </select> Uhr
                    </td>
                </tr>
                <?php }elseif( $typOeffnungszeit == 2 ){ ?>

                <tr>
                    <?php if( $bestandsbuchung["zeitmanagerStunde"] ){ ?>

                        <td id="zeitmanager" style="padding: 10px;">
                            <?php echo translate('Zeit'); ?> &nbsp;&nbsp;
                            <input type="text" style="width: 30px;" class=" input_action validate[custom[integer],max[24],min[1]]" name="zeitmanagerStunde" id="zeitmanagerStunde" value="<?php echo $bestandsbuchung["zeitmanagerStunde"];?>">
                            :
                            <input type="text" style="width: 30px;" class=" input_action validate[custom[integer],max[59],min[0]]" name="zeitmanagerMinute" id="zeitmanagerMinute" value="<?php echo $bestandsbuchung["zeitmanagerMinute"];?>">
                            &nbsp; <?php echo translate('Uhr'); ?></td>
                    <?php }else{ ?>

                        <td id="zeitmanager" style="padding: 10px;">
                            <?php echo translate('Zeit'); ?> &nbsp;&nbsp;
                            <input type="text" style="width: 30px;" class=" input_action validate[custom[integer],max[24],min[1]]" name="zeitmanagerStunde" id="zeitmanagerStunde" value="0">
                            :
                            <input type="text" style="width: 30px;" class=" input_action validate[custom[integer],max[59],min[0]]" name="zeitmanagerMinute" id="zeitmanagerMinute" value="00">
                            &nbsp; <?php echo translate('Uhr'); ?>

                        </td>
                    <?php } ?>

                </tr>
                <?php } ?>


                <?php if( $sprachenmanager ){ ?>

                <tr>
                    <td>
                        <?php echo translate('Programmsprachen'); ?>: &nbsp;&nbsp;
                        <select id="sprachenmanagerSelect" name="spracheManager" class="input_action">
                            <?php echo sprachenmanagerSelect($sprachenmanager); ?>

                        </select>
                    </td>
                </tr>
                <?php } ?>

            </table>
            <!-- Programmvariante -->
          <?php if( $showBlock ){ ?>

          <div class="ueberschrift" >
              <div>
                  <?php echo translate('Datum'); ?>: <span id="buchungsdatum"></span>
                  <!--<span id="Kapazitaet" style="visibility: hidden"></span> -->
              </div>
              <div style="margin-left: 40px;" id="Kapazitaet_Hinweis" class="standardrot">
                  <?php echo translate('Programmkapazität überschritten. Maximale Buchbarkeit:'); ?> <span id="Kapazitaet_Hinweis_buchbar"></span>
              </div>
          </div>

          <div id="buchungsblock">

              <input type='hidden' name='ProgrammId' value="<?php echo $bookingdetails["ProgrammId"];?>">
              <input type='hidden' name='datum' id='datum' value="<?php echo $gebuchtesDatum;?>">
              <input type="hidden" name="sprache" id="sprache" value="1">

              <?php if( $programmvarianten ){ ?>

              <table class="" style="margin-left: 0px; ">
                  <tr>
                      <td><?php echo translate('Anzahl'); ?></td>
                      <td><?php echo translate('Programmvariante'); ?></td>
                      <td><?php echo translate('Einzelpreis'); ?></td>
                      <td><?php echo translate('Preis der Programmvariante'); ?></td>
                  </tr>
                  <?php if( $bookingdetails["buchungspauschale"] == 2 ){ ?>

                  <tr>
                      <td>
                          <input type="text" value="1" class="input_action span-1" disabled="true">
                      </td>
                      <td>
                          <?php echo translate('Buchungspauschale'); ?>

                      </td>
                      <td>
                          30,00 €
                      </td>
                      <td>
                          30,00 €
                      </td>
                  </tr>
                  <?php } ?>

                  <?php $counter1=-1; if( isset($programmvarianten) && is_array($programmvarianten) && sizeof($programmvarianten) ) foreach( $programmvarianten as $key1 => $value1 ){ $counter1++; ?>

                  <tr id="zeile<?php echo $key1;?>">
                      <?php if( $buchungsDetailShow ){ ?>

                          <?php if( $key1 == 0 ){ ?>

                              <td><input type="text" name="<?php echo $key1;?>" id="<?php echo $key1;?>" class="anzahlProgramme input_action span-2 validate[custom[integer]]" value="<?php echo $bestandsbuchung["anzahl"];?>"></td>
                          <?php }else{ ?>

                              <td><input type="text" name="<?php echo $key1;?>" id="<?php echo $key1;?>" class="anzahlProgramme input_action span-2 validate[custom[integer]]" value="0"></td>
                          <?php } ?>

                      <?php }else{ ?>

                          <td><input type="text" name="<?php echo $key1;?>" id="<?php echo $key1;?>" class="anzahlProgramme input_action span-2 validate[custom[integer]]" value="0"></td>
                      <?php } ?>

                      <td>
                          <select name="programmvariante_<?php echo $key1;?>" id="programmvariante_<?php echo $key1;?>" class="span-10 input_action programmvarianteSelect">
                              <?php echo $value1["options"];?>

                          </select>
                      </td>
                      <td class="span-3">
                          <span id="einzelpreis_<?php echo $key1;?>" class="einzelpreis"></span> €
                      </td>
                      <td class="span-3">
                          <span id="preis_<?php echo $key1;?>" class="preis"></span> €
                      </td>

                  </tr>
                  <?php } ?>

                  <tr>
                      <td colspan="4">
                          <?php echo translate('Gesamtpreis'); ?>: <span id='gesamtpreis'></span> €
                      </td>
                  </tr>
              </table>

              <?php if( $flagRabattProgramm == 2 ){ ?>

                  <table class="gitter" style="margin-left: 20px;">
                      <tr>
                          <td colspan="2"><?php echo translate('Achtung ! Nur in einer Preisvariante eine Anzahl eingeben'); ?></td>
                      </tr>
                      <tr>
                          <td>
                              <?php echo translate('Einkaufspreis'); ?>:
                          </td>
                          <td>
                              <input type="text" name="einkaufspreis" id="einkaufspreis" class="input_action validate[required]">
                          </td>
                      </tr>
                      <tr>
                          <td>
                              <?php echo translate('Verkaufspreis'); ?>:
                          </td>
                          <td>
                              <input type="text" name="verkaufspreis" id="verkaufspreis" class="input_action validate[required]">
                          </td>
                      </tr>
                      <tr>
                          <td colspan="2">
                              <input type="submit" style="float: right; margin-right: 30px;" id="warenkorb" name="warenkorb" class="submit" value="<?php echo translate('in den Warenkorb'); ?>">
                          </td>
                      </tr>
                  </table>
              <?php }else{ ?>

                  <div id='submitButton' style="margin-left: 20px;">
                      <input type="submit" style="float: right; margin-right: 30px;" id="warenkorb" name="warenkorb" class="submit" value="<?php echo translate('in den Warenkorb'); ?>">
                  </div>

              <?php } ?>

              <?php } ?>

              <?php if( $programmvarianten ){ ?>

                  </form>
              <?php } ?>

          </div>
          <?php } ?>

          </div>
          <?php } ?>




    </div>
    <!-- Ende Gesamtblock Programm -->