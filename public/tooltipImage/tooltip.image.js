/*
 * Tooltip script
 * powered by jQuery (http://www.jquery.com)
 *
 * written by Alen Grakalic (http://cssglobe.com)
 *
 * for more info visit http://cssglobe.com/post/1695/easiest-tooltip-and-image-preview-using-jquery
 *
 */

this.tooltip = function(){
	/* CONFIG */
		xOffset = 10;
		yOffset = 20;
		// these 2 variable determine popup's distance from the cursor
		// you might want to adjust to get the right result
	/* END CONFIG */


	$(".tooltipImage").hover(function(e){
		this.t = this.title;
		this.title = "";

        if(this.t.length != 0){
            $("body").append("<p id='tooltipImage'>"+ this.t +"</p>");

            $("#tooltipImage")
                .css("top",(e.pageY - xOffset) + "px")
                .css("left",(e.pageX + yOffset) + "px")
                .fadeIn("fast");
        }



    },
	function(){
		this.title = this.t;
		$("#tooltipImage").remove();
    });

	$(".tooltipImage").mousemove(function(e){
		$("#tooltipImage")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px");
	});
};



// starting the script on page load
$(document).ready(function(){
	tooltip();
});