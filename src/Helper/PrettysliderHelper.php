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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;
use TLWeb\Module\Prettyslider\Site\Rule\SlideurlRule;

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
     * Bootstrap button classes that can be selected for a slide button.
     *
     * @since  1.0.0
     */
    private const BUTTON_CLASSES = [
        'btn',
        'btn-primary',
        'btn-secondary',
        'btn-success',
        'btn-danger',
        'btn-warning',
        'btn-info',
        'btn-light',
        'btn-dark',
        'btn-link',
        'btn-lg',
        'btn-sm',
        'w-100',
    ];

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
        if ($params->get('source', 'articles') === 'manual') {
            return $this->getManualSlides($params);
        }

        return $this->getArticleSlides($params, $app);
    }

    /**
     * Build the slides that were entered in the module settings
     *
     * @param   Registry  $params  The module parameters
     *
     * @return  array
     *
     * @since   1.0.0
     */
    private function getManualSlides(Registry $params): array
    {
        $rows   = $params->get('slides');
        $rows   = (\is_object($rows) || \is_array($rows)) ? array_values((array) $rows) : [];
        $slides = [];

        foreach ($rows as $row) {
            $row   = (array) $row;
            $clean = HTMLHelper::_('cleanImageURL', trim((string) ($row['image'] ?? '')));
            $url   = $this->resolveImageUrl($clean->url);

            // A slide needs an image
            if ($url === null) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $alt   = trim((string) ($row['alt'] ?? ''));

            $image          = new \stdClass();
            $image->url     = $url;
            $image->width   = (int) $clean->attributes['width'];
            $image->height  = (int) $clean->attributes['height'];
            $image->caption = '';
            $image->alt     = $alt !== '' ? $alt : $title;

            $slide              = new \stdClass();
            $slide->title       = $title;
            $slide->description = trim((string) ($row['description'] ?? ''));
            $slide->image       = $image;
            $slide->link        = $this->getManualLink((string) ($row['link'] ?? ''));
            $slide->newWindow   = $slide->link !== '' && (int) ($row['newwindow'] ?? 0) === 1;
            $slide->button      = $slide->link !== ''
                ? $this->getButton((string) ($row['buttontext'] ?? ''), $row['buttonclass'] ?? null, (string) ($row['custombuttonclass'] ?? ''))
                : null;

            $slides[] = $slide;
        }

        return $slides;
    }

    /**
     * Build the button of a slide
     *
     * @param   string  $text     The button text
     * @param   mixed   $classes  The selected Bootstrap button classes, with "custom" for the custom class
     * @param   string  $custom   The custom class, used when "custom" is selected
     *
     * @return  \stdClass|null  The button, or null without a text
     *
     * @since   1.0.0
     */
    private function getButton(string $text, $classes, string $custom): ?\stdClass
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        if (\is_string($classes)) {
            $classes = explode(',', $classes);
        }

        $classes   = (\is_object($classes) || \is_array($classes)) ? array_map('strval', array_values((array) $classes)) : [];
        $useCustom = \in_array('custom', $classes, true);
        $classes   = array_values(array_intersect(self::BUTTON_CLASSES, $classes));

        if ($useCustom) {
            $classes[] = self::cleanClass($custom);
        }

        $class = trim(implode(' ', $classes));

        $button        = new \stdClass();
        $button->text  = $text;
        $button->class = $class !== '' ? $class : 'btn btn-primary';

        return $button;
    }

    /**
     * Reduce a class setting to characters that are valid in a list of CSS classes
     *
     * @param   string  $class  One or more CSS classes, separated by spaces
     *
     * @return  string
     *
     * @since   1.0.0
     */
    public static function cleanClass(string $class): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9 _:\/.-]/', '', $class) ?? '') ?? '');
    }

    /**
     * Makes the link of a manual slide safe for use in an href: URLs with a scheme that can run script are
     * dropped (see SlideurlRule), and internal non-SEF links are routed.
     *
     * @param   string  $url  The link as stored in the module params
     *
     * @return  string  The URL to link to (not HTML-escaped), or an empty string when there is no usable link
     *
     * @since   1.0.0
     */
    private function getManualLink(string $url): string
    {
        $url = trim($url);

        if ($url === '' || !SlideurlRule::isAllowed($url)) {
            return '';
        }

        // The url filter saves "index.php?..." as "<site path>/index.php?...", so strip the site path again
        $rootPath = Uri::root(true);

        if (str_starts_with($url, $rootPath . '/index.php')) {
            $url = substr($url, \strlen($rootPath) + 1);
        }

        if (str_starts_with($url, 'index.php')) {
            $url = Route::_($url, false);
        }

        return $url;
    }

    /**
     * Build the slides from the articles in the selected categories
     *
     * @param   Registry         $params  The module parameters
     * @param   SiteApplication  $app     The application
     *
     * @return  array
     *
     * @since   1.0.0
     */
    private function getArticleSlides(Registry $params, SiteApplication $app): array
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

        // The same button on every slide, linking to the article
        $button = null;

        if ((int) $params->get('articlebutton', 0) === 1) {
            $buttonText = trim((string) $params->get('articlebuttontext', ''));
            $button     = $this->getButton(
                $buttonText !== '' ? $buttonText : Text::_('MOD_PRETTYSLIDER_READ_MORE'),
                $params->get('articlebuttonclass'),
                (string) $params->get('articlecustombuttonclass', '')
            );
        }

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
                    $slide->link = Route::_(RouteHelper::getArticleRoute($item->id . ':' . $item->alias, $item->catid, $item->language), false);
                } else {
                    $slide->link = $this->getLoginLink($app);
                }

                $slide->newWindow  = false;
                $slide->button     = $button;
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
     * @return  string  The URL (not HTML-escaped)
     *
     * @since   1.0.0
     */
    private function getLoginLink(SiteApplication $app): string
    {
        $link      = 'index.php?option=com_users&view=login';
        $menuitems = $app->getMenu()->getItems('link', $link);
        $itemId    = isset($menuitems[0]) ? (int) $menuitems[0]->id : $app->getInput()->getInt('Itemid', 0);

        // Use the Itemid of the requesting page only if there is no login menu item
        return Route::_($link . ($itemId > 0 ? '&Itemid=' . $itemId : ''), false);
    }
}
