<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans|Questrial" rel="stylesheet">
    <style type="text/css">
        body {
            cursor: pointer;
        }
    	.kotak1 {
    		padding-top: 10px;
    		padding-bottom: 0;
    		padding-left: 7px;
    		padding-right: 7px;
			background: #fff;
    	}

    	.kotak2 {
    		padding-top: 10px;
    		padding-bottom: 10px;
    		padding-left: 26.3px;
    		padding-right: 26.3px;
			background: #fff;
      height: 100%
    	}

    	.red div {
    		color: #990003;
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

        .space-top {
            padding-top: 15px;
        }

    	.space-text {
    		padding-bottom: 10px;
    	}

    	.space-sch {
    		padding-bottom: 5px;
    		margin-left: 0 !important;
    	}

    	.min-left {
    		margin-left: -15px;
    		margin-right: 10px;
    	}

    	.line-bottom {
    		border-bottom: 0.3px solid #dbdbdb;
    		margin-bottom: 5px;
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
    		color: #666666;
    	}

    	.text-grey-black {
    		color: #4c4c4c;
    	}

		.text-grey-2{
			color: #979797;
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
		@font-face {
                font-family: "ProductSans-Medium";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/ProductSans-Medium.ttf') }}');
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
        .ProductSans-MediumItalic{
            font-family: "ProductSans-Medium";
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

    	.logo-img {
    		width: 16.7px;
    		height: 16.7px;
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

	.day-alphabet{
		margin: 0 10px;
		border-radius: 50%;
		width: 20px;
		height: 20px;
		text-align: center;
		background: #d9d6d6;
		color: white !important;
		padding-top: 1px;
	}

	.day-alphabet-today{
		background: #6c5648;
	}

	.fa-angle-down{
		transform: rotate(0deg);
		transition: transform 0.25s linear;
	}

	.fa-angle-down.open{
		transform: rotate(180deg);
		transition: transform 0.25s linear;
	}

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
  </head>
  <body>

	<div class="kotak1" style="padding-top: 0px;">
  		<div class="container">
  		    <div class="ProductSans space-text" style="color: rgb(74, 0, 0); font-size: 12.7px; padding-bottom: 5px;">Alamat</div>
			<hr style="margin: 0;">
			<div class="ProductSans space-text" style="color: rgb(0, 0, 0); font-size: 12.7px; padding-bottom: 0;">{{$data[0]['outlet_address']}}</div>
	   	</div>
	</div>

	<div class="kotak1">
  		<div class="container">
  		    <div class="ProductSans space-text" style="color: rgb(74, 0, 0); font-size: 12.7px; padding-bottom: 5px;">Nomor Telepon</div>
			<hr style="margin: 0;">
			<div class="ProductSans space-text" style="color: rgb(0, 0, 0); font-size: 12.7px; padding-bottom: 0;">{{$data[0]['outlet_phone']}}</div>
	   	</div>
  	</div>

    <div class="kotak1" @if($data[0]['big_order'] == 0) style='margin-bottom: 220px;' @endif>
  		<div class="container">
  		    <div class="ProductSans space-text" id="testClick" style="color: rgb(74, 0, 0); font-size: 12.7px; padding-bottom: 5px;">Jam Operasional <i style="color: rgb(74, 0, 0);" class="pull-right icon fa fa-angle-down"></i></div>
  			@php
  				$hari = date ("D");

			switch($hari){
				case 'Sun':
					$hari_ini = "Minggu";
				break;

				case 'Mon':
					$hari_ini = "Senin";
				break;

				case 'Tue':
					$hari_ini = "Selasa";
				break;

				case 'Wed':
					$hari_ini = "Rabu";
				break;

				case 'Thu':
					$hari_ini = "Kamis";
				break;

				case 'Fri':
					$hari_ini = "Jumat";
				break;

				default:
					$hari_ini = "Sabtu";
				break;
			}

			@endphp
			<hr style="margin: 0;">
			<div class="row ProductSans">
				<div class="col-8">
				    @if (!empty($data[0]['outlet_schedules']))
						@foreach ($data[0]['outlet_schedules'] as $key => $val)
						@if ($val['day'] == $hari_ini)
							<div id="today" class="pull-left row space-sch">
								<div style="@if ($val['day'] == $hari_ini) color: rgb(131, 112, 70); @else color: rgb(0, 0, 0); @endif font-size: 12.7px; padding-bottom: 0;" class="col-3 min-left">{{ $val['day'] }}</div>
								<div style="@if ($val['day'] == $hari_ini) color: rgb(131, 112, 70); @else color: rgb(0, 0, 0); @endif font-size: 12.7px; padding-bottom: 0;" class="col-9">
									@if($val['is_closed'] == '1')
										TUTUP
									@else
										{{date('H.i', strtotime($val['open']))}} - {{date('H.i', strtotime($val['close']))}}
									@endif
								</div>
							</div>
							@endif
						@endforeach
						@foreach ($data[0]['outlet_schedules'] as $key => $val)
						<div style="display: none;" class="pull-left anotherDay row space-sch">
							<div style="@if ($val['day'] == $hari_ini) color: rgb(131, 112, 70); @else color: rgb(0, 0, 0); @endif font-size: 12.7px; padding-bottom: 0;" class="col-3 min-left">{{ $val['day'] }}</div>
							<div style="@if ($val['day'] == $hari_ini) color: rgb(131, 112, 70); @else color: rgb(0, 0, 0); @endif font-size: 12.7px; padding-bottom: 0;" class="col-9">
								@if($val['is_closed'] == '1')
									TUTUP
								@else
									{{date('H.i', strtotime($val['open']))}} - {{date('H.i', strtotime($val['close']))}}
								@endif
							</div>
							@if ($val['day'] == "Minggu")
							@endif
						</div>
						@endforeach
					@else
						<div class="ProductSans space-text" style="color: rgb(0, 0, 0); font-size: 12.7px; padding-bottom: 0;">Belum Tersedia</div>
					@endif
				</div>
			</div>
	   	</div>
  	</div>

	@if($data[0]['big_order'] == 1)
	<div class="kotak1" style='margin-bottom: 220px'>
  		<div class="container">
  		    <div class="ProductSans text-center space-text" style="color: rgb(0, 0, 0); font-size: 15px; padding-bottom: 5px;">Big Order Delivery Service</div>
		  <div class="ProductSans space-text" style="color: rgb(102, 102, 102); font-size: 12.7px; padding-bottom: 0;">Khusus pemesanan diatas 50 pax, silahkan menghubungi <a style="color: rgb(128, 0, 0); text-decoration: underline;" href="#delivery_service">Call Center</a> kami untuk mendapatkan penawaran special</div>
	   	</div>
	</div>
	@endif

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCOHBNv3Td9_zb_7uW-AJDU6DHFYk-8e9Y&v=3.exp&signed_in=true&libraries=places"></script>
	<script>
	$(document).ready(function() {
    	$("#testClick").click(function() {
    		if($("#today").is(':visible')){
    			$(".icon").addClass('open');
    			$("#today").hide()
    			$(".anotherDay").show(500)
    		} else{
    			$(".icon").removeClass('open');
    			$("#today").show()
    			$(".anotherDay").hide(500)
    		}
    	});
	});
	</script>
  </body>
</html>