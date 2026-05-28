<?php /** @var string $content */ /** @var string $title */ ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1A2B49">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e($title ?? 'Olympics Run 2026') ?></title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-app">

<?php include __DIR__ . '/../partials/navbar.php'; ?>

<main class="flex-grow-1 py-3 py-md-4">
    <div class="container">
        <?php include __DIR__ . '/../partials/flash.php'; ?>
        <?= $content ?>
    </div>
</main>

<footer class="footer mt-auto py-3 bg-navy text-white-50">
    <div class="container small d-md-flex justify-content-between">
        <span>&copy; <?= date('Y') ?> Kerala Olympic Association</span>
        <span>Olympics Run 2026</span>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/app.js"></script>
</body>
</html>
