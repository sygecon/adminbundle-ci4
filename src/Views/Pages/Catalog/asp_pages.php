<?php
//<!-- Extend from layout index -->
$this->extend('Sygecon\AdminBundle\Views\Layout\page')
?>

<!-- Section content -->
<?= $this->section('content') ?>
<div class="card" asp-box="btn">
    <div class="card-header ">
        <h3 class="card-title"><i asp-lazy="<?= $head['icon'] ?>"></i> <?= $head['title'] ?></h3>
        <div class="card-tools d-flex page-language" asp-box="btn">
            <?php if (isset($lang_name)) : ?>
            <?= \Sygecon\AdminBundle\Libraries\HTML\Component::renderSelectLang($lang_name) ?>
            <?php endif ?>
            <button class="btn btn-tool" asp-click="btn-collapse" static="1"></button>
        </div>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <div class="list-wrap">
            <div asp-box="fetch" class="data-lists-wrapper">
                <div class="box">
                    <div class="data-length"></div>
                    <div class="data-filter">
                        <label><?= lang('Admin.table.filter.label') ?>:
                            <input type="search" placeholder="<?= lang('Admin.table.filter.placeholder') ?>">
                        </label>
                    </div>
                </div>
                <ul class="todo-list data-list sortable" asp-tmpl-get="todolist" data-filter="name">
                    <li data-id="{{id}}"><a class="type" href="{{href}}" title="{{link}}"><i class="{{type}}"></i></a>
                        <span style="max-width:100%;"><i data-id="{{id}}" i-name="active" editable="btn" data-role="btncheck" i-callback="post-query" i-change-style="1" i-style="margin-top:-4px;">{{active}}</i></span>
                        <span>
                            <input type="button" class="btn" data-id="{{id}}" 
                                data-type="image_pageicon" data-name="icon" 
                                title="<?= lang('HeadLines.catalog.frm.imageAnnounce') ?>" 
                                style="display:inline;width:34px;height:34px;color:inherit;background-size:32px 32px;background-repeat:no-repeat;background-image:url('{{icon}}');"
                                onclick="AspFM.open()" callback="set-page-icon">
                        </span>
                        <span class="name" title="{{description}}">
                            <a href="<?= $link_page ?>{{node_id}}">{{title}}</a></span>
                        <span class="tools">
                            <i editable="btn" title="<?= lang('HeadLines.catalog.frm.setingTitle') ?>" 
                                i-data-id="{{id}}" i-icon="pencil-square" i-i-role="edit" i-data-preload="get-edit|{{id}}" 
                                i-data-tmpl="template" i-asp-click="modal-confirm" i-data-action="post-query" title="<?= lang('Admin.menu.edit') ?>" 
                                i-asp-tmpl-set="todolist" i-data-title="<?= lang('HeadLines.catalog.frm.setingTitle') ?>" 
                                i-data-btntext="<?= lang('HeadLines.save') ?>">
                            </i>
                            <i editable="btn" title="<?= lang('HeadLines.catalog.frm.addChildPage') ?>" i-data-id="{{node_id}}" 
                                i-icon="file-earmark-plus" i-i-role="create" i-data-tmpl="template" i-asp-click="modal-confirm"
                                i-data-action="post-query|{{node_id}}" i-asp-tmpl-set="todolist" 
                                i-data-title="<?= lang('HeadLines.catalog.frm.setingTitle') ?>" 
                                i-data-btntext="<?= lang('Admin.navbar.add') ?>">
                            </i>
                            <i editable="btn" data-role="delete" i-data-id="{{node_id}}" i-callback="post-query"></i>
                        </span>
                    </li>
                </ul>
                <div class="data-info" role="status"></div>
                <div class="data-paginate"></div>
            </div>
        </div>
    </div>
    <!-- /.card-body -->
    <div class="card-footer" asp-box="btn">
        <button class="btn btn-outline-secondary float-left" onclick="btnSitemapCreate()" title="<?= lang('Admin.titleBtnSitemap') ?>">
            <img alt="<?= lang('Admin.titleBtnSitemap') ?>" asp-lazy="diagram-3"> <?= lang('Admin.titleBtnSitemap') ?>
        </button>
        <button class="btn btn-outline-primary float-right" asp-click="modal-confirm" data-tmpl="on" data-btntext="<?= lang('Admin.navbar.add') ?>" data-title="<?= lang('HeadLines.catalog.frm.setingTitle') ?>" i-role="create" asp-tmpl-set="todolist" data-action="post-query">
            <img alt="<?= lang('Admin.navbar.add') ?>" asp-lazy="plus"> <?= lang('HeadLines.catalog.btnAdd') ?>
        </button>
        <template>
            <form class="p-1" enctype="multipart/form-data">
                <input type="hidden" name="href" value="javascript:void(0)">
                <input type="hidden" name="active" value="false">
                <input type="hidden" name="icon" value="">
                <input type="hidden" name="link" value="">
                <input type="hidden" name="type" value="file">
                <div class="form-row pt-1">
                    <div class="form-group col-md-9">
                        <label title="<?= lang('HeadLines.catalog.frm.hint.name') ?>"><?= lang('Admin.global.name') ?></label>
                        <input type="text" class="form-control mt-1" name="title" placeholder="<?= lang('Admin.global.name') ?>" maxlength="127" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label title="<?= lang('HeadLines.catalog.frm.hint.dataCreate') ?>"><?= lang('HeadLines.catalog.frm.datePublication') ?></label>
                        <input title="<?= lang('HeadLines.catalog.frm.datePublication') ?>" type="datetime" name="updated_at" value="" class="form-control mt-1">
                    </div>
                </div>
                <div class="form-group pt-1">
                    <label title="<?= lang('HeadLines.catalog.frm.hint.slug') ?>"><?= lang('HeadLines.catalog.frm.slug') ?></label>
                    <input type="text" class="form-control mt-1" name="name" placeholder="<?= lang('HeadLines.catalog.frm.slug') ?>" i-pattern="[a-zA-Z0-9-_]" maxlength="127">
                </div>
                <div class="form-group pt-1">
                    <label title="<?= lang('HeadLines.catalog.frm.hint.h1') ?>"><?= lang('HeadLines.field') ?>&nbsp;H1</label>
                    <input type="text" class="form-control mt-1" name="description" placeholder="<?= lang('HeadLines.field') ?>&nbsp;H1" maxlength="255">
                </div>
                <div class="form-group pt-1">
                    <label title="<?= lang('HeadLines.catalog.frm.hint.metaTitle') ?>"><?= lang('HeadLines.field') ?>&nbsp;meta TITLE</label>
                    <input type="text" class="form-control mt-1" name="meta_title" placeholder="Meta TITLE" maxlength="255">
                </div>
                <div class="form-group pt-1">
                    <label title="<?= lang('HeadLines.catalog.frm.hint.metaKeywords') ?>"><?= lang('HeadLines.field') ?>&nbsp;meta KEYWORDS</label>
                    <input type="text" class="form-control mt-1" name="meta_keywords" placeholder="Meta KEYWORDS" maxlength="255" required>
                </div>
                <div class="form-group pt-1">
                    <label title="<?= lang('HeadLines.catalog.frm.hint.metaDescriptions') ?>"><?= lang('HeadLines.field') ?>&nbsp;meta DESCRIPTIONS</label>
                    <textarea class="form-control mt-1" name="meta_description" placeholder="Meta DESCRIPTIONS" style="height: 60px;"></textarea>
                </div>
                <div class="form-row pt-1">
                    <div class="form-group col-md-6">
                        <label title="<?= lang('HeadLines.catalog.frm.hint.dataType') ?>"><?= lang('HeadLines.catalog.frm.pageLayout') ?></label>
                        <select name="layout_id" class="form-select mt-1">
                            <?php if (isset($layouts)) : ?>
                                <?php foreach ($layouts as $item) : ?>
                                    <option value="<?= $item->id ?>" title="<?= $item->name ?>"><?= $item->title ?></option>
                                <?php endforeach; ?>
                            <?php endif ?>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="form-check-label pt-3"><?= lang('HeadLines.catalog.frm.searchDeny') ?>
                            <input type="checkbox" name="search_deny" class="form-check-input ml-1">
                        </label>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="form-check-label pt-3"><?= lang('HeadLines.catalog.frm.navDeny') ?>
                            <input type="checkbox" name="menu_deny" class="form-check-input ml-1">
                        </label>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="form-check-label pt-3"><?= lang('HeadLines.catalog.frm.preventIndexing') ?>
                            <input type="checkbox" name="robots_deny" title="<?= lang('HeadLines.catalog.frm.hint.preventIndexing') ?>" class="form-check-input ml-1">
                        </label>
                    </div>
                </div>
                <div style="display:none"><label>Fill This Field</label><input type="text" name="honeypot" value=""></div>
            </form>
        </template>
    </div>
    <?= csrf_field() ?>
    <!-- /.card-footer-->
</div>
<!-- /.card-->
<?= $this->endSection() ?>

<!-- Section styles -->
<?= $this->section('styles') ?>
<link href="/control/assets/css/filemanager-<?= $valid_hash ?>.css" rel="stylesheet">
<style>
    .todo-list {
        overflow-y: hidden;
    }

    ul>li>.tools {
        margin-top: -3px;
    }

    ul>li>.tools i {
        margin-top: -3px;
        max-width: 32px
    }

    .uncheck>.name a {
        color: #a8a8a8;
    }

    .breadcrumb {
        float: right !important;
    }

    .breadcrumb .page {
        margin-top: .25rem !important;
    }

    .breadcrumb .active>a {
        cursor: default;
        color: #a8a8a8;
    }
</style>
<?= $this->endSection() ?>

<!-- Section content -->
<?= $this->section('scripts') ?>
<script defer src="/control/assets/js/filemanager.<?= $valid_hash ?>.js"></script>
<script defer src="/assets/js/components/asp.get.edit.form.js"></script>
<script defer src="/assets/js/components/asp.query.post.js"></script>
<script defer src="/assets/js/components/asp.select.language.js"></script>
<?= $this->endSection() ?>