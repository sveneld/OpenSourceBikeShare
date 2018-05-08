<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="google-map" :id="mapName"></div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'google-map',
        props: ['name'],
        data: function () {
            return {
                mapName: this.name + "-map",
            }
        },
        mounted() {
            console.log('Google Map mounted.');
            const element = document.getElementById(this.mapName);
            const options = {
                zoom: 14,
                center: new google.maps.LatLng(51.501527,-0.1921837)
            };

            const map = new google.maps.Map(element, options);
        }
    };
</script>

<style scoped>
    .google-map {
        width: 800px;
        height: 600px;
        margin: 0 auto;
        background: gray;
    }
</style>