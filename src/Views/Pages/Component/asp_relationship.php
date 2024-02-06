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
        <div class="table-wrap" style="max-width:1200px;">
            <div class="table-responsive">
                <div asp-box="fetch" class="data-tables-wrapper no-footer">
                    <div class="data-length"></div>
                    <div class="data-filter">
                        <label><?= lang('Admin.table.filter.label') ?>:
                            <input type="search" placeholder="<?= lang('Admin.table.filter.placeholder') ?>">
                        </label>
                    </div>
                    <table class="table table-striped table-hover data-tables no-footer" role="grid">
                        <thead>
                            <tr role="row">
                                <th class="sorting"> № </th>
                                <th class="sorting"><span><?= lang('Admin.global.name') ?></span></th>
                                <th class="sorting"><span><?= lang('Admin.permission.fields.description') ?></span></th>
                                <th colspan="2"><span><?= lang('Admin.global.action') ?></span></th>
                            </tr>
                        </thead>
                        <tbody asp-tmpl-get="tbody">
                            <tr role="row" class="items" data-id="{{id}}">
                                <td class="text-right">{{id}}</td>
                                <td>{{name}}</td>
                                <td>{{title}}</td>
                                <td editable="btn" i-data-id="{{id}}" i-icon="pencil-square" i-i-role="edit" i-data-preload="get-edit|{{id}}"
                                    i-data-tmpl="template" asp-click="modal-confirm" i-data-action="post-query" i-asp-tmpl-set="tbody" title="<?= lang('Admin.menu.edit') ?>"
                                    i-data-height="700px" i-data-title="<?= lang('Admin.menu.edit') ?>" i-data-btntext="<?= lang('HeadLines.save') ?>">
                                </td>
                                <td data-id="{{id}}" editable="btn" data-role="delete" i-callback="post-query"></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="data-info" role="status"></div>
                    <div class="data-paginate"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.card-body -->
    <div class="card-footer" asp-box="btn">
        <button class="btn btn-secondary float-right" asp-click="modal-confirm" data-tmpl="on" data-btntext="<?= lang('Admin.navbar.add') ?>" data-title="<?= lang('Admin.global.dlgCreateTitle') ?>" i-role="create" asp-tmpl-set="tbody" data-action="post-query" data-height="600px">
            <img alt="<?= lang('Admin.navbar.add') ?>" asp-lazy="plus"> <?= lang('Admin.navbar.add') ?>
        </button>
        <template>
            <form id="form-edit-filter" class="p-1" enctype="multipart/form-data">
                <div class="row pt-3">
                    <div class="col-md-6">
                        <label><?= lang('HeadLines.catalog.frm.primaryIdCat') ?></label>
                        <div class="row pl-3">
                            <input type="text" class="col-md-9 form-control mt-1" name="<?= $field['left'] ?>" placeholder="Catalog ID" style="display:inline-block;max-width:93%" readonly="" required>
                            <input type="button" class="col-md-2 toolbtn btn-ace-strong no-cache tree-rebuild tree-all-select" data-only-folder="on" asp-tree-view="slug/linkpages" slug-delimiter="/" title="Add link page" 
                            data-return="id" data-return-more="name" callback="set-left-name-id" style="top:3px;width:32px;height:32px;background-image: url('/images/icons-main/icons/link.svg');"></input>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label><?= lang('HeadLines.catalog.frm.secondaryIdCat') ?></label>
                        <div class="row">
                            <input type="text" class="col-md-9 form-control mt-1" name="<?= $field['right'] ?>" placeholder="Catalog ID" style="display:inline-block;max-width:93%" readonly="" required>
                            <input type="button" class="col-md-2 toolbtn btn-ace-strong no-cache tree-rebuild tree-all-select" data-only-folder="on" asp-tree-view="slug/linkpages" slug-delimiter="/" title="Add link page" 
                            data-return="id" data-return-more="name" callback="set-right-name-id" style="top:3px;width:32px;height:32px;background-image: url('/images/icons-main/icons/link.svg');"></input>
                        </div>
                    </div>
                </div>
                <div class="form-group pt-3">
                    <label><?= lang('Admin.global.name') ?></label>
                    <input type="text" class="form-control mt-1" name="name" placeholder="<?= lang('Admin.global.name') ?>" i-pattern="[a-zA-Z0-9-_]" maxlength="32" required>
                </div>
                <div class="form-group pt-3">
                    <label><?= lang('Admin.permission.fields.description') ?></label>
                    <textarea type="text" class="form-control mt-1" name="title"></textarea>
                </div>
                <div style="display:none"><label>Fill This Field</label><input type="text" name="honeypot" value=""></div>
            </form>
        </template>
    </div>
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
    <script defer src="/control/assets/js/treemodal.<?= $valid_hash ?>.js"></script>
    <script defer src="/assets/js/components/asp.query.post.js"></script>
    <script>
        AspBase.setLeftNameId = function(id, name, elem) {
            let n = Asp.isInt(id);
            if (n !== -1) {
                elem = Asp.closest(elem, 'div', 'input');
                elem.value = n;
                elem = Asp.closest(elem, 'form', 'input[name="name"]');
                if (elem) {
                    let str = elem.value;
                    n = str.indexOf("_");
                    if (n < 1)
                        elem.value = (str == "" ? name : name + "_" + str);
                    else
                        elem.value = name + str.slice(n);
                }
            }
        }
        AspBase.setRightNameId = function(id, name, elem) {
            let n = Asp.isInt(id);
            if (n !== -1) {
                elem = Asp.closest(elem, 'div', 'input');
                elem.value = n;
                elem = Asp.closest(elem, 'form', 'input[name="name"]');
                if (elem) {
                    let str = elem.value;
                    n = str.indexOf("_");
                    if (n < 1)
                        elem.value = (elem.value == "" ? name : elem.value + "_" + name);
                    else
                        elem.value = str.slice(0, ++n) + name;
                }
            }
        }
        AspBase.getEdit = function (elem, id) {
            id = Asp.isInt(id);
            if (id > 0) {
                AspBase.fetch("/edit/" + id)
                .then(function (res) {
                    for (let key in res) {
                        let finp = elem.querySelector("[name=" + key + "]");
                        if (finp) {
                            let v = res[key]
                            finp.value = v;
                            if ((finp.type === "checkbox" || finp.type === "radio") && (v == "1" || v == true)) {
                                finp.checked = true;
                            }
                        }
                        delete res[key];
                    }
                    res = null;
                })
                .catch(function () { Asp.modalClose(elem) })
            } else { Asp.modalClose(elem) }
        }
    </script>
<?= $this->endSection() ?>