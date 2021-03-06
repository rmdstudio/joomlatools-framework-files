<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright	Copyright (C) 2011 - 2014 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/nooku/nooku-files for the canonical source repository
 */
defined('KOOWA') or die;
?>

<div class="k-sidebar-right k-js-sidebar-right">

    <div class="k-sidebar-item">
        <div class="k-sidebar-item__header">
            <?= translate('Selected file info'); ?>
        </div>
        <div class="k-sidebar-item__content">
            <div class="k-content-block" id="files-preview">
                <?= translate('Select a file from the list'); ?>
            </div>
            <div id="insert-button-container"></div>
        </div>
    </div>
</div><!-- .k-sidebar-right -->