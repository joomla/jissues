<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Kernel;

use Joomla\Application\AbstractApplication;
use Joomla\DI\Container;
use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Registry\Registry;
use JTracker\Application\AppInterface;
use JTracker\Service\AuthenticationProvider;
use JTracker\Service\CacheProvider;
use JTracker\Service\DatabaseProvider;
use JTracker\Service\DispatcherProvider;
use JTracker\Service\GitHubProvider;
use JTracker\Service\HttpProvider;
use JTracker\Service\MonologProvider;
use JTracker\Service\RendererProvider;
use JTracker\Service\WebApplicationProvider;
use Psr\Log\LoggerInterface;

/**
 * Application kernel
 *
 * @since  1.0
 */
abstract class AbstractKernel implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Flag indicating this Kernel has been booted
     *
     * @var    boolean
     * @since  1.0
     */
    protected $booted = false;

    /**
     * Boot the Kernel
     *
     * @return  void
     *
     * @since   1.0
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->setContainer($this->buildContainer());

        $this->initializeApps();

        $this->booted = true;
    }

    /**
     * Run the kernel
     *
     * @return  void
     *
     * @since   1.0
     */
    public function run(): void
    {
        $this->boot();

        if (!$this->getContainer()->has(AbstractApplication::class)) {
            throw new \RuntimeException('The application has not been registered with the container.');
        }

        // Set the logger for the application. We're doing it here because there is a recursion issue with service resolution that needs to be fixed.
        $this->getContainer()->get(AbstractApplication::class)->setLogger($this->getContainer()->get(LoggerInterface::class));
        $this->getContainer()->get(AbstractApplication::class)->execute();
    }

    /**
     * Build the service container
     *
     * @return  Container
     *
     * @since   1.0
     */
    protected function buildContainer(): Container
    {
        $config = $this->loadConfiguration();

        $container = new Container();
        $container->set('config', $config, true);

        $container->registerServiceProvider(new AuthenticationProvider())
            ->registerServiceProvider(new CacheProvider())
            ->registerServiceProvider(new DatabaseProvider())
            ->registerServiceProvider(new DispatcherProvider())
            ->registerServiceProvider(new GitHubProvider())
            ->registerServiceProvider(new HttpProvider())
            ->registerServiceProvider(new MonologProvider())
            ->registerServiceProvider(new RendererProvider())
            ->registerServiceProvider(new WebApplicationProvider());

        return $container;
    }

    /**
     * Loads the application's apps
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function initializeApps()
    {
        // Find all components and if they have a AppInterface implementation load their services
        /** @var \DirectoryIterator $fileInfo */
        foreach (new \DirectoryIterator(JPATH_ROOT . '/src/App') as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            $className = 'App\\' . $fileInfo->getFilename() . '\\' . $fileInfo->getFilename() . 'App';

            if (class_exists($className)) {
                /** @var AppInterface $object */
                $object = new $className();

                // Register the app services
                $object->loadServices($this->getContainer());
            }
        }
    }

    /**
     * Load the application's configuration
     *
     * @return  Registry
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    private function loadConfiguration(): Registry
    {
        // Check for a custom configuration.
        $type = trim(getenv('JTRACKER_ENVIRONMENT'));

        $name = ($type) ? 'config.' . $type : 'config';

        // Set the configuration file path for the application.
        $file = JPATH_ROOT . '/etc/' . $name . '.json';

        // Verify the configuration exists and is readable.
        if (!is_readable($file)) {
            throw new \RuntimeException('Configuration file does not exist or is unreadable.');
        }

        // Load the configuration file into an object.
        $configObject = json_decode($this->replaceEnvVars(file_get_contents($file)));

        if ($configObject === null) {
            throw new \RuntimeException(\sprintf('Unable to parse the configuration file %s.', $file));
        }

        $config = new Registry($configObject);

        \defined('JDEBUG') || \define('JDEBUG', ($config->get('debug.system') || $config->get('debug.database')));

        return $config;
    }

    /**
     * Replace any env vars referenced in a string with their values.
     *
     * @param   string  $string  The string to replace.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function replaceEnvVars($string): string
    {
        foreach (array_keys($_ENV) as $var) {
            if (strstr($string, '$' . $var)) {
                $string = str_replace('$' . $var, $_ENV[$var], $string);
            }
        }

        return $string;
    }
}
