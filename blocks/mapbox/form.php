<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<div class="ccm-lgt-mapbox-block-container">
    <fieldset>
        <legend><?php echo t('Map Settings'); ?></legend>

        <div class="form-group">
            <?php
                echo $form->label('centerLatitude', t('Centre Latitude'));
echo $form->text('centerLatitude', $centerLatitude ?? null);
?>
        </div>

        <div class="form-group">
            <?php
    echo $form->label('centerLongitude', t('Centre Longitude'));
echo $form->text('centerLongitude', $centerLongitude ?? null);
?>
        </div>

        <div class="form-group">
            <?php
    echo $form->label('theme', t('Theme'));
echo $form->text('theme', $theme ?? null);
?>
            <div class="help-block">
                <?php echo t('Styles can be found <a href="https://docs.mapbox.com/mapbox-gl-js/api/map/" target="_blank">here</a>. Custom styles can be created in <a href="https://studio.mapbox.com/" target="_blank">Mapbox Studio</a>. Ensure the Mapbox GL JS version is set to <code>3.21.0</code>'); ?>
            </div>
        </div>

        <div class="form-group">
            <?php
    echo $form->label('zoom', t('Zoom'));
echo $form->text('zoom', $zoom ?? null);
?>
        </div>

        <div class="form-group">
            <?php
    echo $form->label('pitch', t('Pitch'));
echo $form->text('pitch', $pitch ?? null);
?>
        </div>

    </fieldset>

    <fieldset>
        <legend><?php echo t('Map Controls'); ?></legend>

        <div class="form-group">
            <div class="form-check">
                <?php echo $form->checkbox('interactive', 1, (isset($interactive) && $interactive > 0) ? true : false); ?>
                <label for="interactive" class="form-check-label"><?php echo t('Allow map interaction?'); ?></label>
            </div>
        </div>

        <div class="form-group">
            <div class="form-check">
                <?php echo $form->checkbox('show_controls', 1, (isset($show_controls) && $show_controls > 0) ? true : false); ?>
                <label for="show_controls" class="form-check-label"><?php echo t('Show controls?'); ?></label>
            </div>
        </div>

        <div class="form-group">
            <?php
    echo $form->label('control_placement', t('Control Placement'));
echo $form->select('control_placement', $controller->getControlPlacementOptions(), $control_placement ?? null);
?>
        </div>
    </fieldset>

    <fieldset>
        <legend><?php echo t('3D Enhancement'); ?></legend>

        <div class="form-group">
            <div class="form-check">
                <?php echo $form->checkbox('showBuildings', 1, (isset($showBuildings) && $showBuildings > 0) ? true : false); ?>
                <label for="showBuildings" class="form-check-label"><?php echo t('Show buildings in 3D?'); ?></label>
            </div>
        </div>

        <div class="form-group">
            <?php
    echo $ch->output('extrusionColor', $extrusionColor ?? null, [ 'preferredFormat' => 'hex' ]);
echo $form->label('extrusionColor', t('Building Colour'));
?>
        </div>
    </fieldset>

    <fieldset>
        <legend><?php echo t('Markers'); ?></legend>

        <div class="ccm-lgt-mapbox-markers ccm-lgt-mapbox-markers-<?php echo $bID ?? 0; ?>"></div>

        <div class="form-group">
            <button type="button" class="btn btn-success ccm-add-lgt-mapbox-marker ccm-add-lgt-mapbox-marker-<?php echo $bID ?? 0; ?>">
                <?php echo t('Add Marker'); ?>
            </button>
        </div>
    </fieldset>
</div>
<script>
    <?php
        $app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
$editorJavascript = $app->make('editor')->outputStandardEditorInitJSFunction();
?>
    var launchEditor = <?php echo $editorJavascript?>;
    $(function() {
        var container           = $('.ccm-lgt-mapbox-block-container');
        var lgtMapboxMarkers    = $('.ccm-lgt-mapbox-markers-<?php echo $bID ?? 0; ?>');
        var _templateLink       = _.template($('#lgt-mapbox-marker-<?php echo $bID ?? 0; ?>').html());

        var attachDelete = function($obj) {
            $obj.click(function() {
                var deleteIt = confirm('<?php echo t('Are you sure?'); ?>');
                if (deleteIt === true) {
                    $(this).closest('.ccm-lgt-mapbox-marker-<?php echo $bID ?? 0; ?>').remove();
                    doSortCount();
                }
            });
        };

        var attachCopyTitle = function($obj) {
            $obj.on('focus blur', function() {
                $(this).closest('.form-group').find('span').html($(this).val());
            });
        };

        var doSortCount = function() {
            $('.ccm-lgt-mapbox-marker-<?php echo $bID ?? 0; ?>').each(function(index) {
                $(this).find('.ccm-lgt-mapbox-marker-sort').val(index);
            });
        };

        <?php if (!empty($rows)) { ?>
            <?php foreach ($rows as $row) { ?>
                lgtMapboxMarkers.append(_templateLink({
                    latitude    : `<?php echo $row['latitude']; ?>`,
                    longitude   : `<?php echo $row['longitude']; ?>`,
                    markerColor : `<?php echo $row['markerColor']; ?>`,
                    sortOrder   : `<?php echo $row['sortOrder']; ?>`,
                }));
            <?php } ?>
        <?php } ?>

        doSortCount();

        $('.ccm-add-lgt-mapbox-marker-<?php echo $bID ?? 0; ?>').click(function() {
            var thisModal = $(this).closest('.ui-dialog-content');
            lgtMapboxMarkers.append(_templateLink({
                latitude    : '',
                longitude   : '',
                markerColor : '',
                sortOrder   : ''
            }));

            $('.ccm-lgt-mapbox-marker-<?php echo $bID ?? 0; ?>').not('.link-closed').each(function() {
                $(this).addClass('link-closed');
                var thisEditButton = $(this).closest('.ccm-lgt-mapbox-marker-<?php echo $bID ?? 0; ?>').find('.btn.ccm-edit-link');
                thisEditButton.text(thisEditButton.data('linkEditText'));
            });
            var newLink = $('.ccm-lgt-mapbox-marker-<?php echo $bID ?? 0; ?>').last();
            var closeText = newLink.find('.btn.ccm-edit-link').data('linkCloseText');
            newLink.removeClass('link-closed').find('.btn.ccm-edit-link').text(closeText);

            thisModal.scrollTop(newLink.offset().top);
            attachDelete(newLink.find('.ccm-delete-lgt-mapbox-marker-<?php echo $bID ?? 0; ?>'));
            attachCopyTitle(newLink.find('.title-field-<?php echo $bID ?? 0; ?>'));

            doSortCount();
        });

        $('.ccm-lgt-mapbox-marker-<?php echo $bID ?? 0; ?>').on('click','.ccm-edit-link', function() {
            $(this).closest('.ccm-lgt-mapbox-marker-<?php echo $bID ?? 0; ?>').toggleClass('link-closed');
            var thisEditButton = $(this);
            if (thisEditButton.data('linkEditText') === thisEditButton.text()) {
                thisEditButton.text(thisEditButton.data('linkCloseText'));
            } else if (thisEditButton.data('linkCloseText') === thisEditButton.text()) {
                thisEditButton.text(thisEditButton.data('linkEditText'));
            }
        });

        $('.ccm-lgt-mapbox-marker-<?php echo $bID ?? 0; ?>').sortable({
            placeholder: 'ui-state-highlight',
            axis: 'y',
            handle: 'i.fa-arrows',
            cursor: 'move',
            update: function() {
                doSortCount();
            }
        });

        attachDelete($('.ccm-delete-lgt-super-nav-entry-<?php echo $bID ?? 0; ?>'));
        attachCopyTitle($('.title-field-<?php echo $bID ?? 0; ?>'));
    });
</script>


<script type="text/template" id="lgt-mapbox-marker-<?php echo $bID ?? 0; ?>">
    <div class="ccm-lgt-mapbox-marker ccm-lgt-mapbox-marker-<?php echo $bID ?? 0; ?> well link-closed">

        <div class="form-group">
            <label class="control-label"><?php echo t('Latitude'); ?></label>
            <input class="form-control ccm-input-text latitude-field-<?php echo $bID ?? 0; ?>" type="text" name="<?php echo $view->field('latitude'); ?>[]" value="<%=latitude%>" />
            <span><%=latitude%>,<%=longitude%></span>
        </div>

        <div class="form-group">
            <label class="control-label"><?php echo t('Longitude'); ?></label>
            <input class="form-control ccm-input-text longitude-field-<?php echo $bID ?? 0; ?>" type="text" name="<?php echo $view->field('longitude'); ?>[]" value="<%=longitude%>" />
        </div>

        <div class="form-group">
            <label class="control-label"><?php echo t('Marker Colour (HEX Value)'); ?></label>
            <input class="form-control ccm-input-text markerColor-field-<?php echo $bID ?? 0; ?>" type="text" name="<?php echo $view->field('markerColor'); ?>[]" value="<%=markerColor%>" />
        </div>

        <button type="button" class="btn btn-sm btn-default ccm-edit-link ccm-edit-link-<?php echo $bID ?? 0; ?>" data-link-close-text="<?php echo t('Collapse'); ?>" data-link-edit-text="<?php echo t('Edit'); ?>"><?php echo t('Edit'); ?></button>
        <button type="button" class="btn btn-sm btn-danger ccm-delete-lgt-mapbox-marker ccm-delete-lgt-mapbox-marker-<?php echo $bID ?? 0; ?>"><?php echo t('Remove'); ?></button>
        <i class="fa fa-arrows"></i>

        <input class="ccm-lgt-mapbox-marker-sort" type="hidden" name="<?php echo $view->field('sortOrder'); ?>[]" value="<%=sortOrder%>"/>
    </div>
</script>