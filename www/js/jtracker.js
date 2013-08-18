/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

var JTracker = {};

JTracker.preview = function(text, preview) {
	var out = $(preview);

	out.html('Loading preview...');

	$.post(
		'/preview',
		{ text: $(text).val() },
		function (r) {
			out.empty();
			if (r.error) {
				out.html(r.error);
			}
			else if (!r.data.length) {
				out.html('Invalid response.');
			}
			else {
				out.html(r.data);
			}
		}
	);
};

JTracker.submitComment = function (issue_number, debugContainer, outContainer, template) {
	var out = $(outContainer);
	var status = $(debugContainer);

	status.html('Submitting comment...');

	$.post(
		'/submit/comment',
		{ text: $('#comment').val(), issue_number: issue_number },
		function (r) {
			if (!r.data) {
				// Misc failure
				status.html('Invalid response.');
			}
			else if (r.error) {
				// Failure
				status.html(r.error);
			}
			else {
				// Success
				status.html(r.message);

				out.html(out.html() + tmpl(template, r.data));
			}
		}
	);
};

JTracker.submitVote = function (issue_number, debugContainer) {
	var status = $(debugContainer);
	var importance = $('input[name=importanceRadios]').filter(':checked').val();
	var experienced = $('input[name=experiencedRadios]').filter(':checked').val();

	status.addClass('disabled').removeAttr('onclick').html('Adding vote...');

	$.post(
		'/submit/vote',
		{ issue_number: issue_number, experienced: experienced, importance: importance },
		function (r) {
			if (r.error) {
				// Failure
				status.addClass('btn-danger').removeClass('btn-success').html(r.error);
			}
			else {
				// Success
				status.html(r.message);
			}
		}
	);
};

JTracker.submitIssue = function(button) {

	// @todo validate

	$(button).html('Submitting...');

	document.editForm.submit();

	return false;
};
