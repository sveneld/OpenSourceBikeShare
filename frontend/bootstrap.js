import Vue from 'vue'
import VueRouter from 'vue-router'
import axios from 'axios'

window.Vue = Vue;

Vue.use(VueRouter);

window.axios = axios;

// window.axios.defaults.header.common = {
//     'X-Requested-With': 'XmlHttpRequest'
// };
