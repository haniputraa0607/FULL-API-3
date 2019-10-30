<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="{{ env('S3_URL_VIEW') }}{{('css/slide.css') }}" rel="stylesheet">
    <style type="text/css">
        @font-face {
                font-family: "ProductSans-Bold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/ProductSans-Bold.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-Regular";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/ProductSans-Regular.ttf') }}');
        }
        .ProductSans{
            font-family: "ProductSans-Regular";
        }
        .ProductSans-Bold{
            font-family: "ProductSans-Bold";
        }
        .kotak {
            margin : 10px;
            padding: 10px;
            /*margin-right: 15px;*/
            -webkit-box-shadow: 0px 0px 21px 0px rgba(168,168,168,1);
            -moz-box-shadow: 0px 0px 21px 0px rgba(168,168,168,1);
            box-shadow: 0px 0px 21px 0px rgba(168,168,168,1);
            border-radius: 3px;
            background: #fff;
            font-family: 'GoogleSans';
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

        .space-bottom20 {
            padding-bottom: 20px;
        }

        .space-top {
            padding-top: 15px;
        }

        .space-text {
            padding-bottom: 10px;
        }

        .line-bottom {
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }

        .text-grey {
            color: rgb(182, 182, 182);
        }
        .text-grey2 {
            color: rgba(0, 0, 0, 0.6);
        }

        .text-much-grey {
            color: #bfbfbf;
        }

        .text-black {
            color: rgb(0, 0, 0);
        }

        .text-red {
            color: #990003;
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
    </style>
  </head>
  <body>
    {{ csrf_field() }}
    @php
        // print_r($data);die();
    @endphp
    <div class="kotak">
        <div class="container line-bottom ProductSans">
            <div class="row space-bottom">
                <div class="col-6 text-grey-black text-14-3px">Beli Kupon</div>
                @php $bulan = ['', 'Januari', 'Februari','Maret','April','Mei','Juni','Juli','Agustus','September','November','Desember']; @endphp
                <div class="col-6 text-right text-grey text-13-3px">{{ date('d', strtotime($data['date'])) }} {{$bulan[date('n', strtotime($data['date']))]}} {{date('Y H:i', strtotime($data['date'])) }}</div>
            </div>
            <div class="row space-text">
                <div class="col-4"></div>
                <div class="col-8 text-right text-black ProductSans-Bold text-13-3px">#TRX-{{ $data['voucher_hash_code'] }}</div>
            </div>
        </div>
        <div class="container ProductSans">
            <div class="row">
                <div class="col-12 text-grey text-13-3px">Transaksi Anda</div>
                <div class="col-12"><hr></div>
            </div>
            <div class="row">
                <div class="col-6 text-black text-13-3px">{{ $data['deal_voucher']['deal']['deals_title'] }}</div>
                <div class="col-6 text-right text-grey2 text-13-3px">@if (!empty($data['voucher_price_cash'])) Rp {{ str_replace(',', '.', number_format($data['voucher_price_cash'])) }} @else -{{ str_replace(',', '.', number_format($data['voucher_price_point'])) }} points @endif</div>
            </div>
            <div class="row">
                @if($data['deal_voucher']['deal']['deals_second_title'])
                <div class="col-6 text-black text-13-3px">{{ $data['deal_voucher']['deal']['deals_second_title'] }}</div>
                @endif
            </div>
            <hr>
            <div class="row">
                <div class="col-6 text-black text-13-3px">Status</div>
                <div class="col-6 text-right text-grey2 text-13-3px">@if (is_null($data['used_at'])) Belum Digunakan @else Digunakan @endif</div>
            </div>
            @if (!is_null($data['used_at'])) 
            <hr>
            <div class="row">
                <div class="col-6 text-black text-13-3px">Tanggal Penggunaan</div>
                <div class="col-6 text-right text-grey2 text-13-3px">{{ date('d', strtotime($data['used_at'])) }} {{$bulan[date('n', strtotime($data['used_at']))]}} {{date('Y H:i', strtotime($data['used_at'])) }}</div>
            </div>
            @endif
        </div>
    </div>



    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.js"></script>
  </body>
</html>