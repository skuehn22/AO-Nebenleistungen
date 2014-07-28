<?php if(!class_exists('raintpl')){exit;}?><!-- Buchungspauschale -->
<?php if( $buchungspauschale ){ ?>
<div class='block_gray block block_unten block_oben span-24 last' style="float: left;">
    <div class="span-5 prepend-1">
        &nbsp;
    </div>
    <div class="span-12">
        <p class="teilueberschrift_gruen"><?php echo translate('Buchungspauschale'); ?></p>

        <p><?php echo $buchungspauschale["preisvarianteName"];?></p>

        <?php echo translate('Preis'); ?>: <?php echo $buchungspauschale["preis"];?> €<br>
        <?php echo translate('Anzahl'); ?>: <?php echo $buchungspauschale["anzahl"];?><br><br>
        <?php echo translate('Gesamtpreis für diese Programmvariante'); ?>: <?php echo $buchungspauschale["gesamtpreis"];?> €
    </div>
</div>
<?php } ?>