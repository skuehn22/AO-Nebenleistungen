$(document).ready(function() {

    // Layout Popup zuschalten
    $("#checkWarenkorb").click(function(){
        $.ajax({
            type: 'POST',
            url: '/front/layout/status-warenkorb/',
            success: function(textPopupLogout)
            {
                if(textPopupLogout == '0'){
                    window.location = "/front/login/index/logout/logout";
                }
                else{
                    $("#layoutPopup").css({left: 650, top: 100});
                    $("#layoutPopupText").html(textPopupLogout);
                    showMask();

                    $("#layoutPopup").css('visibility','visible');
                }
            }
        });
    });

    // Button und Suchfeld Programmsuche
    $("#suchfeldProgramm").val(infoTextSuchfeld);

    $('#suchfeldProgramm').click(
        function(){
            $(this).val('');
        }
    );

    $('#suchfeldProgramm').val(infoTextSuchfeld);

    // Layout Popup abschalten
    $("#layoutPopupNo").click(function(){
        $("#layoutPopup").css('visibility','hide');

        var mask = $('#mask');

        // mask.fadeIn(1000);
        mask.fadeTo("slow",1);

        return false;
    });

    $("#login").hide();
    $('#fb-text').hide();

    // Bild Slider
    $(function(){
        $('#slider ul')
        .after('<div id="sliderNav">')
        .cycle({
            fx:	'fade',
            speed:	1000,
            timeout: 6000,
            pager:	'#sliderNav',
            height: 350
        });
    });

    // Face Book Text
    var facebookDiv = false;
    $(".fb-icon").hover(function(){
        if(loginDiv == true)
            return false;

        $('#fb-text').toggle();
        $("#claim").toggle();

        return false;
    });

    // Login
    var loginDiv = false
    $(".showHide").click(function(){
        $('#login').toggle();
        $("#claim").toggle();

        if(loginDiv == false)
            loginDiv = true;
        else
            loginDiv = false;

        return false;
    });

});

// Blueprint Grid
function gridAn() {
    $("html").addClass("showgrid");
}

function gridAus() {
    $("html").removeClass("showgrid");
}

// ausgrauen Bildschirm
function showMask(){
    //Get the screen height and width
    var maskHeight = $(document).height();
    var maskWidth = $(window).width();

    var mask = $('#mask');

    //Set height and width to mask to fill up the whole screen
    mask.css({
        'width': maskWidth,
        'height': maskHeight,
        'z-index': 999
    });

    // mask.fadeIn(1000);
    mask.fadeTo("slow",0.6);

    return;
}