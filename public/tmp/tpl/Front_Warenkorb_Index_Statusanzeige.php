<?php if(!class_exists('raintpl')){exit;}?><div class="block span-24 block_oben block_unten">
    <div class="span-16 prepend-1">
        <?php echo $breadcrumb;?>
    </div>

    <div class="span-6 append-1 last">
        <span class="standardtextfett">
            <?php echo translate('Kundennummer'); ?>:
            <?php if( $kundenId > 0 ){ ?>
                <?php echo $kundenId;?>
            <?php }else{ ?>
                <?php echo translate('unbekannt'); ?>
            <?php } ?>
            <br>

            <?php if( $kompletteBuchungsnummer != ' ' ){ ?>
                <?php echo translate('Buchungsnummer'); ?>: <?php echo $kompletteBuchungsnummer;?><br>
            <?php } ?>

            <?php echo translate('Status'); ?>:
            <?php if( $buchungsnummerZaehler > 0 ){ ?>
                <?php echo translate('bearbeiten einer Buchung'); ?><br>
            <?php }else{ ?>
                <?php echo translate('neue Buchung'); ?><br>
            <?php } ?>
        </span>
    </div>
</div>