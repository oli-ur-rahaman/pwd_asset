<?php
require __DIR__ . '/app/lib/bootstrap.php';
$job = asset_download_job_fetch_by_token('518b91ed155e03df9c439eb1ad2ff1ad8f00774e');
$request = json_decode((string)$job['request_payload'], true);
$user = asset_download_job_user((int)$job['user_id']);
$groups = [];
$context = null;
foreach ($request['segments'] as $segmentId => $segmentConfig) {
    $segmentRowsByGroup = asset_download_grouped_assets_for_segment((int)$segmentId, $request, $user, $context);
    foreach ($segmentRowsByGroup as $groupValue => $segmentAssets) {
        if (!isset($groups[$groupValue])) {
            $groups[$groupValue] = ['segments' => []];
        }
        $groups[$groupValue]['segments'][(int)$segmentId] = ['assets' => $segmentAssets];
    }
}
$out = __DIR__ . '/storage/runtime/bagerhat_pdf_after_patch.pdf';
asset_download_export_pdf($request, $groups, $out);
echo $out, PHP_EOL;
