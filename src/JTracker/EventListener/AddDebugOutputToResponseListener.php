<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\EventListener;

use App\Debug\TrackerDebugger;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\Application\WebApplicationInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Event listener which adds the debug output to the response
 *
 * @since  1.0
 */
final class AddDebugOutputToResponseListener
{
    /**
     * Application debugger
     *
     * @var    TrackerDebugger
     * @since  1.0
     */
    private $debugger;

    /**
     * Event listener constructor.
     *
     * @param   TrackerDebugger  $debugger  Application debugger
     *
     * @since   1.0
     */
    public function __construct(TrackerDebugger $debugger)
    {
        $this->debugger = $debugger;
    }

    /**
     * Adds the debug output to the response.
     *
     * @param   ApplicationEvent  $event  Event object
     *
     * @return  void
     *
     * @since   1.0
     */
    public function __invoke(ApplicationEvent $event): void
    {
        /** @var WebApplicationInterface $app */
        $app = $event->getApplication();

        if ($app->getResponse() instanceof HtmlResponse || $app->mimeType === 'text/html') {
            $app->setBody(
                str_replace('%%%DEBUG%%%', JDEBUG ? $this->debugger->getOutput() : '', $app->getBody())
            );
        }
    }
}
