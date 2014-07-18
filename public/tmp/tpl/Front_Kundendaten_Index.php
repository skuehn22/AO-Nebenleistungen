<?php if(!class_exists('raintpl')){exit;}?><script type="text/javascript" src="/tpl/Front_Kundendaten_Index.js"></script>

<div class='span-24 block block_oben block_unten last ueberschrift_gruen' style="padding-left: 40px;">
    <?php if( $updateKundendatenErfolgt == 1 ){ ?>
        <span><?php echo translate('Korrektur der persönlichen Daten !'); ?></span>
    <?php }else{ ?>
    <span><?php echo translate('Persönliche Daten korrigiert !'); ?></span>
    <?php } ?>
</div>

<div class="span-24">&nbsp;</div>

<div class="span-17">
    <div class="block_unten block block_oben">
        <form class='formular form' id="formPersonalData" method='post' action='/front/kundendaten/edit/'>
            <?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Personaldata_FormularPersonendaten") . ( substr("Front_Personaldata_FormularPersonendaten",-1,1) != "/" ? "/" : "" );$tpl->draw( basename("Front_Personaldata_FormularPersonendaten") );self::$tpl_dir = $tpl_dir_temp;?>
        </form>
    </div>
</div>