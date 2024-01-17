<?php

if (cfr('ROOT')) {
    set_time_limit(0);
    $ip = '2.2.2.2';
    $startDate = '2023-01-01 00:00:00';
    $startTimestamp = strtotime($startDate);
    $endTimeStamp = time() + (86400 * 10);
    $classifier = new OphanimClassifier();
    $baseStruct = $classifier->getBaseStruct();
    $pathR = $classifier::DATA_PATH . 'R_' . $ip;
    $pathS = $classifier::DATA_PATH . 'S_' . $ip;

    file_put_contents($pathR, '');
    file_put_contents($pathS, '');
    $i = $startTimestamp;

    while ($i <= $endTimeStamp) {
        $lineData = $baseStruct;
        foreach ($lineData as $eachKey => $eachCounters) {
            if ($eachKey != 'time') {
                $lineData[$eachKey] = rand(0, 3227928600);
            } else {
                $lineData[$eachKey] = $i;
            }
        }

        $line = '';
        $line .= implode($classifier::DELIMITER, $lineData);
        $line .= PHP_EOL;
        file_put_contents($pathR, $line, FILE_APPEND);
        file_put_contents($pathS, $line, FILE_APPEND);
        $i = $i + 300;
    }
}
