<?php

/**
 * @package     TLWeb.Module
 * @subpackage  mod_prettyslider
 *
 * @copyright   Copyright (C) 2024 TLWebdesign. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TLWeb\Module\Prettyslider\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

\defined('_JEXEC') or die;

/**
 * Dispatcher class for mod_prettyslider
 *
 * @since  1.0.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Elements that can be used for the slide title.
     *
     * @since  1.0.0
     */
    private const TITLE_TAGS = ['p', 'h2', 'h3', 'h4', 'h5', 'h6'];

    /**
     * Returns the layout data.
     *
     * @return  array|false  The layout data, or false when there is nothing to show
     *
     * @since   1.0.0
     */
    protected function getLayoutData()
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];
        $slides = $this->getHelperFactory()->getHelper('PrettysliderHelper')->getSlides($params, $data['app']);

        // Without slides there is nothing to show
        if (!$slides) {
            return false;
        }

        $titleTag = (string) $params->get('titletag', 'p');

        $data['slides']   = $slides;
        $data['sliderId'] = 'prettySliderCarousel' . (int) $this->module->id;
        $data['titleTag'] = \in_array($titleTag, self::TITLE_TAGS, true) ? $titleTag : 'p';
        $data['autoplay'] = \count($slides) > 1 && (bool) $params->get('autoplay', 1);
        $data['interval'] = min(60, max(2, (int) $params->get('interval', 5))) * 1000;

        if ($data['autoplay']) {
            $wa = $data['app']->getDocument()->getWebAssetManager();
            $wa->getRegistry()->addExtensionRegistryFile('mod_prettyslider');
            $wa->useScript('mod_prettyslider.slider');
        }

        return $data;
    }
}
