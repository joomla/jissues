<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker;

use App\Tracker\Controller\Comment\Ajax\Submit as CommentSubmit;
use App\Tracker\Controller\Issue\Ajax\Vote;
use App\Tracker\Controller\TestResult\Ajax\Alter as TestResultAlter;
use App\Tracker\Controller\TestResult\Ajax\Submit as TestResultSubmit;
use App\Tracker\Controller\Upload\Ajax\Delete as UploadDelete;
use App\Tracker\Controller\Upload\Ajax\Put as UploadPut;
use Joomla\Router\Exception\RouteNotFoundException;
use Joomla\Router\Router;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the routes of the Tracker app.
 *
 * @since  1.0
 */
class RoutesTest extends TestCase
{
    /**
     * Creates a router with the routes of the Tracker app, registered the same way as TrackerApp does.
     *
     * @return  Router
     */
    private function createRouter(): Router
    {
        $router = new Router();

        $maps = json_decode(file_get_contents(JPATH_ROOT . '/src/App/Tracker/routes.json'), true);

        $this->assertIsArray($maps, 'The routes file of the Tracker app is not valid JSON.');

        foreach ($maps as $pattern => $controller) {
            $router->all($pattern, $controller);
        }

        return $router;
    }

    /**
     * Data provider for the project aware AJAX routes.
     *
     * @return  array
     */
    public static function projectAjaxRouteProvider(): array
    {
        return [
            'comment'           => ['/submit/joomla-cms/comment', CommentSubmit::class],
            'vote'              => ['/submit/joomla-cms/vote', Vote::class],
            'submit testresult' => ['/submit/joomla-cms/testresult', TestResultSubmit::class],
            'alter testresult'  => ['/alter/joomla-cms/testresult', TestResultAlter::class],
            'upload'            => ['/upload/joomla-cms/put/', UploadPut::class],
            'delete upload'     => ['/upload/joomla-cms/delete/?file=0123456789abcdef0123456789abcdef.png', UploadDelete::class],
        ];
    }

    /**
     * @param   string  $url         The requested URL
     * @param   string  $controller  The expected controller
     *
     * @return  void
     */
    #[DataProvider('projectAjaxRouteProvider')]
    #[TestDox('The AJAX route $url takes the project alias from the URL')]
    public function testProjectAjaxRoutes(string $url, string $controller): void
    {
        $route = $this->createRouter()->parseRoute($url, 'POST');

        $this->assertSame($controller, $route->getController());
        $this->assertSame(['project_alias' => 'joomla-cms'], $route->getRouteVariables());
    }

    /**
     * Data provider for the AJAX routes without a project, which fell back to the project stored in the session.
     *
     * @return  array
     */
    public static function legacyAjaxRouteProvider(): array
    {
        return [
            'comment'           => ['/submit/comment'],
            'vote'              => ['/submit/vote'],
            'submit testresult' => ['/submit/testresult'],
            'alter testresult'  => ['/alter/testresult'],
            'upload'            => ['/upload/put/'],
            'delete upload'     => ['/upload/delete/'],
        ];
    }

    /**
     * @param   string  $url  The requested URL
     *
     * @return  void
     */
    #[DataProvider('legacyAjaxRouteProvider')]
    #[TestDox('The AJAX route $url without a project alias does not exist anymore')]
    public function testLegacyAjaxRoutesAreGone(string $url): void
    {
        $this->expectException(RouteNotFoundException::class);

        $this->createRouter()->parseRoute($url, 'POST');
    }
}
