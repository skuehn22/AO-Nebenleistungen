<?php if(!class_exists('raintpl')){exit;}?><?php if( count(value.raten) > 0 ){ ?>
    <div class="span-22">&nbsp;</div>
    <div class="span-22 block_oben block_unten block_gray block" style="padding-left: 40px; padding-right: 40px;">
        <?php echo $zimmerZusatztext;?><br>
        <?php if( $bettensteuer ){ ?>
            <br>
                <?php $counter1=-1; if( isset($bettensteuer) && is_array($bettensteuer) && sizeof($bettensteuer) ) foreach( $bettensteuer as $key1 => $value1 ){ $counter1++; ?>
                    <p>
                        <span class="teilueberschrift_gruen"><?php echo $value1["title"];?></span>
                        <br>
                        <?php echo $value1["kurztext"];?>
                    </p>
                <?php } ?>
            </p>
        <?php } ?>
    </div>
<?php } ?>