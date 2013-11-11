/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

$.validator.addMethod("allowedchars", function(value, element) {
	var regex = /^[a-z0-9\-.,()\[\]'"+_@&$#%:\s]+$/i;
	return this.optional(element) || regex.test(value);
}, "This character is not allowed here.");

$.validator.addClassRules({
	validateTitle: {
		allowedchars: true,
		required: true,
		maxlength: 255
	},
	validateBuild: {
		required: true,
		maxlength: 40
	},
	validateDescription: {
		required: true
	}
});
