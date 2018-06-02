<script>
    import appConfig from '@src/app.config'
    import Layout from '@layouts/main'
    import GoogleMap from "../../components/google-map.vue";

    export default {
        page: {
            title: 'Stand Detail',
            meta: [{ name: 'description', content: appConfig.description }],
        },
        components: {
            Layout
        },
        name: 'TODO',
        data: () => ({
        }),
        props: {
            uuid: {
                type: String,
                required: true,
            },
        }
    }
</script>

<template>
  <div class="page-container">
    <md-app>
      <md-app-toolbar class="md-primary">
        <md-button class="md-icon-button">
          <md-icon>back</md-icon>
        </md-button>
        <span class="md-title">Stand name</span>
      </md-app-toolbar>

      <md-app-content>
          {{uuid}}
      </md-app-content>
    </md-app>
  </div>
</template>

<style lang="scss" scoped>
  .md-app {
    border: 1px solid rgba(#000, .12);
  }

  .page-container, .md-app {
      height: 100%;
  }

  .md-app-content {
      padding: 0;
      height: 100%;
  }
</style>

