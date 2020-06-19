<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <link rel="stylesheet" href="{{ env('STORAGE_URL_VIEW') }}{{ ('assets/global/plugins/bootstrap/css/bootstrap.min.css') }}" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
        <link href="https://fonts.googleapis.com/css?family=Open+Sans|Questrial" rel="stylesheet">
        <style type="text/css">
        @font-face {
                font-family: "GoogleSans-Bold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('/fonts/GoogleSans-Bold.ttf') }}');
        }
        @font-face {
                font-family: "GoogleSans-BoldItalic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('/fonts/GoogleSans-BoldItalic.ttf') }}');
        }
        @font-face {
                font-family: "GoogleSans-Italic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('/fonts/GoogleSans-Italic.ttf') }}');
        }
        @font-face {
                font-family: "GoogleSans-Medium";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('/fonts/GoogleSans-Medium.ttf') }}');
        }
        @font-face {
                font-family: "GoogleSans-MediumItalic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('/fonts/GoogleSans-MediumItalic.ttf') }}');
        }
        @font-face {
                font-family: "GoogleSans";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('/fonts/GoogleSans-Regular.ttf') }}');
        }
        .GoogleSans{
            font-family: "GoogleSans";
        }
        .GoogleSans-MediumItalic{
            font-family: "GoogleSans-MediumItalic";
        }
        .GoogleSans-Medium{
            font-family: "GoogleSans-Medium";
        }
        .GoogleSans-Italic{
            font-family: "GoogleSans-Italic";
        }
        .GoogleSans-BoldItalic{
            font-family: "GoogleSans-BoldItalic";
        }
        .GoogleSans-Bold{
            font-family: "GoogleSans-Bold";
        }
        .kotak1 {
            padding-top: 10px;
            padding-bottom: 10px;
            padding-left: 21.3px;
            padding-right: 21.3px;
            background: #fff;
            font-family: 'Seravek', sans-serif;
            word-wrap:break-word;
        }

        .kotak-title {
            padding-top: 10px;
            padding-left: 41.3px;
            padding-right: 41.3px;
            background: #fff;
            font-family: 'Seravek', sans-serif;
            height: 100%;
            word-wrap:break-word;
        }

        .kotak2 {
            padding-top: 10px;
            padding-bottom: 10px;
            padding-left: 26.3px;
            padding-right: 26.3px;
            background: #fff;
            font-family: 'Open Sans', sans-serif;
            height: 100%;
            word-wrap:break-word;
        }

        .kotak3 {
            padding-top: 7px;
            padding-bottom: 10px;
            padding-left: 26.3px;
            padding-right: 26.3px;
            background: #fff;
            font-family: 'Open Sans', sans-serif;
            height: 100%;
            word-wrap:break-word;
        }

        .kelas-testing {
            background-color: #3D6AA2;
            width: 160px;
            height: 90px;
            position: absolute;
            top: calc(10% - 10px);
            left: calc(50% - 80px);
        }

        .completed {
            color: green;
        }

        .bold {
            font-weight: bold;
        }

        .space-bottom {
            padding-bottom: 8px;
        }

        .space-title {
            padding-bottom: 1px;
        }

        .space-top {
            padding-top: 15px;
        }

        .space-text {
            padding-bottom: 10px;
        }

        .line-bottom {
                border-bottom: 1px solid #eee;
                margin-bottom: -10px;
                margin-top: 8px;
        }

        .text-grey {
            color: #aaaaaa;
        }

        .text-much-grey {
            color: #bfbfbf;
        }

        .text-black {
            color: #000000;
        }

        .text-medium-grey {
            color: #806e6e6e;
        }

        .text-grey-white {
            color: #666;
        }

        .text-grey-black {
            color: #4c4c4c;
        }

        .text-grey-red {
            color: #9a0404;
        }

        .text-grey-red-cancel {
            color: rgba(154,4,4,1);
        }

        .text-grey-blue {
            color: rgba(0,140,203,1);
        }

        .text-grey-yellow {
            color: rgba(227,159,0,1);
        }

        .text-grey-green {
            color: rgba(4,154,74,1);
        }

        .text-red{
            color: #990003;
        }


        .text-14-3px {
            font-size: 14.3px;
        }

        .text-14px {
            font-size: 14px;
        }

        .text-15px {
            font-size: 15px;
        }

        .text-13-3px {
            font-size: 13.3px;
        }

        .text-12-7px {
            font-size: 12.7px;
        }

        .text-12px {
            font-size: 12px;
        }

        .text-11-7px {
            font-size: 11.7px;
        }

        .text-custom-px {
            font-size: 12px;
            color: #95989a;
        }

        .logo-img {
            width: 14.7px;
            height: 14.7px;
            margin-top: -7px;
            margin-right: 5px;
        }

        .text-bot {
            margin-left: -15px;
        }

        .owl-dots {
            margin-top: -37px !important;
            position: absolute;
            width: 100%;
            margin-left: 1px;
            height: 37px;
            opacity: 0.5;
        }

        .image-caption-outlet {

        }

        .owl-carousel {
            overflow: hidden;
        }

        .owl-theme .owl-dots .owl-dot span {
            width: 5px !important;
            height: 4px !important;
            margin: 5px 1px !important;
            margin-top: 28px !important;
        }

        .image-caption-all {
            position: absolute;
            z-index: 99999;
            bottom: 0;
            color: white;
            width: 100%;
            background: rgba(0, 0, 0, 0.5);
            padding: 10px;
        }

        .image-caption-you {
            position: absolute;
            z-index: 99999;
            top: 0;
            color: white;
            width: 100%;
            padding: 8%;
        }

        .cf_videoshare_referral {
            display: none !important;
        }

        #grad1 {
          background: rgba(255,255,255,0);
background: -moz-linear-gradient(top, rgba(255,255,255,0) 50%, rgba(255,255,255,1) 82%);
background: -webkit-gradient(left top, left bottom, color-stop(50%, rgba(255,255,255,0)), color-stop(82%, rgba(255,255,255,1)));
background: -webkit-linear-gradient(top, rgba(255,255,255,0) 50%, rgba(255,255,255,1) 82%);
background: -o-linear-gradient(top, rgba(255,255,255,0) 50%, rgba(255,255,255,1) 82%);
background: -ms-linear-gradient(top, rgba(255,255,255,0) 50%, rgba(255,255,255,1) 82%);
background: linear-gradient(to bottom, rgba(255,255,255,0) 60%, rgba(255,255,255,1) 82%);
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#ffffff', GradientType=0 );
    height: 100px;
    width: 100%;
    margin-top: -81px;
        }

        </style>

        <link rel="stylesheet" href="{{ env('STORAGE_URL_VIEW') }}{{ ('assets/css/owl.carousel.min.css') }}">
        <link rel="stylesheet" href="{{ env('STORAGE_URL_VIEW') }}{{ ('assets/css/owl.theme.default.min.css') }}">
    </head>
    {{-- <body> --}}
        @php
           // print_r($result['custom_page_outlet']);die();
        @endphp

        <div id="box" class="box-shadow"></div>

        @if (isset($result['custom_page_image_header']))
            <div class="kotak">
                <div class="container">
                    @if (isset($result['custom_page_image_header']))
                        <div class="row space-bottom">
                            <div class="owl-carousel owl-theme">
                                @foreach ($result['custom_page_image_header'] as $key => $value)
                                <div class="item"> <img style="height: 60vw !important; object-fit: cover" src="{{env('STORAGE_URL_API')}}{{ $value['custom_page_image'] }}"> <div class="questrial-font image-caption-all text-13-3px"></div> </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if (isset($result['custom_page_event_date_start']) || isset($result['custom_page_event_location_name']) || isset($result['custom_page_event_location_address']) || isset($result['custom_page_event_location_phone']) || isset($result['custom_page_event_location_map']))
        <div class="kotak2">
            <div class="">
            @if (isset($result['custom_page_event_date_start']))
                @if ($result['custom_page_event_date_start'] == $result['custom_page_event_date_end'])
                    <div class="row space-bottom">
                        <div class="col-12 text-red text-11-7px GoogleSans-Medium">TANGGAL</div>
                         <div class="col-12 text-grey-black text13-3px GoogleSans-Medium"> {{ date('d F Y', strtotime($result['custom_page_event_date_start'])) }}</div>
                    </div>
                @else
                    <div class="row space-bottom">
                         <div class="col-12 text-red text-11-7px GoogleSans-Medium">TANGGAL</div>
                         <div class="col-12 text-grey-black text-13-3px GoogleSans-Reguler"> {{ date('d F', strtotime($result['custom_page_event_date_start'])) }} - {{ date('d F Y', strtotime($result['custom_page_event_date_end'])) }}</div>
                    </div>
                @endif
            @endif
            @if (isset($result['custom_page_event_time_start']))
                @if ($result['custom_page_event_time_start'] == $result['custom_page_event_time_end'])
                    <div class="row space-bottom">
                        <div class="col-12 text-red text-11-7px GoogleSans-Medium">JAM</div>
                        <div class="col-12 text-grey-black text-13-3px GoogleSans-Reguler"> {{ date('H:i', strtotime($result['custom_page_event_time_start'])) }}</div>
                    </div>
                @else
                    <div class="row space-bottom">
                        <div class="col-12 text-red text-11-7px GoogleSans-Medium">JAM</div>
                        <div class="col-12 text-grey-black text-13-3px GoogleSans-Reguler">{{ date('H:i', strtotime($result['custom_page_event_time_start'])) }} - {{ date('H:i', strtotime($result['custom_page_event_time_end'])) }}</div>
                    </div>
                @endif
            @endif
            @if (isset($result['custom_page_event_location_name']))
                <div class="row space-bottom">
                    <div class="col-12 text-red text-11-7px GoogleSans-Medium">LOKASI</div>
                    <div class="col-12 text-grey-black text-13-3px GoogleSans-Reguler"> {{ $result['custom_page_event_location_name'] }} </div>
                </div>
            @endif

            @if (isset($result['custom_page_event_location_address']))
                <div class="row space-bottom">
                     <div class="col-12 text-red text-11-7px GoogleSans-Medium">ALAMAT</div>
                     <div class="col-12 text-grey-black text-13-3px GoogleSans-Reguler">{{ $result['custom_page_event_location_address'] }} </div>
                </div>
            @endif

            @if (isset($result['custom_page_event_location_phone']))
                <div class="row space-bottom">
                    <div class="col-12 text-red text-11-7px GoogleSans-Medium">TELEPON</div>
                    <div class="col-12 text-grey-black text-13-3px GoogleSans-Reguler"> {{ $result['custom_page_event_location_phone'] }} </div>
                </div>
            @endif

            @if (isset($result['custom_page_event_location_map']))
                <div class="row space-bottom">
                    <div class="col-12 text-grey-black text-12px seravek-font"><div id="map-canvas" style="width:500;height:180px;"></div></div>
                </div>
            @endif

            </div>
            <div class="line-bottom">
                </div>
        </div>
        @endif

        <div class="kotak2">
            <div class="container">
                <div class="row space-bottom text-13-3px GoogleSans-Reguler">
                    <div class="col-12" style="display: inline-block;">{!! $result['custom_page_description'] !!}</div>
                </div>
            </div>
        </div>

        @if (isset($result['custom_page_video_text']))
            <div class="kotak2">
                <div class="container">
                    @if (isset($result['custom_page_video_text']))
                        <div class="row space-bottom">
                            <div class="col-12 text-bot text-black text-15px GoogleSans-Reguler"> <span> {{ $result['custom_page_video_text'] }} :  </span></div>
                        </div>
                    @endif

                    @if (isset($result['custom_page_video']))
                        <div class="row space-bottom">
                            <div class="embed-responsive embed-responsive-16by9" style="height: 60vw !important;">
                                <iframe id="frame1" class="embed-responsive-item" src="https://www.youtube.com/embed/{{ substr($result['custom_page_video'], strpos($result['custom_page_video'], "=") + 1) }}?rel=0" allowfullscreen></iframe><div class="questrial-font image-caption-you text-13-3px"></div>
                            </div>
                        </div>
                    @else
                        <img style="height: 60vw;object-fit: cover" src="https://via.placeholder.com/800x500.png?text=No Video Available">
                    @endif
                </div>
            </div>
        @endif

        @if (isset($result['custom_page_outlet_text']))
            <div class="kotak2">
                <div class="container">
                @if (isset($result['custom_page_outlet_text']))
                    <div class="row space-bottom">
                        <div class="col-12 text-bot text-black text-15px GoogleSans-Reguler"> <span> {{ $result['custom_page_outlet_text'] }} :  </span></div>

                    </div>
                @endif

                @if (isset($result['custom_page_outlet']))
                    <div class="row">
                        <div class="owl-carousel owl-theme">
                            @foreach ($result['custom_page_outlet'] as $key => $value)
                                <div class="item">
                                    @if (isset($value['outlet']['photos'][0]['url_outlet_photo']))
                                        <img style="height: 60vw;object-fit: cover" src="{{ $value['outlet']['photos'][0]['url_outlet_photo'] }}">
                                        <div class="questrial-font image-caption-all text-13-3px">{{ $value['outlet']['outlet_name'] }}</div>
                                    @else
                                        <img style="height: 60vw;object-fit: cover" src="https://via.placeholder.com/800x500.png?text=No Image Available">
                                        <div class="questrial-font image-caption-all text-13-3px">{{ $value['outlet']['outlet_name'] }}</div>
                                    @endif
                                </div>

                            @endforeach
                        </div>

                    </div>
                @endif
                </div>
            </div>
        @endif

        @if (isset($result['custom_page_product_text']))
            <div class="kotak2">
                <div class="container">
                    @if (isset($result['custom_page_product_text']))
                        <div class="row space-bottom">
                            <div class="col-12 text-bot text-black text-15px GoogleSans-Reguler"> <span> {{ $result['custom_page_product_text'] }} :  </span></div>

                        </div>
                    @endif
                    @if (isset($result['custom_page_product']))
                        <div class="row space-bottom">
                            <div class="owl-carousel owl-theme">
                                @foreach ($result['custom_page_product'] as $key => $value)
                                    @if (isset($value['product']['photos'][0]['url_product_photo']))
                                        <div class="item"> <img style="height: 60vw !important; object-fit: cover" src="{{ $value['product']['photos'][0]['url_product_photo'] }}"> <div class="questrial-font image-caption-all text-13-3px">{{ $value['product']['product_name'] }}</div> </div>
                                    @else
                                        <div class="item"> <img style="height: 60vw !important; object-fit: cover" src="https://via.placeholder.com/800x500.png?text=No Image Available"> <div class="questrial-font image-caption-all text-13-3px">{{ $value['product']['product_name'] }}</div> </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="kotak2">
            <div class="container">
                <div class="col-12 text-13-3px" @if (!isset($result['custom_page_button_form'])) hidden @endif>
                    <button style="color:#ffffff; background-color: #990003;" id="action" type="button" class="btn btn-block GoogleSans-Bold">{{$result['custom_page_button_form_text_button']}}</button>
                </div>
            </div>
        </div>

        {{ csrf_field() }}
        <script src="{{ env('STORAGE_URL_VIEW') }}{{ ('assets/js/jquery-3.3.1.slim.min.js') }}" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="{{ env('STORAGE_URL_VIEW') }}{{ ('assets/js/bootstrap.min.js') }}" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

        <script src="{{ env('STORAGE_URL_VIEW') }}{{ ('assets/js/owl.carousel.min.js') }}"></script>
        @php
        if (isset($result['custom_page_button_form'])) {
            $action_type = implode('_', explode(' ', strtolower($result['custom_page_button_form'])));
        } else {
            $action_type = null;
        }
        if (isset($result['custom_page_button_form_text_value'])) {
            $value_action_type = $result['custom_page_button_form_text_value'];
        } else {
            $value_action_type = null;
        }
        @endphp
        <script>
            $(document).ready(function(){
                $("#frame1").contents().find(".ytp-chrome-top-buttons").attr("style","display:none");
            });

            $(document).on('click', '.ampas', function() {
                console.log('testing');
            });

            $(".owl-carousel").owlCarousel({
                margin: 1,
                items : 1,
                loop:false,
            });

            $('#action').click( function() {
                action_type = '{!!$action_type!!}'
                value_action_type = "{!!$value_action_type!!}"
                window.location.replace(window.location.href +"#"+action_type+"*"+value_action_type);
            });

            owl = $('.owl-carousel').owlCarousel();

            owl.on('translated.owl.carousel', function(event) {
                var title2 = $(this).find('.active').find('#image-product').attr('title');
                if(title2) $('.image-caption-product').html(title2);
            });

            var map;
            var lat = "{!! $result['custom_page_event_latitude'] !!}";
            var long = "{!! $result['custom_page_event_longitude'] !!}";

            var markers = [];

            function initialize() {
                var haightAshbury = new google.maps.LatLng(lat,long);
                var marker        = new google.maps.Marker({
                    position:new google.maps.LatLng(lat,long),
                    map: map,
                    anchorPoint: new google.maps.Point(0, -29)
                });

                var mapOptions = {
                    zoom: 12,
                    center: haightAshbury,
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                };

                var infowindow = new google.maps.InfoWindow({
                    content: '<p>Marker Location:</p>'
                });

                map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

                // Adds a marker at the center of the map.
                addMarker(haightAshbury);
            }

            function placeMarker(location) {
                marker = new google.maps.Marker({
                    position: location,
                    map: map,
                });

                markers.push(marker);

                infowindow = new google.maps.InfoWindow({
                    content: 'Latitude: ' + location.lat() + '<br>Longitude: ' + location.lng()
                });

                infowindow.open(map,marker);
            }

            // Add a marker to the map and push to the array.
            function addMarker(location) {
                var marker = new google.maps.Marker({
                    position: location,
                    map: map
                });

                $('#lat').val(location.lat());
                $('#lng').val(location.lng());
                markers.push(marker);
            }

            // Sets the map on all markers in the array.
            function setAllMap(map) {
                for (var i = 0; i < markers.length; i++) {
                    markers[i].setMap(map);
                }
            }

            // Removes the markers from the map, but keeps them in the array.
            function clearMarkers() {
                setAllMap(null);
            }

            // Shows any markers currently in the array.
            function showMarkers() {
                setAllMap(map);
            }

            // Deletes all markers in the array by removing references to them.
            function deleteMarkers() {
                clearMarkers();
                markers = [];
            }

            google.maps.event.addDomListener(window, 'load', initialize);
        </script>
    {{-- </body> --}}
</html>