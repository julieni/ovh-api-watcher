<?php
set_time_limit(0);

function sortJSON(&$json){
    ksort($json, SORT_NATURAL | SORT_FLAG_CASE);
    foreach($json as $k=>$v){
        if(is_array($v))
            sortJSON($json[$k]);
    }
}

foreach (new DirectoryIterator(__DIR__.'/data/') as $fileInfo) {
    if(!$fileInfo->isDir() || $fileInfo->isDot())
        continue;
    foreach(json_decode(file_get_contents(__DIR__.'/data/'.$fileInfo->getBasename().'/root.json'))->apis as $api){
        $f = __DIR__.'/data/'.$fileInfo->getBasename().'/'.str_replace('/','_',$api->path).'.'.$api->format[0];
        $data = json_decode(file_get_contents($f), true);
        sortJSON($data);
        file_put_contents($f, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
