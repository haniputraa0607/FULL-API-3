<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans|Questrial" rel="stylesheet">
    <link href="{{ env('STORAGE_URL_VIEW') }}{{('css/slide.css') }}" rel="stylesheet">
    <style type="text/css">
    	.kotak {
    		margin : 10px;
    		padding: 10px;
    		/*margin-right: 15px;*/
    		-webkit-box-shadow: 0px 0px 21px 0px rgba(168,168,168,1);
			-moz-box-shadow: 0px 0px 21px 0px rgba(168,168,168,1);
			box-shadow: 0px 0px 21px 0px rgba(168,168,168,1);
			border-radius: 3px;
			background: #fff;
			font-family: 'Open Sans', sans-serif;
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

    	.space-text {
    		padding-bottom: 10px;
    	}

    	.line-bottom {
    		border-bottom: 1px solid #eee;
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

    	.text-grey-black {
    		color: #4c4c4c;
    	}

    	.text-grey-red {
    		color: #9a0404;
    	}

    	.text-grey-green {
    		color: #049a4a;
    	}

    	.open-sans-font {
    		font-family: 'Open Sans', sans-serif;
    	}

    	.questrial-font {
    		font-family: 'Questrial', sans-serif;
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
  	<div class="kotak">
  		<div class="container line-bottom">
	   		<div class="row space-bottom">
	   			<div class="col-5 text-grey-black text-14-3px">Buy Voucher</div>
	   			<div class="col-7 text-right text-medium-grey text-12-7px">{{ date('l', strtotime($data['date'])) }}, {{ date('d F Y H:i', strtotime($data['date'])) }}</div>
	   		</div>
	   		<div class="row space-text">
	   			<div class="col-6"></div>
	   			<div class="col-12 text-right bold text-12-7px">{{ $data['detail']['deal_voucher']['voucher_code'] }}</div>
	   		</div>
	   	</div>
	   	<div class="container">
	   		<div class="row">
                <div class="col-12 text-grey text-13-3px">YOUR TRANSACTION</div>
	   			<div class="col-12"><hr></div>
	   		</div>
	   		<div class="row">
	   			<div class="col-6 text-14px text-black">{{ $data['detail']['deal_voucher']['deal']['deals_title'] }}</div>
	   			<div class="col-6 text-right text-14px text-black">{{ $data['point'] }} Points</div>
                <div class="col-12"><hr></div>
	   		</div>
	   		<div class="row">
	   			<div class="col-6 text-14px text-black">Voucher Status</div>
	   			<div class="col-6 text-right text-14px text-black">@if (empty($data['detail']['used_at'])) NOT USED @else USED @endif</div>
                <div class="col-12"><hr></div>
	   		</div>
	   		<div class="row space-text">
	   			<div class="col-6 text-14px text-black">Used Date</div>
	   			<div class="col-6 text-right text-14px text-black">@if (empty($data['detail']['used_at'])) - @else {{ date('Y-m-d H:i', strtotime($data['detail']['used_at'])) }} @endif</div>
	   		</div>
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