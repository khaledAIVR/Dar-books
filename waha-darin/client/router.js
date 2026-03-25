import Vue from 'vue'
import Router from 'vue-router'
import { scrollBehavior } from '~/utils'

Vue.use(Router)

const page = (path) => () =>
    import(`~/pages/${path}`).then((m) => m.default || m)

const routes = [
    { path: '/', name: 'home', component: page('home.vue') },
    {
        path: '/categories',
        name: 'categories',
        component: page('categories.vue')
    },
    {
        path: '/categories/:category',
        name: 'category',
        component: page('category.vue')
    },
    { path: '/authors', name: 'authors', component: page('authors.vue') },
    { path: '/authors/:author', name: 'author', component: page('author.vue') },
    {
        path: '/my-collection',
        name: 'collection',
        component: page('collection.vue')
    },
    { path: '/book/:slug', name: 'book', component: page('SingleBook.vue') },
    { path: '/cart', name: 'cart', component: page('cart.vue') },
    { path: '/favourite', name: 'favourite', component: page('favourite.vue') },
    { path: '/orders', name: 'orders', component: page('orders.vue') },
    { path: '/pricing', name: 'pricing', component: page('pricing.vue') },
    { path: '/faq', name: 'faq', component: page('faq.vue') },
    {
        path: '/checkout/:planId',
        name: 'checkout',
        component: page('checkout.vue')
    },
    { path: '/borrow', name: 'borrow', component: page('CheckoutBooks.vue') },

    { path: '/login', name: 'login', component: page('auth/login.vue') },
    {
        path: '/register',
        name: 'register',
        component: page('auth/register.vue')
    },
    {
        path: '/password/reset',
        name: 'password.request',
        component: page('auth/password/email.vue')
    },
    {
        path: '/password/reset/:token',
        name: 'password.reset',
        component: page('auth/password/reset.vue')
    },
    {
        path: '/email/verify/:id/:hash',
        name: 'verification.verify',
        component: page('auth/verification/verify.vue')
    },
    {
        path: '/email/resend',
        name: 'verification.resend',
        component: page('auth/verification/resend.vue')
    },

    {
        path: '/settings',
        component: page('settings/index.vue'),
        children: [
            { path: '', redirect: { name: 'settings.profile' } },
            {
                path: 'profile',
                name: 'settings.profile',
                component: page('settings/profile.vue')
            },
            {
                path: 'password',
                name: 'settings.password',
                component: page('settings/password.vue')
            },
            {
                path: 'authors',
                name: 'settings.authors',
                component: page('settings/authors.vue')
            },
            {
                path: 'categories',
                name: 'settings.categories',
                component: page('settings/categories.vue')
            }
        ]
    }
]

export function createRouter() {
    return new Router({
        routes,
        scrollBehavior,
        mode: 'history'
    })
}
