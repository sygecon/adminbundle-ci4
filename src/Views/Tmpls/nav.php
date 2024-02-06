    <main id="app" class="sidebar-close">
        <header>
            <div class="header--inner">
                <div class="box box-logo">
                    <a href="/<?= SLUG_ADMIN ?>"><img src="/images/control/AdminKa.png" alt="Logo"></a><span>AdminKa</span>
                </div>
                <!-- /End Brand Logo -->
                <div class="box-btn-nav">
                    <button class="btn-toggle"><span></span></button>
                </div>
                <div class="box box-nav-user">
                    <div class="navbar" asp-box="fetchGet|nav/admin" static="1">
                        <div class="nav-item">
                            <img class="img-circle elevation-2">
                            <a asp-text="user-fullname" class="nav-link nav-items-hide" href="javascript:void(0)" asp-click="dropdownClick"></a>
                        </div>
                        <!-- Dropdown -->
                        <div asp-tmpl-get="navDropDown" asp-key="nav-menu" class="dropdown div-hide">
                            <a class="dropdown-item" href="{{src}}" title="{{title}}">{{name}}</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <nav>
            <div id="sidebar" class="nav--sidebar"></div>
        </nav>
        <?= session()->getFlashdata('error') ?>