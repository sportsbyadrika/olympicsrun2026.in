<?php
$flashMap = [
    'success' => 'alert-success',
    'error'   => 'alert-danger',
    'info'    => 'alert-info',
    'warning' => 'alert-warning',
];
foreach ($flashMap as $type => $class):
    $msg = flash_pull($type);
    if ($msg === null) continue;
?>
    <div class="alert <?= $class ?> alert-dismissible fade show" role="alert">
        <?= e($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endforeach; ?>
