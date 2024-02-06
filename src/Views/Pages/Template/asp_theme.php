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
                                    <th class="sorting"><span>V</span></th>
                                    <th><span><?= lang('Admin.global.logo') ?></span></th>
                                    <th class="sorting"><span><?= lang('Admin.global.name') ?></span></th>
                                    <th class="sorting"><span><?= lang('Admin.permission.fields.description') ?></span></th>
                                    <th colspan="3"><span><?= lang('Admin.global.action') ?></span></th>
                                </tr>
                            </thead>
                            <tbody asp-tmpl-get="tbody">
                                <tr role="row" class="items" data-id="{{id}}">
                                    <td editable="radio" i-name="active" i-callback="set-theme-active|{{id}}" title="<?= lang('Auth.current') ?>">{{active}}</td>
                                    <td><img src="/api/photo/theme/{{path}}/logo" style="height:48px;max-width:120px;"></td>
                                    <td>{{name}}</td>
                                    <td editable="text" i-name="title" i-maxlength="255">{{title}}</td>
                                    <td data-id="{{id}}" editable="btn" data-role="save" i-callback="post-query"></td>
                                    <td data-id="{{id}}" editable="btn" i-icon="pencil-square" title="<?= lang('Admin.editTitle') ?>" data-url="local"></td>
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
            <button class="btn btn-secondary float-right" asp-click="modal-confirm" data-tmpl="on" data-btntext="<?= lang('Admin.navbar.add') ?>"  data-preload="get-themes|0" 
                data-title="<?= lang('Admin.global.dlgCreateTitle') ?>" i-role="create" asp-tmpl-set="tbody" data-action="post-query" data-height="600px">
                <img alt="<?= lang('Admin.navbar.add') ?>" asp-lazy="plus"> <?= lang('Admin.navbar.add') ?>
            </button>
            <template>
                <form id="form-edit-themes" class="p-1" enctype="multipart/form-data">
                    <input type="hidden" name="active" value="0"/> 
                    <div class="form-group pt-3" id="form_select_or_input">
                        <label class="form-label w-100" ><?= lang('Admin.global.name') ?></label>
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
    <script defer src="/assets/js/components/asp.edit.themes.js"></script>
<?= $this->endSection() ?>