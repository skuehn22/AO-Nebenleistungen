<?php if(!class_exists('raintpl')){exit;}?><link href="/tpl_austria/css/bootstrap.css" rel="stylesheet">
<link href="/tpl_austria/css/datepicker.css" rel="stylesheet">

<script type="text/javascript" src="/tpl/Front_Login_Index.js"></script>
<script src="/tpl_austria/js/jquery.js"></script>
<script src="/tpl_austria/js/bootstrap-datepicker.js"></script>




<?php if( $errorMessage ){ ?>

<div style='height: 100px; padding: 20px;'>
    <?php echo translate('Uuuups ! Leider ist ein Fehler aufgetreten. <br>Bitte wenden Sie sich an unseren Support'); ?><br>
    <?php echo translate('Fehlernummer'); ?>: <?php echo $errorMessage;?><p>

    <p>
       <a href="/front/login/"><?php echo translate('zurück zum Start'); ?></a>
    </p>
</div>
<?php } ?>



<!-- Austria -->
    <div id="wrapper">
                     <div class="header" style="">

                     <div class="header_image" style="display:block;">
                            <img src="/tpl_austria/images/platzhalter35.png" style="width:950px;" alt="">
                        </div>
                        <form action="/front/login/save-hotelsearch" method="post">
                          <div class="quickmenu" style="display:block;">
                          <div style="float:left">
                            <div class="q_select_con">
                               <select name="city" class="q_select">
                                <option value="1">
                                  <?php echo translate('Stadt wählen'); ?>

                                 </option>
                                 <?php $counter1=-1; if( isset($service["city"]) && is_array($service["city"]) && sizeof($service["city"]) ) foreach( $service["city"] as $key1 => $value1 ){ $counter1++; ?>

                                   <option value="<?php echo $value1["cityId"];?>">
                                     <?php echo $value1["city"];?>

                                   </option>
                                 <?php } ?>

                               </select>
                             </div>
                          </div>
                          <div style="float:right; ">
                               <div id="show_date" style="float:left;">&nbsp;</div>
                               <div id="date_selection" class="hide" style="float:left; padding-right:5px;">
                      		    <input type="text" class="span2 dates"  name="from" value="Anreise" id="dp2" data-date="12-02-2012" data-date-format="yyyy-mm-dd" >
                      		    <input type="text" class="span2 dates"  name="to"  value="Abreise" id="dp3" data-date="12-02-2012" data-date-format="yyyy-mm-dd" >
                               </div>
                               <div id="show_persons" style="float:left;">&nbsp;</div>
                               <div id="person_selection" class="hide" style="float:left; padding-right:5px;">
                      		     <input type="text" class="span2"  value="Personen" name="adult">
                               </div>


                             <div style="float:right; padding-left:15px;">
                                  <input class="q_submit" type="submit" name="senden" value="&nbsp;">
                             </div>
                          </div>
                          </div>
                      </div>
                    </form>
                </div>
<div class="span-24" style="margin-right:0px;">
 <br><br><img src="/tpl_austria/images/platzhalter36.png"/>
</div>


<div class="span-24 ueberschrift  block_start" style="margin-right:0px;">
 <br>
 <a href="/front/vorschlag/view/city/1"><img src="/tpl_austria/images/platzhalter30.jpg" alt="" width="230px;"/> </a>
 <a href="/front/vorschlag/view/city/1"><img src="/tpl_austria/images/platzhalter31.jpg" alt="" width="230px;"/>  </a>
 <a href="/front/vorschlag/view/city/1"><img src="/tpl_austria/images/platzhalter34.jpg" alt="" width="230px;"/>  </a>
 <div class="teaser_box">
    <div class="teaser_box_text">
    <h4>Lorem ipsum</h4>
    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam faucibus, nisl nec venenatis varius, risus purus condimentum quam, quis pretium mi nibh quis arcu. Proin sagittis sapien ut mauris rutrum sollicitudin. Integer justo odio, rutrum vel iaculis a, laoreet sed arcu. Ut enim ipsum, ultrices quis elit vel, pellentesque blandit sapien. Donec vehicula mauris at nibh convallis ultrices. In id laoreet nisl. Vivamus et porttitor nulla. </div>
   </div>
</div>
<div class="span-24 ueberschrift  block_start" style="margin-right:0px;">
    <img src="/tpl_austria/images/platzhalter33.png" alt="" />
</div>
<div class="span-24" style="margin-right:0px;">
  <div class="teaser_box_small">
  <div class="teaser_box_text">
      <h4>Reiseerlebnisse</h4>
      Ihr habt Fotos von eurer Reise, ein Video gedreht oder über eure Reise geschrieben?
      Schickt uns eure Erlebnisse und teilt Diese mit anderen Gruppen.
      <br><br>E-Mail: <a href="mailto:info@welcomeaustria.at">info@welcomeaustria.at</a>
        <br>  <br>
      <a href="" class="datenschutz">Datenschutzerklärung</a>
   </div>
 </div>

<img src="/tpl_austria/images/platzhalter32.png" alt="" width="695px;" style=" padding-left:10px;   "/>
</div>
<div class="span-24 ueberschrift  block_start" style="margin-right:0px;">

 <img src="/tpl_austria/images/platzhalter11.jpg" alt="" />

</div>

<script>
	if (top.location != location) {
    top.location.href = document.location.href ;
  }
		$(function(){
			window.prettyPrint && prettyPrint();
			$('#dp2').datepicker();
			$('#dp3').datepicker();
			$('#dp3').datepicker();
			$('#dpYears').datepicker();
			$('#dpMonths').datepicker();


			var startDate = new Date(2014,6,24);
			var endDate = new Date(2014,6,25);
			$('#dp4').datepicker()
				.on('changeDate', function(ev){
					if (ev.date.valueOf() > endDate.valueOf()){
						$('#alert').show().find('strong').text('The start date can not be greater then the end date');
					} else {
						$('#alert').hide();
						startDate = new Date(ev.date);
						$('#startDate').text($('#dp4').data('date'));
					}
					$('#dp4').datepicker('hide');
				});
			$('#dp5').datepicker()
				.on('changeDate', function(ev){
					if (ev.date.valueOf() < startDate.valueOf()){
						$('#alert').show().find('strong').text('The end date can not be less then the start date');
					} else {
						$('#alert').hide();
						endDate = new Date(ev.date);
						$('#endDate').text($('#dp5').data('date'));
					}
					$('#dp5').datepicker('hide');
				});

        // disabling dates
        var nowTemp = new Date();
        var now = new Date(nowTemp.getFullYear(), nowTemp.getMonth(), nowTemp.getDate(), 0, 0, 0, 0);

        var checkin = $('#dpd1').datepicker({
          onRender: function(date) {
            return date.valueOf() < now.valueOf() ? 'disabled' : '';
          }
        }).on('changeDate', function(ev) {
          if (ev.date.valueOf() > checkout.date.valueOf()) {
            var newDate = new Date(ev.date)
            newDate.setDate(newDate.getDate() + 1);
            checkout.setValue(newDate);
          }
          checkin.hide();
          $('#dpd2')[0].focus();
        }).data('datepicker');
        var checkout = $('#dpd2').datepicker({
          onRender: function(date) {
            return date.valueOf() <= checkin.date.valueOf() ? 'disabled' : '';
          }
        }).on('changeDate', function(ev) {
          checkout.hide();
        }).data('datepicker');
		});

        </script>

        <script>

          $( "#show_date" ).click(function() {
             if ( $("#date_selection").hasClass( "hide" ) ) {

                $('#date_selection').removeClass("hide");
             }else{
                $('#date_selection').addClass("hide");
             }
          });

           $( "#show_persons" ).click(function() {
             if ( $("#person_selection").hasClass( "hide" ) ) {

                $('#person_selection').removeClass("hide");
             }else{
                $('#person_selection').addClass("hide");
             }
          });

        </script>