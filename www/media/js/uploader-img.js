/*! For license information please see uploader-img.js.LICENSE.txt */
$((function(){"use strict";var e=$("#fileupload");e.fileupload({completed:function(e,t){if(t.result.error)$(".upload-error").delay(3e3).fadeOut();else if(t.result.files[0].error)$(".upload-error").delay(3e3).fadeOut();else{var l=$("#"+t.result.files[0].editorId),a=l.textrange("get").start,s=(t.result.files[0].isImage?"!":"")+"["+t.result.files[0].alt+"]",r="("+t.result.files[0].url+")",i=l.val(),p=i.substr(0,a)+s+r+i.substr(a);l.val(p),l.focus()}}}),e.bind("fileuploadsubmit",(function(e,t){if(!$('input[name="editorId"]').val())return $("#select-message").html("First please select an editor to attach the uploads to.").show().delay(3e3).fadeOut(),$("tbody.files").empty(),!1})),e.bind("fileuploaddestroyed",(function(e,t){var l=t.url.substring(t.url.indexOf("=")+1,t.url.length),a=$("#"+t.context.find("button").prop("id")),s=a.val(),r=new RegExp("!?"+RegExp.escape("[")+"[^"+RegExp.escape("]")+"]*"+RegExp.escape("]")+RegExp.escape("(")+"[^"+RegExp.escape("[]")+"]*?"+RegExp.escape(l)+RegExp.escape(")"),"i"),i=s.replace(r,"");a.val(i)})),e.addClass("fileupload-processing"),e.fileupload("option",{url:"/upload/put/",disableImageResize:/Android(?!.*Chrome)|Opera/.test(window.navigator.userAgent),maxFileSize:1e6,acceptFileTypes:/(\.|\/)(gif|jpe?g|png|txt)$/i})})),RegExp.escape=function(e){return e.replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&")};