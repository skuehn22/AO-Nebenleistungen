<?php if(!class_exists('raintpl')){exit;}?><script type="text/javascript">
    var informationGruppenname = "<?php echo translate(informationGruppenname); ?>";
    var informationBuchungshinweis = "<?php echo translate(informationBuchungshinweis); ?>";

    var buttonFarbVariante = '<?php echo $buttonFarbVariante;?>';
</script>
<script type="text/javascript" src="/tpl/Front_Warenkorb_Index.js"></script>
<script type="text/javascript" src="/tpl/Front_Warenkorb_Buttonfarbe.js"></script>

<!-- Lightbox und Bild Tooltip -->
<script type="text/javascript" src="/simpleModal/jquery.simplemodal.js"></script>
<script type="text/javascript" src="/simpleModal/steuerung.modal.js"></script>
<script type="text/javascript" src="/simpleModal/steuerung.modal.kategorie.js"></script>

<!-- Darstellung der Statusanzeige -->
<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Statusanzeige") . ( substr("Front_Warenkorb_Index_Statusanzeige",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Warenkorb_Index_Statusanzeige") );self::$tpl_dir = $tpl_dir_temp;?>

<div class="span-24">&nbsp;</div>

<!-- wenn keine Artikel im Warenkorb -->
<?php if( $counterToOrder == 0 ){ ?>
<div class="span-24 teilueberschrift" style="height: 200px;">
    <?php echo translate('keine Produkte im Warenkorb'); ?>!
</div>
<?php } ?>

<!-- Button oben -->
<div id='allgemein' class="span-24 block_oben block_unten block">

    <!-- Überschrift des Warenkorbes -->
    <div class="span-12 prepend-1">
        <?php if( $flag_show_buttons ){ ?>
            <?php if( $flagStornierung == 2 ){ ?>
                <span class=" ueberschrift_rot"><?php echo translate('Stornierung'); ?></span>
            <?php }else{ ?>
                <span class=" ueberschrift_gruen"><?php echo translate('Warenkorb'); ?></span>
            <?php } ?>
        <?php }else{ ?>
            <span class=" ueberschrift_gruen"><?php echo translate('Bestelliste'); ?></span>
        <?php } ?>
    </div>

    <!-- Button des Warenkorbes -->
    <?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Button") . ( substr("Front_Warenkorb_Index_Button",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Warenkorb_Index_Button") );self::$tpl_dir = $tpl_dir_temp;?>
</div>

<div id="errorMessage" class="span-24" style="margin-top: 10px;">
    <?php if( $errorMessage != '' ){ ?>
        <span class=" ueberschrift_rot"><?php echo translate($errorMessage); ?></span>
    <?php } ?>
</div>

 <!-- Bestandsbuchung von Programmen -->
<?php if( $blockGebuchteProgramme ){ ?>
    <div class="span-24">&nbsp;</div>
    <?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_BestandsbuchungProgramme") . ( substr("Front_Warenkorb_Index_BestandsbuchungProgramme",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Warenkorb_Index_BestandsbuchungProgramme") );self::$tpl_dir = $tpl_dir_temp;?>
<?php } ?>

<div class="span-24">&nbsp;</div>

<!-- Hotelbuchungen -->
<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Uebernachtungen") . ( substr("Front_Warenkorb_Index_Uebernachtungen",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Warenkorb_Index_Uebernachtungen") );self::$tpl_dir = $tpl_dir_temp;?>

<div class="span-24">&nbsp;</div>

<!-- Programmbuchungen -->
<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Programme") . ( substr("Front_Warenkorb_Index_Programme",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Warenkorb_Index_Programme") );self::$tpl_dir = $tpl_dir_temp;?>

<div class="span-24">&nbsp;</div>

 <!-- Buchungspauschale -->
<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Buchungspauschale") . ( substr("Front_Warenkorb_Index_Buchungspauschale",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Warenkorb_Index_Buchungspauschale") );self::$tpl_dir = $tpl_dir_temp;?>

 <!-- Bettensteuer -->
<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Bettensteuer") . ( substr("Front_Warenkorb_Index_Bettensteuer",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Warenkorb_Index_Bettensteuer") );self::$tpl_dir = $tpl_dir_temp;?>

<!-- Gruppenrabatt -->
<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Gruppenrabatt") . ( substr("Front_Warenkorb_Index_Gruppenrabatt",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Warenkorb_Index_Gruppenrabatt") );self::$tpl_dir = $tpl_dir_temp;?>

<div class="span-24">&nbsp;</div>

<!-- Button unten und Preis -->
<div id='gesamtpreis' class='span-24 block block_oben block_unten'>

    <!-- Preise -->
    <?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Preise") . ( substr("Front_Warenkorb_Index_Preise",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Warenkorb_Index_Preise") );self::$tpl_dir = $tpl_dir_temp;?>

    <!-- Button des Warenkorbes -->
    <?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Button") . ( substr("Front_Warenkorb_Index_Button",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Warenkorb_Index_Button") );self::$tpl_dir = $tpl_dir_temp;?>
</div>


<!-- Orderdata -->
<?php if( $flagOrderData ){ ?>
    <div class='span-24'>&nbsp;</div>

    <div id='orderdata' class="span-22 block block_oben block_unten" style="padding-right: 40px; padding-left: 40px;">

    <?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Orderdata") . ( substr("Front_Warenkorb_Index_Orderdata",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Warenkorb_Index_Orderdata") );self::$tpl_dir = $tpl_dir_temp;?>

    </div>
<?php } ?>

<!-- Bestätigungsfenster -->
<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Warenkorb_Index_Fenster") . ( substr("Front_Warenkorb_Index_Fenster",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Warenkorb_Index_Fenster") );self::$tpl_dir = $tpl_dir_temp;?>