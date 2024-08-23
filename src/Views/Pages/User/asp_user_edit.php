<!-- Extend from layout index -->
<?= $this->extend('Sygecon\AdminBundle\Views\Layout\page') ?>

<!-- Section content -->
<?= $this->section('content') ?>
    <?php 
        if (isset($model_name)) {
            $card = new \Sygecon\AdminBundle\Libraries\HTML\Card(
                $model_name, \Sygecon\AdminBundle\Config\UserControl::FORM_JSON_PATH
            );
            echo $card->show();
        }
    ?>
<?= $this->endSection() ?>

<!-- Section styles -->
<?= $this->section('styles') ?>
<?= $this->endSection() ?>

<!-- Section content -->
<?= $this->section('scripts') ?>
    <script async src="/assets/js/components/asp.save.form.js"></script>
    <script async src="/control/assets/js/imgtofile-<?= $valid_hash ?>.js"></script>
<?= $this->endSection() ?>