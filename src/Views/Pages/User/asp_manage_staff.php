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
            <div class="table-wrap">
				<div class="table-responsive">
					<div asp-box="fetch" class="data-tables-wrapper no-footer">
						<div class="data-length"></div>
						<div class="data-filter">
							<label><?= lang('Admin.table.filter.label') ?>: 
								<input type="search" placeholder="<?= lang('Admin.table.filter.placeholder') ?>">
							</label>
						</div>
                        <table data-name="data[]" class="table table-striped table-hover data-tables no-footer" role="grid">
                            <thead>
                                <tr role="row" >
                                    <th class="sorting">V</th>
                                    <th class="sorting"><span><?= lang('Auth.username') ?></span></th>
                                    <th class="sorting"><span><?= lang('Auth.email') ?></span></th>
                                    <th class="sorting"><span><?= lang('Admin.group.fields.name') ?></span></th>
                                    <th class="sorting"><span><?= lang('Admin.user.fields.join') ?></span></th>
                                    <th colspan="2"><span><?= lang('Admin.global.action') ?></span></th>
                                </tr>
                            </thead>
                            <tbody asp-tmpl-get="tbody">
                                <tr role="row" class="items">
                                    <td data-id="{{id}}" i-name="active" editable="btn" data-role="btncheck" i-change-style="1" i-callback="post-query">{{active}}</td>
                                    <td>{{username}}</td>
                                    <td>{{email}}</td>
                                    <td title="{{group_desc}}">{{group}}</td>
                                    <td class="text-right">{{created_at}}</td>
                                    <td data-id="{{id}}" editable="btn" i-icon="pencil-square" title="<?= lang('Admin.menu.edit') ?>" data-url="local"></td>
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
    <!-- <script src="/control/assets/js/treemodal.929de26f0edf9a6e31e5.js"></script> -->
    <script defer src="/assets/js/components/asp.query.post.js"></script>
<?= $this->endSection() ?>