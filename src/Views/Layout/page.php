<?php if (session('previous_url')) { unset($_SESSION['previous_url']); } ?>
<?= $this->include('Sygecon\AdminBundle\Views\Tmpls\header') ?>
    <?= $this->include('Sygecon\AdminBundle\Views\Tmpls\nav') ?>
    <section id="content" class="box-columns">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <h1><?= $head['h1'] ?></h1>
                    </div>
                    <div class="col-sm-6" style="padding-right:0">
                        <?php if (isset($head['breadcrumb'])): ?>
                            <?= $head['breadcrumb'] ?>
                        <?php else: ?>
                            <?= $this->include('Sygecon\AdminBundle\Views\Tmpls\breadcrumb') ?>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="content">
            <?= $this->renderSection('content') ?>
        </div>
    </section>
<?= $this->include('Sygecon\AdminBundle\Views\Tmpls\footer') ?>    