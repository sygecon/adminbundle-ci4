<!-- Extend from layout index -->
<?= $this->extend('Sygecon\AdminBundle\Views\Layout\page') ?>

<!-- Section content -->
<?= $this->section('content') ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i asp-lazy="<?= $head['iconHtml'] ?>"></i> <?= $head['titleHtml'] ?></h3>
            <div class="card-tools d-flex" asp-box="btn">
                <label class="h5 mt-1 d-sm-none d-md-block">&nbsp;<?= lang('Admin.menu.sidebar.modelsName') ?>&nbsp;</label>
                <span class="h5 mr-3">
                        <select title="<?= lang('Admin.menu.sidebar.modelsDesc') ?>" class="form-select" style="display:inline;min-width:120px;" data-fetch-update="sheet_id">
                            <option value="0"></option>
                            <?php if (isset($sheetList)): ?>  
                                <?php foreach ($sheetList as $item):?>
                                    <option value="<?= $item->id ?>" title="<?= $item->title ?>" <?php if (isset($sheet_id) && $sheet_id === (int) $item->id) echo 'selected="selected"'; ?>><?= $item->name ?></option>
                                <?php endforeach;?>
                            <?php endif ?>
                        </select>
                </span>
                <label class="h5 mt-1 d-sm-none d-md-block">&nbsp;<?= lang('Admin.type') ?>&nbsp;</label>
                <span class="h5 mr-3">
                        <select title="<?= lang('Admin.type') ?>" class="form-select" style="display:inline;min-width:120px;" data-fetch-update="type">
                            <?php if (isset($typeList)): ?>  
                                <?php foreach ($typeList as $item):?>
                                    <option value="<?= $item['name'] ?>" title="<?= $item['title'] ?>" <?php if (isset($type) && $type == $item['name']) echo 'selected="selected"'; ?>><?= $item['name'] ?></option>
                                <?php endforeach;?>
                            <?php endif ?>
                        </select>
                </span>
                <button class="btn btn-tool" asp-click="btn-collapse" static="1"></button>
            </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <div class="row">
                <div asp-ace-editor="php-html" data-name="data_html" ace-btn-save="save">
                    <textarea><?= esc($HTML) ?></textarea>
                </div>
            </div>
            <hr>
            <div class="row">
                <h5 class="pl-3"><i asp-lazy="file-break"></i> <?= lang('HeadLines.catalog.frm.filesGeneratingDataForm') ?></h5>
                <div class="col-sm-6 pr-1" style="border-right: 1px solid gray">
                    <div data-dynamic-list="js-<?= $theme ?>" class="list-wrap" data-loader="fetch-api|resource/script/<?= $id ?>/app"></div> 
                </div>
                <div class="col-sm-6 pl-1" style="border-left: 1px solid gray">
                    <div data-dynamic-list="css-<?= $theme ?>" class="list-wrap" data-loader="fetch-api|resource/style/<?= $id ?>/app"></div>
                </div>
            </div> 
        </div>
        <!-- /.card-body -->
        <div class="card-footer"></div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card-->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i asp-lazy="<?= $head['icon'] ?>"></i> <?= $head['title'] ?></h3>
            <div class="card-tools" asp-box="btn">
                <button class="btn btn-tool" asp-click="btn-collapse" static="1"></button>
            </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <div class="row">
                <div asp-ace-editor="php-html" data-name="data_html_control" ace-btn-save="save">
                    <textarea><?= esc($HTMLControl) ?></textarea>
                </div>
            </div>
            <hr>
            <div class="row">
                <h5 class="pl-3"><i asp-lazy="file-break"></i> <?= lang('HeadLines.catalog.frm.filesGeneratingDataForm') ?></h5>
                <div class="col-sm-6 pr-1" style="border-right: 1px solid gray">
                    <div data-dynamic-list="js" class="list-wrap" data-loader="fetch-api|resource/script/<?= $id ?>/control"></div> 
                </div>
                <div class="col-sm-6 pl-1" style="border-left: 1px solid gray">
                    <div data-dynamic-list="css" class="list-wrap" data-loader="fetch-api|resource/style/<?= $id ?>/control"></div>
                </div>
            </div>
        </div>
        <div class="card-footer"></div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card-->
<?= $this->endSection() ?>

<!-- Section styles -->
<?= $this->section('styles') ?>
    <link href="/control/assets/css/filemanager-<?= $valid_hash ?>.css" rel="stylesheet">
<?= $this->endSection() ?>

<!-- Section content -->
<?= $this->section('scripts') ?>
    <script defer src="/control/assets/js/aceEditor.<?= $valid_hash ?>.js"></script>
    <script defer src="/control/assets/js/filemanager.<?= $valid_hash ?>.js"></script>
    <script defer src="/control/assets/js/dynamic_list.<?= $valid_hash ?>.js"></script>
<?= $this->endSection() ?>