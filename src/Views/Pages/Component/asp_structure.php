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
        <div asp-ace-editor="json" data-name="data_map" ace-btn-save="save">
            <textarea><?= $structure ?></textarea>
        </div>
    </div>
    <!-- /.card-body -->
    <div class="card-footer">
        <button class="btn btn-outline-secondary" onclick="btnStructureApply()"
            data-confirm="<?= lang('HeadLines.catalog.frm.structureConfirm') ?>" 
            data-answer="<?= lang('HeadLines.catalog.frm.structureAnswer') ?>" 
            data-answer-error="<?= lang('HeadLines.catalog.frm.structureAnswerError') ?>"
        >&nbsp;<?= lang('HeadLines.apply') ?>&nbsp;
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
    <script defer src="/control/assets/js/treemodal-<?= $valid_hash ?>.js"></script>
    <script defer src="/control/assets/js/aceAddRoute-<?= $valid_hash ?>.js"></script>
    <script defer src="/control/assets/js/aceEditor-<?= $valid_hash ?>.js"></script>
    <script>
        var btnStructureApply = function() {
            let elem = event.target,
                confirm = elem.getAttribute("data-confirm") || "",
                answer  = elem.getAttribute("data-answer") || "",
                error = elem.getAttribute("data-answer-error") || "Error!";
            if (confirm == "") return;
            if (answer == "") return;

            Asp.confirmQuestion({ title: "", content: confirm })
            .then(() => {
                AspBase.fetch("apply", "", "GET")
                .then(function(res) {
                    if (res) {
                        alert(answer);
                    } else {
                        alert(error);
                    }
                })
                .catch(() => { alert(error); })
            })
            .catch(() => { alert(error); })
        }
    </script>
<?= $this->endSection() ?>