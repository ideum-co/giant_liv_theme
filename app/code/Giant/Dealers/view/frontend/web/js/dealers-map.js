define(['jquery', 'leaflet'], function($, L) {
    'use strict';

    if (!L && window.L) {
        L = window.L;
    }

    return function(config) {
        var dealersData = config.dealers || [];

        function esc(str) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(str || ''));
            return d.innerHTML;
        }

        function initMap() {
            var mapEl = document.getElementById('giant-dealers-map');
            if (!mapEl) return;
            if (!L) {
                mapEl.innerHTML = '<p style="padding:20px;color:#999;text-align:center;">No se pudo cargar el mapa.</p>';
                return;
            }

            var map = L.map('giant-dealers-map').setView([4.570868, -74.297333], 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 18
            }).addTo(map);

            var markers = {};
            dealersData.forEach(function(d) {
                if (d.latitude && d.longitude) {
                    var marker = L.marker([d.latitude, d.longitude]).addTo(map);
                    marker.bindPopup(
                        '<strong>' + esc(d.name) + '</strong><br>' +
                        esc(d.address) + '<br>' +
                        (d.phones ? esc(d.phones) + '<br>' : '') +
                        (d.email ? '<a href="mailto:' + encodeURI(d.email) + '">' + esc(d.email) + '</a>' : '')
                    );
                    markers[d.id] = marker;
                }
            });

            $('.giant-dealer-map-btn').on('click', function() {
                var lat = parseFloat($(this).attr('data-lat'));
                var lng = parseFloat($(this).attr('data-lng'));
                map.setView([lat, lng], 15);
                var card = $(this).closest('.giant-dealer-card');
                var dealerId = card.attr('data-dealer-id');
                if (markers[dealerId]) markers[dealerId].openPopup();
                mapEl.scrollIntoView({behavior: 'smooth', block: 'center'});
            });

            $('.giant-dealer-card').on('click', function(e) {
                if ($(e.target).closest('.giant-dealer-map-btn').length || $(e.target).closest('a').length) return;
                var lat = parseFloat($(this).attr('data-lat'));
                var lng = parseFloat($(this).attr('data-lng'));
                if (lat && lng) {
                    map.setView([lat, lng], 15);
                    var dealerId = $(this).attr('data-dealer-id');
                    if (markers[dealerId]) markers[dealerId].openPopup();
                }
            });

            if (dealersData.length > 0) {
                var validDealers = dealersData.filter(function(d) { return d.latitude && d.longitude; });
                if (validDealers.length > 0) {
                    var bounds = L.latLngBounds(validDealers.map(function(d) { return [d.latitude, d.longitude]; }));
                    map.fitBounds(bounds, {padding: [30, 30]});
                }
            }

            setTimeout(function() { map.invalidateSize(); }, 300);
        }

        initMap();
    };
});
