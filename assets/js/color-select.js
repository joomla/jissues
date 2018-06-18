/**
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

$(document).ready(function() {
	$('.color_select').simpleColor({
		colors: [
			'e11d21', 'eb6420', 'fbca04', '009800', '006b75', '207de5', '0052cc', '5319e7',
			'f7c6c7', 'fad8c7', 'fef2c0', 'bfe5bf', 'bfdadc', 'c7def8', 'bfd4f2', 'd4c5f9'
		],
		cellWidth: 25,
		cellHeight: 25,
		cellMargin: 0,
		columns: 8,
		displayCSS: { 'width': '25px' },
		chooserCSS: { 'left': '25px', 'border': '0' },
		onSelect: function(hex, element) {
			$('#' + element.attr('id') + '_display').val(hex);
		}
	});
});
