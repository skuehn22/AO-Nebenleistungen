<?php if(!class_exists('raintpl')){exit;}?><script type="text/javascript" src="/tpl/Admin_whiteboard_index.js"></script>
<script type="text/javascript">
    Ext.onReady(function(){
        <?php if( $rolleId > 7 ){ ?>
            whiteboardFragenAdmin();
        <?php } ?>
    });

</script>
<div class='span-24' id='info'>
    <div class="blockDescription span-15">
    <h3 style="color: blue">allgemeine Systeminformationen</h3>
    <br>
    Darstellung allgemeiner Systeminformationen.
    </div>
</div>
<div class='span-24'>&nbsp;</div>
<div class="span-10 blockDescription">
    <?php echo $personendaten["title"];?> <?php echo $personendaten["firstname"];?> <?php echo $personendaten["lastname"];?> <br>
	<?php echo $rolle;?>
</div>
<div class="span-24">&nbsp;</div>
<div id='offeneFragen' class="span-22"></div> 
<div id='einzelneFrage' class="span-5"></div>