function classDatumsBerechnung(){
    return{
        ermittleWochentag: function(dd,mm,yyyy){
            var tagesZiffer = new Date(yyyy, mm - 1, dd).getDay();
            var kurzerWochentagsName = this.miniWochentagName[tagesZiffer];

            return kurzerWochentagsName;
        },
        miniWochentagName: new Array("So.", "Mo.", "Die.", "Mi.","Do.", "Fr.", "Sa.")

    }
}

var objectDatumsBerechnung = new classDatumsBerechnung();