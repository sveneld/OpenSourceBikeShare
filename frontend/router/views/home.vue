<script>
    import appConfig from '@src/app.config'
    import Layout from '@layouts/main'
    import GoogleMap from "../../components/google-map.vue";

    export default {
        page: {
            title: 'Map',
            meta: [{ name: 'description', content: appConfig.description }],
        },
        components: {
            GoogleMap,
            Layout
        },
        name: 'Reveal',
        data: () => ({
            menuVisible: false
        })
    }
</script>

<template>
  <div class="page-container">
    <md-app md-mode="reveal">
      <md-app-toolbar class="md-primary">
        <md-button class="md-icon-button" @click="menuVisible = !menuVisible">
          <md-icon>menu</md-icon>
        </md-button>
        <span class="md-title">Whitebikes Bratislava</span>
      </md-app-toolbar>

      <md-app-drawer :md-active.sync="menuVisible">
        <md-toolbar class="md-transparent" md-elevation="0">Menu</md-toolbar>

        <md-list>
          <md-list-item>
            <md-icon>person</md-icon>
            <span class="md-list-item-text">Profile</span>
          </md-list-item>

          <md-list-item>
            <md-icon>history</md-icon>
            <span class="md-list-item-text">Ride history</span>
          </md-list-item>

          <md-list-item>
            <md-icon>clear</md-icon>
            <span class="md-list-item-text">Logout</span>
          </md-list-item>

        </md-list>
      </md-app-drawer>

      <md-app-content>
          <google-map></google-map>
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

  .md-drawer {
    width: 230px;
    max-width: calc(100vw - 125px);
  }
</style>

