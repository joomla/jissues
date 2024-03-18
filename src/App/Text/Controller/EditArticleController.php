<?php

/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Controller;

use App\Text\Model\ArticlesModel;
use Joomla\Controller\AbstractController;
use JTracker\View\BaseHtmlView;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Controller class to update an article.
 *
 * @method  \JTracker\Application\Application getApplication()
 *
 * @since  1.0
 */
class EditArticleController extends AbstractController
{
    /**
     * The articles model
     *
     * @var    ArticlesModel
     * @since  1.0
     */
    private $model;

    /**
     * The edit article HTML view
     *
     * @var    BaseHtmlView
     * @since  1.0
     */
    private $view;

    /**
     * Controller constructor.
     *
     * @param   ArticlesModel  $model  The articles model
     * @param   BaseHtmlView   $view   The articles HTML view
     *
     * @since   1.0
     */
    public function __construct(ArticlesModel $model, BaseHtmlView $view)
    {
        $this->model = $model;
        $this->view  = $view;
    }

    /**
     * Execute the controller.
     *
     * @return  boolean
     *
     * @since   1.0
     */
    public function execute()
    {
        $this->getApplication()->getUser()->authorize('admin');

        // Set view variables required in the template
        $this->view->addData('view', 'article')
            ->addData('layout', 'edit')
            ->addData('app', 'text');

        // Push an empty table object into the view
        // TODO - Twig doesn't use __get to read properties
        $this->view->addData('item', $this->model->findById($this->getInput()->getUint('id'))->getIterator());

        $this->getApplication()->setResponse(
            new HtmlResponse(
                $this->view->render()
            )
        );

        return true;
    }
}
