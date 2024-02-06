<!-- Extend from layout index -->
<?= $this->extend('Sygecon\AdminBundle\Views\Layout\page') ?>

<!-- Section content -->
<?= $this->section('content') ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i asp-lazy="<?= $head['iconHtml'] ?>"></i> 
            <?php if ($type === 'html'): ?>
            <?= $head['titleHtml'] ?>
            <?php else: ?>
            <?= $head['title'] ?>
            <?php endif ?>
            </h3>
            <div class="card-tools d-flex" asp-box="btn">
                <label class="h5 mt-1 d-sm-none d-md-block">&nbsp;<?= lang('Admin.type') ?>&nbsp;</label>
                <span class="h5 mr-3">
                    <select title="<?= lang('Admin.type') ?>" class="form-select" style="display:inline;min-width:120px;" onchange="meTypeUpdate()">
                        <?php if (isset($typeList)): ?>  
                            <?php foreach ($typeList as $item):?>
                                <option value="<?= $item ?>" title="<?= strtoupper($item) ?>" <?php if ($type === $item) echo ' selected="selected"'; ?>><?= strtoupper($item) ?></option>
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
                <?php if ($type === 'html'): ?>
                <form>
                    <div class='form-group col-md-12'>
                        <textarea data-type="full" title="<?= lang('HeadLines.formModel.content') ?>" type="textarea" class="mt-1 editor-tinymce" name="content">
                            <?= $data ?>
                        </textarea>
                    </div>  
                    <div class="pr-3" asp-box="btn">
                        <button type="button" class="btn btn-outline-primary float-right" data-btntext="<?= lang('HeadLines.save') ?>" 
                            data-title="<?= lang('HeadLines.btnConfirmSave') ?>" data-action="save-form-data" 
                            asp-click="modal-confirm"><img alt="<?= lang('HeadLines.save') ?>" asp-lazy="check2-all"> <?= lang('HeadLines.save') ?></button>
                    </div>  
                </form>
                <?php else: ?>
                <div asp-ace-editor="php-html" data-name="content" ace-btn-save="save">
                    <textarea><?= $data ?></textarea>
                </div>
                <?php endif ?>
            </div>
            <hr>
            <div class="row">
                <h5 class="pl-3"><i asp-lazy="file-break"></i> <?= lang('HeadLines.catalog.frm.filesGeneratingDataForm') ?></h5>
                <div class="col-sm-6 pr-1" style="border-right: 1px solid gray">
                    <div data-dynamic-list="js" class="list-wrap" data-loader="fetch-api|resource/script/<?= $id ?>/app"></div> 
                </div>
                <div class="col-sm-6 pl-1" style="border-left: 1px solid gray">
                    <div data-dynamic-list="css" class="list-wrap" data-loader="fetch-api|resource/style/<?= $id ?>/app"></div>
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
    <link href="/control/assets/css/filemanager-<?= $valid_hash ?>.css" rel="stylesheet">

    <?php if ($type === 'html'): ?>
    <link href="/control/assets/css/treemodal-<?= $valid_hash ?>.css" rel="stylesheet">
    <?php endif ?>
<?= $this->endSection() ?>

<!-- Section content -->
<?= $this->section('scripts') ?>
    <script async src="/control/assets/js/filemanager.<?= $valid_hash ?>.js"></script>
    <script defer src="/control/assets/js/dynamic_list.<?= $valid_hash ?>.js"></script>    
    <?php if ($type === 'html'): ?>
    <script defer src="/control/assets/js/treemodal.<?= $valid_hash ?>.js"></script>
    <script defer src="/control/assets/js/tinymce.<?= $valid_hash ?>.js"></script>
    <script defer src="/assets/js/components/asp.save.form.js"></script>
    <?php else: ?>
    <script defer src="/control/assets/js/aceEditor.<?= $valid_hash ?>.js"></script>
    <?php endif ?>

    <script>
        var meType = "<?= $type ?>",
            meTypeUpdate = function() {
                let elem = event.target, 
                    type = elem.value
                if (type !== "" && type !== undefined && meType != type) {
                    Asp.fetch(AspBase.urlCurl, "", "put", "type=" + type)
                    .then(function (res) { 
                        return location.reload();
                    })
                    .catch(function () { elem.value = meType })
                }
            }
    </script>
<?= $this->endSection() ?>