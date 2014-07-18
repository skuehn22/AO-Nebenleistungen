<?php if(!class_exists('raintpl')){exit;}?><table style="width: 100%; margin-left: 40px;">
    <tr>
        <td class="span-5">
            <label for="title"><?php echo translate('Anrede'); ?>: </label>
        </td>
        <td class="span-8" colspan="2">
            <select name="title" size="1" id="title" class="input span-3">
                <?php $counter1=-1; if( isset($titles) && is_array($titles) && sizeof($titles) ) foreach( $titles as $key1 => $value1 ){ $counter1++; ?>
                <?php if( $value1["checked"] == 0 ){ ?>
                    <option value="<?php echo $value1["title"];?>"> <?php echo $value1["title"];?></option>
                <?php }else{ ?>
                    <option value="<?php echo $value1["title"];?>" selected> <?php echo $value1["title"];?></option>
                <?php } ?>
                <?php } ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><label for="firstname"><?php echo translate('Vorname'); ?>: </label></td>
        <td colspan="2">
            <input type='text' name='firstname' id='firstname' class='input span-5' value="<?php echo $personalData["firstname"];?>">
        </td>
    </tr>
    <tr>
        <td><label for="lastname"><?php echo translate('Nachname'); ?> *: </label></td>
        <td colspan="2">
            <input type='text' name='lastname' id='lastname' class='validate[required,minSize[2]] input span-5' value="<?php echo $personalData["lastname"];?>">
        </td>
    </tr>
    <tr>
        <td><label for="company"><?php echo translate('Firma / Schule'); ?> : </label></td>
        <td colspan="2">
            <input type='text' name='company' id='company' class='input span-5' value="<?php echo $personalData["company"];?>">
        </td>
    </tr>
    <tr>
        <td>
            <label for="country"><?php echo translate('Land'); ?> *: </label>
        </td>
        <td  colspan="2">
            <select name="country" size="1" class='input span-5'>
                <?php $counter1=-1; if( isset($country) && is_array($country) && sizeof($country) ) foreach( $country as $key1 => $value1 ){ $counter1++; ?>
                    <?php if( $value1["checked"] > 0 ){ ?>
                        <option value="<?php echo $value1["id"];?>" selected><?php echo $value1["Name"];?></option>
                    <?php }else{ ?>
                        <option value="<?php echo $value1["id"];?>"><?php echo $value1["Name"];?></option>
                    <?php } ?>
                <?php } ?>
            </select>
        </td>
    </tr>
    <tr>
        <td>
            <label for="street"><?php echo translate('Straße / Hausnummer'); ?> *: </label>
        </td>
        <td colspan="2">
            <input type='text' name='street' id='street' class='validate[required,minSize[2]] input' style="width: 248px;" value="<?php echo $personalData["street"];?>">
        </td>
    </tr>
    <tr>
        <td><label for="zip"><?php echo translate('PLZ / Stadt'); ?> *: </label></td>

        <td colspan="2">
            <input type='text' name='zip' id='zip' class='validate[required,minSize[2]] input span-2' value="<?php echo $personalData["zip"];?>">
            <input type='text' name='city' id='city' class='validate[required,minSize[2]] input span-5' value="<?php echo $personalData["city"];?>">
        </td>
    </tr>
    <?php if( $mail_insert ){ ?>
        <tr>
            <td>&nbsp; <?php echo translate('E-Mail'); ?>: </td>
            <td  colspan="2"><?php echo $personalData["email"];?></td>
        </tr>
    <?php }else{ ?>
        <tr>
            <td>
                <label for="email"><?php echo translate('E-Mail'); ?> *: </label>
            </td>
            <td colspan="2">
                <input type='text' name='email' id='email' class='validate[required,custom[email]] input span-5' value="<?php echo $personalData["email"];?>" autocomplete="off">
                <div id='mail_info' name='mail_info'></div>
            </td>
        </tr>
        <tr>
            <td>
                <label for="email_repeat"><?php echo translate('E-Mail wiederholen'); ?> *: </label>
            </td>
            <td colspan="2">
                <input type='text' name='email_repeat' id='email_repeat' class='validate[required,equals[email],custom[email]] input span-5' value="<?php echo $personalData["email"];?>" autocomplete="off">
            </td>
        </tr>
    <?php } ?>
    <tr>
        <td>
            <label for="password"><?php echo translate('Passwort'); ?> *: </label>
        </td>
        <td>
            <input type='password' name='password' id='password' class='validate[required,minSize[8]] input span-5' autocomplete="off">
        </td>
        <td rowspan="3" class="span-8">
            <div style="width: 240px;">
                <?php echo translate('Bitte geben Sie hier ein Passwort Ihrer Wahl ein. Sie können sich mit Ihrer E-Mail und dem Passwort später wieder als Kunde bei uns einloggen.'); ?>
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <label for="password_repeat"><?php echo translate('Passwort wiederholen'); ?> *: </label>
        </td>
        <td>
            <input type='password' name='password_repeat' id='password_repeat' class='validate[required,equals[password],minSize[8]] input span-5' autocomplete="off">
        </td>
    </tr>
    <tr>
        <td>
            <label for="phonenumber"><?php echo translate('Telefonnummer'); ?>: </label>
        </td>
        <td>
            <input type='text' name='phonenumber' id='phonenumber' class='validate[minSize[3],custom[onlyNumberSp]] input span-5' value="<?php echo $personalData["phonenumber"];?>">
        </td>
    </tr>
    <tr>
        <td>
            <label for="schriftwechsel"><?php echo translate('Schriftwechselsprache'); ?> *: </label>
        </td>
        <td colspan="2">
            <select name="schriftwechsel" id='schriftwechsel' size="1" class='input span-5'>
                <option value="1" selected><?php echo translate('deutsch'); ?></option>
                <option value="2"><?php echo translate('englisch'); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td colspan="2">
            <input type='submit' class="submit" value="<?php echo  translate('speichern'); ?>" name='submitPersonaldata' id='personaldata'><span id="informationOfflinekunde"></span>
        </td>
    </tr>
</table>