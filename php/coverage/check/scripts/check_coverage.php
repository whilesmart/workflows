<?php
$baseline = (float)$argv[1];
$current = (float)$argv[2];
$strict_mode = filter_var($argv[3], FILTER_VALIDATE_BOOLEAN);
$threshold = (float)$argv[4] ?? 90;

$tt = $strict_mode ? 'yes' : 'no';
echo "baseline: $baseline\n";
echo "current: $current\n";
echo "strict_mode: $argv[3] - $strict_mode - $tt\n";
echo "threshold: $threshold\n";


echo "Baseline coverage: {$baseline}%\n";
echo "Current coverage: {$current}%\n";

if ($current < $threshold && $current < $baseline) {
    echo "Current coverage of $current% is less than the baseline coverage of $baseline%  ❌\n";
    if ($strict_mode) {
        exit(1);
    }
} else {
    echo "Coverage OK ✅\n";
}
