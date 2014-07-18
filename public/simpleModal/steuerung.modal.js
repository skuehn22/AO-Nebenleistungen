$( document ).ready(function() {

    $('.basic-modal').click(function (e) {

        var bildSrc = this.src;

        if((bildSrc.indexOf("program")) > -1){
            var bildPfadMaxi = "/images/program/maxi/";
            var bildTypId = 1;
        }

        if((bildSrc.indexOf("city")) > -1){
            var bildPfadMaxi = "/images/city/maxi/";
            var bildTypId = 10;
        }

        if((bildSrc.indexOf("propertyImages")) > -1){
            var bildPfadMaxi = "/images/propertyImages/maxi/";
            var bildTypId = 6;
        }

        var kombinierteBildId = this.id;
        var teileBildId = kombinierteBildId.split("_");
        var bildId = teileBildId[1];

        var bildPfadMaxi = bildPfadMaxi + bildId + '.jpg';

        $.ajax({
            url: '/front/bildinformation/bildbeschreibung-copyright',
            type: 'POST',
            data:{
                bildId: bildId,
                bildTypId: bildTypId
            },
            dataType: "json",
            success: function(data){

                var bildinformation = ' ';
                if((data.bildname != null) && (data.copyright != null))
                    bildinformation = data.bildname + " &copy; " + data.copyright;

                $.modal("<table id='lightboxTabelle'><tr><td id='lightboxBild'><img src='" + bildPfadMaxi + "' max-height='750'></td></tr><tr><td id='lightboxBildInformation'> &nbsp;&nbsp; " + bildinformation + "</td></tr></table>",{
                                opacity: 50,
                                // autoResize: true,
                                // autoPosition: true,
                                overlayClose: true,
                                position: [30,100],
                                close: true,
                                onShow: bildSchliessen
                            }
                );
            }
        });

        return false;
    });

    $("#lightboxTabelle").click(function(){
        $.modal.close();
    });

});


function bildSchliessen()
{
    $("#lightboxTabelle").click(function(){
        $.modal.close();
    });
}
