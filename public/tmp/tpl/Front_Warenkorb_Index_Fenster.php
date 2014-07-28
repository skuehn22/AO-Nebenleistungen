<?php if(!class_exists('raintpl')){exit;}?><!-- Bestätigungsfenster -->

<!-- stornieren Buchung -->
<table class="infoWarnung" id="allClear" style="visibility: hidden;">
    <tr>
        <td colspan="2" align="center">
            <?php if( $blockGebuchteProgramme ){ ?>
                <?php echo translate('Wollen Sie den kompletten Warenkorb wirklich stornieren'); ?> ?
            <?php }else{ ?>
                <?php echo translate('Wollen Sie wirklich den Inhalt des Warenkorbes löschen'); ?> ?
            <?php } ?>
        </td>
    </tr>
    <tr>
        <td align="center">
            <form id="allClearAction" action="#" class="form">
                <input type="submit" class="submit" value="Ja">
            </form>
        </td>
        <td align="center">
            <form class="form">
                <input class="allClearNo submit" type="submit" value="Nein">
            </form>
        </td>
    </tr>
</table>

<table class="infoWarnung" id="singleClear" style="visibility: hidden;">
    <tr>
        <td colspan="2" align="center">
            <?php echo translate('Wollen Sie diese Übernachtung wirklich löschen'); ?> ?
        </td>
    </tr>
    <tr>
        <td align="center">
            <form action="#" id="singleClearAction" class="form">
                <input type="submit" class="submit" value="Ja">
            </form>
        </td>
        <td align="center">
            <form class="form">
                <input class="allClearNo submit" type="submit" value="Nein">
            </form>
        </td>
    </tr>
</table>

<table class="infoWarnung" id="singleProduct" style="visibility: hidden;">
    <tr>
        <td colspan="2" align="center">
            <?php echo translate('Wollen Sie dieses Hotelprodukt wirklich löschen'); ?> ?
        </td>
    </tr>
    <tr>
        <td align="center">
            <form action="#" id="singleClearProduct" class="form">
                <input type="submit" class="submit" value="Ja">
            </form>
        </td>
        <td align="center">
            <form class="form">
                <input class="allClearNo submit" type="submit" value="Nein">
            </form>
        </td>
    </tr>
</table>

<table class="infoWarnung" id="singleProgramm" style="visibility: hidden;">
    <tr>
        <td colspan="2" align="center">
            <span id="programmAction"></span>
        </td>
    </tr>
    <tr>
        <td align="center">
            <form action="#" id="singleClearProgramm" class="form">
                <input type="submit" class="submit" value="Ja">
            </form>
        </td>
        <td align="center">
            <form class="form">
                <input class="allClearNo submit" type="submit" value="Nein">
            </form>
        </td>
    </tr>
</table>