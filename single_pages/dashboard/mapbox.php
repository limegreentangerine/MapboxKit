<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<form method="post" action="<?php echo $view->action('save'); ?>">
    <?php echo $token->output('submit') ?>

    <fieldset>
        <legend><?php echo t('API Settings'); ?></legend>

        <div class="form-group">
            <?php echo $form->label('apiKey', t('API Key')); ?>
            <div class="float-end">
                <span class="text-muted small"><?php echo t('Required'); ?></span>
            </div>
            <?php echo $form->text('apiKey', (isset($formContent)) ? $formContent['apiKey'] : (($pkg) ? $pkg->getFileConfig()->get('mapbox.apiKey') : false)); ?>

            <div class="help-block">
                <?php echo t('Api Keys are available at: <a href="https://account.mapbox.com/" target="_blank"><code>https://account.mapbox.com/</code></a>. Please create a new key for each website to monitor usage and allow custom payments.'); ?>
            </div>
        </div>

    </fieldset>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <?php echo $form->submit('save', t('Save Settings'), array('class' => 'btn btn-primary float-end')); ?>
        </div>
    </div>
</form>