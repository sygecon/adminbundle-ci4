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
            <div asp-ace-editor="php-html-layout" data-name="data" ace-btn-save="save">
                <textarea><?= $dataJson ?></textarea>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer"></div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card-->
    <div class="card collapsed">
        <div class="card-header">
            <h3 class="card-title"><i asp-lazy="<?= $head['icon_block'] ?>"></i> <?= $head['title_block'] ?></h3>
            <div class="card-tools" asp-box="btn">
                <button class="btn btn-tool" asp-click="btn-collapse" static="1"></button>
            </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <div class="asp-multiselect-move box-different btn-all-no" data-loader="fetchApi|layout/sheet/<?= $id ?>" data-callback="me-action-select">
                <div class="asp-select-move-box">
                    <!-- <div class="top-panel">
                        <div class="message">
                            Здесь размещаете любое содержание ...
                        </div>
                    </div> -->
                </div>
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
<script defer src="/control/assets/js/multiselect_move-<?= $valid_hash ?>.js"></script>
<script defer src="/control/assets/js/aceEditor-<?= $valid_hash ?>.js"></script>
<script>
    AspBase.meActionSelect = function (option) {
        let action = option.action
        delete option.action
        delete option.all
        AspBase.fetch("", "", "put", "block_id=" + option.value + "&action=" + action)
        .then(function (res) { window.location.reload(); })
        .catch(function () { noop(1) })
    }
</script>
<?= $this->endSection() ?>