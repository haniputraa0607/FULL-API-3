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
                font-family: "WorkSans-Black";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Black.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Bold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Bold.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-ExtraBold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-ExtraBold.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-ExtraLight";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-ExtraLight.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Light";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Light.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Medium";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Medium.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Regular";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Regular.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-SemiBold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-SemiBold.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Thin";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Thin.ttf') }}');
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
    		padding: 16.7px 11.7px;
    		/*margin-right: 15px;*/
            -webkit-box-shadow: 0px 1px 3.3px 0px #eeeeee;
            -moz-box-shadow: 0px 1px 3.3px 0px #eeeeee;
            box-shadow: 0px 1px 3.3px 0px #eeeeee;
			/* border-radius: 3px; */
			background: #fff;
			border-radius: 10px;
    	}

        body {
            background: #ffffff;
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
            color: #de2e1f;
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
    <div class="col-6 text-black text-14-3px WorkSans-Bold" style="margin-top:10px">Tukar Voucher</div>
    <div class="kotak">
		<div class="row">
			<div class="col-4 text-black text-14-3px WorkSans-Bold">Transaksi</div>
			<div class="col-8 text-right text-grey text-13-3px WorkSans">{{ date('d F Y', strtotime($data['date'])) }}</div>
			<div class="col-12 text-right text-13-3px WorkSans-Bold" style="margin-top: 10px;">#VOC-{{ $data['deal_voucher']['voucher_code'] }}</div>
		</div>
        <div class="row" style="margin-top: 30px;">
            <div class="col-6 text-13-3px text-black WorkSans-SemiBold">{{env('POINT_NAME', 'Points')}}</div>
            <div class="col-6 text-right text-13-3px text-red WorkSans-Medium">{{ str_replace(',', '.', number_format($data['balance'])) }}</div>
            <div class="col-12"><hr style="margin-bottom: 20px;margin-top: 16.7px;"></div>
			<div class="col-6 text-13-3px text-black WorkSans-SemiBold">Total Pembayaran</div>
			<div class="col-6 text-right text-13-3px text-dark-grey WorkSans-Medium">{{ str_replace(',', '.', number_format(abs($data['balance']))) }} poin</div>
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