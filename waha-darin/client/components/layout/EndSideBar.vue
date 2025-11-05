<template>
    <aside class="end-aside">
        <div class="sidebar-block">
            <book-list-small />
        </div>
        <div class="sidebar-block">
            <authors-list-small />
        </div>
    </aside>
</template>

<script>
export default {
    name: 'EndSideBar',
    components: {
        'book-list-small': () => import('~/components/layout/BookListSmall'),
        'authors-list-small': () =>
            import('~/components/layout/AuthorsListSmall')
    }
}
</script>

<style scoped></style>
