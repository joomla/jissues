<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_login
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

$langs	= modLoginHelper::getLanguageList();
$return	= modLoginHelper::getReturnURI();
require JModuleHelper::getLayoutPath('mod_login', $params->get('layout', 'default'));
