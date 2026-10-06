<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<p class="text-muted">
    <?php echo t('These settings are read from the site\'s %s file and cannot be changed here.', '<code>.env</code>'); ?>
</p>

<?php foreach ($settings as $setting) { ?>
    <?php if ($setting['populated']) { ?>
        <div class="alert alert-success">
            <strong><?php echo h($setting['label']); ?></strong>
            <code><?php echo h($setting['env']); ?></code>:
            <?php echo h($setting['display']); ?>
        </div>
    <?php } else { ?>
        <div class="alert alert-warning">
            <strong><?php echo h($setting['label']); ?></strong>
            <?php echo t('has not been populated. Add %s to your .env file.', '<code>' . h($setting['env']) . '</code>'); ?>
            <?php if ($setting['default'] !== null) { ?>
                <?php echo t('Using the default: %s.', '<code>' . h($setting['default']) . '</code>'); ?>
            <?php } ?>
        </div>
    <?php } ?>
<?php } ?>

<p class="help-block">
    <?php echo t('Api Keys are available at: <a href="https://account.mapbox.com/" target="_blank"><code>https://account.mapbox.com/</code></a>. Please create a new key for each website to monitor usage and allow custom payments.'); ?>
</p>
