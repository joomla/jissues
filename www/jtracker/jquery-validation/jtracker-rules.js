/**
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

$.validator.addClassRules({
	validateTitle: {
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
