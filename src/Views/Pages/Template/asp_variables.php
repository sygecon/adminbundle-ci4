<!-- Extend from layout index -->
<?= $this->extend('Sygecon\AdminBundle\Views\Layout\page') ?>

<!-- Section content -->
<?= $this->section('content') ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i asp-lazy="<?= $head['icon'] ?>"></i> <?= $head['title'] ?></h3>
            <div class="card-tools d-flex page-language" asp-box="btn">
                <?php if (isset($lang_name)) : ?>
                <?= \Sygecon\AdminBundle\Libraries\HTML\Component::renderSelectLang($lang_name) ?>
                <?php endif ?>
                <button class="btn btn-tool" asp-click="btn-collapse" static="1"></button>
            </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <div class="row">
                <div asp-ace-editor="php-html" data-name="content" ace-btn-save="save">
                    <textarea><?= $data ?></textarea>
                </div>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">  
        </div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card-->
    <?= csrf_field() ?>
<?= $this->endSection() ?>

<!-- Section styles -->
<?= $this->section('styles') ?>
<?= $this->endSection() ?>

<!-- Section content -->
<?= $this->section('scripts') ?>
    <script defer src="/control/assets/js/aceEditor-<?= $valid_hash ?>.js"></script>
    <script defer src="/assets/js/components/asp.select.language.js"></script>
<?= $this->endSection() ?>