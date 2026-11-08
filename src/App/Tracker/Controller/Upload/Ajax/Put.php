<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Upload\Ajax;

use JTracker\Controller\AbstractAjaxController;
use JTracker\Controller\CsrfProtectedControllerInterface;
use JTracker\Upload\File;

/**
 * Upload images controller class.
 *
 * @since  1.0
 */
class Put extends AbstractAjaxController implements CsrfProtectedControllerInterface
{
    /**
     * Prepare the response.
     *
     * @return  mixed
     *
     * @since   1.0
     */
    protected function prepareResponse()
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');
        $user        = $application->getUser();

        // Only logged in users who are allowed to create items may upload files.
        if (!$user->id) {
            throw new \Exception('You are not allowed to upload files.');
        }

        $user->authorize('create');

        $files = $application->input->files->get('files');

        if (!empty($files)) {
            $file = new File($application);

            // Prepare response data
            $host     = $application->get('uri')->base->host;
            $destDir  = $application->getProject()->project_id;

            // The stored name is generated server side, the extension is validated against a whitelist during upload.
            $destName = bin2hex(random_bytes(16)) . '.' . strtolower($file->getExtension());
            $fullPath = $host . '/' . $application->get('system.upload_dir') . '/' . $destDir . '/' . $destName;

            $isImage = (strpos($file->getMimetype(), 'image') !== false);

            $alt = $isImage
                ? 'screen shot ' . date('Y-m-d') . ' at ' . date('H i s')
                : trim($file->getName()) . '.' . $file->getExtension();

            $data = [
                [
                    'url'          => $fullPath,
                    'thumbnailUrl' => $fullPath,
                    'name'         => $file->getName(),
                    'type'         => $file->getMimetype(),
                    'size'         => $file->getSize(),
                    'alt'          => $alt,
                    'isImage'      => $isImage,
                    'deleteUrl'    => '/upload/delete/?file=' . $destName,
                    'deleteType'   => 'POST',
                    'editorId'     => $application->input->get('editorId'),
                ],
            ];

            // Do not pass the thumbnail if not an image
            if (!$isImage) {
                unset($data[0]['thumbnailUrl']);
            }

            // Try to upload file
            try {
                $file->upload($destName);

                // Remember the upload, so the uploader is able to delete it again.
                $session   = $application->getSession();
                $uploads   = (array) $session->get('tracker.uploads', []);
                $uploads[] = $destDir . '/' . $destName;
                $session->set('tracker.uploads', $uploads);
            } catch (\Exception $e) {
                $application->getLogger()->error('Failed uploading file', ['file' => json_encode($file), 'exception' => $e]);

                $errors = [];

                foreach ($file->getErrors() as $error) {
                    $errors[] = $error;
                }

                $data = [
                    [
                        'error' => $errors,
                    ],
                ];
            }

            $this->response->files = $data;
        }
    }
}
