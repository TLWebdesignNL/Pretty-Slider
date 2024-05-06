<?php

/**
 * @package     TLWeb.Module
 * @subpackage  mod_prettyslider
 *
 * @copyright   Copyright (C) 2024 TLWebdesign. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TlwebNamespace\Module\Prettyslider\Site\Helper;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Helper for mod_prettyslider
 *
 * @since  V1.0.0
 */
class PrettysliderHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Retrieve sliders
     *
     * @param   Registry         $params  The module parameters
     * @param   SiteApplication  $app     The application
     *
     * @return  array
     *
     * @since   1.0.0
     */

    public function getSlider(Registry $params, SiteApplication $app): array
    {
        $slides = array();

        $factory = $app->bootComponent('com_content')->getMVCFactory();

        // Get an instance of the generic articles model
        $articles = $factory->createModel('Articles', 'Site', ['ignore_request' => true]);

        // Set application parameters in model
        $appParams = $app->getParams();
        $articles->setState('params', $appParams);

        $articles->setState('list.start', 0);
        $articles->setState('filter.published', ContentComponent::CONDITION_PUBLISHED);

        // Set the filters based on the module params
        $articles->setState('list.limit', (int) $params->get('count', 0));

        // Access filter
        $access     = !ComponentHelper::getParams('com_content')->get('show_noauth');
        $authorised = Access::getAuthorisedViewLevels($app->getIdentity()->get('id'));
        $articles->setState('filter.access', $access);

        $catids = $params->get('catid');
        $articles->setState('filter.category_id', $catids);

        // Ordering
        $ordering = $params->get('article_ordering', 'a.ordering');
        switch ($ordering) {
            case 'random':
                $articles->setState('list.ordering', $this->getDatabase()->getQuery(true)->rand());
                break;

            case 'rating_count':
            case 'rating':
                $articles->setState('list.ordering', $ordering);
                $articles->setState('list.direction', $params->get('article_ordering_direction', 'ASC'));

                if (!PluginHelper::isEnabled('content', 'vote')) {
                    $articles->setState('list.ordering', 'a.ordering');
                }

                break;

            default:
                $articles->setState('list.ordering', $ordering);
                $articles->setState('list.direction', $params->get('article_ordering_direction', 'ASC'));
                break;
        }

        $items = $articles->getItems();

        foreach ($items as &$item) {
            $slide = new \stdClass();

            $item->slug = $item->id . ':' . $item->alias;
            $slide->title = $item->title;
            $slide->images = json_decode($item->images);
            $slide->description = $item->introtext;

            if ($access || \in_array($item->access, $authorised)) {
                // We know that user has the privilege to view the article
                $slide->link = Route::_(RouteHelper::getArticleRoute($item->slug, $item->catid, $item->language));
            } else {
                $input     = $app->getInput();
                $menu      = $app->getMenu();
                $menuitems = $menu->getItems('link', 'index.php?option=com_users&view=login');

                if (isset($menuitems[0])) {
                    $Itemid = $menuitems[0]->id;
                } elseif ($input->getInt('Itemid') > 0) {
                    // Use Itemid from requesting page only if there is no existing menu
                    $Itemid = $input->getInt('Itemid');
                }

                $slide->link = Route::_('index.php?option=com_users&view=login&Itemid=' . $Itemid);
            }
            $slides[] = $slide;
        }

        return $slides;
    }
}
