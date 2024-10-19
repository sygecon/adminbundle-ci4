<?= $this->extend('Sygecon\AdminBundle\Views\Layout\dialog') ?>

<?= $this->section('main') ?>
    <div class="container d-flex justify-content-center p-5">
        <div class="card col-12 col-md-5 shadow-sm">
            <div class="card-body">
                <h4 class="card-title mb-5">
                    <img src="/images/icons-main/icons/<?= $head['icon'] ?>.svg" alt="<?= $head['title'] ?>" style="width:2rem"> <?= lang('Admin.changePassword') ?>
                </h4>
                <div class="alert" role="alert"></div>
                <form name="formChangePassword">
                    <?= csrf_field() ?>

                    <?php if ($old_input) : ?>
                    <!-- Old Password -->
                    <div class="mb-2">
                        <input type="password" class="form-control" name="old_password" inputmode="text" autocomplete="current-password" placeholder="<?= lang('Auth.password') ?>" required />
                    </div>
                    <?php endif ?>
                    <!-- Password -->
                    <div class="mb-2">
                        <input type="password" class="form-control" name="password" inputmode="text" autocomplete="new-password" placeholder="<?= lang('Admin.newPassword') ?>" required />
                    </div>
                    <!-- Password Confirm -->
                    <div class="mb-2">
                        <input type="password" class="form-control" name="password_confirm" inputmode="text" autocomplete="new-password" placeholder="<?= lang('Auth.passwordConfirm') ?>" required />
                    </div>
                    <p class="text-center pt-2">
                        <a href="<?= $previous_url ?>" title="<?= lang('Admin.goBack') ?>" class="toolbtn" style="float:left!important">
                            <img src="/images/icons-main/icons/chevron-double-left.svg" alt="<?= lang('Admin.goBack') ?>" style="width:2rem">
                        </a>
                        <a href="<?= url_to('logout') ?>" title="<?= lang('Admin.global.logout') ?>" style="float:right!important">
                            <img src="/images/icons-main/icons/door-open.svg" alt="<?= lang('Admin.global.logout') ?>" style="width:2rem">
                        </a>
                    </p>
                    <div class="d-grid col-12 col-md-8 mx-auto m-3">
                        <button type="button" class="btn btn-primary btn-block"><?= lang('Admin.changePassword') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
    <script src="/assets/js/components/requestDataInJson.js"></script>
    <script>
        const FORMS  = findForms();

        const sendInputsToServer = async function() {
            const DATA = await findDataFromForm(FORMS[0], false, false);
            await requestDataInJson(DATA, "POST", "user/set-password")
            .then(slug => {
                if (typeof slug !== "boolean" && slug !== "") {
                    slug = window.location.protocol + "//" + window.location.host + "/" + slug;
                    window.location.href = slug;
                }
            });
        }
        
        if (typeof FORMS[0] !== "undefined") {
            const BTN = FORMS[0].querySelector("button.btn-primary");
            if (BTN) {
                BTN.addEventListener("click", sendInputsToServer, false);
            }
        }
    </script>
<?= $this->endSection() ?>