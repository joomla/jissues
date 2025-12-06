<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Make;

use JTracker\Command\TrackerCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class for generating a dependency file.
 *
 * @since  1.0
 */
class Depfile extends TrackerCommand
{
    /**
     * Product object.
     *
     * @var    object
     * @since  1.0
     */
    public $product;

    /**
     * Dependencies.
     *
     * @var    array
     * @since  1.0
     */
    public $dependencies = [];

    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function configure(): void
    {
        $this->setName('make:depfile');
        $this->setDescription('Create and update a dependency file.');
        $this->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Write output to a file.');
    }

    /**
     * Execute the command.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  integer
     *
     * @throws  \RuntimeException
     * @throws  \UnexpectedValueException
     * @since   1.0
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);

        $packages = [];
        $defined  = [];

        $defined['composer'] = json_decode(file_get_contents(JPATH_ROOT . '/composer.json'));
        $defined['npm']      = json_decode(file_get_contents(JPATH_ROOT . '/package.json'));
        $defined['credits']  = json_decode(file_get_contents(JPATH_ROOT . '/credits.json'));

        $installedComposer = json_decode(file_get_contents(JPATH_ROOT . '/vendor/composer/installed.json'));
        $installedNpm      = json_decode(file_get_contents(JPATH_ROOT . '/package-lock.json'));

        $this->product = $defined['composer'];

        foreach ($installedComposer as $entry) {
            $package = new \stdClass();

            $package->name        = $entry->name;
            $package->description = $entry->description ?? '';
            $package->version     = $entry->version;
            $package->sourceURL   = $entry->source->url;
            $package->sourceRef   = $entry->source->reference ?? '';

            $packages['composer'][$entry->name] = $package;
        }

        foreach ($installedNpm->dependencies as $packageName => $packageData) {
            if (!isset($defined['npm']->dependencies->$packageName)) {
                continue;
            }

            $package = new \stdClass();

            $package->name        = $packageName;
            $package->description = '';
            $package->version     = $packageData->version;
            $package->sourceURL   = '';

            $packages['npm'][$packageName] = $package;
        }

        $this->dependencies = $this->getSorted($defined, $packages);

        $twig = new Environment(new FilesystemLoader(__DIR__ . '/tpl'));

        $twig->setCache(false);

        $contents = $twig->render(
            'dependencies.twig',
            ['dependencies' => $this->dependencies, 'product' => $this->product]
        );

        $fileName = $input->getOption('file');

        if ($fileName) {
            $ioStyle->text(\sprintf('Writing contents to: %s', $fileName));

            file_put_contents($fileName, $contents);
        } else {
            $ioStyle->text($contents);
        }

        $ioStyle->newLine();
        $ioStyle->success('Finished.');

        return Command::SUCCESS;
    }

    /**
     * Generate HTML output.
     *
     * @param   array  $defined   List of defined packages
     * @param   array  $packages  List of installed packages
     *
     * @return  string  HTML output
     *
     * @since   1.0
     */
    private function getSorted(array $defined, array $packages)
    {
        $sorted = [];

        foreach (['require' => 'php', 'require-dev' => 'php-dev'] as $sub => $section) {
            $items = [];

            foreach ($defined['composer']->$sub as $packageName => $version) {
                if ($packageName == 'php') {
                    $o          = new \stdClass();
                    $o->version = $version;

                    $sorted['php-version'] = $o;
                    $sorted['php-version'] = $version;

                    continue;
                }

                $item              = new \stdClass();
                $item->packageName = $packageName;
                $item->version     = $version;
                $item->installed   = '';
                $item->description = '';
                $item->sourceRef   = '';
                $item->sourceURL   = '';

                if (isset($packages['composer'][$packageName])) {
                    $item->description = $packages['composer'][$packageName]->description;
                    $item->installed   = $packages['composer'][$packageName]->version;
                    $item->sourceURL   = $packages['composer'][$packageName]->sourceURL;

                    if ($packages['composer'][$packageName]->version == 'dev-master') {
                        $item->sourceRef = $packages['composer'][$packageName]->sourceRef;
                    }
                }

                $items[] = $item;
            }

            $sorted[$section] = $items;
        }

        foreach ($defined['npm']->dependencies as $packageName => $version) {
            $installed = $packages['npm'][$packageName];

            $item = new \stdClass();

            $item->packageName = $packageName;
            $item->version     = $version;
            $item->installed   = $installed->version;
            $item->description = $installed->description ?: '';
            $item->sourceURL   = $installed->sourceURL ?: '';

            $sorted['javascript'][] = $item;
        }

        $sorted['credits'] = $defined['credits'];

        return $sorted;
    }
}
