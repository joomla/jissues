/**
 * User: elkuku
 * Date: 20.06.13
 * Time: 10:20
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

JTracker.submitComment = function (issue_number, outContainer, debugContainer) {
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
				out.html(r.message);
			}
		}
	);
};

JTracker.submitIssue = function(result, debug) {
	var title = $('input[name=title]').val();
	var body = $('textarea[name=body]').val();
	var priority = $('select[name=priority]').val();

	var out = $(result);
	var status = $(debug);

	status.html('Submitting issue report...');

	$.post(
		'/submit/issue',
		{
			title: title,
			body: body,
			priority: priority
		},
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
				out.html(r.message);
			}
		}
	);

};
