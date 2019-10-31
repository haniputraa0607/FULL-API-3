<?php
    use App\Lib\MyHelper;
    $title = "Deals Detail";
?>
@extends('webview.main')

@section('css')
	<style type="text/css">
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
			<div class="col-md-4 col-md-offset-4">
				<div style="background-color: rgb(255, 255, 255); padding: 10px;" class="text-center col-md-12 clearfix ProductSans">
					<div class="title">
						YEAY!
					</div>
					<div style="color: rgba(0, 0, 0, 0.7)">
						Selamat kamu berhasil mendapatkan voucher
					</div>
				</div>

				<div style="background-color: rgb(255, 255, 255);" class="col-md-12 clearfix ProductSans">
					<div class="card">

						<div class="container" style="padding-right: 0px;">
							<div class="pull-left" style="margin-top: 10px;width: 60%;">
								@php $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', "Jul", 'Agu', 'Sep', 'Okt', 'Nov', 'Des']; @endphp
								<p style="font-size:13.5px; color:rgba(32, 32, 32)">{{$deals['deals_voucher']['deal']['deals_title']}}</p>
								<br>
								<p style="font-size:10px; color:rgba(170, 170, 170)">Masa berlaku hingga</p>
								<p style="font-size:10px; color:rgba(170, 170, 170)">{{date('d', strtotime($deals['deals_voucher']['deal']['deals_end']))}} {{$bulan[date('m', strtotime($deals['deals_voucher']['deal']['deals_end']))-1]}} {{ date('Y', strtotime($deals['deals_voucher']['deal']['deals_end'])) }}</p>
								<br>
							</div>
							<div class="pull-right" style="width: 40%;">
							    <svg height="100px" width="150px" class="pull-right" style="border-bottom-right-radius: 10px; border-top-right-radius: 10px;">
                                    <pattern id="pattern" height="100%" width="100%" patternUnits="userSpaceOnUse" viewBox="0 0 1 1" preserveAspectRatio="xMidYMid slice">
                                      <image height="1" width="1" xlink:href="{{ env('S3_URL_API').$deals['deals_voucher']['deal']['deals_image'] }}"  preserveAspectRatio="xMidYMid meet"></image>
                                    </pattern>
                                  <polygon points="30 0, 200 0, 200 100, 0 100" fill="url(#pattern)"></polygon>
                                </svg>
							</div>
						</div>
					</div>
				</div>

				<br>

				<div style="background-color: rgb(248, 249, 251);" class="title-wrapper col-md-12 clearfix ProductSans-Bold">
					<div class="title" style="font-size: 15px; color: rgb(102, 102, 102);">Transaksi</div>
				</div>

				<div style="padding-top: 0px; color: rgb(0, 0, 0); height: 50px;" class="description-wrapper ProductSans">
					<div class="description pull-left">Tanggal</div>
					<div class="description pull-right">{{date('d', strtotime($deals['claimed_at']))}} {{$bulan[date('m', strtotime($deals['claimed_at']))-1]}} {{ date('Y', strtotime($deals['claimed_at'])) }} {{date('H:i', strtotime($deals['claimed_at']))}}</div>
				</div>

				<div style="padding-top: 0px; color: rgb(0, 0, 0); height: 50px;" class="description-wrapper ProductSans">
					<div class="description pull-left">ID Transaksi</div>
					<div class="description pull-right">{{strtotime($deals['claimed_at'])}}</div>
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
				<div style="padding-top: 0px; color: rgb(0, 0, 0); height: 50px;" class="description-wrapper ProductSans">
					<div class="description pull-left">Total Pembayaran</div>
					<div class="description pull-right">{{$payment}}</div>
				</div>

				<div style="padding-top: 0px; color: rgb(0, 0, 0); height: 70px; position: fixed; bottom: 10px; width: 100%;" class="description-wrapper ProductSans">
					<a style="width:100%; background-color:rgb(74, 0, 0); color: rgb(241, 228, 178);" class="btn btn-lg ProductSans" href="#yes">Lihat Voucher</a>
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
