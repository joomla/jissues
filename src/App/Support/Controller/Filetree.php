<?php

/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Support\Controller;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for the developer documentation.
 *
 * @since  1.0
 */
class Filetree extends AbstractTrackerController
{
    /**
     * Execute the controller.
     *
     * @return  string
     *
     * @since   1.0
     */
    public function execute()
    {
        // A path relative to the documentation directory (the client sends it through JavaScript's escape()).
        $dir = rawurldecode($this->getContainer()->get('app')->getInput()->get('dir', '', 'raw'));

        $docuBase = realpath(JPATH_ROOT . '/Documentation');
        $path     = $docuBase ? realpath($docuBase . '/' . $dir) : false;

        // The requested path must be an existing directory inside the documentation directory.
        if (
            !$path
            || !is_dir($path)
            || ($path !== $docuBase && strpos($path, $docuBase . DIRECTORY_SEPARATOR) !== 0)
        ) {
            return '';
        }

        $subPath = trim(str_replace('\\', '/', substr($path, \strlen($docuBase))), '/');

        $response = [];

        $files = scandir($path);

        if ($files === false) {
            return '';
        }

        natcasesort($files);

        if (\count($files) > 2) {
            $response[] = '<ul class="jqueryFileTree" style="display: none;">';

            // All dirs
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && is_dir($path . '/' . $file)) {
                    $rel = ($subPath ? $subPath . '/' : '') . $file;

                    $response[] = '<li class="directory collapsed">'
                        . '<a href="javascript:;" rel="' . $this->escape($rel) . '">' . $this->escape($file) . '</a>'
                        . '</li>';
                }
            }

            // All files
            foreach ($files as $file) {
                if (!is_dir($path . '/' . $file)) {
                    $page = substr($file, 0, strrpos($file, '.'));
                    $ext  = preg_replace('/^.*\./', '', $file);

                    $fullPath = 'page=' . rawurlencode($page)
                        . ($subPath ? '&path=' . str_replace('%2F', '/', rawurlencode($subPath)) : '');

                    $response[] = '<li class="file ext_' . $this->escape($ext) . '">'
                        . '<a href="javascript:;" rel="' . $this->escape($fullPath) . '">'
                        . $this->getTitle($file)
                        . '</a>'
                        . '</li>';
                }
            }

            $response[] = '</ul>';
        }

        return implode("\n", $response);
    }

    /**
     * Escape a string for use in HTML content and attributes.
     *
     * @param   string  $string  The string to escape.
     *
     * @since   1.0
     * @return  string
     */
    private function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate a nice title.
     *
     * @param   string  $file  A file name.
     *
     * @since   1.0
     * @return  string
     */
    private function getTitle($file)
    {
        $title = substr($file, 0, strrpos($file, '.'));

        $title = str_replace(['-', '_'], ' ', $title);

        $title = ucfirst($title);

        $title = $this->escape($title);

        return $title;
    }
}
