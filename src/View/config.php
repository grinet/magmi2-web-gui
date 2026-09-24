<?php
$title = 'Global Configuration';
$active = 'config';
$theme = $theme ?? 'classic';
$themeOptions = $themeOptions ?? \Magmi\Gui\App\Theme::options();

$global = $global ?? [];
$profiles = $profiles ?? [];
$savedAt = $savedAt ?? 'Not saved yet';
$dbTest = $dbTest ?? null;
$pathTest = $pathTest ?? null;

ob_start();
?>

<div class="panel">
    <div class="panel-header">
        <h2>Configure Global Parameters</h2>
        <span class="panel-status">Saved: <?= htmlspecialchars($savedAt) ?></span>
    </div>

    <?php if ($dbTest): ?>
        <div class="report-box <?= $dbTest['status'] === 'success' ? 'report-success' : 'report-error' ?>">
            <?= htmlspecialchars($dbTest['message']) ?>
        </div>
    <?php endif; ?>

    <?php if ($pathTest): ?>
        <div class="report-box <?= $pathTest['status'] === 'success' ? 'report-success' : 'report-error' ?>">
            <?= htmlspecialchars($pathTest['message']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/config/save">
        <?= \Magmi\Gui\Auth\Csrf::field() ?>
        <div class="config-grid">
            <div class="config-section">
                <h3>Database</h3>
                <div class="form-row checkbox-row">
                    <label>
                        <input type="radio" name="db_connection_mode" value="env_php" <?= ($global['db_connection_mode'] ?? 'env_php') === 'env_php' ? 'checked' : '' ?> />
                        Read from Magento env.php
                    </label>
                </div>
                <div class="form-row helper-row">
                    <label></label>
                    <div class="input-column">
                        <span class="field-hint">Requires Magento filesystem path in the Magento section below.</span>
                    </div>
                </div>
                <div class="form-row checkbox-row">
                    <label>
                        <input type="radio" name="db_connection_mode" value="manual" <?= ($global['db_connection_mode'] ?? 'env_php') === 'manual' ? 'checked' : '' ?> />
                        Enter DB credentials manually
                    </label>
                </div>
                <div id="manual-db-fields" class="manual-db-fields">
                    <div class="form-row">
                        <label>Host:</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars($global['db_host'] ?? 'localhost') ?>" />
                    </div>
                    <div class="form-row">
                        <label>Port:</label>
                        <input type="text" name="db_port" value="<?= htmlspecialchars($global['db_port'] ?? '3306') ?>" />
                    </div>
                    <div class="form-row">
                        <label>DB Name:</label>
                        <input type="text" name="db_name" value="<?= htmlspecialchars($global['db_name'] ?? '') ?>" />
                    </div>
                    <div class="form-row">
                        <label>Username:</label>
                        <input type="text" name="db_user" value="<?= htmlspecialchars($global['db_user'] ?? '') ?>" />
                    </div>
                    <div class="form-row">
                        <label>Password:</label>
                        <input type="password" name="db_pass" value="<?= htmlspecialchars($global['db_pass'] ?? '') ?>" />
                    </div>
                    <div class="form-row">
                        <label>Table prefix:</label>
                        <input type="text" name="db_prefix" value="<?= htmlspecialchars($global['db_prefix'] ?? '') ?>" />
                    </div>
                </div>
            </div>

            <div class="config-section">
                <h3>Magento</h3>
                <div class="form-row">
                    <label>Version:</label>
                    <select name="magento_version">
                        <option value="2.4.x" <?= ($global['magento_version'] ?? '') === '2.4.x' ? 'selected' : '' ?>>2.4.x</option>
                        <option value="2.3.x" <?= ($global['magento_version'] ?? '') === '2.3.x' ? 'selected' : '' ?>>2.3.x</option>
                        <option value="2.2.x" <?= ($global['magento_version'] ?? '') === '2.2.x' ? 'selected' : '' ?>>2.2.x</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>Filesystem Path:</label>
                    <input type="text" name="magento_path" value="<?= htmlspecialchars($global['magento_path'] ?? '') ?>" />
                </div>
                <div class="form-row">
                    <label>Debug log path (fallback):</label>
                    <div class="input-column">
                        <input type="text" name="debug_log_path" value="<?= htmlspecialchars($global['debug_log_path'] ?? '') ?>" />
                        <span class="form-help below">By default the Magento <code>var/log</code> directory is used. If that path is not reachable, this fallback directory will store the debug logs.</span>
                    </div>
                </div>
                <div class="form-row checkbox-row">
                    <label>
                        <input type="checkbox" name="enable_msi" <?= !empty($global['enable_msi']) ? 'checked' : '' ?> />
                        Enable Magento MSI (Multi-Source Inventory)
                    </label>
                </div>
            </div>

            <div class="config-section">
                <h3>Global</h3>
                <div class="form-row">
                    <label>Reporting step in %:</label>
                    <input type="text" name="report_step" value="<?= htmlspecialchars($global['report_step'] ?? '0.5') ?>" />
                </div>
                <div class="form-row">
                    <label>Multiselect separator:</label>
                    <input type="text" name="multiselect_separator" value="<?= htmlspecialchars($global['multiselect_separator'] ?? ',') ?>" />
                </div>
                <div class="form-row">
                    <label>Dir permissions:</label>
                    <input type="text" name="dir_permissions" value="<?= htmlspecialchars($global['dir_permissions'] ?? '755') ?>" />
                </div>
                <div class="form-row">
                    <label>File permissions:</label>
                    <input type="text" name="file_permissions" value="<?= htmlspecialchars($global['file_permissions'] ?? '644') ?>" />
                </div>
                <div class="form-row">
                    <label>UI Theme:</label>
                    <select name="ui_theme">
                        <?php foreach ($themeOptions as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>" <?= (($global['ui_theme'] ?? 'classic') === $value) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row checkbox-row">
                    <label>
                        <input type="checkbox" name="disable_attribute_set_update" <?= ($global['disable_attribute_set_update'] ?? false) ? 'checked' : '' ?> />
                        Disable attribute set update
                    </label>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save global parameters</button>
            <button type="submit" formaction="/config/test-path" class="btn btn-secondary">Test Magento path access</button>
            <button type="submit" formaction="/config/test-db" class="btn btn-secondary">Test DB connection</button>
        </div>
    </form>
</div>

<script>
(function () {
    const radios = document.querySelectorAll('input[name="db_connection_mode"]');
    const fields = document.getElementById('manual-db-fields');
    function toggle() {
        const selected = document.querySelector('input[name="db_connection_mode"]:checked');
        if (fields && selected) {
            fields.style.display = selected.value === 'manual' ? 'block' : 'none';
        }
    }
    radios.forEach(r => r.addEventListener('change', toggle));
    toggle();
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
