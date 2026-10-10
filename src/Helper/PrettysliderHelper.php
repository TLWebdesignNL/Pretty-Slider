<?php

/**
 * @package     TLWeb.Module
 * @subpackage  mod_prettyslider
 *
 * @copyright   Copyright (C) 2024 TLWebdesign. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace TLWeb\Module\Prettyslider\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\Helpers\StringHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Helper for mod_prettyslider
 *
 * @since  1.0.0
 */
class PrettysliderHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Maximum number of article batches to load while looking for articles with an image.
     *
     * @since  1.0.0
     */
    private const MAX_BATCHES = 5;

    /**
     * Article orderings that can be selected in the module settings, besides random.
     *
     * @since  1.0.0
     */
    private const ORDERINGS = [
        'a.ordering',
        'fp.ordering',
        'a.title',
        'a.id',
        'a.alias',
        'a.created',
        'modified',
        'publish_up',
        'a.publish_down',
    ];

    /**
     * Retrieve the slides
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
        $maxSlides = max(1, (int) $params->get('maxslides', 5));

        // Get an instance of the generic articles model
        $articles = $app->bootComponent('com_content')->getMVCFactory()
            ->createModel('Articles', 'Site', ['ignore_request' => true]);

        // Set application parameters in model
        $articles->setState('params', $app->getParams());
        $articles->setState('filter.published', ContentComponent::CONDITION_PUBLISHED);
        $articles->setState('list.limit', $maxSlides);

        // Access filter
        $access     = !ComponentHelper::getParams('com_content')->get('show_noauth');
        $authorised = $app->getIdentity()->getAuthorisedViewLevels();
        $articles->setState('filter.access', $access);

        $articles->setState('filter.category_id', $params->get('catid'));

        // Ordering
        $ordering = (string) $params->get('article_ordering', 'a.title');

        if ($ordering === 'random') {
            $articles->setState('list.ordering', $this->getDatabase()->getQuery(true)->rand());
        } else {
            $direction = strtoupper((string) $params->get('article_ordering_direction', 'ASC'));

            $articles->setState('list.ordering', \in_array($ordering, self::ORDERINGS, true) ? $ordering : 'a.title');
            $articles->setState('list.direction', $direction === 'DESC' ? 'DESC' : 'ASC');
        }

        $slides = [];

        // Articles without an image are skipped, so keep loading batches until there are enough slides
        for ($batch = 0; $batch < self::MAX_BATCHES && \count($slides) < $maxSlides; $batch++) {
            $articles->setState('list.start', $batch * $maxSlides);
            $items = $articles->getItems() ?: [];

            foreach ($items as $item) {
                // Random ordering can return the same article in more than one batch
                if (isset($slides[$item->id])) {
                    continue;
                }

                $image = $this->getImage($item, $params);

                if ($image === null) {
                    continue;
                }

                $slide              = new \stdClass();
                $slide->title       = $item->title;
                $slide->description = $this->getDescription($item, $image, $params);

                // An image without alt text gets the article title as its text alternative
                if ($image->alt === '') {
                    $image->alt = $item->title;
                }

                $slide->image = $image;

                if ($access || \in_array($item->access, $authorised)) {
                    // We know that user has the privilege to view the article
                    $slide->link = Route::_(RouteHelper::getArticleRoute($item->id . ':' . $item->alias, $item->catid, $item->language));
                } else {
                    $slide->link = $this->getLoginLink($app);
                }

                $slides[$item->id] = $slide;

                if (\count($slides) >= $maxSlides) {
                    break;
                }
            }

            if (\count($items) < $maxSlides) {
                break;
            }
        }

        return array_values($slides);
    }

    /**
     * Get the image of an article, based on the image priority with the other image as fallback
     *
     * @param   object    $item    The article
     * @param   Registry  $params  The module parameters
     *
     * @return  \stdClass|null  The image, or null when the article has no usable image
     *
     * @since   1.0.0
     */
    private function getImage(object $item, Registry $params): ?\stdClass
    {
        // The images column can be empty or invalid for imported articles
        $data   = json_decode((string) ($item->images ?? ''), true);
        $images = new Registry(\is_array($data) ? $data : []);

        $available = array_filter([
            'intro'    => trim((string) $images->get('image_intro', '')),
            'fulltext' => trim((string) $images->get('image_fulltext', '')),
        ]);

        if (!$available) {
            return null;
        }

        // Try the preferred image first, then the other one
        $priority = (string) $params->get('imagepriority', 'intro');

        if (isset($available[$priority])) {
            $available = [$priority => $available[$priority]] + $available;
        }

        foreach ($available as $key => $value) {
            $clean = HTMLHelper::_('cleanImageURL', $value);
            $url   = $this->resolveImageUrl($clean->url);

            if ($url === null) {
                continue;
            }

            $image          = new \stdClass();
            $image->url     = $url;
            $image->width   = (int) $clean->attributes['width'];
            $image->height  = (int) $clean->attributes['height'];
            $image->caption = trim((string) $images->get('image_' . $key . '_caption', ''));
            $image->alt     = trim((string) $images->get('image_' . $key . '_alt', ''));

            return $image;
        }

        return null;
    }

    /**
     * Turn a stored image value into a URL that can be used in the page
     *
     * @param   string  $url  The image path or URL, without the Joomla image fragment
     *
     * @return  string|null  The URL, or null when it is not a local path or an http(s) URL
     *
     * @since   1.0.0
     */
    private function resolveImageUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if ($url === '' || $parts === false) {
            return null;
        }

        if (isset($parts['scheme'])) {
            return \in_array(strtolower($parts['scheme']), ['http', 'https'], true) ? $url : null;
        }

        // Protocol-relative URLs are not local files
        if (isset($parts['host'])) {
            return null;
        }

        return Uri::root(true) . '/' . ltrim($url, '/');
    }

    /**
     * Get the slide description from the configured source, truncated to the configured length
     *
     * @param   object     $item    The article
     * @param   \stdClass  $image   The slide image
     * @param   Registry   $params  The module parameters
     *
     * @return  string  The description as plain text
     *
     * @since   1.0.0
     */
    private function getDescription(object $item, \stdClass $image, Registry $params): string
    {
        $text = match ((string) $params->get('descsource', 'articletext')) {
            // Plugin tags such as {loadposition x} are not processed here, so leave them out
            'articletext'  => preg_replace('/\{\/?[a-z][a-z0-9_-]*(\s[^{}]*)?\}/i', '', (string) $item->introtext) ?? '',
            'imagealt'     => $image->alt,
            'imagecaption' => $image->caption,
            default        => '',
        };

        if (trim($text) === '') {
            return '';
        }

        return StringHelper::truncate($text, (int) $params->get('desclength', 40), true, false);
    }

    /**
     * Get the link to the login page, for articles the visitor is not allowed to view
     *
     * @param   SiteApplication  $app  The application
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function getLoginLink(SiteApplication $app): string
    {
        $link      = 'index.php?option=com_users&view=login';
        $menuitems = $app->getMenu()->getItems('link', $link);
        $itemId    = isset($menuitems[0]) ? (int) $menuitems[0]->id : $app->getInput()->getInt('Itemid', 0);

        // Use the Itemid of the requesting page only if there is no login menu item
        return Route::_($link . ($itemId > 0 ? '&Itemid=' . $itemId : ''));
    }
}
