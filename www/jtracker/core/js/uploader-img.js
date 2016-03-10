/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/*jslint nomen: true, regexp: true */
/*global $, window, blueimp */

$(function () {
    'use strict';

    var uploadarea = $('#fileupload');
    // Initialize the jQuery File Upload widget:
    uploadarea.fileupload({
        completed: function (e, data) {
            if (data.result.error) {
                $('.upload-error').delay(3000).fadeOut();
            } else if (!data.result.files[0].error) {
                var target = $('#' + data.result.files[0].editorId);
                var cursorStart = target.textrange('get').start;
                var prefix = data.result.files[0].isImage ? '!' : '';
                var alt = prefix + '[' + data.result.files[0].alt + ']';
                var url = '(' + data.result.files[0].url + ')';
                var content = target.val();
                var newContent = content.substr(0, cursorStart) + alt + url + content.substr(cursorStart);

                target.val(newContent);
                target.focus();
            } else {
                $('.upload-error').delay(3000).fadeOut();
            }
        }
    });

    uploadarea.bind('fileuploadsubmit', function (e, data) {
        var val = $('input[name="editorId"]').val();
        if (!val) {
            $('#select-message').html(g11n3t('First please select an editor to attach the uploads to.'))
            .show().delay(3000).fadeOut();
            $('tbody.files').empty();

            return false;
        }
    });

    uploadarea.bind('fileuploaddestroyed', function (e, data) {
        var match = '=';
        var fileName = data.url.substring(data.url.indexOf(match) + match.length, data.url.length);
        var target = $('#' + data.context.find('button').prop('id'));
        var content = target.val();
        var regex = new RegExp('!?' + RegExp.escape('[') + '[^' + RegExp.escape(']') + ']*' + RegExp.escape(']') + RegExp.escape('(') + '[^' + RegExp.escape('[]') + ']*?' + RegExp.escape(fileName) + RegExp.escape(')'), 'i');
        var newContent = content.replace(regex, '');
        target.val(newContent);
    });

    // Load existing files:
    uploadarea.addClass('fileupload-processing');
    uploadarea.fileupload('option', {
        url: '/upload/put/',
        // Enable image resizing, except for Android and Opera,
        // which actually support image resizing, but fail to
        // send Blob objects via XHR requests:
        disableImageResize: /Android(?!.*Chrome)|Opera/
            .test(window.navigator.userAgent),
        maxFileSize: 1000000,
        acceptFileTypes: /(\.|\/)(gif|jpe?g|png|txt|pdf|docx|xlsx|pptx)$/i
    });
});

RegExp.escape= function(s) {
    return s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')
};
