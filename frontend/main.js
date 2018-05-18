import Vue from 'vue'
import App from './app.vue'
import router from '@router'
import store from '@state/store'
import '@components/_globals'

const app = new Vue({
    router,
    store,
    render: h => h(App),
}).$mount('#app')
