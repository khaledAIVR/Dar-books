<template>
    <div class="loader">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">{{ $t('Loading') }}</span>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.loader {
    display: inline-block;
    position: absolute;
}
</style>
