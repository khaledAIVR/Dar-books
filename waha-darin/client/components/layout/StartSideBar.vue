<template>
    <aside>
        <div class="sidebar-block start-menu">
            <p>{{ $t('Browse Books') }}</p>
            <ul class="list-group">
                <li class="list-group-item">
                    <nuxt-link :to="{ name: 'home' }" tabindex="1">
                        <icon name="fire" :title="$t('Most popular')" />
                        {{ $t('Most popular') }}
                    </nuxt-link>
                </li>
                <!--<li class="list-group-item">
          <nuxt-link
            :to="{ name: 'collection' }"
            tabindex="2"
          >
            <icon
              name="book"
              :title="$t('Books for you')"
            />
            {{ $t('Books for you') }}
          </nuxt-link>
        </li>-->
                <li class="list-group-item">
                    <nuxt-link :to="{ name: 'categories' }" tabindex="3">
                        <icon
                            name="grid"
                            :title="$t('Books Categories')"
                            color="dark"
                        />
                        {{ $t('Books Categories') }}
                    </nuxt-link>
                </li>
                <li class="list-group-item ">
                    <nuxt-link :to="{ name: 'authors' }" tabindex="4">
                        <icon name="pen" :title="$t('Authors')" />
                        {{ $t('Authors') }}
                    </nuxt-link>
                </li>
            </ul>
            <div role="separator" class="dropdown-divider" />
        </div>
        <div class="sidebar-block start-menu">
            <p>{{ $t('Your library') }}</p>
            <ul class="list-group">
                <li class="list-group-item ">
                    <nuxt-link :to="{ name: 'cart' }">
                        <icon name="cart" :title="$t('My Cart')" />
                        {{ $t('My Cart') }}
                    </nuxt-link>
                </li>
                <li class="list-group-item ">
                    <nuxt-link :to="{ name: 'orders' }">
                        <icon name="books" :title="$t('My Orders')" />
                        {{ $t('My Orders') }}
                    </nuxt-link>
                </li>
                <li class="list-group-item">
                    <nuxt-link :to="{ name: 'favourite' }">
                        <icon name="heart" :title="$t('Favorite books')" />
                        {{ $t('Favorite books') }}
                    </nuxt-link>
                </li>

                <li class="list-group-item">
                    <nuxt-link :to="{ name: 'settings.categories' }">
                        <icon name="grid" :title="$t('My Categories')" />
                        {{ $t('My Categories') }}
                    </nuxt-link>
                </li>
            </ul>
            <div role="separator" class="dropdown-divider" />
        </div>
        <div class="sidebar-block start-menu">
            <p>{{ $t('About Waha Dar-in') }}</p>
            <ul class="list-group">
                <li class="list-group-item">
                    <a href="#">
                        {{ $t('About Us') }}
                    </a>
                </li>
                <li class="list-group-item">
                    <nuxt-link :to="{ name: 'pricing' }">
                        {{ $t('Pricing') }}
                    </nuxt-link>
                </li>
                <li class="list-group-item">
                    <nuxt-link :to="{ name: 'faq' }">
                        {{ $t('FAQ') }}
                    </nuxt-link>
                </li>
                <li class="list-group-item">
                    <a href="#">
                        {{ $t('Terms and conditions') }}
                    </a>
                </li>
            </ul>
            <div role="separator" class="dropdown-divider" />
        </div>
    </aside>
</template>

<script>
export default {
    name: 'StartSideBar'
}
</script>

<style lang="scss" scoped></style>
