<?php

/**
 * @package     TLWeb.Module
 * @subpackage  mod_prettyslider
 *
 * @copyright   Copyright (C) 2024 TLWebdesign. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

$minHeight = $params->get('minheight', 0);
$maxHeight = $params->get('maxheight', 0);

?>
<div class="pretty-slider">
    <?php
    foreach ($slides as $slide) : ?>
        <?php
        if ($slide->image->url != "") :
            $width = $slide->image->attributes['width'];
            $height = $slide->image->attributes['height'];
            $aspectRatio = 0.25;

            if ($width != 0 && $height != 0) {
                $aspectRatio = $height / $width;
            }
            ?>
            <div class="ratio d-flex justify-content-center align-items-center p-3 p-sm-5"
                 style="
                         --aspect-ratio: <?php echo round($aspectRatio * 100); ?>%;
                         background:url('<?php echo $slide->image->url; ?>') center center / cover no-repeat;
                         <?php echo ($minHeight) ? "min-height: " . $minHeight . "px;" : ""; ?>
                         <?php echo ($maxHeight) ? "max-height: " . $maxHeight . "px;" : ""; ?>
                         ">
                <div
                        class="content d-flex flex-column align-items-<?php
                        echo $slide['position']; ?>
                        w-auto h-auto position-relative text-white text-center"
                >
                    <div class="h3 title">
                        <span class=""
                              style="-webkit-box-decoration-break:clone;box-decoration-break:clone;"
                        >
                            <?php
                            echo $slide->title; ?>
                        </span>
                    </div>
                    <?php
                    if (!empty($slide->description)) : ?>
                        <div class="description mt-sm-2">
                            <span class=""
                                  style="-webkit-box-decoration-break:clone;box-decoration-break:clone;"
                            >
                                <?php
                                echo $slide->description; ?>
                            </span>
                        </div>
                    <?php
                    endif; ?>
                </div>
            </div>
        <?php
        endif; ?>
    <?php
    endforeach; ?>
</div>
