/**
 * Created with JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 19.02.13
 * Time: 15:11
 * To change this template use File | Settings | File Templates.
 */

$(document).ready(function() {

    $("#container18").css({
        position: "relative",
        left: "-50px",
        top: "100px"
    });

    $("#container1").css({
        position: "relative",
        left: "-120px",
        top: "0px"
    });

    $("#container12").css({
        position: "relative",
        left: "-100px",
        top: "100px"
    });

    $("#container2").css({
       position: "relative",
       left: "50px",
       top: "-130px"
   });

    $("#container3").css({
       position: "relative",
       left: "-80px",
       top: "30px"
   });

    $("#container15").css({
       position: "relative",
       left: "-100px",
       top: "-80px"
   });

    $("#container16").css({
        position: "relative",
        left: "-120px",
        top: "30px"
     });

    $("#container4").css({
        position: "relative",
        left: "30px",
        top: "-240px"
    });

    $("#container17").css({
       position: "relative",
       left: "-20px",
       top: "-130px"
    });

    $("#container9").css({
        position: "relative",
        left: "-60px",
        top: "-60px"
    });

    $("#container5").css({
        position: "relative",
        left: "-80px",
        top: "-80px"
    });

    $("#container6").css({
        position: "relative",
        left: "60px",
        top: "-280px"
    });

    $("#container10").css({
        position: "relative",
        left: "-70px",
        top: "-320px"
    });

    $("#container7").css({
        position: "relative",
        left: "-140px",
        top: "-240px"
    });

    $("#container19").css({
        position: "relative",
        left: "-130px",
        top: "-170px"
    });

    $("#container8").css({
        position: "relative",
        left: "570px",
        top: "-480px"
    });


    $("#container50").css({
        position: "relative",
        left: "0px",
        top: "0px"
    });


    $(".grayImage").mouseover(function(){
        var id = this.id;
        id = parseInt(id,10);

        for(var i = 1; i < 60; i++){
            var info = $("#" + i + " > p");
            var bild = $('#image' + i);
            var div = $("#" + i);

            if(i == id){

                // Position Info
                var position = div.position();

                // farbiges Bild verschwindet, graues Bild erscheint
                $(bild).attr('src','/images/city/gray/' + id + '.jpg');

            info.css({
                    position: "absolute",
                    top: position.top + 10 + "px",
                    left: position.left + 10 + "px",
                    color: '#e26902'
                }).show();
            }
            else{
                // graues Bild verschwindet, farbiges Bild erscheint
                $(bild).attr('src','/images/city/mosaik/' + i + '.jpg');

                info.hide();
            }
        }

    });

});