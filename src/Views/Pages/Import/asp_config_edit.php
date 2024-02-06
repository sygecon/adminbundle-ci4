<!-- Extend from layout index -->
<?= $this->extend('Sygecon\AdminBundle\Views\Layout\page') ?>

<!-- Section content -->
<?= $this->section('content') ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i asp-lazy="<?= $head['icon'] ?>"></i> <?= $head['title'] ?></h3>
            <div class="card-tools d-flex" asp-box="btn">
                <button class="btn btn-tool" asp-click="btn-collapse" static="1"></button>
            </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <div class="row">
                <div asp-ace-editor="json-import" data-name="json" ace-btn-save="save">
                    <textarea><?= $data ?></textarea>
                </div>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer" asp-box="btn">
            <button class="btn btn-secondary" asp-click="modal-confirm" 
                data-title="<?= lang('HeadLines.createConfigTemplate') ?>" data-action="gen-template" data-height="600px">
                <?= lang('HeadLines.createConfigTemplate') ?>
            </button>
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
    <script defer src="/control/assets/js/aceEditor.<?= $valid_hash ?>.js"></script>
    <script>
        const CARRYOVER_JSON = function(objJSON) {
            if (objJSON && typeof objJSON === "object") {
                return JSON.stringify(objJSON, null, 4).trim()
            }
            return objJSON
        }

        AspBase.genTemplate = function() {
            return new Promise(function (resolve) {
                AspBase.fetch('template', "JSON", "GET")
                    .then(function (config) {
                        const BODY = document.querySelector(".card-body"),
                            EDITOR = ace.edit(BODY.querySelector(".ace_editor"));
                        if (EDITOR && typeof EDITOR !== "undefined") {
                            let elem = EDITOR.getSession();
                            elem.setValue(CARRYOVER_JSON(config));
                            elem.me_update = true;
                            elem = BODY.querySelector(".btn-save");
                            if (elem !== null) { elem.disabled = false; }
                            EDITOR.focus();
                        }
                        resolve()
                    })
                    .catch(function () { resolve() })
            });
        }
    </script>
<?= $this->endSection() ?>