<?php
//<!-- Extend from layout index -->
$this->extend('Sygecon\AdminBundle\Views\Layout\page') 
?>

<!-- Section content -->
<?= $this->section('content') ?>
    <div style="padding:.5rem 1rem;margin-bottom:.5rem;background-color:rgba(0,0,0,.03);border-bottom:1px solid rgba(0,0,0,.125);">
        <h3 style="float:left;font-size:24px;font-weight:400;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            &nbsp;<i style="background-image: url('/images/icons-main/icons/file-earmark.svg');"></i> <?= $data->title ?>
        </h3>
        <div class="page-language d-flex" style="float:right;margin-right:-.625rem;">
            <?= \Sygecon\AdminBundle\Libraries\HTML\Component::renderSelectLang($lang) ?>
        </span>
        </div>
    </div>
    <?php
        if (isset($data) && isset($data->model)) {
            $card = new \Sygecon\AdminBundle\Libraries\HTML\Card($data->model['sheet_name']);
            echo $card->setLangName($lang)
                ->setNodeId((int) $data->node_id)
                ->setTitle(lang('HeadLines.mainSettings'), (string) $data->id)
                ->setValues($data->model['data'])
                ->showWithHtmlEditors();
            echo csrf_field();
        }
    ?>
<?= $this->endSection() ?>

<!-- Section styles -->
<?= $this->section('styles') ?>
    <link href="/control/assets/css/treemodal-<?= $valid_hash ?>.css" rel="stylesheet">
    <link href="/control/assets/css/filemanager-<?= $valid_hash ?>.css" rel="stylesheet">    
    <style>
        .breadcrumb {float: right !important;}
        .breadcrumb .page {margin-top: .25rem !important;}
        .input-group a {color:#007bff;font-weight:400;margin:auto 2px !important;}
    </style>
<?= $this->endSection() ?>

<!-- Section content -->
<?= $this->section('scripts') ?>
<script defer src="/control/assets/js/treemodal.<?= $valid_hash ?>.js"></script>
<script defer src="/control/assets/js/filemanager.<?= $valid_hash ?>.js"></script>
<script defer src="/control/assets/js/tinymce.<?= $valid_hash ?>.js"></script>
<script defer src="/assets/js/components/asp.save.form.js"></script>
<script defer src="/assets/js/components/asp.select.language.js"></script>
<?= $this->endSection() ?>