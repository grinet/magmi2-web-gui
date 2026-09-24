<?php
$title = 'Run Import';
$active = 'import';

$profiles = $profiles ?? [];
$preview = $preview ?? null;
$selectedImportMode = $selectedImportMode ?? 'xcreate';
$selectedImportFile = $selectedImportFile ?? null;
$currentProfileName = $currentProfile?->getName() ?? 'Default';
$importModeOptions = [
    'xcreate' => 'Create + Update (xcreate)',
    'create' => 'Create only',
    'update' => 'Update only',
];

ob_start();
?>

<div class="panel">
    <div class="panel-header">
        <h2>Import setup</h2>
    </div>

    <?php if (isset($report)): ?>
        <?php
        $hasErrors = !empty($report['errors']);
        $succeeded = in_array($report['status'] ?? false, [true, 'ok', 'success'], true) && !$hasErrors;
        $statusClass = $succeeded ? 'report-success' : 'report-error';
        ?>
        <div class="report-box <?= $statusClass ?>">
            <h3>Import Report</h3>
            <?php if (isset($report['message'])): ?>
                <p><?= htmlspecialchars($report['message']) ?></p>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Total Rows</span>
                    <span class="stat-value"><?= $report['total'] ?? 0 ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Processed</span>
                    <span class="stat-value"><?= $report['processed'] ?? 0 ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Skipped</span>
                    <span class="stat-value"><?= $report['skipped'] ?? 0 ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Errors</span>
                    <span class="stat-value"><?= count($report['errors'] ?? []) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Status</span>
                    <span class="stat-value <?= $succeeded ? 'text-success' : 'text-danger' ?>"><?= $succeeded ? 'OK' : 'Error' ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Last product ID</span>
                    <span class="stat-value"><?= htmlspecialchars((string)($report['last_processed_id'] ?? '-')) ?></span>
                </div>
            </div>

            <?php if (!empty($report['processed_ids'])): ?>
                <p><strong>Processed product IDs:</strong> <?= htmlspecialchars(implode(', ', $report['processed_ids'])) ?></p>
            <?php endif; ?>

            <?php if (!empty($report['debug_log_file'])): ?>
                <p><strong>Debug log file:</strong> <?= htmlspecialchars($report['debug_log_file']) ?></p>
            <?php endif; ?>

            <?php if (!empty($report['debug'])): ?>
                <details>
                    <summary>SQL Debug Log (<?= count($report['debug']) ?>)</summary>
                    <pre class="debug-log"><?php foreach ($report['debug'] as $entry) { echo htmlspecialchars(json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "\n"; } ?></pre>
                </details>
            <?php endif; ?>

            <?php if ($hasErrors): ?>
                <div class="error-list">
                    <h4>Errors</h4>
                    <ul>
                        <?php foreach ($report['errors'] as $error): ?>
                            <li>Row <?= $error['row'] ?? '?' ?>: <?= htmlspecialchars($error['message'] ?? '') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($preview): ?>
        <div class="report-box <?= $preview['status'] === 'success' ? 'report-success' : 'report-error' ?>">
            <h3>Preview: <?= htmlspecialchars($preview['profile']) ?></h3>
            <p><?= htmlspecialchars($preview['message']) ?></p>
            <?php if (!empty($preview['headers']) || !empty($preview['rows'])): ?>
                <table class="preview-table">
                    <thead>
                        <tr>
                            <?php foreach ($preview['headers'] as $header): ?>
                                <th><?= htmlspecialchars((string) $header) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview['rows'] as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                    <td><?= htmlspecialchars((string) $cell) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/import/run">
        <?= \Magmi\Gui\Auth\Csrf::field() ?>
        <div class="form-row">
            <label>Import profile</label>
            <select name="profile">
                <?php foreach ($profiles as $p): ?>
                    <option value="<?= htmlspecialchars($p->getName()) ?>" <?= $currentProfileName === $p->getName() ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p->getName()) ?>
                    </option>
                <?php endforeach; ?>
                <?php if (empty($profiles)): ?>
                    <option value="Default" selected>Default</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-row">
            <label>Import mode</label>
            <div class="input-column">
                <select name="import_mode">
                    <?php foreach ($importModeOptions as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $selectedImportMode === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="form-help below">Defaults to the profile setting. Override here for a single run.</span>
            </div>
        </div>

        <div class="form-row helper-row">
            <label></label>
            <div class="input-column">
                <?php if ($selectedImportFile): ?>
                    <span class="field-hint">Selected source: <strong><?= htmlspecialchars(basename($selectedImportFile)) ?></strong></span>
                <?php else: ?>
                    <span class="field-hint text-danger">No source file is selected for this profile. Choose and save a file in Import profiles.</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <label>Row limit</label>
            <div class="input-column">
                <input type="number" name="import_limit" value="<?= htmlspecialchars((string)($importLimit ?? '')) ?>" min="0" step="1" placeholder="All rows" style="max-width:120px;" />
                <span class="form-help below">Leave blank or 0 to import all rows. Use this to test imports on a small number of items.</span>
            </div>
        </div>

        <div class="form-actions">
            <label class="checkbox-inline">
                <input type="checkbox" name="debug" value="1" <?= !empty($debugChecked) ? 'checked' : '' ?> /> Include SQL details
            </label>
            <label class="checkbox-inline">
                <input type="checkbox" name="debug_log" value="1" <?= !empty($debugLogChecked) ? 'checked' : '' ?> /> Save a debug log
            </label>
            <button type="submit" formaction="/import/preview" class="btn btn-secondary">Preview data</button>
            <button type="submit" class="btn btn-primary btn-run">Run import</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
