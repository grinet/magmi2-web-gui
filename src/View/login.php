<?php
use Magmi\Gui\App\Icon;
$title = 'Sign in';
$active = 'login';
$mode = $mode ?? 'emergency';
$error = $error ?? null;
ob_start();
?>
<div class="auth-layout">
    <section class="auth-story" aria-label="About Magmi">
        <a href="/login" class="brand">magmi</a>
        <div class="auth-story-content"><span class="auth-kicker"><span class="status-dot"></span> THE MAGENTO IMPORT WORKSPACE</span><h1>Big catalogs.<br><span>Simple imports.</span></h1><p>Bring your product data together.<br>Build a repeatable workflow.<br>Get back to growing your store.</p>
            <div class="import-illustration" aria-hidden="true"><div class="illustration-source"><?= Icon::render('file') ?><span>YOUR PRODUCT DATA<small>CSV · TSV · Excel</small></span><span class="file-pill">.csv</span></div><div class="illustration-connector"><span></span><span>MAP & PROCESS</span><?= Icon::render('arrow') ?></div><div class="illustration-destination"><span class="illustration-cube"><?= Icon::render('box') ?></span><span>Your Magento catalog<small>A workflow that works for you.</small></span><span class="illustration-check"><?= Icon::render('check') ?></span></div></div>
        </div>
        <div class="auth-story-footer"><span>Purpose-built for Magento.</span><span>01 — NEW ERA</span></div>
    </section>
    <section class="auth-form-side">
        <div class="auth-topline"><span>YOUR CATALOG STARTS HERE</span><button type="button" class="icon-button" data-theme-toggle aria-label="Switch color theme"><?= Icon::render('moon') ?></button></div>
        <div class="login-container"><div class="login-heading"><span class="login-symbol"><?= Icon::render('layers') ?></span><h2>Welcome back.</h2><p>Sign in to your import workspace.</p></div>
        <?php if ($error): ?><div class="report-box report-error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" action="/login" class="login-form">
            <?= \Magmi\Gui\Auth\Csrf::field() ?>
            <div class="form-row"><label for="username">Username</label><input id="username" type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Your username" autocomplete="username" required autofocus /></div>
            <div class="form-row"><label for="password">Password</label><div class="password-field"><input id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required /><button type="button" data-password-toggle aria-controls="password" aria-label="Show password">Show</button></div></div>
            <button type="submit" class="btn btn-primary login-submit">Sign in to workspace <?= Icon::render('arrow') ?></button>
        </form>
        <div class="auth-access-note"><?= Icon::render('shield') ?><div><?php if ($mode === 'emergency'): ?><strong>Local access</strong>Use the recovery credentials provided by your administrator.<details><summary>Where to find your credentials</summary><p>Authorized server administrators can read <code>data/emergency_password.txt</code> from the application directory.</p></details><?php else: ?><strong>Your Magento account</strong>Use your Magento administrator username and password.<?php endif; ?></div></div>
        </div>
        <footer class="auth-footer"><span>Magmi New Era</span><span>Community edition · <?= date('Y') ?></span></footer>
    </section>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
