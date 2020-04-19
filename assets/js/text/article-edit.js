/**
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

;(function (window, $) {
	'use strict';

	$(function () {
	    $('#save-article').click(function (e) {
            $('#editForm').submit();
        });

        $('#text').markItUp(myMarkdownSettings);

        $('a[data-toggle="tab"]').on('shown', function (e) {
            if ($(e.target).attr('href') === '#preview') {
                JTracker.preview('#text', '#preview');
            }
        });
    });
})(window, jQuery);
