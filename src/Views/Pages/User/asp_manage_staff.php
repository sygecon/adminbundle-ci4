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
        <div class="card-footer"<?php if (isset($groups)) echo ' asp-box="btn"'; ?>>
            <?php if (isset($groups) && $groups): ?>
            <button class="btn btn-secondary float-right" asp-click="modal-confirm" data-tmpl="on" data-btntext="<?= lang('Admin.navbar.add') ?>" 
                data-title="<?= lang('Admin.global.dlgCreateTitle') ?>" i-role="create" asp-tmpl-set="tbody" data-action="post-query" data-height="500px">
                <img alt="<?= lang('Admin.navbar.add') ?>" asp-lazy="plus"> <?= lang('Admin.navbar.add') ?>&nbsp;
            </button>
            <template>
                <form id="form-add-user" class="p-1" enctype="multipart/form-data">
                    <?php if (isset($date_create) && $date_create): ?>
                    <input type="hidden" name="created_at" value="<?= esc($date_create) ?>"/>
                    <?php endif ?>
                    <div class="form-row mt-2 mb-2">
                        <!-- Username -->
                        <div class="form-group col-md-6">
                            <input type="text" class="form-control mt-1" name="username" inputmode="text" autocomplete="username" placeholder="<?= lang('Auth.username') ?>" value="<?= old('username') ?>" i-pattern="[a-zA-Z0-9-_]" maxlength="30" required />
                        </div>
                        <!-- Email -->
                        <div class="form-group col-md-6">
                            <input type="email" class="form-control mt-1" name="email" inputmode="email" autocomplete="email" placeholder="<?= lang('Auth.email') ?>" value="<?= old('email') ?>" required />
                        </div>
                    </div>
                    <div class="form-row mb-2">
                        <!-- Password -->
                        <div class="form-group col-md-6">
                            <input type="password" class="form-control mt-1" name="password" inputmode="text" autocomplete="new-password" placeholder="<?= lang('Auth.password') ?>" required />
                        </div>
                        <!-- Password (Again) -->
                        <div class="form-group col-md-6">
                            <input type="password" class="form-control mt-1" name="password_confirm" inputmode="text" autocomplete="new-password" placeholder="<?= lang('Auth.passwordConfirm') ?>" required />
                        </div>
                    </div>
                    <div class="form-row mt-2 mb-2">
                        <!-- phone -->
                        <div class="form-group col-md-5">
                            <label class="form-label w-100"><?= lang('Admin.user.formPhone') ?>
                                <input type="tel" class="form-control mt-1" name="phone" inputmode="text" autocomplete="phone" placeholder="+7 (123) 456-78-90" value="" i-pattern="[a-zA-Z0-9-_]" maxlength="24" />
                            </label>
                        </div>
                        <!-- Group -->
                        <div class="form-group col-md-7 mb-2">
                            <label class="form-label w-100"><?= lang('Admin.group.fields.name') ?>
                                <select type="select" name="group" value="4" class="form-select mt-1">
                                    <?php foreach ($groups as $key => $item): ?>
                                    <option value="<?= $key ?>" title="<?= esc($item[1]) ?>"><?= esc($item[0]) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </label>
                        </div>
                    </div>
                    <div style="display:none"><label>Fill This Field</label><input type="text" name="honeypot" value=""></div>
                </form>
            </template>
            <?php endif ?>
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