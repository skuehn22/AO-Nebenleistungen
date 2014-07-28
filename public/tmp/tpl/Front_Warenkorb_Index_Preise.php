<?php if(!class_exists('raintpl')){exit;}?><div class="span-12 prepend-1">
   <?php if( $counterToOrder > 0 ){ ?>
       <span class="teilueberschrift_grau">
           <?php echo translate('Gesamtpreis aller Buchungen'); ?>: <?php echo translatePricing($totalPrice); ?> € <br>
               <?php if( $gesamtRabattWarenkorb > 0 ){ ?>
                   <?php echo translate('Rabatt ihrer Buchung'); ?>: <?php echo translatePricing($gesamtRabattWarenkorb); ?> € <br>
                   <?php echo translate('neuer Preis'); ?>: <?php echo translatePricing($neuerPreis); ?> €
               <?php } ?>
       </span>
   <?php }else{ ?>
   &nbsp;
   <?php } ?>
</div>