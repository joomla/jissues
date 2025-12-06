<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\EventListener;

use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationErrorEvent;
use Joomla\Application\WebApplicationInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Renderer\RendererInterface;
use Joomla\Router\Exception\MethodNotAllowedException;
use Joomla\Router\Exception\RouteNotFoundException;
use JTracker\Application\Application;
use JTracker\Authentication\Exception\AuthenticationException;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Error handling event subscriber
 *
 * @since  1.0
 */
class ErrorSubscriber implements SubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Layout renderer
     *
     * @var    RendererInterface
     * @since  1.0
     */
    private $renderer;

    /**
     * Event subscriber constructor.
     *
     * @param   RendererInterface  $renderer  Layout renderer
     *
     * @since   1.0
     */
    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ApplicationEvents::ERROR => 'handleWebError',
        ];
    }

    /**
     * Handle web application errors.
     *
     * @param   ApplicationErrorEvent  $event  Event object
     *
     * @return  void
     *
     * @since   1.0
     */
    public function handleWebError(ApplicationErrorEvent $event): void
    {
        /** @var Application $app */
        $app = $event->getApplication();

        $app->mark('Handling Throwable: ' . \get_class($event->getError()));

        switch (true) {
            case $event->getError() instanceof AuthenticationException:
                $context = [
                    'exception' => $event->getError(),
                    'action'    => $event->getError()->getAction(),
                ];

                // The exceptions contains the User object and the action.
                if ($event->getError()->getUser()->username) {
                    $context['user'] = $event->getError()->getUser()->username;
                    $context['id']   = $event->getError()->getUser()->id;
                }

                // Log the error for reference
                $this->logger->error(
                    'Authentication error',
                    $context
                );

                $this->prepareResponse($event);

                break;

            case $event->getError() instanceof MethodNotAllowedException:
                // Log the error for reference
                $this->logger->error(
                    \sprintf('Route `%s` not supported by method `%s`', $app->get('uri.route'), $app->input->getMethod()),
                    ['exception' => $event->getError()]
                );

                $this->prepareResponse($event);

                $app->setHeader('Allow', implode(', ', $event->getError()->getAllowedMethods()));

                break;

            case $event->getError() instanceof RouteNotFoundException:
                // Log the error for reference
                $this->logger->error(
                    \sprintf('Route `%s` not found', $app->get('uri.route')),
                    ['exception' => $event->getError()]
                );

                $this->prepareResponse($event);

                break;

            default:
                $this->logError($event->getError());

                $this->prepareResponse($event);

                break;
        }
    }

    /**
     * Log the error.
     *
     * @param   \Throwable  $throwable  The error being processed
     *
     * @return  void
     *
     * @since   1.0
     */
    private function logError(\Throwable $throwable): void
    {
        $this->logger->error(
            \sprintf('Uncaught Throwable of type %s caught.', \get_class($throwable)),
            ['exception' => $throwable]
        );
    }

    /**
     * Prepare the response for the event
     *
     * @param   ApplicationErrorEvent  $event  Event object
     *
     * @return  void
     *
     * @since   1.0
     */
    private function prepareResponse(ApplicationErrorEvent $event): void
    {
        /** @var Application $app */
        $app = $event->getApplication();

        // If we hit here in a CLI context things will fail. So just bail out.
        if ($app instanceof WebApplicationInterface) {
            $app->allowCache(false);
        }

        switch (true) {
            case $app->getInput()->getString('_format', 'html') === 'json':
            case $app->mimeType === 'application/json':
            case $app->getResponse() instanceof JsonResponse:
                $data = [
                    'code'    => $event->getError()->getCode(),
                    'message' => $event->getError()->getMessage(),
                    'error'   => true,
                ];

                $response = new JsonResponse($data);

                break;

            default:
                $body = $this->renderer->render(
                    'exception.twig',
                    [
                        'exception' => $event->getError(),
                    ]
                );

                $response = new HtmlResponse($body);

                break;
        }

        switch ($event->getError()->getCode()) {
            case 401:
                $response = $response->withStatus(401);

                break;

            case 403:
                $response = $response->withStatus(403);

                break;

            case 404:
                $response = $response->withStatus(404);

                break;

            case 405:
                $response = $response->withStatus(405);

                break;

            case 500:
            default:
                $response = $response->withStatus(500);

                break;
        }

        $app->setResponse($response);
    }
}
