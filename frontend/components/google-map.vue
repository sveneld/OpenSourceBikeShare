<template>
    <div class="google-map" :id="mapName"></div>
</template>

<script>
    import axios from 'axios'

    const iconBase = "http://" + window.location.host + '/images/';
    const icons = {
        basic: {
            icon: iconBase + 'marker_green.png'
        },
        empty: {
            icon: iconBase + 'marker_red.png'
        },
        repair: {
            icon: iconBase + 'marker_blue.png'
        }
    };

    class BikeMarker extends google.maps.OverlayView{
        constructor(latlng, map, stand) {
            super();
            this.latlng = latlng;
            this.stand = stand;
            this.bikesCount = this.stand.bikes.data.length;
            this.setMap(map);
        }

        onAdd() {
            let self = this;

            let div = document.createElement('div')
            div.className = 'marker'
            div.style.position = 'absolute'
            div.style.cursor = 'pointer'
            if (this.bikesCount > 0) {
                div.style.backgroundImage = 'url(' + icons.basic.icon + ')'
            } else {
                div.style.backgroundImage = 'url(' + icons.empty.icon + ')'
            }
            div.style.backgroundSize = '60px 60px'
            let span = document.createElement('span')
            span.className = 'bike-count'
            span.innerHTML = this.bikesCount
            div.appendChild(span)
            let span2 = document.createElement('span')
            span2.className = 'stand-name'
            span2.innerHTML = this.stand.name
            div.appendChild(span2)
            let panes = this.getPanes()
            panes.overlayImage.appendChild(div)

            google.maps.event.addDomListener(div, 'click', function(event) {
                google.maps.event.trigger(self, 'click', event, self.stand);
            });

            this.div = div
        }

        draw() {
            let self = this;
            let point = this.getProjection().fromLatLngToDivPixel(this.latlng);
            if (point) {
                this.div.style.left = (point.x - 30) + 'px'
                this.div.style.top = (point.y - 30) + 'px'
            }
        }

        remove() {
            if (this.div){
                this.div.parentNode.removeChild(this.div)
                this.div = null
            }
        }

        getPosition() {
            return this.latlng
        }

        setVisible(visible) {
            if (this.div) {
                if (visible) {
                    this.div.style.display = 'table';
                    this.visible = true;
                } else {
                    this.div.style.display = 'none';
                    this.visible = false;
                }
            }
        }

        getVisible() {
            return this.visible
        }

        getStand() {
            return this.stand
        }
    }

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
            const element = document.getElementById(this.mapName);
            const options = {
                zoom: 13,
                center: new google.maps.LatLng(48.14816, 17.10674)
            };

            this.map = new google.maps.Map(element, options);

            axios.get('/api/stands?include=bikes')
                .then(response => (
                    this.stands = response.data.data
                ))
        },
        watch: {
            stands: function (stands) {
                const map = this.map
                let that = this
                stands.forEach((stand) => {
                    const position = new google.maps.LatLng(stand.latitude, stand.longitude);
                    const marker = new BikeMarker(position, map, stand)

                    google.maps.event.addListener(marker, 'click', function(event, stand) {
                        that.$router.push({ name: 'stand', params: { uuid: stand.uuid }})
                    })
                });
            }
        }
    }
</script>

<style>
    .google-map {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        background: gray;
    }

    .marker {
        display: block;
        position: relative;
        width: 60px;
        height: 60px;
    }
    .marker span.bike-count {
        position: absolute;
        top: -2px;
        left: 2px;
        font-size: 170%;
    }
    .marker span.stand-name {
        position: absolute;
        bottom: 1px;
        text-transform: uppercase;
        left: 2px;
        font-size: 75%;
    }
</style>