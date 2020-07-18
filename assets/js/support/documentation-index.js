/**
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Only define the Joomla namespace if not defined.
JTracker = window.JTracker || {};

;(function (window, $, JTracker) {
	'use strict';

	JTracker.initDocumentation = function (basePath, baseUrl) {
		JTracker.basePath = basePath;
		JTracker.baseUrl = baseUrl;

		$('#filetree').fileTree({
			root: '',
			script: basePath + 'filetree',
			multiFolder: false
		}, function (fullPath) {
			JTracker.loadDocumentationPage(fullPath);
		});

		JTracker.resizeDocumentationLoadingContainer();
	};

	JTracker.loadDocumentationPage = function (fullPath) {
		$.ajax({
			url: JTracker.basePath + 'documentation/show/?' + fullPath,
			beforeSend: function () {
				$('#loading').show();
			},
			success: function (response) {
				if (response.error) {
					$('#docs-container').html(tmpl('tplDocuError', response));
				} else {
					$('#docs-container').html(tmpl('tplDocuPage', response));
				}
			},
			error: (function () {
				alert('error..');
			}),
			complete: function () {
				$('#loading').hide();

				// Resize the loading overlay
				JTracker.resizeDocumentationLoadingContainer();
			}
		});
	};

	JTracker.resizeDocumentationLoadingContainer = function () {
		var outerContainer = $('div.body > div.container'),
			pageSection = $('#docs-main'),
			loading = $('#loading');

		loading.css('top', pageSection.position().top - $(window).scrollTop())
			.css('left', pageSection.position().left)
			.css('width', outerContainer.width())
			.css('height', outerContainer.height())
			.css('display', 'none');
	};
})(window, jQuery, JTracker);
