<?php
/**
 * Force-submits any in-progress quiz attempt whose timer has run out.
 *
 * Designed for cron — runs in a few hundred ms even with thousands of rows.
 * Suggested schedule: every minute during quiz windows.
 *
 *   * * * * *  cd /var/www/olympicsrun2026.in && php bin/force-submit-expired.php >> logs/cron.log 2>&1
 *
 * Safe to run concurrently with browser-triggered submissions — the
 * underlying QuizAttempt::submit() takes a row-level lock and short-circuits
 * if the attempt is already submitted.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require __DIR__ . '/../includes/bootstrap.php';

$start = microtime(true);
$count = QuizAttempt::forceSubmitExpired();
$ms    = (int)round((microtime(true) - $start) * 1000);

printf("[%s] force-submitted %d expired attempt(s) in %d ms\n",
    date('Y-m-d H:i:s'),
    $count,
    $ms
);
exit(0);
