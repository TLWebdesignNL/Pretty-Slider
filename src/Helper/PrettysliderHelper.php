<?php

/**
 * @package     TLWeb.Module
 * @subpackage  mod_prettyslider
 *
 * @copyright   Copyright (C) 2024 TLWebdesign. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TLWeb\Module\Prettyslider\Site\Helper;

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
use Joomla\CMS\HTML\Helpers\StringHelper;
use Joomla\CMS\HTML\HTMLHelper;


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

    public function getSlides(Registry $params, SiteApplication $app): array
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
        $articles->setState('list.limit', (int) $params->get('maxslides', 5));

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
            $images           = json_decode($item->images);
            $imagesProperties = get_object_vars($images);
            $imageUrlExists['intro']   = (!empty($images->image_intro)) ?? null;
            $imageUrlExists['fulltext']   = (!empty($images->image_fulltext)) ?? null;

            // check that there is an image
            $isImage = array_filter($imageUrlExists, function ($value) {
                return !is_null($value);
            });

            // check if there is an image url, else we don't add the article to the slides
            if ($isImage) {
                $slide = new \stdClass();

                $slide->title = $item->title;
                // get image priority
                $imagePriority = $params->get("imagepriority", "intro");

                // check if the image priority has a valid url else set the priority to the other option as fallback
                if (!$imageUrlExists[$imagePriority]) {
                    $imagePriority = array_key_first($imageUrlExists);
                }

                //  create image data based on priority
                $image          = new \stdClass();
                $imageUrlKey = 'image_' . $imagePriority;
                $imageCaptionKey = 'image_' . $imagePriority . '_caption';
                $imageAltKey = 'image_' . $imagePriority  . '_alt';
                $cleanImageUrl = HTMLHelper::_('cleanImageURL', $images->{$imageUrlKey});
                $image->url   = $cleanImageUrl->url;
                $image->height = $cleanImageUrl->attributes['height'];
                $image->width = $cleanImageUrl->attributes['width'];
                $image->caption = $images->{$imageCaptionKey};
                $image->alt     = $images->{$imageAltKey};

                // assign image variable to slide
                $slide->image = $image;

                switch ($params->get("descsource", "")) {
                    case "articletext":
                        $descriptionText = $item->introtext;
                        break;
                    case "imagealt":
                        $descriptionText = $image->alt;
                        break;
                    case "imagecaption":
                        $descriptionText = $image->caption;
                        break;
                    default:
                        $descriptionText = "";
                }
                // define description based on settings
                $slide->description = StringHelper::truncate(
                    $descriptionText,
                    $params->get("desclength", 50),
                    true,
                    false
                );

                // check if we have access to the article and then create a link
                if ($access || \in_array($item->access, $authorised)) {
                    // We know that user has the privilege to view the article
                    $slide->link = Route::_(RouteHelper::getArticleRoute($item->id . ':' . $item->alias, $item->catid, $item->language));
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
        }

        return $slides;
    }
}
