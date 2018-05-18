<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="google-map" :id="mapName"></div>
            </div>
            <div class="col-md-12">
                {{ stands }}
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
                stands: [],
                map: null
            }
        },
        mounted() {
            console.log('Google Map mounted.');
            const element = document.getElementById(this.mapName);
            const options = {
                zoom: 14,
                center: new google.maps.LatLng(51.501527,-0.1921837)
            };

            this.map = new google.maps.Map(element, options);
            axios.get('http://bikeshare.press/api/stands2')
                .then(response => (this.stands = response));
        },
        watch: {
            stands: function (stands) {
                const map = this.map;
                stands.forEach((stand) => {
                    const position = new google.maps.LatLng(coord.latitude, coord.longitude);
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