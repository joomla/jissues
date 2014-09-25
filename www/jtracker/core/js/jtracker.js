/**
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

var JTracker = {};

JTracker.preview = function(text, preview) {
	var out = $(preview);

	out.html(g11n3t('Loading preview...'));

	$.post(
		'/preview',
		{ text: $(text).val() },
		function (r) {
			out.empty();
			if (r.error) {
				out.html(r.error);
			}
			else if (!r.data.length) {
				out.html(g11n3t('Invalid response.'));
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

	status.html(g11n3t('Submitting comment...'));

	$.post(
		'/submit/comment',
		{ text: $('#comment').val(), issue_number: issue_number },
		function (r) {
			if (!r.data) {
				// Misc failure
				status.html(g11n3t('Invalid response.'));
			}
			else if (r.error) {
				// Failure
				status.html(r.error);
			}
			else {
				// Success
				status.html(r.message);

				out.html(out.html() + tmpl(template, r.data));

				// Clear textarea and files
				$('#comment').val('');
				$('tbody.files').empty();
			}
		}
	);
};

JTracker.submitVote = function (issueId, debugContainer) {
	var status = $(debugContainer);
	var importance = $('input[name=importanceRadios]').filter(':checked').val();
	var experienced = $('input[name=experiencedRadios]').filter(':checked').val();

	status.addClass('disabled').removeAttr('href').removeAttr('onclick').html(g11n3t('Adding vote...'));

	$.post(
		'/submit/vote',
		{ issueId: issueId, experienced: experienced, importance: importance },
		function (r) {
			if (r.error) {
				// Failure
				status.addClass('btn-danger').removeClass('btn-success').html(r.error);
			}
			else {
				// Success
				status.html(r.message);

				// Update votes display if this is not the first vote on an item
				if (r.data.votes > 1) {
					$('div[id=experienced]').html(r.data.experienced + '/' + r.data.votes);
					$('div[id=importance]').html((r.data.importanceScore).toFixed(2));
				}
			}
		}
	);
};

JTracker.submitTest = function (issueId, statusContainer, resultContainer, templateName) {
	var status = $(statusContainer);
	var result = $(resultContainer);
	var testResult = $('input[name=tested]').filter(':checked').val();

	status.html(g11n3t('Submitting test result...'));

	$.post(
		'/submit/testresult',
		{ issueId: issueId, result: testResult },
		function (r) {
			if (r.error) {
				// Failure
				status.addClass('btn-danger').removeClass('btn-success').html(r.error);
			}
			else {
				// Success
				status.html(r.message);

				var data = $.parseJSON(r.data);

				JTracker.updateTests(data.testResults.testsSuccess, data.testResults.testsFailure)

				result.html(result.html() + tmpl(templateName, data.event));
			}
		}
	);
};

JTracker.alterTest = function (issueId, statusContainer, resultContainer, templateName) {
	var status = $(statusContainer);
	var result = $(resultContainer);
	var altered = $('select[name=altered]').val();
	var user   = $('input[name=altered-user]').val();

	if ('' == user) {
		status.html(g11n3t('Please select a user'));

		return;
	}

	status.html(g11n3t('Submitting test result...'));

	$.post(
		'/alter/testresult',
		{ issueId: issueId, user: user, result: altered },
		function (r) {
			if (r.error) {
				// Failure
				status.addClass('btn-danger').removeClass('btn-success').html(r.error);
			}
			else {
				// Success
				status.html(r.message);

				var data = $.parseJSON(r.data);

				JTracker.updateTests(data.testResults.testsSuccess, data.testResults.testsFailure);

				result.html(result.html() + tmpl(templateName, data.event));
			}
		}
	);
};

JTracker.updateTests = function (testsSuccess, testsFailure) {
	$('#usertests-success-num').text(testsSuccess.length);
	$('#usertests-success').text(testsSuccess.join(', '));

	$('#usertests-fail-num').text(testsFailure.length);
	$('#usertests-fail').text(testsFailure.join(', '));
};

/**
 * Get a contrasting color (black or white).
 *
 * http://24ways.org/2010/calculating-color-contrast/
 *
 * @param   string  hexColor  The hex color.
 *
 * @return  string
 *
 * @since   1.0
 */
JTracker.getContrastColor = function(hexColor) {
	var r = parseInt(hexColor.substr(0, 2), 16);
	var g = parseInt(hexColor.substr(2, 2), 16);
	var b = parseInt(hexColor.substr(4, 2), 16);
	var yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;

	return (yiq >= 128) ? 'black' : 'white';
};
