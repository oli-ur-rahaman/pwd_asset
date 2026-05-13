<?php
require __DIR__ . '/header.php';
$info = get_info_row();
?>
<section class="card">
    <h2>Site Name</h2>
    <form method="post" action="index.php" class="grid">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_interface">
        <label>Site Name
            <input type="text" name="site_name" value="<?= e((string)($info['site_name'] ?? '')); ?>" placeholder="PWD Asset Management System">
        </label>
        <button type="submit">Save</button>
    </form>
</section>

<section class="card">
    <h2>Video Tutorial URL</h2>
    <form method="post" action="index.php" class="grid">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_interface">
        <label>Video Tutorial URL
            <input type="url" name="video_tutorial_url" placeholder="https://..." value="<?= e((string)($info['video_tutorial_url'] ?? '')); ?>">
        </label>
        <button type="submit">Save</button>
    </form>
</section>

<section class="card">
    <h2>Login Message (HTML)</h2>
    <form method="post" action="index.php" class="split">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_interface">
        <label>HTML Input
            <textarea name="login_message" id="login-message" rows="10" placeholder="<b>Welcome</b>"><?= e((string)($info['login_message'] ?? '')); ?></textarea>
        </label>
        <div>
            <div class="preview-title">Preview</div>
            <div id="login-preview" class="preview-panel"></div>
        </div>
        <button type="submit">Save</button>
    </form>
</section>

<section class="card">
    <h2>Welcome Message</h2>
    <form method="post" action="index.php" class="split">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_interface">
        <label>HTML Input
            <textarea name="welcome_message" id="welcome-message" rows="10" placeholder="<h3>Welcome</h3><p>Notice...</p>"><?= e((string)($info['welcome_message'] ?? '')); ?></textarea>
        </label>
        <div>
            <div class="preview-title">Preview</div>
            <div id="welcome-preview" class="preview-panel"></div>
        </div>
        <button type="submit">Save</button>
    </form>
</section>
<?php require __DIR__ . '/footer.php'; ?>
