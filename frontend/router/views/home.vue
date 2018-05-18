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
      Layout },
  created() {
  },
  mounted() {
  }

}
</script>

<template>
  <Layout>
    <h1>Home Page</h1>
    <!--<img-->
      <!--src="@assets/images/logo.png"-->
      <!--alt="Logo"-->
    <!--&gt;-->

    <google-map></google-map>

  </Layout>
</template>
