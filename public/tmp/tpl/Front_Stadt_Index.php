<?php if(!class_exists('raintpl')){exit;}?><script type="text/javascript" src="/simpleModal/jquery.simplemodal.js"></script>
<script type="text/javascript" src="/simpleModal/steuerung.modal.js"></script>
<script type="text/javascript" src="/tooltipImage/tooltip.image.js"></script>

<div id='ablaufleiste' class="span-24 last">
    <div class="block block_oben block_unten ueberschrift_gruen" style="padding-left: 40px;"><?php echo $breadcrumb;?></div>
</div>

<div class="span-24">&nbsp;</div>

<div class="span-12">
    <table width="100%">
        <tr>
            <td style="height: 100px;">&nbsp;</td>
        </tr>
        <tr>
            <td align="center">
                <form class="form" method="post" action="/front/hotelsearch/index/city/<?php echo $cityId;?>">
                    <input type='submit' name='senden' class="submit" value="<?php echo translate('Übernachtung auswählen'); ?>" style="width: 400px; height: 40px; font-size: 20px;">
                </form>
            </td>
        </tr>
        <tr>
            <td colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <td align="center">
                <form class="form" method="post" action="/front/programmstart/index/city/<?php echo $cityId;?>">
                    <input type='submit' name='senden' class="submit" value="<?php echo translate('Programm auswählen'); ?>"  style="width: 400px; height: 40px; font-size: 20px;">
                </form>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>

        <!-- Überschrift Premiumprogramme -->
        <?php if( $premiumProgrammeEinerStadt ){ ?>

        <tr>
            <td style="padding-top: 100px; padding-bottom: 20px;">
                <span class="ueberschrift_gruen"><?php echo translate('empfohlene Programme'); ?></span>
            </td>
        </tr>
        <?php } ?>


        <!-- Premiumprogramme -->
        <?php $counter1=-1; if( isset($premiumProgrammeEinerStadt) && is_array($premiumProgrammeEinerStadt) && sizeof($premiumProgrammeEinerStadt) ) foreach( $premiumProgrammeEinerStadt as $key1 => $value1 ){ $counter1++; ?>

        <tr>
            <td>
                <a href="/front/programmdetail/index/city/<?php echo $cityId;?>/programId/<?php echo $value1["id"];?>" class="none">
                <div class='block_oben block_unten block' style="padding-left: 40px; float: left;">
                    <span class="ueberschrift"><?php echo $value1["progname"];?></span><br>
                    <div class="span-4"><?php echo anzeigenBilder($value1["id"],1,150,1,0); ?></div>

                    <?php echo $value1["txt"];?>

                </div>
                <div>&nbsp;</div>
                </a>
            </td>
        </tr>
        <?php } ?>

    </table>
</div>

<div class="span-12 last">
    <div class="ueberschrift block_oben block block_unten" style="padding-left: 10px;">
        <?php echo $cityname;?>

    </div>
    <div class="block_unten block_links_rechts" style="padding: 10px; min-height: 200px;">
        <div style="width: 440; float: left; padding: 5px 0px 0px 5px; margin: 0px 5px 20px 0px;" class="bildrahmen">
            <?php echo anzeigenBilder($cityId,10,430, 1); ?>

        </div>
            <?php if( $citybeschreibung ){ ?>

                <?php echo $citybeschreibung;?>

            <?php } ?>

    </div>
</div>