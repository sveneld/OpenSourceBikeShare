import Vue from 'vue'
import App from './app.vue'
import router from '@router'
import store from '@state/store'
import '@components/_globals'
import VueMaterial from 'vue-material'
import 'vue-material/dist/vue-material.min.css'
import 'vue-material/dist/theme/default.css'

Vue.use(VueMaterial)

const app = new Vue({
    router,
    store,
    render: h => h(App),
}).$mount('#app')


