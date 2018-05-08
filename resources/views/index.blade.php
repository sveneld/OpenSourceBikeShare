<!DOCTYPE html>
<html lang="{{ config('admin.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/css/frontend.css">

    <title>Whitebikes</title>

    <script
            src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCE7wnWtm1R56jjoUmJq_0IYepHrt6ulUk">
    </script>
</head>
<body>
    <div id="app">
        <h1>Hello App!</h1>
        <p>
            <router-link to="/" exact>Home</router-link>
            <router-link to="/map">Map</router-link>
            <router-link to="/about">About</router-link>
        </p>
        <router-view></router-view>

        <div id="map" style="width: 500px; height: 500px"></div>


    </div>

    <script src="/js/frontend.js"></script>

</body>
</html>
