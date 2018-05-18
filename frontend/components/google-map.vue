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
    import axios from 'axios'

    export default {
        name: 'google-map',
        props: ['name'],
        data: function () {
            return {
                mapName: this.name + "-map",
                stands: [],
                map: null
            }
        },
        mounted() {
            console.log('Google Map mounted.');
            const element = document.getElementById(this.mapName);
            const options = {
                zoom: 13,
                center: new google.maps.LatLng(48.14816, 17.10674)
            };

            this.map = new google.maps.Map(element, options);



            // TODO move data from component to store
            axios.get('/api/stands?include=bikes')
                .then(response => (
                    this.stands = response.data.data
                ));
        },
        watch: {
            stands: function (stands) {
                const map = this.map;
                stands.forEach((stand) => {
                    const position = new google.maps.LatLng(stand.latitude, stand.longitude);
                    const marker = new google.maps.Marker({
                        position,
                        map
                    });
                });
            }
        }
    }
</script>

<style scoped>
    .google-map {
        width: 800px;
        height: 600px;
        margin: 0 auto;
        background: gray;
    }
</style>