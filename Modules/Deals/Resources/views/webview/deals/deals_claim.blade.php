<?php
    use App\Lib\MyHelper;
    $title = "Deals Detail";
?>
@extends('webview.main')

@section('css')
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
    	p{
    		margin-top: 0px !important;
    		margin-bottom: 0px !important;
    	}
    	.deals-detail > div{
    		padding-left: 0px;
    		padding-right: 0px;
    	}
    	.deals-img{
    		width: 100%;
    		height: auto;
    	}
    	.title-wrapper{
    		background-color: #f8f8f8;
    		position: relative;
    		display: flex;
    	}
    	.col-left{
    		flex: 70%;
    	}
    	.col-right{
    		flex: 30%;
    	}
    	.title-wrapper > div{
    		padding: 10px 5px;
    	}
    	.title{
    		font-size: 18px;
    		color: rgba(32, 32, 32);
    	}
    	#timer{
    		position: absolute;
    		right: 0px;
			bottom:0px;
			width: 100%;
    		padding: 10px;
    		/*border-bottom-left-radius: 7px !important;*/
    		color: #fff;
            display: none;
    	}
        .bg-yellow{
            background-color: #d1af28;
        }
        .bg-red{
            background-color: #c02f2fcc;
        }
        .bg-black{
            background-color: rgba(0, 0, 0, 0.5);
        }
        .bg-grey{
            background-color: #cccccc;
        }
    	.fee{
			margin-top: 30px;
			font-size: 18px;
			color: #000;
    	}
    	.description-wrapper{
    		padding: 20px;
    	}
		.outlet-wrapper{
		    padding: 0 20px;
		}
    	.description{
    	    padding-top: 10px;
    	    font-size: 14px;
			height: 20px;
    	}
    	.subtitle{
    		margin-bottom: 10px;
    		color: #000;
    		font-size: 15px;
    	}
    	.outlet{
    	    font-size: 13.5px;
    	}
    	.outlet-city:not(:first-child){
    		margin-top: 10px;
    	}

    	.voucher{
    	    margin-top: 30px;
    	}
    	.font-red{
    	    color: #990003;
    	}

        @media only screen and (min-width: 768px) {
            /* For mobile phones: */
            .deals-img{
	    		width: auto;
	    		height: auto;
	    	}
        }
		.card {
			box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
			transition: 0.3s;
			width: 100%;
			border-radius: 10px;
			background-repeat: no-repeat;
			background-size: 40% 100%;
			background-position: right;
		}

		.card:hover {
			box-shadow: 0 0 5px 0 rgba(0,0,0,0.1);
		}

		 .image-4 {
         clip-path: polygon(30% 0, 100% 0, 100% 100%, 0 100%);
		}
    </style>
@stop

@section('content')
	<div class="deals-detail">
		@if(!empty($deals))
			<div class="col-md-4 col-md-offset-4" style="background-color: #f8f9fb;">
				<div style="background-color: #ffffff;padding: 10px;box-shadow: 0 0.7px 3.3px #eeeeee;" class="col-md-12 clearfix WorkSans">
					<div class="text-center title WorkSans-SemiBold" style="color: #a6ba35;font-size: 20px;">
						Horayy!
					</div>
					<div class="text-center WorkSans-SemiBold" style="color: #333333;margin-top: 13.3px;">
						Terima kasih telah membeli
					</div>
					<div style="position: relative;margin-top: 26.7px;">
						<hr style="position:absolute;z-index: 1;border: none;border-left: 1px dashed #eeeeee;height: 98px;left: 36%;top: -5%;">
						<div style="width: 56%;height: 100px;position: absolute;top: 10%;left: 40%;">
							<div class="cotainer">
								<div class="pull-left" style="margin-top: 10px;">
									@php $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', "Juli", 'Agustus', 'September', 'Oktober', 'November', 'Desember']; @endphp
									<p style="font-size: 15px;color: #333333;">{{$deals['deals_voucher']['deal']['deals_title']}}</p>
									<p style="font-size: 13.3px;color: #333333;">{{$deals['deals_voucher']['deal']['deals_second_title']}}</p>
									<div style="@if (isset($deals['deals_voucher']['deal']['deals_second_title'])) margin-top: 20px; @else margin-top: 38px; @endif"></div>
									<p style="font-size: 10.7px;color: #707070;padding: 5px 10px;background-color: #f0f3f7;border-radius: 100px;">Kedaluwarsa {{date('d', strtotime($deals['deals_voucher']['deal']['deals_end']))}} {{$bulan[date('m', strtotime($deals['deals_voucher']['deal']['deals_end']))-1]}} {{ date('Y', strtotime($deals['deals_voucher']['deal']['deals_end'])) }}</p>
								</div>
							</div>
						</div>
						<img src="{{ env('S3_URL_API').$deals['deals_voucher']['deal']['deals_image'] }}" alt="" style="width: 85px;position: absolute;border-radius: 50%;top: 14.5%;left: 6.5%;">
						<img style="width:100%" height="130px" src="{{ env('S3_URL_API')}}img/asset/bg_item_kupon_saya.png" alt="">
					</div>
				</div>

				<div style="background-color: #ffffff;;margin-top: 10px;" class="title-wrapper col-md-12 clearfix WorkSans-Bold">
					<div class="title" style="font-size: 15px; color: #333333;">Transaksi</div>
				</div>

				<div style="background-color: #ffffff;padding-top: 0px; color: #333333; height: 40px;" class="description-wrapper WorkSans">
					<div class="description pull-left WorkSans-SemiBold">Tanggal</div>
					<div style="color: #707070;" class="description pull-right">{{date('d', strtotime($deals['claimed_at']))}} {{$bulan[date('m', strtotime($deals['claimed_at']))-1]}} {{ date('Y', strtotime($deals['claimed_at'])) }} {{date('H:i', strtotime($deals['claimed_at']))}}</div>
				</div>

				<div style="background-color: #ffffff;padding-top: 0px; color: #333333; height: 60px;" class="description-wrapper WorkSans">
					<div class="description pull-left WorkSans-SemiBold">ID Transaksi</div>
					<div style="color: #707070;" class="description pull-right">{{strtotime($deals['claimed_at'])}}</div>
				</div>

				@php
					if ($deals['voucher_price_point'] != null) {
						$payment = number_format($deals['voucher_price_point']).' points';
					} elseif ($deals['voucher_price_cash'] != null) {
						$payment = number_format($deals['voucher_price_cash']).' points';
					} else {
						$payment = 'Gratis';
					}
				@endphp
				<div style="background-color: #ffffff;">
					<div style="background-color: #f0f3f7;padding-top: 0px;color: #333333;height: 45px;border-radius: 5px;margin: 0px 15px;" class="description-wrapper WorkSans">
						<div class="description pull-left WorkSans-SemiBold">Total Pembayaran</div>
						<div class="description pull-right WorkSans-SemiBold">{{$payment}}</div>
					</div>
				</div>

				<div style="background-color: #ffffff;padding-top: 0px; color: rgb(0, 0, 0); height: 70px; position: fixed; bottom: 10px; width: 100%;" class="description-wrapper WorkSans">
					<a style="width:100%; background-color: #383b67; color: #ffffff;" class="btn btn-lg WorkSans-Bold" href="#yes">Lihat Voucher</a>
				</div>
			</div>
		@else
			<div class="col-md-4 col-md-offset-4">
				<h4 class="text-center" style="margin-top: 30px;">Deals is not found</h4>
			</div>
		@endif
	</div>
@stop

@section('page-script')
    @if(!empty($deals))
        <script type="text/javascript">
            @php $month = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', "Juli", 'Agustus', 'September', 'Oktober', 'November', 'Desember']; @endphp

            // timer
            var deals_start = "{{ strtotime($deals['deals_voucher']['deal']['deals_start']) }}";
            var deals_end   = "{{ strtotime($deals['deals_voucher']['deal']['deals_end']) }}";
            var timer_text;
            var difference;

            if (server_time >= deals_start && server_time <= deals_end) {
                // deals date is valid and count the timer
                difference = deals_end - server_time;
                document.getElementById('timer').classList.add("bg-black");
            }
            else {
                // deals is not yet start
                difference = deals_start - server_time;
                document.getElementById('timer').classList.add("bg-grey");
            }

            var display_flag = 0;
            this.interval = setInterval(() => {
                if(difference >= 0) {
                    timer_text = timer(difference);
					@if($deals['deals_voucher']['deal']['deals_status'] == 'available')
					if(timer_text.includes('lagi')){
						document.getElementById("timer").innerHTML = "<i class='fas fa-clock'></i> &nbsp; Berakhir dalam";
					}else{
						document.getElementById("timer").innerHTML = "<i class='fas fa-clock'></i> &nbsp; Berakhir pada";
					}
                    document.getElementById("timer").innerHTML += " ";
                    document.getElementById('timer').innerHTML += timer_text;
                    @elseif($deals['deals_voucher']['deal']['deals_status'] == 'soon')
                    document.getElementById("timer").innerHTML = "<i class='fas fa-clock'></i> &nbsp; Akan dimulai pada";
                    document.getElementById("timer").innerHTML += " ";
                    document.getElementById('timer').innerHTML += "{{ date('d', strtotime($deals['deals_voucher']['deal']['deals_start'])) }} {{$month[date('m', strtotime($deals['deals_voucher']['deal']['deals_start']))-1]}} {{ date('Y', strtotime($deals['deals_voucher']['deal']['deals_start'])) }} : {{ date('H:i', strtotime($deals['deals_voucher']['deal']['deals_start'])) }}";
                    @endif

                    difference--;
                }
                else {
                    clearInterval(this.interval);
                }

                // if days then stop the timer
                if (timer_text!=null && timer_text.includes("day")) {
                    clearInterval(this.interval);
                }

                // show timer
                if (display_flag == 0) {
                    document.getElementById('timer').style.display = 'block';
                    display_flag = 1;
                }
            }, 1000); // 1 second

            function timer(difference) {
                if(difference === 0) {
                    return null;    // stop the function
                }

                var daysDifference, hoursDifference, minutesDifference, secondsDifference, timer;

                // countdown
                daysDifference = Math.floor(difference/60/60/24);
                if (daysDifference > 0) {
					timer = "{{ date('d', strtotime($deals['deals_voucher']['deal']['deals_end'])) }} {{$month[ date('m', strtotime($deals['deals_voucher']['deal']['deals_end']))-1]}} {{ date('Y', strtotime($deals['deals_voucher']['deal']['deals_end'])) }}";
                  //  timer = daysDifference + " hari";
                    console.log('timer d', timer);
                }
                else {
                    difference -= daysDifference*60*60*24;

                    hoursDifference = Math.floor(difference/60/60);
                    difference -= hoursDifference*60*60;
                    hoursDifference = ("0" + hoursDifference).slice(-2);

                    minutesDifference = Math.floor(difference/60);
                    difference -= minutesDifference*60;
                    minutesDifference = ("0" + minutesDifference).slice(-2);

                    secondsDifference = Math.floor(difference);

                    if (secondsDifference-1 < 0) {
                        secondsDifference = "00";
                    }
                    else {
                        secondsDifference = secondsDifference-1;
                        secondsDifference = ("0" + secondsDifference).slice(-2);
                    }
                    console.log('timer h', hoursDifference);
                    console.log('timer m', minutesDifference);
                    console.log('timer s', secondsDifference);

                    timer = hoursDifference + " : " + minutesDifference + " : " + secondsDifference;
                    console.log('timer', timer);
                }

                return timer;
            }
        </script>
    @endif
@stop
