<!-- Extend from layout index -->
<?= $this->extend('Sygecon\AdminBundle\Views\Layout\page') ?>

<!-- Section content -->
<?= $this->section('content') ?>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i asp-lazy="<?= $head['icon'] ?>"></i> <?= $head['title'] ?></h3>
            <div class="card-tools" asp-box="btn">
                <button class="btn btn-tool" asp-click="btn-collapse" static="1"></button>
            </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <div asp-ace-editor="php-control-box" data-name="data_control" ace-btn-save="save">
                <textarea><?= $dataControl ?></textarea>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer" asp-box="btn"></div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card-->
    
<?= $this->endSection() ?>

<!-- Section styles -->
<?= $this->section('styles') ?>
    <link href="/control/assets/css/treemodal-<?= $valid_hash ?>.css" rel="stylesheet">
<?= $this->endSection() ?>

<!-- Section content -->
<?= $this->section('scripts') ?>
    <script defer src="/control/assets/js/aceAddControl.<?= $valid_hash ?>.js"></script>
    <script defer src="/control/assets/js/treemodal.<?= $valid_hash ?>.js"></script>
    <script defer src="/control/assets/js/aceEditor.<?= $valid_hash ?>.js"></script>
<?= $this->endSection() ?>