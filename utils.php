<?php

/**
 * Debug a variable by displaying its contents
 * @param mixed $var Variable to display in the debug message
 * @param string $title Title of the debug message
 * @return none
 */
function debug($var, $title = '')
{
    // Check if we are in cli mode or webserver/browser mode

    if (php_sapi_name() == "cli") $html = FALSE;
    else $html = TRUE;

    // Show html
    
    if (is_string($var)){
        $var = str_replace('<', '&lt;', $var);
        $var = str_replace('>', '&gt;', $var);
    }

    // Get some useful info/stats to display

    $memoryUsage = getFriendlySize(memory_get_usage());
    $memoryPeak = getFriendlySize(memory_get_peak_usage());
    $memoryLimit = ini_get('memory_limit');
    $time = date("H:i:s");

    $stacktrace = debug_backtrace();
    $calledFrom = "{$stacktrace[0]['file']} on line {$stacktrace[0]['line']}";

    // Display the debug message
    
    if ($html)
    {
        echo "<div style='text-align:left;background-color:#CCC;padding:15px;'>";
        if (!empty($title)) echo "<pre style='font-weight:bold;text-align:left;color:#000;margin:0;padding:0 0 15px 15px;'>{$title}</pre>";
        echo "<pre style='text-align:left;color:#000;background-color:#FFF;display:block;margin:0;padding:15px;'>";
    }
    echo "[{$memoryUsage}/{$memoryPeak}/{$memoryLimit}] {$time} - {$calledFrom}\n";
    print_r($var);	
    if ($html) echo "</pre></div>";
}

/**
 * Calculate human-readable file or memory sizes
 */
function getFriendlySize($value)
{
    $metric = Array('B', 'K', 'M', 'G', 'T', 'P');
    $currentMetric = 0;
    while (($value / 1024) > 1)
    {
        $value = $value / 1024;
        $currentMetric++;
    }
    $value = round($value, 2);
    return "{$value}{$metric[$currentMetric]}";
}