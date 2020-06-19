<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="{{ env('STORAGE_URL_VIEW') }}{{('css/slide.css') }}" rel="stylesheet">
    <style type="text/css">
        @font-face {
            font-family: 'Seravek';
            font-style: normal;
            font-weight: 400;
            src: url('{{env("STORAGE_URL_VIEW") }}{{("assets/fonts/Seravek.ttf")}}') format('truetype');
        }

        @font-face {
            font-family: 'Seravek Light';
            font-style: normal;
            font-weight: 400;
            src: url('{{env("STORAGE_URL_VIEW") }}{{("assets/fonts/Seravek-Light.ttf")}}') format('truetype');
        }

        @font-face {
            font-family: 'Seravek Medium';
            font-style: normal;
            font-weight: 400;
            src: url('{{env("STORAGE_URL_VIEW") }}{{("assets/fonts/Seravek-Medium.ttf")}}') format('truetype');
        }

        @font-face {
            font-family: 'Seravek Italic';
            font-style: normal;
            font-weight: 400;
            src: url('{{env("STORAGE_URL_VIEW") }}{{("assets/fonts/Seravek-Italic.ttf")}}') format('truetype');
        }

        @font-face {
            font-family: 'Roboto Regular';
            font-style: normal;
            font-weight: 400;
            src: url('{{env("STORAGE_URL_VIEW") }}{{("assets/fonts/Roboto-Regular.ttf")}}') format('truetype');
        }

        .kotak {
            margin : 10px;
            padding: 10px;
            /*margin-right: 15px;*/
            -webkit-box-shadow: 0px 1px 3.3px 0px rgba(168,168,168,1);
            -moz-box-shadow: 0px 1px 3.3px 0px rgba(168,168,168,1);
            box-shadow: 0px 1px 3.3px 0px rgba(168,168,168,1);
            /* border-radius: 3px; */
            background: #fff;
            font-family: 'Open Sans', sans-serif;
        }

        .kotak-qr {
            -webkit-box-shadow: 0px 0px 5px 0px rgba(214,214,214,1);
            -moz-box-shadow: 0px 0px 5px 0px rgba(214,214,214,1);
            box-shadow: 0px 0px 5px 0px rgba(214,214,214,1);
            background: #fff;
            width: 130px;
            height: 130px;
            margin: 0 auto;
            border-radius: 20px;
            padding: 10px;
        }

        .kotak-full {
            margin-bottom : 15px;
            padding: 10px;
            background: #fff;
            font-family: 'Open Sans', sans-serif;
        }

        .kotak-inside {
        	padding-left: 25px;
        	padding-right: 25px
        }

        .kotak-biasa {
            background: #fafafa;
        }

        .completed {
            color: green;
        }

        .bold {
            font-weight: bold;
        }

        .space-bottom {
            padding-bottom: 15px;
        }

        .space-top-all {
            padding-top: 15px;
        }

        .space-text {
            padding-bottom: 10px;
        }

        .space-nice {
        	padding-bottom: 20px;
        }

        .space-bottom-big {
        	padding-bottom: 25px;
        }

        .space-top {
        	padding-top: 5px;
        }

        .line-bottom {
            border-bottom: 1px solid rgba(0,0,0,.1);
            margin-bottom: 15px;
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

        .text-grey-light {
            color: #b6b6b6;
        }

        .text-grey-medium-light{
            color: #a9a9a9;
        }

        .text-black-grey-light{
            color: #5f5f5f;
        }


        .text-medium-grey-black{
            color: #424242;
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

        .text-greyish-brown{
            color: #6c5648;
        }

        .open-sans-font {
            font-family: 'Open Sans', sans-serif;
        }

        .questrial-font {
            font-family: 'Questrial', sans-serif;
        }

        .seravek-font {
            font-family: 'Seravek';
        }

        .seravek-light-font {
            font-family: 'Seravek Light';
        }

        .seravek-medium-font {
            font-family: 'Seravek Medium';
        }

        .seravek-italic-font {
            font-family: 'Seravek Italic';
        }

        .roboto-regular-font {
            font-family: 'Roboto Regular';
        }

        .text-21-7px {
            font-size: 21.7px;
        }

        .text-16-7px {
            font-size: 16.7px;
        }

        .text-15px {
            font-size: 15px;
        }

        .text-14-3px {
            font-size: 14.3px;
        }

        .text-14px {
            font-size: 14px;
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

        .round-greyish-brown{
            border: 1px solid #6c5648;
            border-radius: 50%;
            width: 10px;
            height: 10px;
            display: inline-block;
            margin-right:3px;
        }

        .bg-greyish-brown{
            background: #6c5648;
        }

        .round-white{
            width: 10px;
            height: 10px;
            display: inline-block;
            margin-right:3px;
        }

        .line-vertical{
            font-size: 5px;
            width:10px;
            margin-right: 3px;
        }

        .inline{
            display: inline-block;
        }

        .vertical-top{
            vertical-align: top;
            padding-top: 5px;
        }

        .top-5px{
            top: -5px;
        }
        .top-10px{
            top: -10px;
        }
        .top-15px{
            top: -15px;
        }
        .top-20px{
            top: -20px;
        }
        .top-25px{
            top: -25px;
        }
        .top-30px{
            top: -30px;
        }
        .top-35px{
            top: -35px;
        }

        #map{
            border-radius: 10px;
            width: 100%;
            height: 150px;
        }

        .label-free{
            background: #6c5648;
            padding: 3px 15px;
            border-radius: 6.7px;
            float: right;
        }

        .text-strikethrough{
            text-decoration:line-through
        }

        #modal-usaha {
            position: fixed;
            top: 0;
            left: 0;
            background: rgba(0,0,0, 0.5);
            width: 100%;
            display: none;
            height: 100vh;
            z-index: 999;
        }

        .modal-usaha-content {
            position: absolute;
            left: 50%;
            top: 50%;
            margin-left: -125px;
            margin-top: -125px;
        }

    </style>
  </head>
  <body>
    {{ csrf_field() }}
    <a id="modal-usaha" href="#">
        <div class="modal-usaha-content">
            <img class="img-responsive" style="display: block; max-width: 100%; padding-top: 10px" src="{{ $data['qr'] }}">
        </div>
    </a>
    <div class="kotak-ampuh">
        <div class="container">
            <div class="row">
                <div class="col-4 text-16-7px text-greyish-brown seravek-medium-font" style="padding-bottom: 10px;padding-top: 10px">{{ $data['status'] }}</div>
                <div class="col-8 text-right text-medium-grey text-13-3px seravek-light-font" style="padding-bottom: 10px;padding-top: 15px">{{ date('d F Y H:i', strtotime($data['transaction_date'])) }}</div>
            </div>
        </div>
    </div>

    @if ($data['trasaction_payment_type'] != 'Offline')
        @if(isset($data['detail']['pickup_by']) && $data['detail']['pickup_by'] == 'GO-SEND')
            <div class="kotak-biasa">
                <div class="container">
                    <div class="row text-center">
                        <div class="col-12 seravek-font text-15px space-nice text-grey">Detail Pengiriman</div>
                        <div class="col-12 text-greyish-brown text-21-7px space-bottom seravek-medium-font">GO-SEND</div>
                        <div class="col-12 text-16-7px text-black space-bottom seravek-light-font">
                            {{ $data['detail']['transaction_pickup_go_send']['destination_name'] }}
                            <br>
                            {{ $data['detail']['transaction_pickup_go_send']['destination_phone'] }}
                        </div>
                        <div class="kotak-inside col-12">
                            <div class="col-12 text-13-3px text-grey-white space-nice text-center seravek-light-font">{{ $data['detail']['transaction_pickup_go_send']['destination_address'] }}</div>
                        </div>
                        <div class="col-12 text-15px space-bottom text-black seravek-light-font">Map</div>
                        <div class="col-12 space-bottom-big">
                            <div class="container">
                                <div id="map"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="kotak-biasa" style="padding-top: 10px">
                <div class="container">
                    <div class="row text-center">
                        <div class="col-12 seravek-font text-15px space-text text-grey">Kode Pickup</div>

                        <div class="kotak-qr" id="gambar-usaha">
                            <div class="col-12 text-14-3px space-top"><img class="img-responsive" style="display: block; max-width: 100%; padding-top: 10px" src="{{ $data['qr'] }}"></div>
                        </div>

                        <div class="col-12 text-greyish-brown text-21-7px space-bottom space-top-all seravek-medium-font">{{ $data['detail']['order_id'] }}</div>
                        <div class="col-12 text-16-7px text-black space-text seravek-light-font">{{ $data['user']['name'] }} <br> {{ $data['user']['phone'] }}
                        </div>
                        <div class="col-12 text-16-7px space-nice text-black seravek-light-font text-greyish-brown">Pesanan Anda akan diproses pada
                        </div>
                        @if ($data['detail']['pickup_type'] == 'set time')
                            <div class="col-12 text-21-7px space-nice text-greyish-brown seravek-medium-font">{{ date('H:i', strtotime($data['detail']['pickup_at'])) }}</div>
                        @elseif($data['detail']['pickup_type'] == 'at arrival')
                            <div class="col-12 text-21-7px space-nice text-greyish-brown seravek-medium-font">Saat Kedatangan</div>
                        @else
                            <div class="col-12 text-21-7px space-nice text-greyish-brown seravek-medium-font">Saat Ini</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif

    <div class="kotak" style="margin-top: 15px;">
        <div class="container line-bottom">
            <div class="row space-bottom">
                <div class="col-6 text-grey-black text-14-3px seravek-font">{{ $data['outlet']['outlet_name'] }}</div>
                <div class="col-6 text-right text-medium-grey text-13-3px seravek-light-font">{{ date('d F Y H:i', strtotime($data['transaction_date'])) }}</div>
            </div>
            <div class="row space-text">
                <div class="col-4"></div>
                <div class="col-8 text-right text-medium-grey-black text-13-3px seravek-font">#{{ $data['transaction_receipt_number'] }}</div>
            </div>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-12 text-13-3px text-grey-light seravek-light-font">
                    Transaksi Anda
                    <hr style="margin:10px 0 20px 0">
                </div>
                @php $countQty = 0; @endphp
                @foreach ($data['product_transaction'] as $key => $val)
                    <div class="col-7 text-13-3px text-black seravek-light-font">{{ $val['product']['product_name'] }}</div>
                    <div class="col-5 text-right text-13-3px text-black seravek-light-font">{{ str_replace(',', '.', number_format($val['transaction_product_subtotal'])) }}</div>
                    <div class="col-12 text-grey text-12-7px text-black-grey-light seravek-light-font">{{ $val['transaction_product_qty'] }} x {{ str_replace(',', '.', number_format($val['transaction_product_price'])) }}</div>
                    <div class="space-bottom col-12">
                        <div class="space-bottom text-12-7px text-grey-medium-light seravek-italic-font" style="word-wrap: break-word;">
                            @if (isset($val['transaction_product_note']))
                                {{ $val['transaction_product_note'] }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    @php $countQty += $val['transaction_product_qty']; @endphp
                @endforeach

                <div class="col-12 text-12-7px text-right"><hr style="margin:0"></div>
                <div class="col-6 text-13-3px space-bottom space-top-all text-black seravek-font">SubTotal ({{$countQty}} item)</div>
                <div class="col-6 text-13-3px space-bottom space-top-all text-black text-right seravek-font">{{ str_replace(',', '.', number_format($data['transaction_subtotal'])) }}</div>
            </div>
        </div>
    </div>

    <div class="kotak">
        <div class="container">
            <div class="row">
                <div class="col-12 text-14-3px space-top seravek-font text-greyish-brown">Detail Pembayaran <hr> </div>
                <div class="col-6 text-13-3px space-text seravek-light-font text-black">SubTotal ({{$countQty}} item)</div>
                <div class="col-6 text-13-3px text-right space-text seravek-light-font text-grey-black">{{ number_format($data['transaction_subtotal'], 0, ',', '.') }}</div>

                @if($data['transaction_tax'] > 0)
                <div class="col-6 text-13-3px space-text seravek-light-font text-black">Tax</div>
                <div class="col-6 text-13-3px text-right seravek-light-font text-grey-black">{{ number_format($data['transaction_tax'], 0, ',', '.') }}</div>
                @endif

                @if(isset($data['detail']['pickup_by']) && $data['detail']['pickup_by'] == 'GO-SEND')
                <div class="col-6 text-13-3px space-text seravek-light-font text-black">Ongkos Kirim</div>
                    @if($data['transaction_is_free'] == '1')
                        <div class="col-6 text-13-3px text-right seravek-font text-white"><div class="label-free">FREE</div></div>
                    @else
                        @if($data['transaction_shipment_go_send'] != $data['transaction_shipment'])
                            <div class="col-6 text-13-3px text-right seravek-light-font text-grey-black text-strikethrough">{{ number_format($data['transaction_shipment_go_send'], 0, ',', '.') }}</div>
                            <div class="col-12 space-text text-13-3px text-right seravek-light-font text-greyish-brown">{{ number_format($data['transaction_shipment'], 0, ',', '.') }}</div>
                        @else
                            <div class="col-6 text-13-3px text-right seravek-light-font text-grey-black">{{ number_format($data['transaction_shipment'], 0, ',', '.') }}</div>
                        @endif
                    @endif
                @endif

                <div class="col-12 text-12-7px text-right"><hr></div>
                <div class="col-6 text-13-3px seravek-font text-black ">Total Pembayaran</div>
                <div class="col-6 text-13-3px text-right seravek-font text-black">{{ number_format($data['transaction_grandtotal']) }}</div>
            </div>
        </div>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.js"></script>

    @if(isset($data['detail']['pickup_by']) && $data['detail']['pickup_by'] == 'GO-SEND')

    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCOHBNv3Td9_zb_7uW-AJDU6DHFYk-8e9Y&callback=initMap">
    </script>

    <script>
        // Initialize and add the map
        function initMap() {
            // The location of Uluru
            var uluru = {lat: parseFloat("{{$data['detail']['transaction_pickup_go_send']['destination_latitude']}}"), lng: parseFloat("{{$data['detail']['transaction_pickup_go_send']['destination_longitude']}}")};
            // The map, centered at Uluru
            var map = new google.maps.Map(
                document.getElementById('map'), {
                    zoom: 15,
                    center: uluru,
                    disableDefaultUI: true
                });
            // The marker, positioned at Uluru
            var marker = new google.maps.Marker({position: uluru, map: map});
        }
    </script>
    @endif

    <script>
        $(document).ready(function() {
            $(document).on('click', '#gambar-usaha', function() {
                $('#modal-usaha').fadeIn('fast');
            });

            $(document).on('click', '#modal-usaha', function() {
                $('#modal-usaha').fadeOut('fast');
            });
        });
    </script>

  </body>
</html>