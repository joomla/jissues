/**
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

const deleteArticleLinks = document.querySelectorAll('.delete-article');

deleteArticleLinks.forEach((article) => {
	article.addEventListener('click', (e) => {
		e.preventDefault();

		let formClass = '.delete-article-' + article.dataset.id + '-form';
		document.getElementById(formClass).submit();
	})
});
