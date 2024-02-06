<?= $this->extend('Sygecon\AdminBundle\Views\Layout\dialog') ?>

<?= $this->section('title') ?><?= lang('Admin.changePassword') ?> <?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="container d-flex justify-content-center p-5">
        <div class="card col-12 col-md-5 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-5"><?= lang('Admin.changePassword') ?></h5>

                <?php if (session('error') !== null) : ?>
                    <div class="alert alert-danger" role="alert"><?= session('error') ?></div>
                <?php elseif (session('errors') !== null) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php if (is_array(session('errors'))) : ?>
                            <?php foreach (session('errors') as $error) : ?>
                                <?= $error ?>
                                <br>
                            <?php endforeach ?>
                        <?php else : ?>
                            <?= session('errors') ?>
                        <?php endif ?>
                    </div>
                <?php endif ?>

                <?php if (session('message') !== null) : ?>
                <div class="alert alert-success" role="alert"><?= session('message') ?></div>
                <?php endif ?>

                <form action="<?= url_to('user/change-password') ?>" method="POST">
                    <?= csrf_field() ?>
                    <!-- Old Password -->
                    <div class="mb-2">
                        <input type="password" class="form-control" name="old_password" inputmode="text" autocomplete="current-password" placeholder="<?= lang('Auth.password') ?>" required />
                    </div>
                    <!-- Password -->
                    <div class="mb-2">
                        <input type="password" class="form-control" name="password" inputmode="text" autocomplete="new-password" placeholder="<?= lang('Admin.newPassword') ?>" required />
                    </div>
                    <!-- Password Confirm -->
                    <div class="mb-2">
                        <input type="password" class="form-control" name="password_confirm" inputmode="text" autocomplete="new-password" placeholder="<?= lang('Auth.passwordConfirm') ?>" required />
                    </div>

                    <p class="text-center"><a href="<?= url_to('logout') ?>"><?= lang('Admin.global.logout') ?></a></p>
                    <div class="d-grid col-12 col-md-8 mx-auto m-3">
                        <button type="button" class="btn btn-primary btn-block" onclick="setPassword()"><?= lang('Admin.changePassword') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
    <script>
        var setPassword = function () {
            let form = event.target.form,
                data = Object.assign(...Array.from(
                    new FormData(form).entries(), ([x,y]) => ( {[x]:y} )
                )),
                error = true;
            if (typeof data.password !== "undefined" && typeof data.old_password !== "undefined" && typeof data.password_confirm !== "undefined") {
                if (data.password !== "" && data.old_password !== "" && data.password_confirm !== "") {
                    if (data.password !== data.old_password && data.password_confirm == data.password) {
                        error = false;
                        fetch(form.action, {
                            method: "post",
                            headers: {
                                "Content-Type": "application/json, text/plain",
                                "X-Requested-With": "XMLHttpRequest"
                            },
                            body: JSON.stringify(data)
                        })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (json) {
                            form.reset();
                            if (json.url !== "") {
                                if (json.message !== "") alert(json.message);
                                return location.replace(json.url);
                            }
                            if (json.error !== "") alert(json.error);
                        })
                        .catch (function (error) {
                            form.reset();
                            alert("Error! The password has not been changed.");
                        });
                        // console.log(data);
                    }
                }
            }
            form.reset();
            if (error === true) { alert("Error! The password has not been changed."); }
        }
    </script>
<?= $this->endSection() ?>