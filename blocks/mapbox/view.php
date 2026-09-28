<?php defined('C5_EXECUTE') or die('Access Denied.');
$c = Page::getCurrentPage();
?>

<?php if (is_object($c) && $c->isEditMode()) { ?>
    <div class="ccm-edit-mode-disabled-item"><?php echo t('Mapbox Map.'); ?></div>
<?php } else { ?>
    <mapbox-component id="mapbox_<?php echo $bID; ?>" class="block__mapbox" data-config='<?php echo htmlspecialchars($config, ENT_QUOTES, 'UTF-8'); ?>'></mapbox-component>
<?php } ?>