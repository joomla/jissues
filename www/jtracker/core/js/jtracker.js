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

				// Update votes display
				if (r.data.votes > 0) {
					$('div[id=experienced]').html(r.data.experienced + '/' + r.data.votes);
					$('div[id=importance]').html((r.data.importanceScore).toFixed(2));
                    $('div#issue-votes').show();
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

JTracker.setupAtJS = function(id, projectAlias) {
	var emojis = $.map(
		[
			"smile", "iphone", "girl", "smiley", "heart", "kiss", "copyright", "coffee",
			"a", "ab", "airplane", "alien", "ambulance", "angel", "anger", "angry",
			"arrow_forward", "arrow_left", "arrow_lower_left", "arrow_lower_right",
			"arrow_right", "arrow_up", "arrow_upper_left", "arrow_upper_right",
			"art", "astonished", "atm", "b", "baby", "baby_chick", "baby_symbol",
			"balloon", "bamboo", "bank", "barber", "baseball", "basketball", "bath",
			"bear", "beer", "beers", "beginner", "bell", "bento", "bike", "bikini",
			"bird", "birthday", "blue_car", "blue_heart", "blush",
			"boar", "boat", "bomb", "book", "boot", "bouquet", "bow", "bowtie",
			"boy", "bread", "briefcase", "broken_heart", "bug", "bulb",
			"person_with_blond_hair", "phone", "pig", "pill", "pisces",
			"point_down", "point_left", "point_right", "point_up", "point_up_2",
			"police_car", "poop", "post_office", "postbox", "pray", "princess",
			"punch", "purple_heart", "question", "rabbit", "racehorse", "radio",
			"up", "us", "v", "vhs", "vibration_mode", "virgo", "vs", "walking",
			"warning", "watermelon", "wave", "wc", "wedding", "whale", "wheelchair",
			"wind_chime", "wink", "wolf", "woman",
			"womans_hat", "womens", "x", "yellow_heart", "zap", "zzz", "+1",
			"-1", 'tongue'
		],
		function(value, i) {return {key: value, name:value}});

	var emoji_config = {
		at: ":",
		data: emojis,
		tpl:"<li data-value=':${key}:'><img src='https://assets-cdn.github.com/images/icons/emoji/${key}.png' height='20' width='20' /> ${name}</li>"
	};

	var user_config = {
		at: "@",
		search_key: 'username',
		callbacks: {
			remote_filter: function(query, callback) {
				$.getJSON('/fetch/users', {q: query}, function(response) {
					callback(response.data)
				})
			}
		},
		tpl:"<li data-value='@${username}'><img src='/images/avatars/${username}' height='20' width='20'> ${username} <small>${name}</small></li>"
	};

	var issue_config = {
		at: '#',
		search_key: 'issue_number',
		callbacks: {
			remote_filter: function(query, callback) {
				$.getJSON('/fetch/issues', {q: query}, function(response) {
					callback(response.data)
				})
			}
		},
		tpl:"<li data-value='#${issue_number}'>${issue_number} <small>${title}</small></li>"
	};

	$('#' + id)
		.atwho(emoji_config)
		.atwho(user_config)
		.atwho(issue_config);
};
