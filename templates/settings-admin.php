<?php
script('pto', 'pto-admin-settings');
style('pto', 'admin-settings');
?>

<div id="pto-admin-settings" class="section">
    <h2><?php p($l->t('PTO Management')); ?></h2>
    
    <div class="pto-admin-panel">
        <h3><?php p($l->t('Policy Management')); ?></h3>
        <p class="settings-hint"><?php p($l->t('Create and manage PTO policies for your organization')); ?></p>
        <div id="pto-policy-management"></div>
    </div>

    <div class="pto-admin-panel">
        <h3><?php p($l->t('Manager Assignment')); ?></h3>
        <p class="settings-hint"><?php p($l->t('Assign managers who can approve PTO requests')); ?></p>
        <div id="pto-manager-assignment"></div>
    </div>
</div>
