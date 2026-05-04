<?php
/*
 * Override of vendor/tcg/voyager sidebar.
 * Non-super-admins only see borrowing/subscription-related items.
 * Super-admins see everything.
 */
$isSuperAdmin = Auth::check()
    && method_exists(Auth::user(), 'isSuperAdmin')
    && Auth::user()->isSuperAdmin();

// Routes only super-admins may see in the sidebar
$superAdminRoutes = [
    'voyager.users.index',
    'voyager.roles.index',
    'voyager.plans.index',
    'voyager.bank_account_details.index',
    'voyager.book-import.page',
    'voyager.database.index',
    'voyager.bread.index',
    'voyager.compass.index',
    'voyager.hooks',
    'voyager.menus.index',
    'voyager.media.index',
    'voyager.pages.index',
    'voyager.posts.index',
    'voyager.settings.index',
];

$rawMenu = menu('admin', '_json');

if (!$isSuperAdmin) {
    $items = json_decode($rawMenu, true) ?? [];
    $items = array_values(array_filter($items, function ($item) use ($superAdminRoutes) {
        $route = $item['route'] ?? '';
        return !in_array($route, $superAdminRoutes, true);
    }));
    $rawMenu = json_encode($items);
}
?>
<div class="side-menu sidebar-inverse">
    <nav class="navbar navbar-default" role="navigation">
        <div class="side-menu-container">
            <div class="navbar-header">
                <a class="navbar-brand" href="{{ route('voyager.dashboard') }}">
                    <div class="logo-icon-container">
                        <?php $admin_logo_img = Voyager::setting('admin.icon_image', ''); ?>
                        @if($admin_logo_img == '')
                            <img src="{{ voyager_asset('images/logo-icon-light.png') }}" alt="Logo Icon">
                        @else
                            <img src="{{ Voyager::image($admin_logo_img) }}" alt="Logo Icon">
                        @endif
                    </div>
                    <div class="title">{{Voyager::setting('admin.title', 'VOYAGER')}}</div>
                </a>
            </div><!-- .navbar-header -->

            <div class="panel widget center bgimage"
                 style="background-image:url({{ Voyager::image( Voyager::setting('admin.bg_image'), voyager_asset('images/bg.jpg') ) }}); background-size: cover; background-position: 0px;">
                <div class="dimmer"></div>
                <div class="panel-content">
                    <img src="{{ $user_avatar }}" class="avatar" alt="{{ Auth::user()->name }} avatar">
                    <h4>{{ ucwords(Auth::user()->name) }}</h4>
                    <p>{{ Auth::user()->email }}</p>

                    <a href="{{ route('voyager.profile') }}" class="btn btn-primary">{{ __('voyager::generic.profile') }}</a>
                    <div style="clear:both"></div>
                </div>
            </div>

        </div>
        <div id="adminmenu">
            <admin-menu :items="{{ $rawMenu }}"></admin-menu>
        </div>
    </nav>
</div>
