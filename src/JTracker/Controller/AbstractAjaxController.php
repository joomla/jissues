<?php

/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Controller;

use Joomla\Application\WebApplicationInterface;

/**
 * Abstract controller for AJAX requests
 *
 * @since  1.0
 */
abstract class AbstractAjaxController extends AbstractTrackerController
{
    /**
     * AjaxResponse object.
     *
     * @var    AjaxResponse
     * @since  1.0
     */
    protected $response;

    /**
     * Constructor.
     *
     * @since   1.0
     */
    public function __construct()
    {
        parent::__construct();

        $this->response = new AjaxResponse();
    }

    /**
     * Execute the controller.
     *
     * @return  string  JSON response
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public function execute()
    {
        ob_start();

        try {
            $this->prepareResponse();
        } catch (\Exception $e) {
            // Log the error
            $this->getContainer()->get('app')->getLogger()->critical(
                \sprintf(
                    'Exception of type %1$s thrown',
                    \get_class($e)
                ),
                ['exception' => $e]
            );

            $this->response->error = $e->getMessage();
        }

        $errors = ob_get_clean();

        if ($errors) {
            $this->response->error .= $errors;
        }

        $this->getContainer()->get('app')->mimeType = 'application/json';

        return json_encode($this->response);
    }

    /**
     * Allows setting the status header into the application
     *
     * @param   int  $code  The status code to set into the application
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function setStatusCode($code = 200)
    {
        $app = $this->getContainer()->get('app');

        if ($app instanceof WebApplicationInterface) {
            $app->setHeader('Status', (int) $code);
        }
    }

    /**
     * Prepare the response.
     *
     * @return  void
     *
     * @since   1.0
     */
    abstract protected function prepareResponse();
}
