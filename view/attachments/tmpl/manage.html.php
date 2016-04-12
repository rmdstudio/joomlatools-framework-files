<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright	Copyright (C) 2011 - 2014 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/joomlatools-framework-files for the canonical source repository
 */
defined('KOOWA') or die;

$can_attach = isset(parameters()->config['can_attach']) ? parameters()->config['can_attach'] : true;
$can_detach = isset(parameters()->config['can_detach']) ? parameters()->config['can_detach'] : true;

$query = url()->getQuery(true);

$table     = $query['table'];
$row       = $query['row'];
$component = isset($component) ? $component : substr($query['option'], 4);
$callback  = isset($query['callback']) ? $query['callback'] : null;
?>

<?= helper('bootstrap.load', array('class' => array('full_height', 'com_files--attachments'))); ?>
<?= helper('behavior.jquery'); ?>

<?= import('com:files.files.scripts.html'); ?>

<ktml:script src="media://koowa/com_files/js/files.attachments.js"/>
<ktml:style src="media://koowa/com_files/css/files.css"/>

<script>
    Files.sitebase = '<?= $sitebase; ?>';
    Files.token = '<?= $token; ?>';

    kQuery(function($)
    {
        var config = <?= json_encode(KObjectConfig::unbox(parameters()->config)); ?>,
            options = {
                cookie: {path: '<?=object('request')->getSiteUrl()?>'},
                callback: <?= json_encode(isset($callback) ? $callback : '') ?>,
                url:  "<?= route('component='. urlencode($component) .'&view=attachments&format=json&table=' . $table . '&row=' . $row, true, false) ?>",
                root_text: <?= json_encode(translate('Root folder')) ?>,
                editor: <?= json_encode(parameters()->editor); ?>,
                types: <?= json_encode(KObjectConfig::unbox(parameters()->types)); ?>,
                container: <?= json_encode($container->toArray()) ?>
            };
        options = Object.append(options, config);

        Files.app = new Files.Attachments.App(options);

        var app = Files.app;

        var updateGridCount = function() {
            $('.koowa_dialog__file_dialog_attachments .count').html('(' + this.getCount() + ')');
        }.bind(app.grid);

        // Update attachements label count.
        app.grid.addEvent('afterInsertRows', function() {
            updateGridCount();
        });

        // Update attachements label count.
        app.grid.addEvent('afterDeleteNode', function() {
            updateGridCount();
        });

        app.grid.addEvent('afterRenderObject', function(object, position)
        {
            var that = this;

            $(object.object.element).find('span').click(function()
            {
                var attachment = object.object.name;
                that.select(attachment);

                if (confirm(<?= json_encode(translate('You are about to remove this attachment. Would you like to proceed?')) ?>)) {
                    that.detach(attachment);
                }
            });
        }.bind(app.grid));

        app.grid.addEvent('afterInsertNode', function(data)
        {
            this.select(data.node); // Auto-select attached file after attach.

        }.bind(app.grid));

        Attachments = Attachments.getInstance(
            {
                url: "<?= route('component=' . urlencode($component) . '&view=attachment&container=' . urlencode($container->slug), true, false) ?>",
                selector: '.koowa_dialog',
                csrf_token: <?= json_encode(object('user')->getSession()->getToken()) ?>
            }
        );

        $('.attachments-uploader').on('uploader:uploaded', function (event, data)
        {
            var response = data.result.response;

            if (typeof response.entities !== 'undefined') {
                app.grid.attach(data.file.name);
            }
        }).on('uploader:create', function() {
            $(this).addClass('k-upload--boxed-top');
        });

        // Attach action implementation
        app.grid.attach = function (attachment)
        {
            this.fireEvent('beforeAttachAttachment', {attachment: attachment});
            Attachments.attach(attachment);
        }.bind(app.grid);

        // After attach logic.
        Attachments.bind('after.attach', function (event, context)
            {
                var url = "<?= route('component=' . urlencode($component) . '&view=attachments&container=' . urlencode($container->slug) . '&format=json&name={name}&table={table}&row={row}', true, false) ?>";

                url = Attachments.replace(url, {
                    name: context.attachment,
                    table: <?= json_encode($table) ?>,
                    row: <?= json_encode($row) ?>
                });

                var that = this;

                $.ajax({
                    url: url,
                    method: 'get',
                    success: function (data) {
                        that.insertRows(data.entities);
                        that.fireEvent('afterAttachAttachment', {attachment: {name: context.attachment, entity: data.entities.pop()}});
                    },
                    error: function() {
                        that.fireEvent('afterAttachAttachment', {attachment: {name: context.attachment}});
                    }
                });
            }.bind(app.grid)
        );

        var setContext = function (context) {
            context.url += (context.url.search(/\?/) ? '&' : '?');
            context.url += 'name=' + Attachments.escape(context.attachment);

            context.data.table = <?= json_encode($table) ?>;
            context.data.row = <?= json_encode($row) ?>;
        };

        Attachments.bind('before.attach', function (event, context) {
            setContext(context)
        });

        // Detach grid implementation.
        app.grid.detach = function (attachment)
        {
            node = this.nodes.get(attachment);

            if (node) {
                this.fireEvent('beforeDetachAttachment', {node: node});

                Attachments.detach(attachment);
            }
        }.bind(app.grid);

        Attachments.bind('before.detach', function (event, context) {
            setContext(context)
        });

        Attachments.bind('after.detach', function (event, context)
        {
            this.erase(context.attachment);

            $('#files-preview').empty();

            this.fireEvent('afterDetachAttachment', {node: node});
        }.bind(app.grid));
    });
</script>

<?= import('com:files.files.templates_compact.html');?>
<?= import('com:files.attachments.templates_manage.html', array('can_detach' => $can_detach));?>

<div class="koowa_dialog koowa_dialog--file_dialog">
    <div class="koowa_dialog__layout">
        <div class="koowa_dialog__wrapper">
            <div class="attachments-table">
                <? if ($can_attach): ?>
                    <div class="attachments-upload">
                        <?= helper('uploader.container', array(
                            'container' => $container->slug,
                            'element' => '.attachments-uploader',
                            'options'   => array(
                                'multi_selection' => true,
                                'duplicate_mode' => 'unique',
                                'url' => route('component=' . urlencode($component) . '&view=file&plupload=1&routed=1&format=json&container=' .
                                               (isset($container) ? $container->slug : ''), false, false)
                            )
                        )) ?>
                    </div>
                <? endif ?>
                <div class="attachments-list">
                    <div class="attachments-attached">

                        <div class="koowa_dialog__wrapper__child koowa_dialog__file_dialog_attachments">
                            <h2 class="koowa_dialog__title">
                                <?= translate('Attached files'); ?>
                                <span class="count"></span>
                            </h2>
                            <div class="koowa_dialog__child__content koowa_spinner_container" id="attachments-spinner">
                                <div class="koowa_dialog__child__content__box">
                                    <div id="attachments-grid" style="max-height:450px;">

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="attachments-preview">

                        <div class="koowa_dialog__wrapper__child koowa_dialog__file_dialog_insert">
                            <h2 class="koowa_dialog__title">
                                <?= translate('Selected attachment info'); ?>
                            </h2>
                            <div class="koowa_dialog__child__content koowa_spinner_container">
                                <div class="koowa_dialog__child__content__box">
                                    <div id="files-preview"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div id="files-grid" style="display: none; max-height:450px;">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>