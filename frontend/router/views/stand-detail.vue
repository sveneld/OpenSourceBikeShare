<script>
    import appConfig from '@src/app.config'
    import Layout from '@layouts/main'

    export default {
        page: {
            title: 'Stand Detail',
            meta: [{ name: 'description', content: appConfig.description }],
        },
        components: {
            Layout
        },
        name: 'StandDetail',
        data() {
            return {
                bikes: this.stand.bikes.data,
                toolbarStyle: {
                    backgroundImage: 'url(' + this.stand.photo + ')'
                }
            }
        },
        props: {
            uuid: {
                type: String,
                required: true,
            },
            stand: {
                type: Object,
                required: true,
            }
        }
    }
</script>

<template>
  <div class="page-container">
    <md-app>
      <md-app-toolbar class="md-primary" :style="toolbarStyle">
        <md-button to="/" class="md-icon-button">
          <md-icon>arrow_back</md-icon>
        </md-button>
      </md-app-toolbar>

      <md-app-content>
          <h1><span class="stand-name md-headline">{{stand.name}}</span></h1>
          <p class="stand-description">{{stand.description}}</p>
          <md-divider></md-divider>
          <md-subheader>Available bikes</md-subheader>

          <div class="bikes-list">
              <md-chip v-for="bike in bikes" v-bind:data="bike" v-bind:key="bike.uuid" :class="[bike.status === 'free' ? 'button-available' : 'button-broken']"
                       class="md-accent" md-clickable>
                  {{bike.bike_num}}
              </md-chip>
          </div>

          <md-divider></md-divider>
          <md-subheader>Bikes to return</md-subheader>
      </md-app-content>
    </md-app>
  </div>
</template>

<style lang="scss" scoped>
  .button-available {
      background-color: green !important;
  }
  .button-available:hover, .button-broken:hover {
      background-color: grey !important;
  }
  .button-broken {
      background-color: #f0ad4e !important;
  }

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

  .stand-name {
      padding-left: 16px;
      padding-right: 16px;
  }

  .stand-description {
      padding-left: 16px;
      padding-right: 16px;
  }

  .md-app-toolbar {
      min-height: 240px;
      background-Size: cover;
      align-items: flex-start;
  }
  .bikes-list {
      padding: 0 16px 16px 16px;
  }



</style>

