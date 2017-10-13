<?php
$fh = fopen(__DIR__ . '/events.csv', 'w');
$listFh = fopen(__DIR__ . '/list.csv', 'w');
fputcsv($listFh, array('file', 'source'));
fputcsv($fh, array('area', 'place', 'contact', 'phone', 'time_begin', 'time_end', 'title', 'url'));
for($i = 1; $i < 34; $i++) {
  $pageFile = __DIR__ . '/tmp/page_' . $i;
  if(!file_exists($pageFile)) {
    file_put_contents($pageFile, file_get_contents('http://www.tainan.gov.tw/tn/agr/list.asp?nsub=H5A000&topage=' . $i));
  }
  $page = file_get_contents($pageFile);
  $links = explode('</a></li>', $page);
  foreach($links AS $link) {
    if(false !== strpos($link, '臺南市寺廟廟會活動明細')) {
      $parts = explode('tnpage.asp?id={', $link);
      $id = substr($parts[1], 0, strpos($parts[1], '}&nsub='));
      $itemFile = __DIR__ . '/tmp/item_' . $id;
      $itemUrl = 'http://www.tainan.gov.tw/tn/agr/tnpage.asp?id=' . $id;
      if(!file_exists($itemFile)) {
        file_put_contents($itemFile, file_get_contents($itemUrl));
      }
      $item = file_get_contents($itemFile);
      $pos = strpos($item, '相關檔案');
      if(false !== $pos) {
        $links = explode('</a></li>', substr($item, $pos));
        foreach($links AS $k => $link) {
          $idParts = explode('<a href="../', $link);
          if(isset($idParts[1])) {
            $idParts[1] = 'http://www.tainan.gov.tw/tn/' . substr($idParts[1], 0, strpos($idParts[1], '"'));
            fputcsv($listFh, array($idParts[1], $itemUrl));
          }
        }
      }
      $lines = explode('</tr>', $item);
      foreach($lines AS $line) {
        $cols = explode('</td>', $line);
        switch(count($cols)) {
          case 8:
            foreach($cols AS $k => $v) {
              $cols[$k] = trim(strip_tags($v));
              $cols[$k] = preg_replace("/\s+/", ' ', $cols[$k]);
            }
            if($cols[0] === '區別') {
              continue;
            }
            $cols[4] = strtr($cols[4], array(
              '/' => '-',
              '日' => '',
              '106/' => '2017-',
              '105/' => '2016-',
              '104/' => '2015-',
            ));
            $cols[4] = preg_split('/[、至 ]+/', $cols[4]);
            $cols[5] = preg_split('/[、至 ]+/', $cols[5]);
            $data = array(
              'area' => $cols[0],
              'place' => $cols[1],
              'contact' => $cols[2],
              'phone' => $cols[3],
              'time_begin' => '',
              'time_end' => '',
              'title' => $cols[6],
              'url' => $itemUrl,
            );
            if(count($cols[4]) === 1) {
              $data['time_begin'] = $data['time_end'] = $cols[4][0];
            } else {
              $data['time_begin'] = $cols[4][0];
              $data['time_end'] = $cols[4][1];
            }
            if(isset($cols[5][1])) {
              $data['time_begin'] .= ' ' . str_pad(intval($cols[5][0]), 2, '0', STR_PAD_LEFT) . ':00:00';
              $data['time_end'] .= ' ' . str_pad(intval($cols[5][1]), 2, '0', STR_PAD_LEFT) . ':00:00';
            } else {
              $data['time_begin'] .= ' 00:00:00';
              $data['time_end'] .= ' 23:59:59';
            }
            fputcsv($fh, $data);
          break;
        }
      }
    }
  }
}
