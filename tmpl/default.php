<?php

/**
 * @package     TLWeb.Module
 * @subpackage  mod_prettyslider
 *
 * @copyright   Copyright (C) 2024 TLWebdesign. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * @var  \stdClass  $module
 * @var  array      $slides
 * @var  string     $sliderId
 * @var  string     $titleTag
 * @var  boolean    $autoplay
 * @var  integer    $interval
 * @var  string     $moduleclassSfx
 */

// The rotation is started and stopped by the module script, so the visitor can pause it
HTMLHelper::_('bootstrap.carousel', '#' . $sliderId, ['interval' => $interval, 'ride' => false, 'pause' => false]);

$slideCount = count($slides);
?>
<div class="mod-prettyslider prettySliderWrapper<?php echo $moduleclassSfx !== '' ? ' ' . htmlspecialchars($moduleclassSfx, ENT_QUOTES, 'UTF-8') : ''; ?>">
    <div id="<?php echo $sliderId; ?>"
         class="carousel slide"
         role="region"
         aria-roledescription="<?php echo Text::_('MOD_PRETTYSLIDER_CAROUSEL'); ?>"
         aria-label="<?php echo htmlspecialchars($module->title, ENT_QUOTES, 'UTF-8'); ?>"
         <?php if ($autoplay) : ?>
            data-prettyslider
         <?php endif; ?>
    >
        <?php if ($autoplay) : ?>
            <button class="prettyslider-toggle"
                    type="button"
                    hidden
                    data-prettyslider-toggle
                    data-label-pause="<?php echo Text::_('MOD_PRETTYSLIDER_PAUSE'); ?>"
                    data-label-play="<?php echo Text::_('MOD_PRETTYSLIDER_PLAY'); ?>"
                    aria-label="<?php echo Text::_('MOD_PRETTYSLIDER_PAUSE'); ?>"
            >
                <span class="prettyslider-toggle-icon" aria-hidden="true"></span>
            </button>
        <?php endif; ?>
        <div class="carousel-inner">
            <?php foreach ($slides as $index => $slide) : ?>
                <div class="carousel-item<?php echo $index === 0 ? ' active' : ''; ?>"
                     role="group"
                     aria-roledescription="<?php echo Text::_('MOD_PRETTYSLIDER_SLIDE'); ?>"
                     aria-label="<?php echo Text::sprintf('MOD_PRETTYSLIDER_SLIDE_OF', $index + 1, $slideCount); ?>"
                >
                    <div class="w-100 h-100">
                        <img src="<?php echo htmlspecialchars($slide->image->url, ENT_QUOTES, 'UTF-8'); ?>"
                             alt="<?php echo htmlspecialchars($slide->image->alt, ENT_QUOTES, 'UTF-8'); ?>"
                             class="d-block w-100 h-auto"
                             <?php if ($slide->image->width > 0 && $slide->image->height > 0) : ?>
                                width="<?php echo $slide->image->width; ?>"
                                height="<?php echo $slide->image->height; ?>"
                             <?php endif; ?>
                             <?php if ($index > 0) : ?>
                                loading="lazy"
                             <?php endif; ?>
                        >
                        <div class="carousel-caption">
                            <<?php echo $titleTag; ?> class="h5 prettyslider-title">
                                <a href="<?php echo $slide->link; ?>">
                                    <?php echo htmlspecialchars($slide->title, ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </<?php echo $titleTag; ?>>
                            <?php if ($slide->description !== '') : ?>
                                <p class="d-none d-md-block"><?php echo htmlspecialchars($slide->description, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($slideCount > 1) : ?>
            <button class="carousel-control-prev"
                    type="button"
                    data-bs-target="#<?php echo $sliderId; ?>"
                    data-bs-slide="prev"
            >
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden"><?php echo Text::_('JPREVIOUS'); ?></span>
            </button>
            <button class="carousel-control-next"
                    type="button"
                    data-bs-target="#<?php echo $sliderId; ?>"
                    data-bs-slide="next"
            >
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden"><?php echo Text::_('JNEXT'); ?></span>
            </button>
        <?php endif; ?>
    </div>
</div>
