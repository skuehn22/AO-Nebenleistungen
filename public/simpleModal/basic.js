/*
 * SimpleModal Basic Modal Dialog
 * http://simplemodal.com
 *
 * Copyright (c) 2013 Eric Martin - http://ericmmartin.com
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 */

jQuery(function ($) {
	// Load dialog on page load
	//$('#basic-modal-content').modal();

	// Load dialog on click
	$('.basic-modal').click(function (e) {

        var minibild = this.id;
        var maxibild = minibild + '_maxi';

        var options = {
            opacity: 50,
            autoResize: true,
            autoPosition: true
        };

		$('#' + maxibild).modal(options);

		return false;
	});
});