<?php if(!class_exists('raintpl')){exit;}?><script type="text/javascript" src="/tpl/Front_Login_Index.js"></script>
<?php if( $errorMessage ){ ?>

<div style='height: 100px; padding: 20px;'>
    <?php echo translate('Uuuups ! Leider ist ein Fehler aufgetreten. <br>Bitte wenden Sie sich an unseren Support'); ?><br>
    <?php echo translate('Fehlernummer'); ?>: <?php echo $errorMessage;?><p>

    <p>
       <a href="/front/login/"><?php echo translate('zurück zum Start'); ?></a>
    </p>
</div>
<?php } ?>


<div class='span-24'>
    <!-- Einbau der Sätze / Claimbereich -->
    <?php if( $flagSlogan ){ ?>


    <!-- <div class="block_oben block_unten block slogan span-18" style="float: left; margin-left: 110px; margin-bottom: 20px;">
           <p align="center"><?php echo translate('Klassenfahrten und Gruppenreisen planen - vormerken - buchen. <br />Verbindliche Preise und Verfügbarkeiten in Echtzeit. Ihr detailliertes Angebot - sofort'); ?></p>
    </div>  -->
    <?php } ?>

    <div class='span-24 ueberschrift block_unten block_oben block' >
         <p>
        <div style="line-height: 24px; width:550px; margin-left:245px; font-size:18px; text-align:;">
        <br><br>     <br><br>
         <div><h1>Programmauswahl für Stadt:</h1></div>
         <?php $counter1=-1; if( isset($service["city"]) && is_array($service["city"]) && sizeof($service["city"]) ) foreach( $service["city"] as $key1 => $value1 ){ $counter1++; ?>

             <a href="/front/programmstart/index/city/<?php echo $value1["cityId"];?>"><?php echo $value1["city"];?></a>  <br>
         <?php } ?>

         <br><br><br>
         </div>
        </p>
    </div>

</div>