<?php
use Magmi\Gui\App\Icon;
$title = 'Overview';
$active = 'home';
$profileRows = $profileRows ?? [];
$fileCount = $fileCount ?? 0;
$configured = $configured ?? false;
$readyCount = count(array_filter($profileRows, static fn($row) => $row['available']));
ob_start();
?>
<section class="overview-hero"><div><span class="hero-label"><span class="status-dot"></span> A BETTER IMPORT WORKFLOW</span><h2>Your catalog.<br>Ready for what’s next.</h2><p>Turn product files into repeatable imports.<br>Choose a profile, check your data, and let’s get to work.</p><a class="btn btn-primary" href="/import">Start an import <?= Icon::render('arrow') ?></a><a class="hero-secondary" href="/profile">Manage profiles <?= Icon::render('chevron') ?></a></div><div class="hero-visual" aria-hidden="true"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="visual-file visual-file-one"><?= Icon::render('file') ?><span>CSV</span></div><div class="visual-file visual-file-two"><?= Icon::render('file') ?><span>XLSX</span></div><div class="visual-center"><?= Icon::render('box') ?><span>YOUR CATALOG</span></div><span class="visual-spark spark-one">+</span><span class="visual-spark spark-two">+</span></div></section>
<div class="overview-metrics">
    <div class="metric-card"><div><span>Import profiles</span><strong><?= count($profileRows) ?><small>saved configurations</small></strong></div><span class="metric-icon"><?= Icon::render('layers') ?></span></div>
    <div class="metric-card"><div><span>Source files</span><strong><?= (int) $fileCount ?><small>in your import directory</small></strong></div><span class="metric-icon"><?= Icon::render('file') ?></span></div>
    <div class="metric-card"><div><span>Profiles with a file</span><strong><?= $readyCount ?><small>source file available</small></strong></div><span class="metric-icon"><?= Icon::render('check') ?></span></div>
</div>
<div class="dashboard-columns">
<section class="panel profiles-panel"><div class="panel-header"><div><h2>Your import profiles</h2><p>A saved setup for every workflow.</p></div><a class="text-link" href="/profile">Manage <?= Icon::render('arrow') ?></a></div>
<?php if ($profileRows): ?><div class="table-scroll"><table class="workspace-table"><thead><tr><th>Profile / source</th><th>Import mode</th><th>Source status</th><th><span class="sr-only">Open profile</span></th></tr></thead><tbody><?php foreach ($profileRows as $row): ?><tr><td><div class="profile-cell"><span class="table-file-icon"><?= Icon::render('layers') ?></span><span><strong><?= htmlspecialchars($row['name']) ?></strong><small><?= htmlspecialchars($row['file'] ? basename($row['file']) : 'No source selected') ?></small></span></div></td><td><span class="mode-label"><?= htmlspecialchars(['create' => 'Create only', 'update' => 'Update only', 'xcreate' => 'Create + update'][$row['mode']] ?? $row['mode']) ?></span></td><td><span class="status-pill <?= $row['available'] ? 'status-ready' : 'status-pending' ?>"><span></span><?= $row['available'] ? 'File available' : 'Select a file' ?></span></td><td><form action="/profile/switch" method="POST" class="row-action"><?= \Magmi\Gui\Auth\Csrf::field() ?><input type="hidden" name="profile_name" value="<?= htmlspecialchars($row['name']) ?>" /><button type="submit" class="icon-button" aria-label="Open <?= htmlspecialchars($row['name']) ?> profile"><?= Icon::render('arrow') ?></button></form></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><span class="empty-icon"><?= Icon::render('layers') ?></span><h3>Your first workflow starts here.</h3><p>Create a profile to save your source file and import preferences.</p><a href="/profile" class="btn btn-secondary">Set up a profile <?= Icon::render('arrow') ?></a></div><?php endif; ?>
<div class="panel-footnote"><?= Icon::render('shield') ?>Preview your source data before each import.</div></section>
<section class="panel getting-started"><div class="panel-header"><div><span class="eyebrow">THE ESSENTIALS</span><h2>From file to catalog</h2></div></div><a class="setup-step" href="/config"><span class="step-number">01</span><span><strong>Connect your store</strong><small><?= $configured ? 'Connection settings saved. Test them in Settings.' : 'Add your Magento connection in Settings.' ?></small></span><?= Icon::render('chevron') ?></a><a class="setup-step" href="/profile"><span class="step-number">02</span><span><strong>Make it repeatable</strong><small>Choose your file and configure a profile.</small></span><?= Icon::render('chevron') ?></a><a class="setup-step" href="/import"><span class="step-number">03</span><span><strong>Preview. Then import.</strong><small>Review the first rows before you run.</small></span><?= Icon::render('chevron') ?></a></section>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
