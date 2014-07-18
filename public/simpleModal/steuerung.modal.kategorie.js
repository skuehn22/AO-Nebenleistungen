function anzeigeMaxbildKategorie()
{
    $('.basic-modal-kategorie').click(function (e) {

        var bildSrc = this.src;

        var bildPfadMaxi = "/images/kategorieImages/maxi/";
        var bildTypId = 7;

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

                $.modal("<table id='lightboxTabelle'><tr><td id='lightboxBild'><img src='" + bildPfadMaxi + "' width='750'></td></tr><tr><td id='lightboxBildInformation'> &nbsp;&nbsp; " + bildinformation + "</td></tr></table>",{
                                opacity: 50,
                                autoResize: true,
                                autoPosition: true,
                                overlayClose: true
                            }
                );
            }
        });

        return false;
    });
}