<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="{{ config('url.storage_url_view') }}{{('css/slide.css') }}" rel="stylesheet">
    <style type="text/css">
        @font-face {
                font-family: "WorkSans-Black";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-Black.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Bold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-Bold.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-ExtraBold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-ExtraBold.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-ExtraLight";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-ExtraLight.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Light";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-Light.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Medium";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-Medium.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Regular";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-Regular.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-SemiBold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-SemiBold.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Thin";
                font-style: normal;
                font-weight: 400;
                src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-Thin.ttf') }}');
        }
        .WorkSans-Black{
            font-family: "WorkSans-Black";
        }
        .WorkSans-Bold{
            font-family: "WorkSans-Bold";
        }
        .WorkSans-ExtraBold{
            font-family: "WorkSans-ExtraBold";
        }
        .WorkSans-ExtraLight{
            font-family: "WorkSans-ExtraLight";
        }
        .WorkSans-Medium{
            font-family: "WorkSans-Medium";
        }
        .WorkSans-Regular{
            font-family: "WorkSans-Regular";
        }
        .WorkSans{
            font-family: "WorkSans-Regular";
        }
        .WorkSans-SemiBold{
            font-family: "WorkSans-SemiBold";
        }
        .WorkSans-Thin{
            font-family: "WorkSans-Thin";
        }

        .kotak {
            margin : 10px;
            padding: 10px;
            /*margin-right: 15px;*/
            -webkit-box-shadow: 0px 1.7px 3.3px 0px #eeeeee;
            -moz-box-shadow: 0px 1.7px 3.3px 0px #eeeeee;
            box-shadow: 0px 1.7px 3.3px 0px #eeeeee;
            /* border-radius: 3px; */
            background: #fff;
            font-family: 'WorkSans';
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
            padding-bottom: 5px;
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
            color: #707070;
        }

        .text-grey-light {
            color: #b6b6b6;
        }

        .text-grey-medium-light{
            color: #a9a9a9;
        }

        .text-black-grey-light{
            color: #333333;
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

        .text-20px {
            font-size: 20px;
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

        .round-grey{
            border: 1px solid #aaaaaa;
            border-radius: 50%;
            width: 7px;
            height: 7px;
            display: inline-block;
            margin-right:3px;
        }

        .bg-red{
            background: #990003;
        }

        .bg-grey{
            background: #aaaaaa;
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

        .modal.fade .modal-dialog {
            transform: translate3d(0, 0, 0);
        }
        .modal.in .modal-dialog {
            transform: translate3d(0, 0, 0);
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
  <body style="@if(isset($data['admin'])) background:#fff; @endif background:#F7F8FA;">
  <div class="@if(isset($data['admin'])) body-admin @endif">
{{ csrf_field() }}
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content" style="border-radius: 42.3px; border: 0;">
            <div class="modal-body">
                <img class="img-responsive" style="display: block; width: 100%; padding: 30px" src="{{ $data['qr'] }}">
            </div>
            </div>
        </div>
    </div>

    <!-- <div id="modal-usaha">
        <div class="modal-usaha-content">
            <img class="img-responsive" style="display: block; max-width: 100%; padding-top: 10px" src="{{ $data['qr'] }}">
        </div>
    </div> -->

    @if ($data['trasaction_type'] != 'Offline')
        @if(isset($data['detail']['pickup_by']) && $data['detail']['pickup_by'] == 'GO-SEND')
            <div class="kotak-biasa">
                <div class="container">
                <div class="row text-center">
                    <div class="col-12 text-16-7px WorkSans-Bold" style="color: #FFFFFF;">
                        @if($data['detail']['reject_at'] != null)
                            PESANAN ANDA DITOLAK
                        @elseif($data['detail']['taken_at'] != null)
                            PESANAN SUDAH DIAMBIL
                        @elseif($data['detail']['ready_at'] != null)
                            PESANAN ANDA SUDAH SIAP
                        @elseif($data['detail']['receive_at'] != null)
                            PESANAN DITERIMA
                        @else
                            PESANAN ANDA MENUNGGU KONFIRMASI
                        @endif
                    </div>
                </div>
            </div>
                <div class="container">
                    <div class="row text-center">
                        <div class="col-12 WorkSans text-15px space-nice text-grey">Detail Pengiriman</div>
                        <div class="col-12 text-red text-21-7px space-bottom WorkSans-Medium">GO-SEND</div>
                        <div class="col-12 text-16-7px text-black space-bottom WorkSans">
                            {{ $data['detail']['transaction_pickup_go_send']['destination_name'] }}
                            <br>
                            {{ $data['detail']['transaction_pickup_go_send']['destination_phone'] }}
                        </div>
                        <div class="kotak-inside col-12">
                            <div class="col-12 text-13-3px text-grey-white space-nice text-center WorkSans">{{ $data['detail']['transaction_pickup_go_send']['destination_address'] }}</div>
                        </div>
                        <div class="col-12 text-15px space-bottom text-black WorkSans">Map</div>
                        <div class="col-12 space-bottom-big">
                            <div class="container">
                                <div id="map"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="kotak-biasa" style="background-color: #FFFFFF;box-shadow: 0 0.7px 3.3px #eeeeee;">
                <div class="container" style="padding: 0px;">
                    <div class="kotak-full" style="background-color: #EAB308;margin-bottom: 0px;box-shadow: 0 3.3px 6.7px #b3b3b3;">
                        <div class="container">
                            <div class="row text-center">
                                <div class="col-12 text-16-7px WorkSans-Bold" style="color: #FFFFFF;">
                                    @if($data['detail']['reject_at'] != null)
                                        PESANAN ANDA DITOLAK
                                    @elseif($data['detail']['taken_at'] != null)
                                        PESANAN SUDAH DIAMBIL
                                    @elseif($data['detail']['ready_at'] != null)
                                        PESANAN ANDA SUDAH SIAP
                                    @elseif($data['detail']['receive_at'] != null)
                                        PESANAN DITERIMA
                                    @else
                                        PESANAN ANDA MENUNGGU KONFIRMASI
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="kotak-biasa" style="background-color: #FFFFFF;padding: 15px;margin-top: 10px;box-shadow: 0 0.7px 3.3px #eeeeee;">
                        <div class="container">
                            <div class="row text-center">
                                <div class="col-12 text-15px text-black-grey-light space-text WorkSans-Bold">{{ $data['outlet']['outlet_name'] }}</div>
                                <div class="kotak-inside col-12">
                                    <div class="col-12 text-11-7px text-grey-white space-nice text-center WorkSans">{{ $data['outlet']['outlet_address'] }}</div>
                                </div>
                                <div class="col-12 WorkSans-Bold text-14px space-text text-black-grey-light">Kode Pickup Anda</div>

                                <div style="width: 135px;height: 135px;margin: 0 auto;" data-toggle="modal" data-target="#exampleModal">
                                    <div class="col-12 text-14-3px space-top"><img class="img-responsive" style="display: block; max-width: 100%; padding-top: 10px" src="{{ $data['qr'] }}"></div>
                                </div>
                                <div class="col-12 text-black-grey-light text-20px WorkSans-SemiBold">{{ $data['detail']['order_id'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="kotak-biasa" style="background-color: #FFFFFF;padding: 15px;margin-top: 10px;box-shadow: 0 0.7px 3.3px #eeeeee;">
            <div class="container">
                <div class="row text-center">
                    @if(isset($data['admin']))
                    <div class="col-12 text-16-7px text-black space-text WorkSans">{{ strtoupper($data['user']['name']) }}</div>
                    <div class="col-12 text-16-7px text-black WorkSans space-nice">{{ $data['user']['phone'] }}</div>
                    @endif
                        <div class="col-12 text-13-3px space-nice text-black-grey-light WorkSans-Medium" style="padding-bottom: 10px;">
                            @if ($data['detail']['pickup_type'] == 'set time') 
                                Pesanan Anda akan siap pada 
                            @else 
                                Pesanan Anda akan diproses pada 
                            @endif
                        </div>
                        <div class="col-12 text-14px space-text text-black-grey-light WorkSans-SemiBold" style="padding-bottom: 20px;">{{ date('d', strtotime($data['transaction_date'])) }} {{ $bulan[date('n', strtotime($data['transaction_date']))] }} {{ date('Y', strtotime($data['transaction_date'])) }}</div>
                        <div class="col-12 text-15px space-nice text-black-grey-light WorkSans-Bold" style="padding-bottom: 8.3px;">PICK UP</div>
                        <div class="col-12 text-21-7px WorkSans-Bold" style="color: #a6ba35;">
                            @if ($data['detail']['pickup_type'] == 'set time') 
                                {{ date('H:i', strtotime($data['detail']['pickup_at'])) }} 
                            @elseif($data['detail']['pickup_type'] == 'at arrival') 
                                SAAT KEDATANGAN
                            @else 
                                SAAT INI
                            @endif
                        </div>
                </div>
            </div>
        </div>

    @else
        @if(isset($data['admin']) && isset($data['user']['name']))
        <div class="kotak-biasa space-top-all">
            <div class="container">
                <div class="row text-center">
                    <div class="col-12 text-16-7px text-black space-text WorkSans">{{ strtoupper($data['user']['name']) }}</div>
                    <div class="col-12 text-16-7px text-black WorkSans space-nice">{{ $data['user']['phone'] }}</div>

                </div>
            </div>
        </div>
        @endif
    @endif

    <div class="kotak-biasa" style="background-color: #FFFFFF;padding: 15px;margin-top: 10px;box-shadow: 0 0.7px 3.3px #eeeeee;">
        <div class="row space-bottom">
            <div class="col-4 text-black-grey-light text-14px WorkSans-Bold">Transaksi</div>
            <div class="col-8 text-grey-white text-right text-medium-grey text-11-7px WorkSans">{{ date('d', strtotime($data['transaction_date'])) }} {{ $bulan[date('n', strtotime($data['transaction_date']))] }} {{ date('Y H:i', strtotime($data['transaction_date'])) }}</div>
        </div>
        <div class="row space-text">
            <div class="col-4"></div>
            <div class="col-8 text-right text-black-grey-light text-13-3px WorkSans-SemiBold">#{{ $data['transaction_receipt_number'] }}</div>
        </div>
        <div class="kotak" style="margin: 0px;border-radius: 10px;">
            <div class="row">
                @foreach ($data['product_transaction'] as $keyProduct => $itemProduct)
                    <div class="col-2 text-14px WorkSans text-black">
                        <div class="round-grey bg-grey" style="background: #aaaaaa;"></div>
                    </div>
                    <div class="col-10 text-14px WorkSans-SemiBold text-black" style="margin-left: -40px;margin-bottom: 10px;">{{$keyProduct}}</div>
                    @foreach ($itemProduct as $item)
                        <div class="col-2 text-13-3px WorkSans-SemiBold text-black">{{$item['transaction_product_qty']}}x</div>
                        <div class="col-6 text-14px WorkSans-SemiBold text-black" style="margin-left: -30px;margin-right: 20px;">{{$item['product']['product_name']}}</div>
                        <div class="col-4 text-13-3px text-right WorkSans-SemiBold text-black">{{ str_replace(',', '.', number_format(explode('.',$item['transaction_product_price'])[0])) }}</div>
                    @endforeach
                    @if ($itemProduct != end($data['product_transaction']))
                        <div class="col-12">
                            <hr style="border-top: 1px solid #eeeeee;">
                        </div>
                    @endif
                    @if ($item['product']['product_discounts'] != [])
                        <div class="col-2 text-13-3px WorkSans text-black">{{$item['transaction_product_qty']}}x</div>
                        <div class="col-6 text-13-3px WorkSans text-black" style="margin-left: -20px;margin-right: 20px;">{{$item['product']['product_name']}}</div>
                        <div class="col-4 text-13-3px text-right WorkSans text-black">{{ str_replace(',', '.', number_format($data['transaction_subtotal'])) }}</div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="kotak-biasa" style="background-color: #FFFFFF;padding: 15px;margin-top: 10px;box-shadow: 0 0.7px 3.3px #eeeeee;">
        <div class="row space-bottom">
            <div class="col-12 text-14px WorkSans-Bold text-black">Detail Pembayaran</div>
        </div>
        <div class="kotak" style="margin: 0px;margin-top: 10px;border-radius: 10px;">
            <div class="row">
                <div class="col-6 text-13-3px WorkSans-SemiBold text-black ">Subtotal ({{$data['transaction_item_total']}} item)</div>
                <div class="col-6 text-13-3px text-right WorkSans-SemiBold text-black">{{ str_replace(',', '.', number_format($data['transaction_subtotal'])) }}</div>
            </div>
        </div>
        <div style="margin: 0px;margin-top: 10px;padding: 10px;background: #f0f3f7;">
            <div class="row">
                <div class="col-6 text-13-3px WorkSans-SemiBold text-black ">Grand Total</div>
                @if(isset($data['balance']))
                <div class="col-6 text-13-3px text-right WorkSans-SemiBold text-black">{{ str_replace(',', '.', number_format($data['transaction_grandtotal'] - $data['balance'])) }}</div>
                @else
                <div class="col-6 text-13-3px text-right WorkSans-SemiBold text-black">{{ str_replace(',', '.', number_format($data['transaction_grandtotal'])) }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="kotak-biasa" style="background-color: #FFFFFF;padding: 15px;margin-top: 10px;box-shadow: 0 0.7px 3.3px #eeeeee;">
        <div class="row space-bottom">
            <div class="col-12 text-14px WorkSans-SemiBold text-black">Metode Pembayaran</div>
        </div>
        <div class="kotak" style="margin: 0px;margin-top: 10px;border-radius: 10px;">
            <div class="row">
                <div class="col-6 text-13-3px WorkSans-SemiBold text-black ">{{$data['trasaction_payment_type']}}</div>
                @if(isset($data['balance']))
                <div class="col-6 text-13-3px text-right WorkSans-SemiBold text-black">{{ str_replace(',', '.', number_format($data['transaction_grandtotal'] - $data['balance'])) }}</div>
                @else
                <div class="col-6 text-13-3px text-right WorkSans-SemiBold text-black">{{ str_replace(',', '.', number_format($data['transaction_grandtotal'])) }}</div>
                @endif
            </div>
        </div>
    </div>

    @if ($data['trasaction_type'] != 'Offline')
    <div class="kotak-biasa" style="background-color: #FFFFFF;padding: 15px;margin-top: 10px;">
        <div class="row space-bottom">
            <div class="col-12 text-14px WorkSans-Bold text-black">Status Pesanan</div>
        </div>
        <div class="kotak" style="margin: 0px;margin-top: 10px;border-radius: 10px;">
            <div class="row">
                @php $top = 5; $bg = true; @endphp
                @if($data['detail']['reject_at'] != null)
                    <div class="col-12 text-13-3px WorkSans-Medium text-black">
                        <div class="round-grey bg-grey"></div>
                        Pesanan Anda ditolak
                    </div>
                    <div class="col-12 top-5px">
                        <div class="inline text-center">
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                        </div>
                        <div class="inline vertical-top">
                            <div class="text-11-7px WorkSans text-black space-bottom">
                                {{date('d', strtotime($data['detail']['reject_at']))}} {{$bulan[date('n', strtotime($data['detail']['reject_at']))]}} {{date('Y H:i', strtotime($data['detail']['reject_at']))}}
                            </div>
                        </div>
                    </div>
                    @php $top += 5; $bg = false; @endphp
                @endif
                @if($data['detail']['taken_at'] != null)
                    <div class="col-12 text-13-3px WorkSans-Medium text-black top-{{$top}}px">
                        <div class="round-grey @if($bg) bg-grey @endif"></div>
                        Pesanan Anda sudah diambil
                    </div>
                    @php $top += 5; $bg = false; @endphp
                    <div class="col-12 top-{{$top}}px">
                        <div class="inline text-center">
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                        </div>
                        <div class="inline vertical-top">
                            <div class="text-11-7px WorkSans text-black space-bottom">
                                {{date('d F Y H:i', strtotime($data['detail']['taken_at']))}}
                            </div>
                        </div>
                    </div>
                    @php $top += 5; @endphp
                @endif
                @if($data['detail']['ready_at'] != null)
                    <div class="col-12 text-13-3px WorkSans-Medium text-black top-{{$top}}px">
                        <div class="round-grey @if($bg) bg-grey @endif"></div>
                        Pesanan Anda sudah siap
                    </div>
                    @php $top += 5; $bg = false; @endphp
                    <div class="col-12 top-{{$top}}px">
                        <div class="inline text-center">
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                        </div>
                        <div class="inline vertical-top">
                            <div class="text-11-7px WorkSans text-black space-bottom">
                                {{date('d', strtotime($data['detail']['ready_at']))}} {{$bulan[date('n', strtotime($data['detail']['ready_at']))]}} {{date('Y H:i', strtotime($data['detail']['ready_at']))}}
                            </div>
                        </div>
                    </div>
                    @php $top += 5; @endphp
                @endif
                @if($data['detail']['receive_at'] != null)
                    <div class="col-12 text-13-3px WorkSans-Medium text-black top-{{$top}}px">
                        <div class="round-grey @if($bg) bg-grey @endif"></div>
                            Pesanan Anda sudah diterima
                    </div>
                    @php $top += 5; $bg = false; @endphp
                    <div class="col-12 top-{{$top}}px">
                        <div class="inline text-center">
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                            <div class="line-vertical text-grey-medium-light">|</div>
                        </div>
                        <div class="inline vertical-top">
                            <div class="text-11-7px WorkSans text-black space-bottom">
                                {{date('d', strtotime($data['detail']['receive_at']))}} {{$bulan[date('n', strtotime($data['detail']['receive_at']))]}} {{date('Y H:i', strtotime($data['detail']['receive_at']))}}
                            </div>
                        </div>
                    </div>
                    @php $top += 5; @endphp
                @endif
                <div class="col-12 text-13-3px WorkSans-Medium text-black top-{{$top}}px">
                    <div class="round-grey @if($bg) bg-grey @endif"></div>
                    Pesanan Anda Menunggu Konfirmasi
                </div>
                <div class="col-12 text-11-7px WorkSans text-black space-bottom top-{{$top}}px">
                    <div class="round-white"></div>
                    {{date('d', strtotime($data['transaction_date']))}} {{$bulan[date('n', strtotime($data['transaction_date']))]}} {{date('Y H:i', strtotime($data['transaction_date']))}}
                </div>
            </div>
        </div>
    </div>
    @endif

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
            $('#exampleModal').on('show.bs.modal', function(e) {

                var url = window.location.href;
                var result = url.replace("#true", "");
                result = result.replace("#false", "");

                window.location.href = result + '#true'
            });

            $('#exampleModal').on('hide.bs.modal', function(e) {
                window.location.href = window.location.href + '#false'

                var url = window.location.href;
                var result = url.replace("#true", "");
                result = result.replace("#false", "");

                window.location.href = result + '#false'
            });
        });
    </script>
    </div>
  </body>
</html>