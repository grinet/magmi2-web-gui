<?php
$title = 'Profile Configuration';
$active = 'profile';

$profiles = $profiles ?? [];
$currentProfile = $currentProfile ?? null;
$plugins = $plugins ?? [];
$savedAt = $savedAt ?? 'Not saved yet';

$dsConfig = $currentProfile?->getDatasourceConfig() ?? [];
$pluginConfig = $currentProfile?->getPluginConfig() ?? [];

$catNames = [
    'itemprocessors' => 'Itemprocessors',
    'preprocessors' => 'Input Data Preprocessing',
    'producttypes' => 'Product Type Import',
    'relatedproducts' => 'Related Products',
];

$docUrls = [
    'categorycreator' => 'https://github.com/dweeves/magmi/wiki/On_the_fly_category_creator',
    'attributedelete' => 'https://github.com/dweeves/magmi/wiki/Attribute_deleter',
    'downloadable' => 'https://github.com/dweeves/magmi/wiki/Downloadable_products_importer',
    'groupprice' => 'https://github.com/dweeves/magmi/wiki/Group_Price_Importer',
    'imageattr' => 'https://github.com/dweeves/magmi/wiki/Image_attributes_processor',
    'indexer' => 'https://github.com/dweeves/magmi/wiki/On_the_fly_indexer',
    'customoptions' => 'https://github.com/dweeves/magmi/wiki/Custom_Options',
    'productdelete' => 'https://github.com/dweeves/magmi/wiki/Product_deleter',
    'producttags' => 'https://github.com/dweeves/magmi/wiki/Product_Tags_Importer',
    'tierprice' => 'https://github.com/dweeves/magmi/wiki/Tier_price_importer',
    'weeetax' => 'https://github.com/dweeves/magmi/wiki/Weee_Tax_importer',
    'columnmapper' => 'https://github.com/dweeves/magmi/wiki/Column_mapper',
    'skufinder' => 'https://github.com/dweeves/magmi/wiki/SKU_Finder',
    'defaultvalues' => 'https://github.com/dweeves/magmi/wiki/Default_Values_setter',
    'limiter' => 'https://github.com/dweeves/magmi/wiki/Magmi_Import_Limiter',
    'genericmapper' => 'https://github.com/dweeves/magmi/wiki/Generic_mapper',
    'valuereplacer' => 'https://github.com/dweeves/magmi/wiki/Value_Replacer',
    'valuetrimmer' => 'https://github.com/dweeves/magmi/wiki/Value_Trimmer_for_select_multiselect',
    'groupeditem' => 'https://github.com/dweeves/magmi/wiki/Grouped_Item_processor',
    'bundleitem' => 'https://github.com/dweeves/magmi/wiki/Bundle_Item_processor',
    'configurableitem' => 'https://github.com/dweeves/magmi/wiki/Configurable_Item_processor',
    'crossupsell' => 'https://github.com/dweeves/magmi/wiki/Cross_Upsell_Importer',
    'productrelater' => 'https://github.com/dweeves/magmi/wiki/Product_relater',
];

$importModeOptions = [
    'xcreate' => 'Create + Update (xcreate)',
    'create' => 'Create only',
    'update' => 'Update only',
];

ob_start();
?>

<div class="panel">
    <div class="panel-header">
        <h2>Configure Current Profile (<?= htmlspecialchars($currentProfile?->getName() ?? 'Default') ?>)</h2>
        <span class="panel-status">Saved: <?= htmlspecialchars($savedAt) ?></span>
    </div>

    <form method="POST" action="/profile/save">
        <?= \Magmi\Gui\Auth\Csrf::field() ?>
        <div class="profile-select-row">
            <label>Profile to configure:</label>
            <select name="profile_name">
                <?php $hasDefaultProfile = false; ?>
                <?php foreach ($profiles as $p): ?>
                    <?php $hasDefaultProfile = $hasDefaultProfile || ($p->getName() === 'Default'); ?>
                    <option value="<?= htmlspecialchars($p->getName()) ?>" <?= ($currentProfile?->getName() === $p->getName()) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p->getName()) ?>
                    </option>
                <?php endforeach; ?>
                <?php if (!$hasDefaultProfile): ?>
                    <option value="Default" <?= ($currentProfile?->getName() ?? 'Default') === 'Default' ? 'selected' : '' ?>>Default</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="copy-profile-row">
            <label>Copy Selected Profile to:</label>
            <input type="text" name="copy_to" />
            <button type="submit" formaction="/profile/copy" class="btn btn-secondary">Copy Profile &amp; switch</button>
            <input type="hidden" name="copy_from" value="<?= htmlspecialchars($currentProfile?->getName() ?? 'Default') ?>" />
        </div>

        <div class="form-actions top-actions">
            <button type="submit" class="btn btn-primary">Save Profile (<?= htmlspecialchars($currentProfile?->getName() ?? 'Default') ?>)</button>
        </div>

        <div class="datasource-section">
            <h3>Datasources</h3>
            <div class="form-row">
                <label>CSV Datasource v1.3.1</label>
                <span class="plugin-desc">This plugin enables magmi import from csv files (using Dataflow format + magmi extended columns)</span>
            </div>
            <div class="form-row">
                <label>Import type:</label>
                <div class="input-column">
                    <select name="import_mode">
                        <?php foreach ($importModeOptions as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>" <?= (($dsConfig['mode'] ?? 'xcreate') === $value) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-help below">Determines whether the import creates new products, updates existing ones, or performs both (xcreate).</span>
                </div>
            </div>
            <div class="form-row">
                <label>CSV base directory:</label>
                <div class="input-column">
                    <input type="text" name="csv_basedir" value="<?= htmlspecialchars($dsConfig['basedir'] ?? 'var/import') ?>" />
                    <span class="field-hint">Relative paths are relative to magento base directory, absolute paths will be used as is</span>
                </div>
            </div>
            <?php
            $importFileOptions = $importFileOptions ?? [];
            $selectedImportFile = $selectedImportFile ?? null;
            $optionValues = array_column($importFileOptions, 'value');
            ?>
            <div class="form-row">
                <label>File to import:</label>
                <div class="input-column">
                    <select name="datasource_file">
                        <option value="">-- Select file --</option>
                        <?php if ($selectedImportFile && !in_array($selectedImportFile, $optionValues, true)): ?>
                            <option value="<?= htmlspecialchars($selectedImportFile) ?>" selected>
                                Current selection — <?= htmlspecialchars(basename($selectedImportFile)) ?>
                            </option>
                        <?php endif; ?>
                        <?php foreach ($importFileOptions as $option): ?>
                            <option value="<?= htmlspecialchars($option['value']) ?>" <?= ($selectedImportFile === $option['value']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($option['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-help below">Files are pulled from the allowed import directories (Magento <code>var/import</code> when configured, otherwise Magmi <code>data</code>).</span>
                </div>
            </div>
            <div class="form-row">
                <label>Custom path (optional):</label>
                <div class="input-column">
                    <input type="text" name="datasource_file_custom" value="<?= htmlspecialchars($dsConfig['file'] ?? '') ?>" placeholder="Enter relative or absolute path" />
                    <span class="form-help below">Leave blank to use the dropdown selection. Relative paths resolve from the CSV base directory.</span>
                </div>
            </div>

            <h4>CSV options</h4>
            <div class="form-row">
                <label>CSV separator:</label>
                <div class="input-column">
                    <input type="text" name="csv_separator" value="<?= htmlspecialchars($dsConfig['separator'] ?? ',') ?>" size="3" style="max-width:100px;width:100%;" />
                </div>
            </div>
            <div class="form-row">
                <label>CSV enclosure:</label>
                <div class="input-column">
                    <input type="text" name="csv_enclosure" value="<?= htmlspecialchars($dsConfig['enclosure'] ?? '"') ?>" size="3" style="max-width:100px;width:100%;" />
                </div>
            </div>
            <div class="form-row">
                <label>CSV value separator:</label>
                <div class="input-column">
                    <input type="text" name="csv_value_separator" value="<?= htmlspecialchars($dsConfig['value_separator'] ?? ',') ?>" size="3" style="max-width:100px;width:100%;" />
                    <span class="field-hint">For multi select fields and <code>media_gallery</code> column.</span>
                </div>
            </div>
            <div class="form-row checkbox-row">
                <label><input type="checkbox" name="csv_headless" <?= ($dsConfig['headless'] ?? false) ? 'checked' : '' ?> /> Headless CSV (Use Column Mapper Plugin to set processable column names)</label>
            </div>
            <div class="form-row checkbox-row">
                <label><input type="checkbox" name="csv_truncated" <?= ($dsConfig['allow_truncated_lines'] ?? false) ? 'checked' : '' ?> /> Allow truncated lines (bypasses data line structure correlation with headers)</label>
            </div>
            <div class="form-row checkbox-row">
                <label><input type="checkbox" name="csv_malformed" <?= ($dsConfig['malformed'] ?? false) ? 'checked' : '' ?> /> Malformed CSV (column list line not at top of file)</label>
            </div>
        </div>

        <div class="plugins-section">
            <h3>Plugins</h3>
            <?php foreach ($plugins as $category => $catPlugins): ?>
                <div class="plugin-category">
                    <h4><?= htmlspecialchars($catNames[$category] ?? $category) ?></h4>
                    <div class="plugin-list">
                        <?php foreach ($catPlugins as $plugin):
                            $pid = $plugin->getIdentifier();
                            $implemented = \Magmi\Core\Plugin\PluginDiscovery::hasImportHooks($plugin);
                            $isEnabled = $pluginConfig[$pid]['enabled'] ?? false;
                            $pconf = $pluginConfig[$pid]['config'] ?? [];
                            $fields = ($plugin instanceof \Magmi\Core\Plugin\ConfigurablePluginInterface) ? $plugin->getConfigFields() : [];
                            $hasConfig = !empty($fields);
                            $docUrl = $docUrls[$pid] ?? '#';
                        ?>
                            <div class="plugin-item" data-pid="<?= htmlspecialchars($pid) ?>">
                                <div class="plugin-header <?= $isEnabled ? '' : 'disabled' ?>" onclick="toggleAccordion('<?= htmlspecialchars($pid) ?>')">
                                    <div class="plugin-header-left">
                                        <input type="checkbox" name="plugins[<?= htmlspecialchars($pid) ?>]" value="1" <?= $isEnabled ? 'checked' : '' ?> <?= $implemented ? '' : 'disabled' ?> onclick="event.stopPropagation(); togglePlugin('<?= htmlspecialchars($pid) ?>', this.checked);" />
                                        <span class="plugin-title"><?= htmlspecialchars($plugin->getName()) ?> <small><?= htmlspecialchars($plugin->getVersion()) ?></small><?php if (!$implemented): ?> <small>Not implemented</small><?php endif; ?></span>
                                    </div>
                                    <div class="plugin-header-right">
                                        <a href="<?= htmlspecialchars($docUrl) ?>" target="_blank" class="doc-link" onclick="event.stopPropagation();">documentation</a>
                                        <?php if ($isEnabled): ?>
                                            <span class="accordion-icon" id="icon-<?= htmlspecialchars($pid) ?>">&#9654;</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="plugin-body" id="body-<?= htmlspecialchars($pid) ?>">
                                    <?php if ($hasConfig): ?>
                                        <div class="plugin-config" id="cfg-<?= htmlspecialchars($pid) ?>" style="display: <?= $isEnabled ? 'block' : 'none' ?>;">
                                            <?php foreach ($fields as $field): ?>
                                                <div class="form-row">
                                                    <label><?= htmlspecialchars($field['label'] ?? $field['name']) ?>:</label>
                                                    <?php if (($field['type'] ?? 'text') === 'checkbox'): ?>
                                                        <input type="checkbox" name="plugin_config[<?= htmlspecialchars($pid) ?>][<?= htmlspecialchars($field['name']) ?>]" value="1" <?= ($pconf[$field['name']] ?? $field['default'] ?? false) ? 'checked' : '' ?> />
                                                    <?php elseif (($field['type'] ?? 'text') === 'select'): ?>
                                                        <select name="plugin_config[<?= htmlspecialchars($pid) ?>][<?= htmlspecialchars($field['name']) ?>]">
                                                            <?php foreach ($field['options'] ?? [] as $optVal => $optLabel): ?>
                                                                <option value="<?= htmlspecialchars($optVal) ?>" <?= ($pconf[$field['name']] ?? $field['default'] ?? '') === $optVal ? 'selected' : '' ?>><?= htmlspecialchars($optLabel) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    <?php elseif (($field['type'] ?? 'text') === 'textarea'): ?>
                                                        <textarea name="plugin_config[<?= htmlspecialchars($pid) ?>][<?= htmlspecialchars($field['name']) ?>]" rows="3" cols="50"><?= htmlspecialchars($pconf[$field['name']] ?? $field['default'] ?? '') ?></textarea>
                                                    <?php else: ?>
                                                        <input type="<?= htmlspecialchars($field['type'] ?? 'text') ?>" name="plugin_config[<?= htmlspecialchars($pid) ?>][<?= htmlspecialchars($field['name']) ?>]" value="<?= htmlspecialchars($pconf[$field['name']] ?? $field['default'] ?? '') ?>" size="60" />
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="no-config">No configuration options for this plugin.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
        function toggleAccordion(pid) {
            var header = document.querySelector("div.plugin-item[data-pid='" + pid + "'] .plugin-header");
            if (header.classList.contains('disabled')) {
                return;
            }
            var body = document.getElementById('body-' + pid);
            var icon = document.getElementById('icon-' + pid);
            if (body.style.display === 'block') {
                body.style.display = 'none';
                if (icon) { icon.innerHTML = '&#9654;'; }
            } else {
                body.style.display = 'block';
                if (icon) { icon.innerHTML = '&#9660;'; }
            }
        }
        function togglePlugin(pid, checked) {
            var header = document.querySelector("div.plugin-item[data-pid='" + pid + "'] .plugin-header");
            var body = document.getElementById('body-' + pid);
            var cfg = document.getElementById('cfg-' + pid);
            var right = header.querySelector('.plugin-header-right');
            var icon = document.getElementById('icon-' + pid);

            if (checked) {
                header.classList.remove('disabled');
                if (!icon) {
                    var span = document.createElement('span');
                    span.className = 'accordion-icon';
                    span.id = 'icon-' + pid;
                    span.innerHTML = '&#9654;';
                    right.appendChild(span);
                }
                body.style.display = 'block';
                if (icon) { icon.innerHTML = '&#9660;'; }
                if (cfg) { cfg.style.display = 'block'; }
            } else {
                header.classList.add('disabled');
                body.style.display = 'none';
                if (cfg) { cfg.style.display = 'none'; }
                if (icon) { icon.remove(); }
            }
        }
        </script>

        <script>
        (function () {
            const select = document.querySelector('select[name="profile_name"]');
            const form = document.querySelector('form[action="/profile/save"]');
            if (!select || !form) return;

            let isDirty = false;
            const originalValue = select.value;

            form.addEventListener('input', function (e) {
                if (e.target !== select) {
                    isDirty = true;
                }
            });

            select.addEventListener('change', function () {
                if (isDirty && !confirm('You have unsaved changes. Switch without saving?')) {
                    select.value = originalValue;
                    return;
                }

                const switchForm = document.createElement('form');
                switchForm.method = 'POST';
                switchForm.action = '/profile/switch';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'profile_name';
                input.value = select.value;
                switchForm.appendChild(input);
                switchForm.appendChild(form.querySelector('input[name="csrf_token"]').cloneNode(true));
                document.body.appendChild(switchForm);
                switchForm.submit();
            });
        })();
        </script>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Profile (<?= htmlspecialchars($currentProfile?->getName() ?? 'Default') ?>)</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
