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
 * @var  boolean    $progress
 * @var  boolean    $indicators
 * @var  string     $moduleclassSfx
 * @var  string     $mainDivClass
 * @var  string     $titleClass
 * @var  string     $descriptionClass
 */

// The rotation is started and stopped by the module script, so the visitor can pause it
HTMLHelper::_('bootstrap.carousel', '#' . $sliderId, ['interval' => $interval, 'ride' => false, 'pause' => false]);

$slideCount = count($slides);
?>
<div class="<?php echo htmlspecialchars(trim('mod-prettyslider prettySliderWrapper ' . ($indicators ? 'has-indicators ' : '') . $moduleclassSfx . ' ' . $mainDivClass), ENT_QUOTES, 'UTF-8'); ?>">
    <div id="<?php echo $sliderId; ?>"
         class="carousel slide"
         role="region"
         aria-roledescription="<?php echo Text::_('MOD_PRETTYSLIDER_CAROUSEL'); ?>"
         aria-label="<?php echo htmlspecialchars($module->title, ENT_QUOTES, 'UTF-8'); ?>"
         <?php if ($autoplay) : ?>
            data-prettyslider
            style="--prettyslider-interval: <?php echo (int) $interval; ?>ms;"
         <?php endif; ?>
    >
        <?php if ($progress) : ?>
            <div class="prettyslider-progress" aria-hidden="true"><span class="prettyslider-progress-bar"></span></div>
        <?php endif; ?>
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
                    <?php
                    $link   = $slide->link !== '' ? htmlspecialchars($slide->link, ENT_QUOTES, 'UTF-8') : '';
                    $target = $slide->newWindow ? ' target="_blank" rel="noopener noreferrer"' : '';
                    // Without a title or button the image carries the link, which needs the alt text as its name
                    $linkImage = $link !== '' && $slide->title === '' && !$slide->button && $slide->image->alt !== '';
                    ?>
                    <div class="w-100 h-100">
                        <?php if ($linkImage) : ?>
                            <a href="<?php echo $link; ?>"<?php echo $target; ?>>
                        <?php endif; ?>
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
                        <?php if ($linkImage) : ?>
                            <?php if ($slide->newWindow) : ?>
                                <span class="visually-hidden"><?php echo Text::_('MOD_PRETTYSLIDER_OPENS_NEW_WINDOW'); ?></span>
                            <?php endif; ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($slide->title !== '' || $slide->description !== '' || $slide->button) : ?>
                            <div class="carousel-caption">
                                <?php if ($slide->title !== '') : ?>
                                    <<?php echo $titleTag; ?> class="<?php echo htmlspecialchars(trim($titleClass . ' prettyslider-title'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php if ($link !== '') : ?>
                                            <a href="<?php echo $link; ?>"<?php echo $target; ?>>
                                                <?php echo htmlspecialchars($slide->title, ENT_QUOTES, 'UTF-8'); ?>
                                                <?php if ($slide->newWindow) : ?>
                                                    <span class="visually-hidden"><?php echo Text::_('MOD_PRETTYSLIDER_OPENS_NEW_WINDOW'); ?></span>
                                                <?php endif; ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo htmlspecialchars($slide->title, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </<?php echo $titleTag; ?>>
                                <?php endif; ?>
                                <?php if ($slide->description !== '') : ?>
                                    <p class="<?php echo htmlspecialchars(trim($descriptionClass . ' prettyslider-description'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($slide->description, ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                                <?php if ($slide->button && $link !== '') : ?>
                                    <a href="<?php echo $link; ?>"<?php echo $target; ?>
                                       class="<?php echo htmlspecialchars($slide->button->class . ' prettyslider-button', ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($slide->button->text, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if ($slide->title !== '') : ?>
                                            <span class="visually-hidden">: <?php echo htmlspecialchars($slide->title, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                        <?php if ($slide->newWindow) : ?>
                                            <span class="visually-hidden"><?php echo Text::_('MOD_PRETTYSLIDER_OPENS_NEW_WINDOW'); ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($indicators) : ?>
            <div class="carousel-indicators">
                <?php foreach ($slides as $index => $slide) : ?>
                    <button type="button"
                            data-bs-target="#<?php echo $sliderId; ?>"
                            data-bs-slide-to="<?php echo $index; ?>"
                            aria-label="<?php echo Text::sprintf('MOD_PRETTYSLIDER_GO_TO_SLIDE', $index + 1); ?>"
                            <?php if ($index === 0) : ?>
                                class="active"
                                aria-current="true"
                            <?php endif; ?>
                    ></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
