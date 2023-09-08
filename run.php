<?php

// fetch data from LaMetric API

$json = file_get_contents('https://developer.lametric.com/api/v1/apps/13?skip=0&limit=1000');
$data = json_decode($json, true);

$vendors = [];

foreach ($data['apps'] as $app) {
    if (!isset($vendors[$app['vendor_id']])) {
        $vendors[$app['vendor_id']] = [
            'name' => $app['vendor'],
            'total_downloads' => 0,
            'total_likes' => 0,
            'apps' => []
        ];
    }

    $vendors[$app['vendor_id']]['total_downloads'] += $app['downloads'];
    $vendors[$app['vendor_id']]['total_likes'] += $app['likes']['total'];
    $vendors[$app['vendor_id']]['apps'][] = [
        'name' => $app['title'],
        'downloads' => $app['downloads'],
        'likes' => $app['likes']['total'],
    ];
}

$likesValues = array_column($vendors, 'total_likes');
array_multisort($likesValues, SORT_DESC, $vendors);

// write README.md

$handle = fopen(__DIR__ . '/README.md', 'w');

$readme = <<<EOT
# LaMetric Hall of Fame

This is a list of the most popular developers for the LaMetric Time smart clock.

EOT;

fwrite($handle, $readme);

$tableHeaders = <<<EOT

| Rank | Vendor | Downloads | Likes | Apps |
|:----:|--------|:---------:|:-----:|:----:|
EOT;

fwrite($handle, $tableHeaders);

$i = 1;
foreach ($vendors as $vendor) {
    $tableRow = "\n" . '|#' . $i . '|' . $vendor['name'] . '|' . number_format($vendor['total_downloads']) .
        '|' . number_format($vendor['total_likes']) . '|';
    fwrite($handle, $tableRow);

    $apps = $vendor['apps'];

    $likesValues = array_column($apps, 'likes');
    array_multisort($likesValues, SORT_DESC, $apps);

    $detailedApps = '';

    foreach ($apps as $app) {
        $detailedApps .= '<tr><td>' . $app['name'] . '</td><td>' . number_format($app['downloads']) .
            '</td><td>' . number_format($app['likes']) . '</td></tr>';
    }

    $totalApps = count($apps);
    $detailedTable = '<details><summary>View ' . $totalApps . ' app' . ($totalApps > 1 ? 's' : '') .
        '</summary><table><thead><tr><th>Name</th><th>Downloads</th><th>Likes</th></tr></thead><tbody>'
        . $detailedApps . '</tbody></table></details>|';

    fwrite($handle, $detailedTable);

    $i++;
}

fclose($handle);