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

JTracker.submitIssue = function(button) {

	// @todo validate

	$(button).html('Submitting...');

	document.editForm.submit();

	return false;
};
