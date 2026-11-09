<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\View;

use Joomla\Model\StatefulModelInterface;
use JTracker\View\BaseHtmlView;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Tests for the BaseHtmlView class.
 *
 * @since  1.0
 */
class BaseHtmlViewTest extends TestCase
{
    /**
     * Creates a view with an in-memory Twig environment.
     *
     * @param   array  $templates  The templates, keyed by name
     *
     * @return  BaseHtmlView
     */
    private function createView(array $templates): BaseHtmlView
    {
        return new BaseHtmlView(
            $this->createStub(StatefulModelInterface::class),
            new Environment(new ArrayLoader($templates))
        );
    }

    #[TestDox('The view renders its layout with the view data')]
    public function testRenderUsesLayoutAndData(): void
    {
        $view = $this->createView(['page.twig' => '{{ greeting }}, {{ name }}!']);

        $view->setLayout('page.twig')
            ->addData('greeting', 'Hello')
            ->addData('name', 'World');

        $this->assertSame('page.twig', $view->getLayout());
        $this->assertSame('Hello, World!', $view->render());
        $this->assertSame('Hello, World!', (string) $view);
    }

    #[TestDox('Template variables are escaped')]
    public function testRenderEscapesData(): void
    {
        $view = $this->createView(['page.twig' => '{{ name }}']);

        $view->setLayout('page.twig')->addData('name', '<b>');

        $this->assertSame('&lt;b&gt;', $view->render());
    }

    #[TestDox('The data can be replaced, removed and cleared')]
    public function testDataManipulation(): void
    {
        $view = $this->createView([]);

        $view->addData('a', 1)->addData('b', 2);
        $this->assertSame(['a' => 1, 'b' => 2], $view->getData());

        $view->removeData('a');
        $this->assertSame(['b' => 2], $view->getData());

        $view->setData(['c' => 3]);
        $this->assertSame(['c' => 3], $view->getData());

        $view->clearData();
        $this->assertSame([], $view->getData());
    }

    #[TestDox('Data of one view does not leak into another view sharing the same Twig environment')]
    public function testDataIsNotSharedBetweenViews(): void
    {
        $twig  = new Environment(new ArrayLoader(['page.twig' => '[{{ app }}]']));
        $model = $this->createStub(StatefulModelInterface::class);

        $first  = (new BaseHtmlView($model, $twig))->setLayout('page.twig')->addData('app', 'tracker');
        $second = (new BaseHtmlView($model, $twig))->setLayout('page.twig');

        $this->assertSame('[tracker]', $first->render());
        $this->assertSame('[]', $second->render());
        $this->assertSame($twig, $second->getTwig());
    }

    #[TestDox('Casting a view with a missing template to a string returns the error message')]
    public function testToStringReturnsErrorMessage(): void
    {
        $view = $this->createView([]);

        $view->setLayout('missing.twig');

        $this->assertStringContainsString('missing.twig', (string) $view);
    }
}
