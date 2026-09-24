<?php
use Magmi\Gui\App\Version;
use Magmi\Gui\App\Icon;
use Magmi\Gui\Auth\Csrf;

$theme = $theme ?? 'modern-light';
$assetRoot = dirname(__DIR__, 2) . '/public';
$assetVersion = (string) max(filemtime($assetRoot . '/css/style.css'), filemtime($assetRoot . '/js/app.js'));
$appVersion = Version::webGui();
$isLogin = ($active ?? '') === 'login';
$sections = [
    'home' => ['Overview', 'Your catalog operations, in one place.', '/', 'grid'],
    'profile' => ['Import profiles', 'Configure your source, mapping and processing rules.', '/profile', 'layers'],
    'import' => ['Run an import', 'Preview your data, then bring your catalog up to date.', '/import', 'upload'],
    'config' => ['Settings', 'Manage your Magento connection and workspace preferences.', '/config', 'settings'],
];
$page = $sections[$active ?? 'home'] ?? $sections['home'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme === 'modern-dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title><?= htmlspecialchars($title ?? 'Magmi') ?> · Magmi</title>
    <script>try{const t=localStorage.getItem('magmi-appearance');if(t==='dark'||t==='light')document.documentElement.dataset.theme=t;}catch(e){}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;450;500;550;600;650;700&family=Manrope:wght@400;500;600;650;700;750;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css?v=<?= htmlspecialchars($assetVersion) ?>">
    <script src="/js/app.js?v=<?= htmlspecialchars($assetVersion) ?>" defer></script>
</head>
<body class="<?= $isLogin ? 'auth-page' : 'workspace-page' ?>">
<a class="skip-link" href="#main-content">Skip to content</a>
<?php if (!$isLogin): ?>
    <aside class="sidebar" id="workspace-nav" aria-label="Workspace navigation">
        <a href="/" class="brand">magmi</a>
        <div class="workspace-label"><span class="workspace-avatar">M</span><span>Magento workspace<small>Catalog management</small></span></div>
        <span class="nav-caption">WORKSPACE</span>
        <nav class="main-nav">
            <?php foreach ($sections as $key => [$label, $description, $href, $icon]): ?>
                <a href="<?= $href ?>" class="nav-item <?= ($active ?? '') === $key ? 'active' : '' ?>" <?= ($active ?? '') === $key ? 'aria-current="page"' : '' ?>><?= Icon::render($icon) ?><span><?= $label ?></span><?php if (($active ?? '') === $key): ?><span class="nav-dot"></span><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <div class="sidebar-note"><?= Icon::render('box') ?><strong>Built for your catalog.</strong><p>Reusable profiles.<br>Less repetitive work.</p></div>
            <div class="sidebar-account"><span class="account-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['magmi_username'] ?? 'M', 0, 1))) ?></span><span><?= htmlspecialchars($_SESSION['magmi_username'] ?? 'Workspace user') ?><small>Magento importer</small></span><form method="POST" action="/logout"><?= Csrf::field() ?><button type="submit" class="icon-button" aria-label="Sign out" title="Sign out"><?= Icon::render('logout') ?></button></form></div>
        </div>
    </aside>
    <button class="nav-backdrop" data-nav-close aria-label="Close navigation" tabindex="-1"></button>
    <div class="workspace-main">
        <header class="topbar">
            <div class="breadcrumb"><button type="button" class="icon-button mobile-menu" data-nav-toggle aria-controls="workspace-nav" aria-expanded="false" aria-label="Open navigation"><?= Icon::render('menu') ?></button><span>Workspace</span><?= Icon::render('chevron') ?><strong><?= $page[0] ?></strong></div>
            <div class="topbar-actions"><span class="edition-badge">COMMUNITY EDITION</span><button type="button" class="icon-button" data-theme-toggle aria-label="Switch color theme" title="Switch color theme"><?= Icon::render('moon') ?></button></div>
        </header>
        <main class="container" id="main-content">
            <div class="page-heading"><div><div class="eyebrow">CATALOG WORKSPACE</div><h1><?= $page[0] ?></h1><p><?= $page[1] ?></p></div><?php if (($active ?? '') !== 'import'): ?><a class="btn btn-primary" href="/import"><?= Icon::render('upload') ?> New import</a><?php endif; ?></div>
            <?= $content ?? '' ?>
        </main>
        <footer class="footer"><span>Magmi New Era <span class="footer-dot">·</span> <?= htmlspecialchars($appVersion) ?></span><span>Made for Magento <span class="footer-dot">·</span> OSL 3.0</span></footer>
    </div>
<?php else: ?>
    <main id="main-content"><?= $content ?? '' ?></main>
<?php endif; ?>
</body>
</html>
