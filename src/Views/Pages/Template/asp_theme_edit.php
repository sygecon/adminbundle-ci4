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
            <input type="hidden" id="ace-fname">
            <div id="list-btn" class="row">
                <div class="col-md-10"><h4 id="ace-editor-title"></h4></div>
                <div class="col-md-2 mb-1">
                    <button class="btn btn-outline-danger btn-right" title="Закрыть редактор" onclick="closeAceEditor()">
                        <i asp-lazy="x-lg"></i> Закрыть
                    </button>
                </div>
            </div>
            <div id="ace-editor-css" asp-ace-editor="css" data-name="data[css]" ace-btn-save="save">
                <pre></pre>
            </div>
            <div id="ace-editor-js" asp-ace-editor="js" data-name="data[js]" ace-btn-save="save">
                <pre></pre>
            </div>
            <hr>
            <div class="row justify-content-md-center">
                <div class="col">
                    <div class="d-flex flex-column" style="border-right: 1px solid gray">
                        <div class="p-2 bg-info"><i asp-lazy="code-square"></i> Скрипты (*.js)
                            <button class="btn" title="Менеджер файлов" data-type="js-themes-<?= $theme ?>" callback="open-js-file" asp-btn-click="open-fm"><i asp-lazy="pencil-square"></i></button>
                        </div>
                        <div>
                            <div data-dynamic-list="js-themes-<?= $theme ?>" class="list-wrap" data-loader="fetch|resource/scripts"></div> 
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex flex-column" style="border-left: 1px solid gray">
                        <div class="p-2 bg-info"><i asp-lazy="command"></i> Файлы стиля (*.css)
                            <button class="btn" title="Менеджер файлов" data-type="css-themes-<?= $theme ?>" callback="open-css-file" asp-btn-click="open-fm"><i asp-lazy="pencil-square"></i></button>
                        </div>
                        <div>
                            <div data-dynamic-list="css-themes-<?= $theme ?>" class="list-wrap" data-loader="fetch|resource/styles"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer" asp-box="btn">
            <button class="btn bg-info float-right" title="<?= lang('Admin.descBtnMinify') ?>" asp-click="modal-confirm" data-action="minify-assets" data-title="<?= lang('Admin.titleDlgMinify') ?>">
                <img alt="<?= lang('Admin.titleBtnMinify') ?>" asp-lazy="file-zip"> <?= lang('Admin.titleBtnMinify') ?>
            </button>
        </div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card-->
<?= $this->endSection() ?>

<!-- Section styles -->
<?= $this->section('styles') ?>
    <link href="/control/assets/css/filemanager-<?= $valid_hash ?>.css" rel="stylesheet">
    <style>
        @media (min-width: 768px){
            .justify-content-md-center {
                justify-content: center!important;
            }
        }
        .dynamic-list {
            border-right: 1px solid gray;
        }
        .bg-info {
            font-weight: 400;
            /* font-size: 1.1rem; */
            background-color: #1d7af0 !important;
            color: white;
        }
        .bg-info:hover {
            color: black;
        }
        .bg-info button {
            float: right;
            width: 32px;
            height: 32px;
            background-color: #7f7f7f;
        }
        .bg-info button:hover {
            background-color: white;
        }
        .bg-info button i {
            cursor: pointer !important;
        }
    </style>
<?= $this->endSection() ?>

<!-- Section content -->
<?= $this->section('scripts') ?>    
    <script defer src="/control/assets/js/filemanager.<?= $valid_hash ?>.js"></script>
    <script defer src="/control/assets/js/dynamic_list.<?= $valid_hash ?>.js"></script>
    <script defer src="/control/assets/js/aceEditor.<?= $valid_hash ?>.js"></script>
    <script defer src="/assets/js/components/asp.ace.editor.themes.js"></script>
    <script>
        AspBase.minifyAssets = function() {
            AspBase.fetch("minify", "", "PUT")
            .then(function (res) {
                noop(1);
            })
            .catch(function () { noop(0) })
        }
    </script> 
<?= $this->endSection() ?>