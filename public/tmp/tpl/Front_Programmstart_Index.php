<?php if(!class_exists('raintpl')){exit;}?><script type="text/javascript" src="/simpleModal/jquery.simplemodal.js"></script>
<script type="text/javascript" src="/simpleModal/steuerung.modal.js"></script>

<!-- Ablaufleiste -->
<!--<div id='ablaufleiste' class="span-24">
   <div class="block block_oben block_unten" style="padding: 0px 0px 0px 40px;"><?php echo $breadcrumb;?></div>
</div>     -->


<div class='span-23 last'>

	<?php $counter1=-1; if( isset($cityEvents) && is_array($cityEvents) && sizeof($cityEvents) ) foreach( $cityEvents as $key1 => $value1 ){ $counter1++; ?>

    <!-- Kopf -->
    <div class='span-23 ueberschrift block_oben block' style="padding-left: 40px;">
        <div class="span-12"><?php if( $cityId == 0 ){ ?> <?php echo $value1["ort"];?>: <?php } ?> <?php echo $value1["progname"];?> &nbsp;</div>


    </div>

    <!-- Programmblock -->
    <div class="span-23 block_unten block" style="padding-left: 40px; min-height: 50px;">


        <div class='span-9'>
            <?php echo $value1["txt"];?> <a href="/front/programmdetail/index/city/<?php echo $value1["cityId"];?>/programId/<?php echo $value1["id"];?>"><?php echo translate('mehr'); ?></a>
            <span class="standardgruen">
                <?php if( $value1["languages"] ){ ?><?php echo translate('Sprachen'); ?>:  <?php $counter2=-1; if( isset($value1["languages"]) && is_array($value1["languages"]) && sizeof($value1["languages"]) ) foreach( $value1["languages"] as $key2 => $value2 ){ $counter2++; ?> <img src='/flags/<?php echo $value2["flag"];?>.png'> <?php } ?><br><?php } ?>

            </span>
        </div>

        <div class="span-3">
            <?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );self::$tpl_dir .= dirname("Front_Programmstart_Buchungstyp") . ( substr("Front_Programmstart_Buchungstyp",-1,1) != "/" ? "/" : "" );$tpl->assign( "key", $key1 ); $tpl->assign( "value", $value1 );$tpl->draw( basename("Front_Programmstart_Buchungstyp") );self::$tpl_dir = $tpl_dir_temp;?>

        </div>
        <div class="span-3 push-3 standardtext">
            <?php echo translate('Preis ab'); ?>:  <?php echo translatePricing($value1["werbepreis"]); ?> €
        </div>
        <div class="span-2">
            <form method='post' class="form" action='/front/programmdetail/index/city/<?php echo $value1["cityId"];?>/programId/<?php echo $value1["id"];?>'><input type='submit' class="submit" value='<?php echo translate('buchen'); ?>...'></form>
        </div>
    </div>
    <div class="span-15">&nbsp;</div>
	<?php } ?>


</div>


<!-- Paginator -->
<?php if( $paginator ){ ?>

<div class='span-24 block_oben block block_unten'>

    <div style="float: left; width: 50px; margin-top: 10px; margin-left: 40px;">
        <?php if( $paginator ){ ?>

            <?php echo translate('Seiten'); ?> :
        <?php }else{ ?>

            &nbsp;
        <?php } ?>

    </div>

    <!-- Paginator -->
    <div class="span-6">
        <?php if( $paginator ){ ?>

            <?php $counter1=-1; if( isset($paginator) && is_array($paginator) && sizeof($paginator) ) foreach( $paginator as $key1 => $value1 ){ $counter1++; ?>

                <div class="kreis">
                    <a class="<?php echo $value1["class"];?>" href='/<?php echo $value1["module"];?>/<?php echo $value1["controller"];?>/<?php echo $value1["action"];?>/seite/<?php echo $value1["seite"];?>/city/<?php echo $value1["cityId"];?>'><?php echo $value1["anzeige"];?></a>
                </div>
            <?php } ?>

        <?php }else{ ?>

            &nbsp;
        <?php } ?>

    </div>

</div>
<?php } ?>


