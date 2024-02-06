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
                                    <th colspan="3"><span><?= lang('Admin.global.action') ?></span></th>
                                </tr>
                            </thead>
                            <tbody asp-tmpl-get="tbody">
                                <tr role="row" class="items" data-id="{{id}}">
                                    <td class="text-right">{{id}}</td>
                                    <td>{{name}}</td>
                                    <td editable="text" i-name="title" i-maxlength="255">{{title}}</td>
                                    <td data-id="{{id}}" editable="btn" data-role="save" i-callback="post-query"></td>
                                    <td data-id="{{id}}" editable="btn" i-icon="pencil-square" title="<?= lang('Admin.editorTitle') ?>" data-url="local"></td>
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
            <button class="btn btn-secondary float-right" asp-click="modal-confirm" data-tmpl="on" data-btntext="<?= lang('Admin.navbar.add') ?>" 
                data-title="<?= lang('Admin.global.dlgCreateTitle') ?>" i-role="create" asp-tmpl-set="tbody" data-action="post-query" data-height="600px">
                <img alt="<?= lang('Admin.navbar.add') ?>" asp-lazy="plus"> <?= lang('Admin.navbar.add') ?>
            </button>
            <template>
                <form id="form-edit-block" class="p-1" enctype="multipart/form-data">
                    <div class="form-group pt-3">
                        <label><?= lang('Admin.global.name') ?></label>
                        <input type="text" class="form-control mt-1" name="name" placeholder="<?= lang('Admin.global.name') ?>" i-pattern="[a-zA-Z0-9-_]" maxlength="64" required>
                    </div>
                    <div class="form-group pt-3">
                        <label><?= lang('Admin.menu.sidebar.modelsDesc') ?></label>
                        <select name="sheet_id" class="form-select mt-1">
                            <?php if (isset($sheets)) : ?>
                                <?php foreach ($sheets as $item) : ?>
                                    <option value="<?= $item->id ?>" title="<?= $item->name ?>"><?= $item->title ?></option>
                                <?php endforeach; ?>
                            <?php endif ?>
                        </select>
                    </div>
                    <div class="form-group pt-3">
                        <label><?= lang('Admin.permission.fields.description') ?></label>
                        <textarea class="form-control mt-1" name="title" placeholder="<?= lang('Admin.permission.fields.description') ?>" style="height: 150px;"></textarea>
                    </div>
                    <div style="display:none"><label>Fill This Field</label><input type="text" name="honeypot" value=""></div>
                </form>
            </template>
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
    <script defer src="/assets/js/components/asp.query.post.js"></script>
<?= $this->endSection() ?>