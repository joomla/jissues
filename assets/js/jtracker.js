/**
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

;(function (window, $) {
	'use strict';

    window.JTracker = {
        /**
         * Render a Markdown input to formatted HTML
         *
         * @param {String} text    The text to format
         * @param {String} preview The target element
         */
        preview: function (text, preview) {
            var out = $(preview);

            out.html('Loading preview...');

            $.post(
                '/preview',
                {text: $(text).val()},
                function (r) {
                    out.empty();

                    if (r.error) {
                        out.html(r.error);
                    } else if (!r.data.length) {
                        out.html('Invalid response.');
                    } else {
                        out.html(r.data);
                    }
                }
            );
        },

        /**
         * Submit a comment
         *
         * @param {String} issue_number    The issue number
         * @param {String} statusContainer The target status container
         * @param {String} outContainer    The target output container
         * @param {String} template        The name of the output template
         * @param {String} shaContainer    The container where the commit SHA is located
         */
        submitComment: function (issue_number, statusContainer, outContainer, template, shaContainer) {
            var out = $(outContainer),
                status = $(statusContainer),
                sha = $(shaContainer).val();

            status.html('Submitting comment...');

            $.post(
                '/submit/comment',
                {text: $('#comment').val(), issue_number: issue_number, sha: sha},
                function (r) {
                    if (!r.data) {
                        // Misc failure
                        status.html('Invalid response.');
                    } else if (r.error) {
                        // Failure
                        status.html(r.error);
                    } else {
                        // Success
                        status.html(r.message);

                        out.html(out.html() + tmpl(template, r.data));

                        // Clear textarea and files
                        $('#comment').val('');
                        $('tbody.files').empty();

                        // Submit test result but only when the "Not Tested" option is not selected
                        var testResult = $('input[name=comment-tested]').filter(':checked').val();

                        if (testResult > 0) {
                            JTracker.submitTestWithComment(out, 'tplNewTestResult');
                        }
                    }
                }
            );
        },

        /**
         * Submit a vote on an issue
         *
         * @param {String} issueId        The issue ID
         * @param {String} debugContainer The target element
         */
        submitVote: function (issueId, debugContainer) {
            var status = $(debugContainer),
                importance = $('input[name=importanceRadios]').filter(':checked').val(),
                experienced = $('input[name=experiencedRadios]').filter(':checked').val();

            status.addClass('disabled').removeAttr('href').removeAttr('onclick').html('Adding vote...');

            $.post(
                '/submit/vote',
                {issueId: issueId, experienced: experienced, importance: importance},
                function (r) {
                    if (r.error) {
                        // Failure
                        status.addClass('btn-danger').removeClass('btn-success').html(r.error);
                    } else {
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
        },

        /**
         * Submit a test result on an issue
         *
         * @param issueId          The issue ID being tested
         * @param statusContainer  The container where the submission status is displayed
         * @param resultContainer  The container where test results are displayed
         * @param commentContainer The container with the comments for the test
         * @param shaContainer     The container where the commit SHA is located
         * @param templateName     The name of the output template
         */
        submitTest: function (issueId, statusContainer, resultContainer, commentContainer, shaContainer, templateName) {
            var status = $(statusContainer),
                result = $(resultContainer),
                testResult = $('input[name=tested]').filter(':checked').val(),
                comment = $(commentContainer).val(),
                sha = $(shaContainer).val();

            status.html('Submitting test result...');

            $.post(
                '/submit/testresult',
                {issueId: issueId, result: testResult, comment: comment, sha: sha},
                function (r) {
                    if (r.error) {
                        // Failure
                        status.addClass('btn-danger').removeClass('btn-success').html(r.error);
                    } else {
                        // Success
                        status.addClass('text-success').html(r.message);

                        var data = $.parseJSON(r.data);

                        JTracker.updateTests(data.testResults.testsSuccess, data.testResults.testsFailure);

                        result.html(result.html() + tmpl(templateName, data.event));

                        // Update comment checkboxes
                        $('#current-test-result').val(testResult);
                        $('.comment-test-result').prop('checked', false);
                        $("#comment-test-result-" + testResult).prop('checked', true);

                        // Hide the container
                        $('#testContainer').delay(1000).slideUp();
                    }
                }
            );
        },

        /**
         * Submit a test result with a comment
         *
         * @param {jQuery} result       The container where results are displayed
         * @param {String} templateName The name of the output template
         */
        submitTestWithComment: function (result, templateName) {
            var issueId = $('#issue-id').val(),
                testResult = $('input[name=comment-tested]').filter(':checked').val(),
                currentTestResult = $('#current-test-result').val();

            if (testResult && currentTestResult != testResult) {
                $.post(
                    '/submit/testresult',
                    {issueId: issueId, result: testResult},
                    function (r) {
                        if (r.error) {
                            // Failure
                            result.html(r.error);
                        } else {
                            // Update comment checkboxes and current test result
                            $('.test-result').prop('checked', false);
                            $("#test-result-" + testResult).prop('checked', true);
                            $('.comment-test-result').prop('checked', false);
                            $("#comment-test-result-" + testResult).prop('checked', true);
                            $('#current-test-result').val(testResult);

                            var data = $.parseJSON(r.data);

                            JTracker.updateTests(data.testResults.testsSuccess, data.testResults.testsFailure);
                            result.html(result.html() + tmpl(templateName, data.event));

                            // Hide the container
                            $('#testContainer').delay(1000).slideUp();
                        }
                    }
                );
            }
        },

        /**
         * Alter a test result on an issue
         *
         * @param issueId          The issue ID being tested
         * @param statusContainer  The container where the submission status is displayed
         * @param resultContainer  The container where test results are displayed
         * @param shaContainer     The container where the commit SHA is located
         * @param templateName     The name of the output template
         */
        alterTest: function (issueId, statusContainer, resultContainer, shaContainer, templateName) {
            var status = $(statusContainer),
                result = $(resultContainer),
                sha = $(shaContainer).val(),
                altered = $('select[name=altered]').val(),
                user = $('input[name=altered-user]').val();

            if ('' == user) {
                status.html('Please select a user');

                return;
            }

            status.html('Submitting test result...');

            $.post(
                '/alter/testresult',
                {issueId: issueId, user: user, result: altered, sha: sha},
                function (r) {
                    if (r.error) {
                        // Failure
                        status.addClass('btn-danger').removeClass('btn-success').html(r.error);
                    } else {
                        // Success
                        status.addClass('text-success').html(r.message);

                        var data = $.parseJSON(r.data);

                        JTracker.updateTests(data.testResults.testsSuccess, data.testResults.testsFailure);

                        result.html(result.html() + tmpl(templateName, data.event));

                        // Hide the container
                        //$('#testAlterContainer').delay(1000).slideUp();
                    }
                }
            );
        },

        /**
         * Update the test result data
         *
         * @param {Array} testsSuccess Array of users with successful tests
         * @param {Array} testsFailure Array of users with failed tests
         */
        updateTests: function (testsSuccess, testsFailure) {
            $('#usertests-success-num').text(testsSuccess.length);
           	$('#usertests-success').text(testsSuccess.join(', '));

           	$('#usertests-fail-num').text(testsFailure.length);
           	$('#usertests-fail').text(testsFailure.join(', '));
        },

        /**
         * Get a contrasting color for a given hex color code
         *
         * @param {String} hexColor A hex color code to contrast
         * @returns {String}
         */
        getContrastColor: function (hexColor) {
            var r = parseInt(hexColor.substr(0, 2), 16),
                g = parseInt(hexColor.substr(2, 2), 16),
                b = parseInt(hexColor.substr(4, 2), 16),
                yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;

            return (yiq >= 128) ? 'black' : 'white';
        },

        /**
         * Configure the jquery.atwho integration
         *
         * @param {String} id           The target element ID
         * @param {String} projectAlias The alias for the currently active project
         */
        setupAtJS: function (id, projectAlias) {
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
                function (value, i) {
                    return {key: value, name: value}
                });

            var emoji_config = {
                at: ":",
                data: emojis,
                displayTpl: "<li data-value=':${key}:'><img src='https://assets-cdn.github.com/images/icons/emoji/${key}.png' height='20' width='20' /> ${name}</li>",
                insertTpl: ":${name}:"
            };

            var user_config = {
                at: "@",
                searchKey: 'username',
                callbacks: {
                    remoteFilter: function (query, callback) {
                        $.getJSON('/fetch/users', {q: query}, function (response) {
                            callback(response.data)
                        })
                    }
                },
                displayTpl: "<li data-value='@${username}'><img src='/images/avatars/${username}.png' height='20' width='20'> ${username} <small>${name}</small></li>",
                insertTpl: "@${username}"
            };

            var issue_config = {
                at: '#',
                searchKey: 'issue_number',
                callbacks: {
                    remoteFilter: function (query, callback) {
                        $.getJSON('/fetch/issues', {q: query}, function (response) {
                            callback(response.data)
                        })
                    }
                },
                displayTpl: "<li data-value='#${issue_number}'>${issue_number} <small>${title}</small></li>",
                insertTpl: "#${issue_number}"
            };

            $('#' + id)
                .atwho(emoji_config)
                .atwho(user_config)
                .atwho(issue_config);
        }
    };
})(window, jQuery);
