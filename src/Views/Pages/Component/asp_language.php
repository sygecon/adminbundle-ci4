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
					<div asp-box="fetchApi|lang/list" class="data-tables-wrapper no-footer">
						<div class="data-length"></div>
						<div class="data-filter">
							<label><?= lang('Admin.table.filter.label') ?>: 
								<input type="search" placeholder="<?= lang('Admin.table.filter.placeholder') ?>">
							</label>
						</div>
                        <table class="table table-striped table-hover data-tables no-footer sortable" role="grid">
                            <thead>
                                <tr role="row">
                                    <th> <i style="margin-left:.5em;background-image: url(/images/icons-main/icons/grip-vertical.svg);"></i></th>
                                    <th class="text-center">ID</th>
                                    <th><span><?= lang('Admin.language.fields.name') ?></span></th>
                                    <th><span><?= lang('Admin.language.fields.description') ?></span></th>
                                    <th><span><?= lang('Admin.language.fields.icon') ?></span></th>
                                    <th colspan="2"><span><?= lang('Admin.global.action') ?></span></th>
                                </tr>
                            </thead>
                            <tbody asp-tmpl-get="tbody">
                                <tr role="row" class="items" data-id="{{id}}">
                                    <td></td>
                                    <td class="text-center">{{id}}</td>
                                    <td>{{name}}</td>
                                    <td editable="text" i-name="title" i-maxlength="31">{{title}}</td>
                                    <td style="vertical-align: middle; margin-left: auto; margin-right: auto;">
                                        <input type="text" name="icon"  data-url="faf" class="form-control mt-1 icon-picker"
                                            value="{{icon}}" placeholder="<?= lang('Admin.language.fields.icon') ?>">
                                    </td>
                                    <td data-id="{{id}}" editable="btn" data-role="save" i-callback="post-query"></td>
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
            <button class="btn btn-secondary float-right" asp-click="modal-confirm" data-tmpl="on" data-btntext="<?= lang('Admin.language.add') ?>" 
                data-title="<?= lang('Admin.language.title_add') ?>" i-role="create" asp-tmpl-set="tbody" data-action="post-query" data-height="700px">
                <img alt="<?= lang('Admin.language.add') ?>" asp-lazy="plus"> <?= lang('Admin.language.add') ?>
            </button>
            <template>
                <form id="form-edit-permission" class="p-1" enctype="multipart/form-data">
                    <div class="form-group pt-3">
                        <label><?= lang('Admin.language.fields.name') ?></label>
                        <input type="text" class="form-control mt-1" name="name" placeholder="<?= lang('Admin.language.fields.plc_name') ?>" i-pattern="[a-zA-Z]" maxlength="7" required>
                    </div>
                    <div class="form-group pt-3">
                        <label><?= lang('Admin.language.fields.description') ?></label>
                        <input type="text" class="form-control mt-1" name="title" placeholder="<?= lang('Admin.language.fields.plc_description') ?>" maxlength="31" required>
                    </div>
                    <div class="form-group pt-3">
                        <label><?= lang('Admin.language.fields.icon') ?></label>
                        <input type="text" name="icon"  data-url="faf" class="form-control mt-1 icon-picker"
                            value="" placeholder="<?= lang('Admin.language.fields.icon') ?>">
                    </div>
                    <div style="display:none"><label>Fill This Field</label><input type="text" name="honeypot" value=""></div>
                </form>
            </template>
        </div>
        <!-- /.card-footer-->
    </div>
    <?= csrf_field() ?>
    <!-- /.card-->
<?= $this->endSection() ?>

<!-- Section styles -->
<?= $this->section('styles') ?>
    
<?= $this->endSection() ?>

<!-- Section content -->
<?= $this->section('scripts') ?>
    <script defer src="/control/assets/js/iconpicker-<?= $valid_hash ?>.js"></script>
    <script defer src="/assets/js/components/asp.query.post.js"></script>
<?= $this->endSection() ?>