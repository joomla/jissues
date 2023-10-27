/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!******************************************!*\
  !*** ./assets/js/text/articles-index.js ***!
  \******************************************/
/**
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
;

(function (window, $) {
  'use strict';

  $(function () {
    $('.delete-article').click(function (e) {
      e.preventDefault();
      var articleId = $(this).attr('data-id');
      var formClass = '.delete-article-' + articleId + '-form';
      $(formClass).submit();
    });
  });
})(window, jQuery);
/******/ })()
;