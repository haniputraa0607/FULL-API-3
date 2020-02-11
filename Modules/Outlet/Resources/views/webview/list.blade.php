<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<!-- Bootstrap CSS -->
    <link href="{{ env('API_URL') }}css/general.css" rel="stylesheet">
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
	p { margin: 0 0 0.0001pt; }

    </style>
        <link rel="stylesheet" href="{{ env('S3_URL_VIEW') }}{{ ('assets/css/owl.carousel.min.css') }}">
        <link rel="stylesheet" href="{{ env('S3_URL_VIEW') }}{{ ('assets/css/owl.theme.default.min.css') }}">
  </head>
  <body>

    <div class="kotak1" style='margin-bottom: 20px;'>
  		<div class="container">
  			@php
  				$hari = date ("D");

			switch($hari){

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
				
				case 'Sun':
					$hari_ini = "Minggu";
				break;
			}

			@endphp
			<div class="row WorkSans">
				<div class="col-12">
				    @if (!empty($data[0]['outlet_schedules']))
						@foreach ($data[0]['outlet_schedules'] as $key => $val)
						<div style="@if ($val['day'] == $hari_ini) color: `#383b67; @else color: #AAAAAA; @endif font-size: 13.3px; padding-bottom: 3px;" class="WorkSans-Bold">{{ strtoupper($val['day']) }}</div>
						<div style="@if ($val['day'] == $hari_ini) color: `#383b67; @else color: #AAAAAA; @endif font-size: 13.3px; padding-bottom: 0; padding-left: 5px;">
							@if($val['is_closed'] == '1')
								TUTUP
							@else
								{{date('H.i', strtotime($val['open']))}} - {{date('H.i', strtotime($val['close']))}}
							@endif
						</div>
						<hr style="margin-bottom: 5px;margin-top: 5px; @if(end($data[0]['outlet_schedules']) == $val) display: none; @endif">
						@endforeach
					@else
						<div class="WorkSans space-text" style="color: #AAAAAA; font-size: 11.7px; padding-bottom: 0;">Belum Tersedia</div>
					@endif
				</div>
			</div>
	   	</div>
  	</div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
	<script src="{{ env('API_URL') }}js/jquery.js"></script>
	<script src="{{ env('API_URL') }}js/general.js"></script>
  </body>
</html>