<?php

require_once __DIR__.'/vendor/autoload.php';

$dir = __DIR__.'/data/'.date('Y-m-d');
if(!is_dir($dir)){
    mkdir($dir);
    $baseurl = 'https://api.ovh.com/1.0/';
    $root = json_decode(file_get_contents($baseurl));
    file_put_contents($dir.'/root.json', json_encode($root, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
else{
    $root = json_decode(file_get_contents($dir.'/root.json'));
}
foreach($root->apis as $api){
    $file = $dir.'/'.str_replace('/','_',$api->path).'.'.$api->format[0];
    if(!file_exists($file) || file_get_contents($file) === 'null'){
        $data = json_decode(file_get_contents($root->basePath.$api->path.'.'.$api->format[0]), true);
        sortJSON($data);
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

$dates = [];
foreach (new DirectoryIterator(__DIR__.'/data/') as $fileInfo) {
    if($fileInfo->isDir() && !$fileInfo->isDot())
        $dates[$fileInfo->getBasename()] = $fileInfo->getBasename();
}
krsort($dates);


$date_to = (isset($_GET['date_to']) && isset($dates[$_GET['date_to']])) ? $_GET['date_to'] : array_values($dates)[0];
$date_from = (isset($_GET['date_from']) && isset($dates[$_GET['date_from']])) ? $_GET['date_from'] : array_values($dates)[1];


function sortJSON(&$json){
    ksort($json, SORT_NATURAL | SORT_FLAG_CASE);
    foreach($json as $k=>$v){
        if(is_array($v))
            sortJSON($json[$k]);
    }
}

?><!DOCTYPE html>
<html lang="en-us">
<head>
<meta charset="UTF-8">
<title>OVH API Watcher</title>
<style>
body {
	background: #fff;
	font-family: Arial;
	font-size: 12px;
}
.Differences {
	width: 100%;
	border-collapse: collapse;
	border-spacing: 0;
	empty-cells: show;
}

.Differences thead th {
	text-align: left;
	border-bottom: 1px solid #000;
	background: #aaa;
	color: #000;
	padding: 4px;
}
.Differences tbody th {
	text-align: right;
	background: #ccc;
	width: 4em;
	padding: 1px 2px;
	border-right: 1px solid #000;
	vertical-align: top;
	font-size: 13px;
}

.Differences td {
	padding: 1px 2px;
	font-family: Consolas, monospace;
	font-size: 13px;
}

.DifferencesSideBySide .ChangeInsert td.Left {
	background: #dfd;
}

.DifferencesSideBySide .ChangeInsert td.Right {
	background: #cfc;
}

.DifferencesSideBySide .ChangeDelete td.Left {
	background: #f88;
}

.DifferencesSideBySide .ChangeDelete td.Right {
	background: #faa;
}

.DifferencesSideBySide .ChangeReplace .Left {
	background: #fe9;
}

.DifferencesSideBySide .ChangeReplace .Right {
	background: #fd8;
}

.Differences ins, .Differences del {
	text-decoration: none;
}

.DifferencesSideBySide .ChangeReplace ins, .DifferencesSideBySide .ChangeReplace del {
	background: #fc0;
}

.Differences .Skipped {
	background: #f7f7f7;
}

.DifferencesInline .ChangeReplace .Left,
.DifferencesInline .ChangeDelete .Left {
	background: #fdd;
}

.DifferencesInline .ChangeReplace .Right,
.DifferencesInline .ChangeInsert .Right {
	background: #dfd;
}

.DifferencesInline .ChangeReplace ins {
	background: #9e9;
}

.DifferencesInline .ChangeReplace del {
	background: #e99;
}

pre {
	width: 100%;
	overflow: auto;
}
</style>
</head>
<body>
<h1>OVH API Watcher</h1>
<form action="index.php" method="GET">
    <p>
        <label>New version</label>
        <select name="date_to">
        <?php foreach($dates as $date){ ?>
        <option value="<?php echo $date; ?>"<?php if($date == $date_to) echo ' selected="selected"'; ?>><?php echo $date; ?></option>
        <?php } ?></select>
        <label>Old version</label>
        <select name="date_from">
        <?php foreach($dates as $date){ ?>
        <option value="<?php echo $date; ?>"<?php if($date == $date_from) echo ' selected="selected"'; ?>><?php echo $date; ?></option>
        <?php } ?></select>
        <button type="submit">Go !</button>
        </p>
</form>
<?php
foreach(json_decode(file_get_contents(__DIR__.'/data/'.$date_to.'/root.json'))->apis as $api){
    echo '<h2>',$api->path,'</h2>';
    $f_from =  __DIR__.'/data/'.$date_from.'/'.str_replace('/','_',$api->path).'.'.$api->format[0];
    $f_to = __DIR__.'/data/'.$date_to.'/'.str_replace('/','_',$api->path).'.'.$api->format[0];
    $file_from = file_exists($f_from) ? file($f_from) : array();
    $file_to = file_exists($f_to) ? file($f_to) : array();
    $diff = new Diff($file_from, $file_to, []);
    $renderer = new Diff_Renderer_Html_SideBySide();
    echo $diff->Render($renderer);
}
?>
</body>
</html>
