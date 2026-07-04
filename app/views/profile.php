<?php
require __DIR__ . '/header.php';
$user = current_user();
$accessOptions = office_user_access_options();
$officeContext = current_office_context($user);
$officeUsers = $officeContext ? array_values(array_filter(get_office_users((int)$officeContext['office_type'], (int)$officeContext['office_id']), static fn(array $officeUser): bool => (int)($officeUser['is_primary_office_user'] ?? 0) !== 1)) : [];
$superadminUsers = is_superadmin() ? get_superadmin_additional_users() : [];
$projectBackups = can_manage_superadmin_scope() ? list_project_backup_archives() : [];
?>
<section class="card">
    <h2>Profile</h2>
    <form method="post" action="index.php" class="grid profile-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="update_profile">
        <label>Officer Name
            <input type="text" name="officer_name" value="<?= e((string)($user['officer_name'] ?? '')); ?>" required>
        </label>
        <button type="submit" class="btn-small">Update Name</button>
    </form>
</section>

<section class="card">
    <h2>Change Password</h2>
    <form method="post" action="index.php" class="grid profile-form">
        <?= csrf_input(); ?>
        <input type="hidden" name="action" value="update_profile">
        <label>New Password
            <input type="password" name="password" required>
        </label>
        <label>Confirm New Password
            <input type="password" name="password_confirm" required>
        </label>
        <button type="submit" class="btn-small">Update Password</button>
    </form>
</section>

<?php if (is_superadmin()): ?>
    <?php $info = get_info_row() ?? []; ?>
    <section class="card">
        <h2>Login Page Video Link</h2>
        <form method="post" action="index.php" class="grid profile-form">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="save_login_page_video_link_setting">
            <label class="checkbox-label">
                <input type="checkbox" name="login_video_link_enabled" value="1" <?= (int)($info['login_video_link_enabled'] ?? 1) === 1 ? 'checked' : ''; ?>>
                Show the `Video Tutorial` link on the login page
            </label>
            <button type="submit" class="btn-small">Save Setting</button>
        </form>
    </section>

    <section class="card">
        <h2>BIMH Data</h2>
        <p class="hint">This is a global BIMH master upload. If a BIMH ID already exists, that row will be updated. If the BIMH ID is new, a new row will be created. Duplicate BIMH IDs cannot exist in the database because `BIMH ID` is the unique primary key.</p>
        <?php if (!can_manage_superadmin_scope()): ?>
            <p class="hint">View-only superadmin users can see this section but cannot upload BIMH data.</p>
        <?php else: ?>
            <form method="post" action="index.php" enctype="multipart/form-data" class="grid profile-form">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="upload_bimh_data">
                <label>Upload BIMH Excel File
                    <input type="file" name="bimh_file" accept=".xlsx,.xls" required>
                </label>
                <button type="submit" class="btn-small">Update BIMH Data</button>
            </form>
        <?php endif; ?>
    </section>

    <?php if (can_manage_superadmin_scope()): ?>
        <section class="card">
            <h2>Project Backup</h2>
            <p class="hint">Create a ZIP backup of the whole project and store it on the server. Backup files are saved inside server storage and can be downloaded or permanently deleted from here.</p>
            <form method="post" action="index.php" class="inline-form" id="project-backup-form" onsubmit="if(window.assetOpenProjectBackupProgress){window.assetOpenProjectBackupProgress();}">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="create_project_backup">
                <button type="submit" class="btn-small" id="project-backup-start">Create Backup ZIP</button>
            </form>
            <div class="modal-backdrop" id="project-backup-progress-modal" aria-hidden="true">
                <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="project-backup-progress-title">
                    <div class="flash-modal-head">
                        <h3 id="project-backup-progress-title">Preparing backup...</h3>
                    </div>
                    <div class="hint" id="project-backup-progress-text">Please wait while the ZIP and SQL backup are being created.</div>
                    <div class="hero-row" style="align-items:center;gap:16px;margin-top:12px;">
                        <div style="flex:1 1 auto;"></div>
                        <div id="project-backup-progress-percent" style="min-width:56px;text-align:right;font-weight:700;">0%</div>
                    </div>
                    <div style="margin-top:10px;background:#dfe7f2;border-radius:999px;overflow:hidden;height:12px;">
                        <div id="project-backup-progress-bar" style="width:0%;height:12px;background:linear-gradient(90deg,#0f5ea8,#1c8f6a);transition:width .25s ease;"></div>
                    </div>
                    <div class="hint" id="project-backup-progress-count" style="margin-top:8px;">0 / 0 files</div>
                    <div class="modal-actions" style="justify-content:flex-end;margin-top:16px;">
                        <button type="button" class="modal-close" id="project-backup-progress-close">Hide</button>
                    </div>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Created At</th>
                        <th>ZIP Size</th>
                        <th>SQL Size</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($projectBackups === []): ?>
                        <tr>
                            <td colspan="5" class="muted">No project backups are stored yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($projectBackups as $backup): ?>
                            <tr>
                                <td><?= e((string)$backup['filename']); ?></td>
                                <td><?= e((string)$backup['created_at']); ?></td>
                                <td><?= e((string)$backup['size_label']); ?></td>
                                <td><?= e((string)($backup['sql_size_label'] !== '' ? $backup['sql_size_label'] : '-')); ?></td>
                                <td>
                                    <div class="action-row">
                                        <a class="btn-small" href="<?= e((string)$backup['download_url']); ?>">Download ZIP+SQL</a>
                                        <?php if ((string)($backup['sql_download_url'] ?? '') !== ''): ?>
                                            <a class="btn-small" href="<?= e((string)$backup['sql_download_url']); ?>">Download SQL</a>
                                        <?php endif; ?>
                                        <form method="post" action="index.php" class="inline-form" onsubmit="return confirm('This backup will be permanently deleted. Continue?');">
                                            <?= csrf_input(); ?>
                                            <input type="hidden" name="action" value="delete_project_backup">
                                            <input type="hidden" name="file" value="<?= e((string)$backup['filename']); ?>">
                                            <button type="submit" class="btn-small btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <script>
            (function () {
                var form = document.getElementById('project-backup-form');
                if (!form) {
                    return;
                }
                var startButton = document.getElementById('project-backup-start');
                var modal = document.getElementById('project-backup-progress-modal');
                var title = document.getElementById('project-backup-progress-title');
                var text = document.getElementById('project-backup-progress-text');
                var percent = document.getElementById('project-backup-progress-percent');
                var bar = document.getElementById('project-backup-progress-bar');
                var count = document.getElementById('project-backup-progress-count');
                var closeButton = document.getElementById('project-backup-progress-close');
                var pollTimer = null;

                window.assetOpenProjectBackupProgress = function () {
                    if (!modal) {
                        return;
                    }
                    modal.classList.add('open');
                    modal.setAttribute('aria-hidden', 'false');
                };

                var hideProgress = function () {
                    if (!modal) {
                        return;
                    }
                    modal.classList.remove('open');
                    modal.setAttribute('aria-hidden', 'true');
                };

                if (closeButton) {
                    closeButton.addEventListener('click', function () {
                        hideProgress();
                    });
                }

                var updateProgress = function (payload) {
                    var pct = Math.max(0, Math.min(100, parseInt(payload.progress_percent || 0, 10) || 0));
                    var processed = parseInt(payload.processed_files || 0, 10) || 0;
                    var total = parseInt(payload.total_files || 0, 10) || 0;
                    window.assetOpenProjectBackupProgress();
                    percent.textContent = pct + '%';
                    bar.style.width = pct + '%';
                    title.textContent = payload.status === 'completed' ? 'Backup ready' : (payload.status === 'failed' ? 'Backup failed' : 'Creating backup...');
                    text.textContent = payload.message || 'Preparing backup...';
                    count.textContent = processed + ' / ' + total + ' files';
                };

                var pollStatus = function (statusUrl) {
                    pollTimer = window.setTimeout(function () {
                        fetch(statusUrl, { credentials: 'same-origin' })
                            .then(function (response) { return response.json(); })
                            .then(function (payload) {
                                updateProgress(payload);
                                if (payload.status === 'completed') {
                                    if (startButton) {
                                        startButton.disabled = false;
                                    }
                                    window.setTimeout(function () { window.location.reload(); }, 1500);
                                    return;
                                }
                                if (payload.status === 'failed') {
                                    if (startButton) {
                                        startButton.disabled = false;
                                    }
                                    return;
                                }
                                pollStatus(statusUrl);
                            })
                            .catch(function () {
                                if (startButton) {
                                    startButton.disabled = false;
                                }
                                updateProgress({ status: 'failed', message: 'Unable to read backup progress.', progress_percent: 0, processed_files: 0, total_files: 0 });
                            });
                    }, 800);
                };

                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    window.assetOpenProjectBackupProgress();
                    if (startButton) {
                        startButton.disabled = true;
                    }
                    updateProgress({ status: 'pending', message: 'Preparing backup queue...', progress_percent: 0, processed_files: 0, total_files: 0 });
                    var formData = new FormData(form);
                    fetch('index.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (payload) {
                            if (!payload.status_url) {
                                throw new Error(payload.message || 'Unable to start backup job.');
                            }
                            pollStatus(payload.status_url);
                        })
                        .catch(function (error) {
                            if (startButton) {
                                startButton.disabled = false;
                            }
                            updateProgress({ status: 'failed', message: error.message || 'Unable to start backup job.', progress_percent: 0, processed_files: 0, total_files: 0 });
                        });
                });
            })();
        </script>
    <?php endif; ?>

    <section class="card" id="profile-superadmin-users">
        <div class="hero-row office-manage-users-head">
            <div>
                <h2>Additional Users</h2>
                <p class="hint">These users get superadmin view-only access. Default password is 1234.</p>
            </div>
        </div>
        <?php if (!can_manage_superadmin_scope()): ?>
            <p class="hint">View-only superadmin users can view this list but cannot change it.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="office-users-table">
                    <thead>
                    <tr>
                        <th>SL</th>
                        <th>User Name</th>
                        <th>ID</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody data-managed-user-body>
                    <?php foreach ($superadminUsers as $index => $managedUser): ?>
                        <tr>
                            <td><?= e((string)($index + 1)); ?></td>
                            <td><input type="text" name="officer_name" value="<?= e((string)($managedUser['officer_name'] ?? '')); ?>" form="superadmin-managed-user-<?= e((string)$managedUser['id']); ?>"></td>
                            <td><input type="email" name="email_id" value="<?= e((string)$managedUser['email_id']); ?>" form="superadmin-managed-user-<?= e((string)$managedUser['id']); ?>" required></td>
                            <td>1234</td>
                            <td><input type="text" value="View Only" readonly></td>
                            <td>
                                <div class="action-row">
                                    <form method="post" action="index.php" class="inline-form" id="superadmin-managed-user-<?= e((string)$managedUser['id']); ?>">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="save_superadmin_additional_user">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$managedUser['id']); ?>">
                                        <button type="submit" class="btn-small">Save</button>
                                    </form>
                                    <form method="post" action="index.php" class="inline-form">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="reset_superadmin_additional_user_password">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$managedUser['id']); ?>">
                                        <button type="submit" class="btn-small">Reset Password</button>
                                    </form>
                                    <form method="post" action="index.php" class="inline-form">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="toggle_superadmin_additional_user_status">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$managedUser['id']); ?>">
                                        <input type="hidden" name="active_status" value="<?= (int)($managedUser['active_status'] ?? 1) === 1 ? '0' : '1'; ?>">
                                        <button type="submit" class="btn-small <?= (int)($managedUser['active_status'] ?? 1) === 1 ? 'btn-danger' : ''; ?>"><?= (int)($managedUser['active_status'] ?? 1) === 1 ? 'Disable' : 'Enable'; ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <template data-managed-user-template>
                <tr>
                    <td data-managed-user-sl></td>
                    <td><input type="text" name="officer_name" form="__FORM_ID__"></td>
                    <td><input type="email" name="email_id" form="__FORM_ID__" required></td>
                    <td>1234</td>
                    <td><input type="text" value="View Only" readonly></td>
                    <td>
                        <form method="post" action="index.php" class="inline-form managed-user-create-form" id="__FORM_ID__">
                            <?= csrf_input(); ?>
                            <input type="hidden" name="action" value="save_superadmin_additional_user">
                            <button type="submit" class="btn-small">Save</button>
                        </form>
                    </td>
                </tr>
            </template>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-add-managed-user-row="profile-superadmin-users">+ Add Row</button>
            </div>
        <?php endif; ?>
    </section>
<?php elseif ($officeContext): ?>
    <section class="card" id="profile-managed-user-table">
        <div class="hero-row office-manage-users-head">
            <div>
                <h2>Additional Users</h2>
                <p class="hint">Default password for newly added users is 1234.</p>
            </div>
        </div>

        <?php if (!office_allows_user_management((int)$officeContext['office_type'], (int)$officeContext['office_id'])): ?>
            <p class="hint">This office is currently not allowed to manage additional users.</p>
        <?php elseif (!user_can_manage_office_users($user, (int)$officeContext['office_type'], (int)$officeContext['office_id'])): ?>
            <p class="hint">Only the office head can manage additional users for this office.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="office-users-table">
                    <thead>
                    <tr>
                        <th>SL</th>
                        <th>User Name</th>
                        <th>ID</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody data-managed-user-body>
                    <?php foreach ($officeUsers as $index => $officeUser): ?>
                        <tr>
                            <td><?= e((string)($index + 1)); ?></td>
                            <td><input type="text" name="officer_name" value="<?= e((string)($officeUser['officer_name'] ?? '')); ?>" form="profile-managed-user-<?= e((string)$officeUser['id']); ?>"></td>
                            <td><input type="email" name="email_id" value="<?= e((string)$officeUser['email_id']); ?>" form="profile-managed-user-<?= e((string)$officeUser['id']); ?>" required></td>
                            <td>1234</td>
                            <td>
                                <select name="office_access_level" form="profile-managed-user-<?= e((string)$officeUser['id']); ?>">
                                    <?php foreach ($accessOptions as $level => $label): ?>
                                        <?php if ($level === 1) continue; ?>
                                        <option value="<?= e((string)$level); ?>" <?= (int)$officeUser['office_access_level'] === $level ? 'selected' : ''; ?>><?= e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <div class="action-row">
                                    <form method="post" action="index.php" class="inline-form" id="profile-managed-user-<?= e((string)$officeUser['id']); ?>">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="save_additional_office_user">
                                        <input type="hidden" name="office_type" value="<?= e((string)$officeContext['office_type']); ?>">
                                        <input type="hidden" name="office_id" value="<?= e((string)$officeContext['office_id']); ?>">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$officeUser['id']); ?>">
                                        <input type="hidden" name="return_page" value="profile">
                                        <button type="submit" class="btn-small">Save</button>
                                    </form>
                                    <form method="post" action="index.php" class="inline-form">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="reset_additional_office_user_password">
                                        <input type="hidden" name="office_type" value="<?= e((string)$officeContext['office_type']); ?>">
                                        <input type="hidden" name="office_id" value="<?= e((string)$officeContext['office_id']); ?>">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$officeUser['id']); ?>">
                                        <input type="hidden" name="return_page" value="profile">
                                        <button type="submit" class="btn-small">Reset Password</button>
                                    </form>
                                    <form method="post" action="index.php" class="inline-form">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="action" value="toggle_additional_office_user_status">
                                        <input type="hidden" name="office_type" value="<?= e((string)$officeContext['office_type']); ?>">
                                        <input type="hidden" name="office_id" value="<?= e((string)$officeContext['office_id']); ?>">
                                        <input type="hidden" name="managed_user_id" value="<?= e((string)$officeUser['id']); ?>">
                                        <input type="hidden" name="active_status" value="<?= (int)($officeUser['active_status'] ?? 1) === 1 ? '0' : '1'; ?>">
                                        <input type="hidden" name="return_page" value="profile">
                                        <button type="submit" class="btn-small <?= (int)($officeUser['active_status'] ?? 1) === 1 ? 'btn-danger' : ''; ?>"><?= (int)($officeUser['active_status'] ?? 1) === 1 ? 'Disable' : 'Enable'; ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <template data-managed-user-template>
                <tr>
                    <td data-managed-user-sl></td>
                    <td><input type="text" name="officer_name" form="__FORM_ID__"></td>
                    <td><input type="email" name="email_id" form="__FORM_ID__" required></td>
                    <td>1234</td>
                    <td>
                        <select name="office_access_level" form="__FORM_ID__">
                            <?php foreach ($accessOptions as $level => $label): ?>
                                <?php if ($level === 1) continue; ?>
                                <option value="<?= e((string)$level); ?>" <?= $level === 2 ? 'selected' : ''; ?>><?= e($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <form method="post" action="index.php" class="inline-form managed-user-create-form" id="__FORM_ID__">
                            <?= csrf_input(); ?>
                            <input type="hidden" name="action" value="save_additional_office_user">
                            <input type="hidden" name="office_type" value="<?= e((string)$officeContext['office_type']); ?>">
                            <input type="hidden" name="office_id" value="<?= e((string)$officeContext['office_id']); ?>">
                            <input type="hidden" name="return_page" value="profile">
                            <button type="submit" class="btn-small">Save</button>
                        </form>
                    </td>
                </tr>
            </template>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-add-managed-user-row="profile-managed-user-table">+ Add Row</button>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
