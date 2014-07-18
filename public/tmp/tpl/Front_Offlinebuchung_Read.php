<?php if(!class_exists('raintpl')){exit;}?><div class="span-23">
    <form method="post" action="/front/offlinebuchung/suchen-benutzer/">
        <table>
            <tr><td colspan="2">&nbsp;&nbsp;<span class="tabellenueberschrift_gruen"><?php echo translate('Firmensuche'); ?>:</span></td></tr>
            <tr>
                <td><label for="hob"><?php echo translate('HOB Nummer'); ?>: </label></td>
                <td><input type='text' name='hobNummer' id='hobNummer' class='input span-5' value="<?php echo $personalData["hobNummer"];?>">
                </td>
            </tr>
            <tr>
                <td><label for="hob"><?php echo translate('Adress Id'); ?>: </label></td>
                <td><input type='text' name='id' id='id' class='input span-5' value="<?php echo $personalData["id"];?>">
                </td>
            </tr>
            <tr>
                <td><label for="lastname"><?php echo translate('Name'); ?>: </label></td>
                <td><input type='text' name='lastname' id='lastname' class='input span-5'
                           value="<?php echo $personalData["lastname"];?>"></td>
            </tr>
            <tr>
                <td><label for="company"><?php echo translate('Firma / Schule'); ?> : </label></td>
                <td><input type='text' name='company' id='company' class='input span-5' value="<?php echo $personalData["company"];?>"></td>
            </tr>
            <tr>
                <td><label for="email"><?php echo translate('E-Mail'); ?>: </label></td>
                <td>
                    <input type='text' name='email' id='email' class='input span-5' value="<?php echo $personalData["email"];?>">
                </td>
            </tr>
            <tr>
                <td><label for="email"><?php echo translate('E-Mail Offline'); ?>: </label></td>
                <td>
                    <input type='text' name='email_offline' id='email_offline' class='input span-5' value="<?php echo $personalData["email_offline"];?>">
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td><input type='submit' class="submit" value="<?php echo  translate('suchen'); ?>" name='submitSuchen' id='submitSuchen'>
                </td>
            </tr>
        </table>
    </form>

    <br>

    <?php if( $benutzer ){ ?>
    <table style="width: 100%" class="gitter">
        <tr>
            <td colspan="10" style="padding-top: 5px; padding-top: 5px;">&nbsp;&nbsp;<span class="tabellenueberschrift_gruen"><?php echo translate('gefundene Personen'); ?></span></td>
        </tr>
        <tr>
            <td><?php echo  translate('Vorname'); ?></td>
            <td><?php echo  translate('Nachname'); ?></td>
            <td><?php echo  translate('Firma / Schule'); ?></td>
            <td><?php echo  translate('Straße / Hausnummer'); ?></td>
            <td><?php echo  translate('PLZ'); ?></td>
            <td><?php echo  translate('Stadt'); ?></td>
            <td><?php echo  translate('E-Mail'); ?></td>
            <td><?php echo  translate('Telefonnr. geschäftlich'); ?></td>
            <td><?php echo  translate('Telefonnr. privat'); ?></td>
            <td>&nbsp;</td>
        </tr>
        <tr><td colspan="10" style="height: 7px;"></td></tr>
        <?php $counter1=-1; if( isset($benutzer) && is_array($benutzer) && sizeof($benutzer) ) foreach( $benutzer as $key1 => $value1 ){ $counter1++; ?>
        <tr>
            <td><?php echo $value1["firstname"];?></td>
            <td><?php echo $value1["lastname"];?></td>
            <td><?php echo $value1["company"];?></td>
            <td><?php echo $value1["street"];?></td>
            <td><?php echo $value1["zip"];?></td>
            <td><?php echo $value1["city"];?></td>
            <td><?php echo $value1["email"];?></td>
            <td><?php echo $value1["phonenumber"];?></td>
            <td><?php echo $value1["mobile"];?></td>
            <td><form class="form" method="post" action="/front/offlinebuchung/uebernahme-benutzer/"><input type="hidden" name="id" value="<?php echo $value1["id"];?>"><input type="submit" class="submit" name="umschreiben" value="<?php echo  translate('übernehmen'); ?>"></form></td>
        </tr>
        <?php } ?>
    </table>
    <?php } ?>
</div>


