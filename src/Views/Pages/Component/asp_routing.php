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
        <label class="text-nowrap bd-highlight pr-3 pb-1 float-right" for="meace_route_link_label">
            <span id="meace_route_link_label" onchange="setLabelAsValue(this)"></span>
        </label>
        <div asp-ace-editor="php-route-box" data-name="data_route" ace-btn-save="save">
            <textarea><?= $dataRoute ?></textarea>
        </div>
    </div>
    <!-- /.card-body -->
    <div class="card-footer"></div>
    <!-- /.card-footer-->
</div>
<!-- /.card-->
<?= $this->endSection() ?>

<!-- Section styles -->
<?= $this->section('styles') ?>
<?= $this->endSection() ?>

<!-- Section content -->
<?= $this->section('scripts') ?>
    <script defer src="/control/assets/js/treemodal-<?= $valid_hash ?>.js"></script>
    <script defer src="/control/assets/js/aceAddRoute-<?= $valid_hash ?>.js"></script>
    <script defer src="/control/assets/js/aceEditor-<?= $valid_hash ?>.js"></script>
<?= $this->endSection() ?>