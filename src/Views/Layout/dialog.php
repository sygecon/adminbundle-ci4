<!doctype html>
<html lang="<?= $locale ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1, shrink-to-fit=no">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="robots" content="noindex, nofollow">
        <meta http-equiv="Cache-Control" content="no-cache">
        <title><?= $head['title'] ?></title>
        <link rel="icon" href="/favicon.ico" type="image/ico">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <?= $this->renderSection('styles') ?>
    </head>
    <body class="bg-light">
        <main role="main" class="container">
            <?= $this->renderSection('main') ?>
        </main>
        <?= $this->renderSection('scripts') ?>
    </body>
</html>    