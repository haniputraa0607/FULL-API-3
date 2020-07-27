<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="{{ config('url.storage_url_view') }}{{('css/slide.css') }}" rel="stylesheet">
    <style type="text/css">
         @font-face {
                font-family: "GoogleSans-Bold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('/fonts/GoogleSans-Bold.ttf') }}');
        }
        @font-face {
                font-family: "GoogleSans-BoldItalic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('/fonts/GoogleSans-BoldItalic.ttf') }}');
        }
        @font-face {
                font-family: "GoogleSans-Italic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('/fonts/GoogleSans-Italic.ttf') }}');
        }
        @font-face {
                font-family: "GoogleSans-Medium";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('/fonts/GoogleSans-Medium.ttf') }}');
        }
        @font-face {
                font-family: "GoogleSans-MediumItalic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('/fonts/GoogleSans-MediumItalic.ttf') }}');
        }
        @font-face {
                font-family: "GoogleSans";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('/fonts/GoogleSans-Regular.ttf') }}');
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

        .kotak {
            margin : 10px;
            padding: 10px;
            /*margin-right: 15px;*/
            -webkit-box-shadow: 0px 1px 3.3px 0px rgba(168,168,168,1);
            -moz-box-shadow: 0px 1px 3.3px 0px rgba(168,168,168,1);
            box-shadow: 0px 1px 3.3px 0px rgba(168,168,168,1);
            /* border-radius: 3px; */
            background: #fff;
            font-family: 'GoogleSans';
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
            font-family: 'GoogleSans';
        }

        .kotak-inside {
        	padding-left: 25px;
        	padding-right: 25px
        }

        body {
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

        .text-red{
            color: #990003;
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

        .round-red{
            border: 1px solid #990003;
            border-radius: 50%;
            width: 10px;
            height: 10px;
            display: inline-block;
            margin-right:3px;
        }

        .bg-red{
            background: #990003;
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

        .body-admin{
            max-width: 480px;
            margin: auto;
            background-color: #fafafa;
            border: 1px solid #7070701c;
        }

    </style>
  </head>
  @php $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; @endphp
  <body style="@if(isset($data['admin'])) background:#fff @endif">
  <div class="@if(isset($data['admin'])) body-admin @endif">
    {{ csrf_field() }}
    @php
        // print_r($data);die();
    @endphp

    @if ($data['trasaction_payment_type'] != 'Offline')
        <div class="kotak-full">
            <div class="container">
                <div class="row text-center">
                    <div class="col-12 text-13-3px text-black GoogleSans">
                        Silakan lakukan pembayaran terlebih dahulu
                    </div>
                </div>
            </div>
        </div>
        @if (isset($data['data_payment']))
        	@foreach ($data['data_payment'] as $pay)
        		@if ($pay['type'] == 'Midtrans')
        			<div class="kotak-biasa">
			            <div class="container">
			                <div class="row text-center" id="cont">
			                    <div class="col-12 GoogleSans text-15px space-text text-grey">Virtual Number</div>
                                <div class="col-12 text-red text-21-7px space-bottom space-top-all GoogleSans-Medium"><span id="myInput">{{ $pay['eci'] }}</span> &nbsp;
                                    <i class="fa fa-clone clone" data-togle="tooltip" title="Hooray!" onclick="copyToClipboard('#myInput')" style="cursor: pointer;"><div id="popover" rel="popover" data-content="Copied to clipboard" data-original-title="Copied"></div></i>
                                </div>
                                @if (isset($pay['bank']))
                                    <div class="col-12 text-16-7px text-black space-text GoogleSans">{{strtoupper($pay['bank'])}}</div>
                                @endif
                                @if (isset($pay['store']))
                                    <div class="col-12 text-16-7px text-black space-text GoogleSans">{{strtoupper($pay['store'])}}</div>
                                @endif
			                </div>
			            </div>
			        </div>
        		@endif
        	@endforeach
        @endif
    @endif
    <div class="kotak">
        <div class="container line-bottom">
            @if(isset($data['admin']))
            <div class="col-12 text-16-7px text-black space-text GoogleSans">{{ strtoupper($data['user']['name']) }}</div>
            <div class="col-12 text-16-7px text-black GoogleSans space-nice">{{ $data['user']['phone'] }}</div>
            @endif
            <div class="row space-bottom">
                <div class="col-6 text-grey-black text-14-3px GoogleSans">{{ $data['outlet']['outlet_name'] }}</div>
                <div class="col-6 text-right text-medium-grey text-13-3px GoogleSans">{{ date('d', strtotime($data['transaction_date'])) }} {{ $bulan[date('n', strtotime($data['transaction_date']))] }} {{ date('Y', strtotime($data['transaction_date'])) }}</div>
            </div>
            <div class="row space-text">
                <div class="col-4"></div>
                <div class="col-8 text-right text-medium-grey-black text-13-3px GoogleSans">#{{ $data['transaction_receipt_number'] }}</div>
            </div>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-12 text-13-3px text-grey-light GoogleSans">
                    Transaksi Anda
                    <hr style="margin:10px 0 20px 0">
                </div>
                @php $countQty = 0; @endphp
                @foreach ($data['product_transaction'] as $key => $val)
                    <div class="col-7 text-13-3px text-black GoogleSans">{{ $val['product']['product_name'] }}</div>
                    <div class="col-5 text-right text-13-3px text-black GoogleSans">{{ str_replace(',', '.', number_format($val['transaction_product_subtotal'])) }}</div>
                    <div class="col-12 text-grey text-12-7px text-black-grey-light GoogleSans">{{ $val['transaction_product_qty'] }} x {{ str_replace(',', '.', number_format($val['transaction_product_price'])) }}</div>
                    <div class="space-bottom col-12">
                        <div class="space-bottom text-12-7px text-grey-medium-light GoogleSans-Italic" style="word-break: break-word">
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
                <div class="col-6 text-13-3px space-bottom space-top-all text-black GoogleSans">Subtotal ({{$countQty}} item)</div>
                <div class="col-6 text-13-3px space-bottom space-top-all text-black text-right GoogleSans">{{ str_replace(',', '.', number_format($data['transaction_subtotal'])) }}</div>
            </div>
        </div>
    </div>

    <div class="kotak">
        <div class="container">
            <div class="row">
                <div class="col-12 text-14-3px space-top GoogleSans text-red">Detail Pembayaran <hr> </div>
                <div class="col-6 text-13-3px space-text GoogleSans text-black">Subtotal ({{$countQty}} item)</div>
                <div class="col-6 text-13-3px text-right space-text GoogleSans text-grey-black">{{ number_format($data['transaction_subtotal'], 0, ',', '.') }}</div>

                @if($data['transaction_tax'] > 0)
                <div class="col-6 text-13-3px space-text GoogleSans text-black">Tax</div>
                <div class="col-6 text-13-3px text-right GoogleSans text-grey-black">{{ number_format($data['transaction_tax'], 0, ',', '.') }}</div>
                @endif

                @if(isset($data['detail']['pickup_by']) && $data['detail']['pickup_by'] == 'GO-SEND')
                <div class="col-6 text-13-3px space-text GoogleSans text-black">Ongkos Kirim</div>
                    @if($data['transaction_is_free'] == '1')
                        <div class="col-6 text-13-3px text-right GoogleSans text-white"><div class="label-free">FREE</div></div>
                    @else
                        @if($data['transaction_shipment_go_send'] != $data['transaction_shipment'])
                            <div class="col-6 text-13-3px text-right GoogleSans text-grey-black text-strikethrough">{{ number_format($data['transaction_shipment_go_send'], 0, ',', '.') }}</div>
                            <div class="col-12 space-text text-13-3px text-right GoogleSans text-red">{{ number_format($data['transaction_shipment'], 0, ',', '.') }}</div>
                        @else
                            <div class="col-6 text-13-3px text-right GoogleSans text-grey-black">{{ number_format($data['transaction_shipment'], 0, ',', '.') }}</div>
                        @endif
                    @endif
                @endif

                @if(isset($data['balance']))
                <div class="col-6 text-13-3px space-text GoogleSans">Points</div>
                <div class="col-6 text-13-3px text-right GoogleSans text-red">- {{ number_format(abs($data['balance']), 0, ',', '.') }}</div>
                @endif

                <div class="col-12 text-12-7px text-right"><hr></div>
                <div class="col-6 text-13-3px GoogleSans text-black ">Total Pembayaran</div>
                @if(isset($data['balance']))
                <div class="col-6 text-13-3px text-right GoogleSans text-black">{{ number_format(($data['transaction_grandtotal'] - $data['balance']), 0, ',', '.') }}</div>
                @else
                <div class="col-6 text-13-3px text-right GoogleSans text-black">{{ number_format($data['transaction_grandtotal'], 0, ',', '.') }}</div>
                @endif
            </div>
        </div>
    </div>

    @if ($data['transaction_payment_status'] == 'Completed'|| $data['transaction_payment_status'] == 'Paid')
        <div class="kotak">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-14-3px space-top text-red GoogleSans">Metode Pembayaran <hr> </div>
                        @if(count($data['data_payment']) > 1)
                            @foreach($data['data_payment'] as $pay)
                                @if(isset($pay['type']) && $pay['type'] == 'Midtrans')
                                    @if(isset($pay['payment_type']))
                                    <div class="col-6 text-13-3px GoogleSans text-black">
                                        {{ ucwords(str_replace('_', ' ', $pay['payment_type'])) }}
                                    </div>
                                    <div class="col-6 text-13-3px text-black text-right GoogleSans">
                                    </div>
                                    @endif

                                    @if(isset($pay['bank']))
                                    <div class="col-6 text-black text-12-7px GoogleSans">
                                        {{ ucwords(str_replace('_', ' ', $pay['bank'])) }}
                                    </div>
                                    @endif

                                    @if(!isset($pay['bank']) && !isset($pay['payment_type']))
                                    <div class="col-6 text-black text-13-3px">
                                        Online Payment
                                    </div>
                                    <div class="col-6 text-13-3px text-black text-right GoogleSans">
                                    </div>
                                    @endif
                                @elseif(isset($pay['type']) && $pay['type'] == 'Ovo')
                                    <div class="col-6 text-13-3px GoogleSans text-black">
                                        Ovo
                                    </div>
                                    <div class="col-6 text-13-3px text-black text-right GoogleSans">
                                    </div>
                                @endif
                            @endforeach
                        @else
                        <div class="col-6 text-13-3px GoogleSans text-black">
                            @if ($data['trasaction_payment_type'] == 'Balance')
                                Points
                            @elseif ($data['trasaction_payment_type'] == 'Midtrans')
                                @if(isset($data['data_payment'][0]['payment_type']))
                                    {{ ucwords(str_replace('_', ' ', $data['data_payment'][0]['payment_type'])) }}
                                @else
                                    Online Payment
                                @endif
                            @elseif ($data['trasaction_payment_type'] == 'Manual')
                                Transfer Bank
                            @elseif ($data['trasaction_payment_type'] == 'Ovo')
                                Ovo
                            @elseif ($data['trasaction_payment_type'] == 'Offline')
                                @if(isset($data['data_payment'][0]['payment_bank']))
                                    {{$data['data_payment'][0]['payment_bank']}}
                                @else
                                    TUNAI
                                @endif
                            @endif
                        </div>
                        <div class="col-6 text-13-3px text-right text-black GoogleSans">
                        @if ($data['trasaction_payment_type'] == 'Offline')
                            SELESAI
                        @else
                            LUNAS
                        @endif
                        </div>
                        @endif

                    @if (isset($data['payment']['bank']))
                        <div class="col-6 text-black text-12-7px GoogleSans">{{ $data['payment']['bank'] }}</div>
                    @endif

                    @if (isset($data['payment']['payment_method']))
                        <div class="col-6 text-black text-12-7px GoogleSans">{{ $data['payment']['payment_method'] }}</div>
                    @endif

                    @if(isset($data['data_payment'][0]['bank']))
                    <div class="col-6 text-black text-12-7px GoogleSans">
                        {{ ucwords(str_replace('_', ' ', $data['data_payment'][0]['bank'])) }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

    <!-- @endif -->
    <input type="hidden" id="payment" value="{{ json_encode($data['data_payment']) }}">
    <input type="hidden" id="url" value="{{ config('url.app_url') }}">
    {{ csrf_field() }}

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
  </div>
  </body>

  <script type="text/javascript">
	   $(document).ready(function() {

      	$("#popover").popover('hide');

    });

       function copyToClipboard(element) {
          var $temp = $("<input>");
          $("body").append($temp);
          $temp.val($(element).text()).select();
          document.execCommand("copy");
          $temp.remove();
          $("#popover").popover('show');
          setTimeout(
              function()
              {
                $("#popover").popover('hide');
              }, 1000);
        }
    </script>
</html>