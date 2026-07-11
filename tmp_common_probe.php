<?php
require __DIR__ . '/app/lib/bootstrap.php';
$pdo = db();
$seg = $pdo->query("SELECT id,segment_name FROM segments WHERE segment_name='admin_common'")->fetch(PDO::FETCH_ASSOC);
$fields = $pdo->query('SELECT id,label,field_key,data_type,is_import_enabled,is_common_row_field,is_required,mandatory_scope,secondary_of_field_id,sort_order FROM asset_fields WHERE segment_id=' . (int)$seg['id'] . ' AND active_status=1 ORDER BY sort_order,id')->fetchAll(PDO::FETCH_ASSOC);
$profiles = $pdo->query('SELECT * FROM asset_common_profiles WHERE segment_id=' . (int)$seg['id'] . ' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$bindings = $pdo->query('SELECT * FROM asset_common_profile_fields WHERE profile_id IN (SELECT id FROM asset_common_profiles WHERE segment_id=' . (int)$seg['id'] . ') ORDER BY profile_id,id')->fetchAll(PDO::FETCH_ASSOC);
$assets = $pdo->query('SELECT id,asset_number,category_id,common_profile_id,common_row_key,is_user_added_row,office_type,office_id FROM assets WHERE segment_id=' . (int)$seg['id'] . ' AND deleted_at IS NULL ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['segment'=>$seg,'fields'=>$fields,'profiles'=>$profiles,'bindings'=>$bindings,'assets'=>$assets], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
