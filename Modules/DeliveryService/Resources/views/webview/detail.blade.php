<?php
    use App\Lib\MyHelper;
    $title = "Delivery Service";
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
        .bg-yellow{
            background-color: #d1af28;
        }
        .bg-red{
            background-color: #c02f2fcc;
        }
        .bg-black{
            background-color: #000c;
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
    	.subtitle2{
    		margin-bottom: 20px;
    		color: #aaaaaa;
    		font-size: 15px;
    	}
    	.kode-text{
    	    margin: 20px 0 8px;
    		color: #aaaaaa;
    		font-size: 18px;
    	}
    	.voucher-code{
    	    font-size: 22px;
    	}
    	#invalidate {
            color:#fff;
            background-color: #990003;
            border: none;
            border-radius: 5px;
            margin-bottom: 70px;
            margin-top: 30px;
            width: 90%;
            height: 48px;
            font-size: 18px;
        }
        #qr-code-modal{
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0, 0.5);
            /*width: 100%;*/
            /*height: 100vh;*/
            display: none;
            z-index: 999;
            overflow-y: auto;
        }
        #qr-code-modal-content{
            position: absolute;
            left: 50%;
            top: 50%;
            margin-left: -155px;
            margin-top: -155px;
            padding: 30px;
            background: #fff;
            border-radius: 42.3px;
            border: 0;
        }
         .deals-qr {
            background: #fff;
            width: 135px;
            height: 135px;
            margin: 0 auto;
        }
		.card {
			background-color: rgb(248, 249, 251);
			transition: 0.3s;
			width: 100%;
			border-radius: 10px;
			background-repeat: no-repeat;
			background-size: 40% 100%;
			background-position: right;
		}
		#timer{
    		right: 0px;
			bottom:0px;
			width: 100%;
    	}
		#day{
    		right: 0px;
			bottom:0px;
			width: 100%;
    		padding: 5px;
    	}
		.card:hover {
			box-shadow: 0 0 5px 0 rgba(0,0,0,0.1);
		}
        @media only screen and (min-width: 768px) {
            .deals-img{
	    		width: auto;
	    		height: auto;
	    	}
        }

		select {
			font-family: ProductSans;
			background-color: transparent;
			width: 100%;
			padding: $select-padding 0;
			font-size: $select-font-size;
			color: $select-color;
			border: none;
			border-bottom: 1px solid $select-border-color;
		}

		select:focus {
			outline: none
		}
    </style>
@stop

@section('content')
	<div class="deals-detail" style="margin-top:20px; margin-bottom: 220px;">
		<div class="col-md-4 col-md-offset-4">
			<div style="background-color: rgb(255, 255, 255); color: rgb(84, 84, 84);" class="title-wrapper col-md-12 clearfix ProductSans">
				<div style="line-height: 1.5; font-size:14px;" class="title ProductSans">
					{!!$result['head']['description']!!}
				</div>
			</div>

			<hr style="width:90%; margin-top: 0px; margin-bottom: 5px;">

			<div style="background-color: rgb(255, 255, 255); color: rgb(84, 84, 84); justify-content:center;" class="title-wrapper col-md-12 clearfix ProductSans">
				<div style="font-size:18px;padding-bottom: 0px;" class="title">
					{!!$result['content']['head_content']!!}
				</div>
			</div>

			<div style="background-color: rgb(255, 255, 255); color: rgb(84, 84, 84);" class="title-wrapper col-md-12 clearfix ProductSans">
				<div style="line-height: 1.5; font-size:14px;" class="title">
					{!!$result['content']['description_content']!!}
				</div>
			</div>

			<div style="position: fixed;width: 100%;bottom: 20px; background-color: rgb(255, 255, 255);">
				<div style="background-color: rgb(255, 255, 255); color: rgb(84, 84, 84);" class="title-wrapper col-md-12 clearfix ProductSans">
					<div style="font-size:14px;padding-bottom: 5px;" class="title">
						Area Order
					</div>
				</div>

				<div style="background-color: rgb(255, 255, 255);" class="title-wrapper col-md-12 clearfix ProductSans">
					<select class="browser-default ProductSans" style="font-size:16px; width:100%; color: rgb(74, 0, 0);">
						@foreach ($result['area'] as $item)
						<option value="{{$item['phone_number']}}">{{$item['area_name']}}</option>
						@endforeach
					</select>
				</div>
				<div style="background-color: rgb(255, 255, 255);" class="col-md-12">
					<hr style="margin-top: 2px;">
				</div>

				<div style="background-color: rgb(255, 255, 255);" class="title-wrapper col-md-12 clearfix ProductSans">
					<div style="font-size:10px;padding-bottom: 0px; width: 100%;" class="title">
						<a style="width: 100%;background-color: rgb(74, 0, 0);text-decoration: none;border-color: transparent;color: rgb(241, 228, 178);" class="btn btn-lg">Call Us</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<br>
@stop

@section('page-script')
    <script type="text/javascript" src="{{ env('STORAGE_URL_VIEW') }}{{('assets/global/plugins/jquery.min.js') }}" type="text/javascript"></script>
	<script type="text/javascript">
		$("select").change(function() {
			$("a").attr("href", "#call*"+this.value)
		});
		$( document ).ready(function() {
			$( "select" ).trigger( "change" );
		});
    </script>
@stop