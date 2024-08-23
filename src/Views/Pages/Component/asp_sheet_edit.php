<!-- Extend from layout index -->
<?= $this->extend('Sygecon\AdminBundle\Views\Layout\page') ?>


<!-- Section content -->
<?= $this->section('content') ?>
    <div class="card <?php if(! $isTable) echo 'collapsed'; ?>"">
        <div class="card-header">
            <h3 class="card-title"><i asp-lazy="<?= $head['icon'] ?>"></i> <?= $head['title'] ?></h3>
            <div class="card-tools" asp-box="btn">
                <button class="btn btn-tool" asp-click="btn-collapse" static="1"></button>
            </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <div class="row">
                <div asp-ace-editor="php-control-box" data-name="data_app_model" ace-btn-save="save">
                    <textarea> <?= $appModel ?> </textarea>
                </div>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer"></div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card-->
    <div class="card collapsed">
        <div class="card-header">
            <h3 class="card-title"><i asp-lazy="<?= $head['icon'] ?>"></i> <?= $head['titleBase'] ?></h3>
            <div class="card-tools" asp-box="btn">
                <button class="btn btn-tool" asp-click="btn-collapse" static="1"></button>
            </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <div class="row">
                <div asp-ace-editor="php-direct-box" data-name="data_direct_model" ace-btn-save="save">
                    <textarea> <?= $directModel ?> </textarea>
                </div>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer"></div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card-->
    <div class="card <?php if($isTable) echo 'collapsed'; ?>">
        <div class="card-header">
            <h3 class="card-title"><i asp-lazy="table"></i> <?= lang('HeadLines.catalog.frm.tableDataStructure') ?></h3>
            <div class="card-tools" asp-box="btn">
                <button class="btn btn-tool" asp-click="btn-collapse" static="1"></button>
            </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <div asp-ace-editor="json-model" data-name="data_table" ace-btn-save="save">
                <pre><?= $dataTable ?></pre>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
        </div>
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
<script defer src="/control/assets/js/aceAddControl-<?= $valid_hash ?>.js"></script>
<script defer src="/control/assets/js/aceAddModel-<?= $valid_hash ?>.js"></script>
<script defer src="/control/assets/js/aceEditor-<?= $valid_hash ?>.js"></script>
<?= $this->endSection() ?>