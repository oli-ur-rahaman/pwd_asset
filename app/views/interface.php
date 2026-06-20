<?php
require __DIR__ . '/header.php';
$canManageSuperadmin = can_manage_superadmin_scope();
$info = get_info_row();
?>
<?php if (!$canManageSuperadmin): ?>
    <style>
        .superadmin-readonly-page button[type="submit"] {
            display: none !important;
        }
        .superadmin-readonly-page input:not([type="hidden"]),
        .superadmin-readonly-page textarea {
            pointer-events: none;
            background: #f4f6f8;
            color: #425466;
        }
    </style>
<?php endif; ?>
<div class="<?= !$canManageSuperadmin ? 'superadmin-readonly-page' : ''; ?>">
<?php if (!$canManageSuperadmin): ?>
    <section class="card">
        <p class="hint">View-only superadmin users can review interface settings here but cannot save changes.</p>
    </section>
<?php endif; ?>
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
    <h2>Theme</h2>
    <form method="post" action="index.php" class="grid">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_interface">
        <label>Global Theme
            <select name="ui_theme_key">
                <?php foreach (asset_theme_options() as $themeKey => $themeLabel): ?>
                    <option value="<?= e($themeKey); ?>" <?= asset_normalize_theme_key((string)($info['ui_theme_key'] ?? '')) === $themeKey ? 'selected' : ''; ?>><?= e($themeLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Save</button>
    </form>
</section>

<section class="card">
    <h2>Video Tutorial</h2>
    <form method="post" action="index.php" class="grid" enctype="multipart/form-data">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="save_interface">
        <p class="hint">Use Tutorial URL for external video links. Use Hosted Tutorial Video or Hosted Tutorial Server Filename for private MP4 tutorial playback inside the system. Hosted UI upload supports up to 500 MB. Larger MP4 files upload by FileZilla to <code><?= e(asset_tutorial_video_manual_path_note()); ?></code>.</p>
        <label>Video Tutorial URL
            <input type="url" name="video_tutorial_url" placeholder="https://..." value="<?= e((string)($info['video_tutorial_url'] ?? '')); ?>">
        </label>
        <label>Hosted Tutorial Video (MP4 up to 500 MB)
            <input type="file" name="global_tutorial_video_file" accept="video/mp4">
            <span class="hint">UI upload supports MP4 up to 500 MB. Larger files upload by FileZilla to <code><?= e(asset_tutorial_video_manual_path_note()); ?></code> and register the filename below.</span>
        </label>
        <label>Hosted Tutorial Server Filename
            <input type="text" name="global_hosted_tutorial_video_filename" placeholder="example.mp4">
            <?php if (!empty($info['hosted_tutorial_video_path'])): ?><span class="hint">Current hosted video: <?= e((string)($info['hosted_tutorial_video_original_name'] ?? $info['hosted_tutorial_video_path'] ?? '')); ?></span><?php endif; ?>
        </label>
        <?php if (!empty($info['hosted_tutorial_video_path'])): ?><label class="inline-check"><input type="checkbox" name="remove_global_hosted_tutorial_video" value="1"> Remove hosted video</label><?php endif; ?>
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
 </div>
<?php require __DIR__ . '/footer.php'; ?>
