import VueRouter from 'vue-router'

let routes = [
    {
        path: '/',
        component: require('./views/Home.vue')
    },
    {
        path: '/map',
        component: require('./views/GoogleMap.vue'),
        props: {name: "googleMap"}
    }

];

export default new VueRouter({
    routes
});