<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Twig;

use JTracker\Twig\AssetsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * Tests for the AssetsExtension class.
 *
 * @since  1.0
 */
class AssetsExtensionTest extends TestCase
{
    /**
     * @testdox  The extension registers the asset function as runtime function of the asset packages
     *
     * @return  void
     */
    public function testGetFunctions(): void
    {
        $functions = (new AssetsExtension())->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('asset', $functions[0]->getName());
        $this->assertSame([Packages::class, 'getUrl'], $functions[0]->getCallable());
    }

    /**
     * @testdox  The asset function resolves URLs with the asset packages runtime
     *
     * @return  void
     */
    public function testAssetFunction(): void
    {
        $twig = new Environment(new ArrayLoader([
            'test' => '{{ asset("css/template.css") }}|{{ asset("js/vendor/lib.js", "noversion") }}',
        ]));
        $twig->addExtension(new AssetsExtension());
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            Packages::class => static function () {
                return new Packages(
                    new PathPackage('media', new StaticVersionStrategy('abc123')),
                    ['noversion' => new PathPackage('media', new EmptyVersionStrategy())]
                );
            },
        ]));

        $this->assertSame('/media/css/template.css?abc123|/media/js/vendor/lib.js', $twig->render('test'));
    }
}
