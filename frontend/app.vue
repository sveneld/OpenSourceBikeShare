<script>
    import appConfig from '@src/app.config'

    export default {
        page: {
            // All subcomponent titles will be injected into this template.
            titleTemplate(title) {
                title = typeof title === 'function' ? title(this.$store) : title
                return title ? `${title} | ${appConfig.title}` : appConfig.title
            },
        },
    }
</script>

<template>
    <div id="app">
        <!--<h1>Here comes the app</h1>-->
        <!--
        Even when routes use the same component, treat them
        as distinct and create the component again.
        -->
        <router-view :key="$route.fullPath"/>
    </div>
</template>

 This should generally be the only global CSS in the app.
<style lang="scss">
    // Allow element/type selectors, because this is global CSS.
    // stylelint-disable selector-max-type, selector-class-pattern

    // Normalize default styles across browsers,
    @import '~normalize.css/normalize.css';
    // Style loading bar between pages.
    @import '~nprogress/nprogress.css';

    // Design variables and utilities from src/design.
    /*@import '@design';*/
    @import 'frontend/design/index.scss';
    /*@import 'design/bootstrap.min.css';*/

    html, body, #app{
        height: 100%;
        width: 100%;
    }
    body {
        margin: 0;
    }

    // ===
    // Vendor
    // ===

    #nprogress .bar {
        background: $color-link-text;
    }
</style>
