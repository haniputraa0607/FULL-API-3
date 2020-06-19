<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="-1" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <style type="text/css">
        @font-face {
            font-family: 'Seravek';
            font-style: normal;
            font-weight: 400;
            src: url('{{env("STORAGE_URL_VIEW") }}{{("assets/fonts/Seravek.ttf?")}}') format('truetype');
        }

        @font-face {
            font-family: 'Seravek Light';
            font-style: normal;
            font-weight: 400;
            src: url('{{env("STORAGE_URL_VIEW") }}{{("assets/fonts/Seravek-Light.ttf?")}}') format('truetype');
        }

        @font-face {
            font-family: 'Seravek Medium';
            font-style: normal;
            font-weight: 400;
            src: url('{{env("STORAGE_URL_VIEW") }}{{("assets/fonts/Seravek-Medium.ttf?")}}') format('truetype');
        }

        @font-face {
            font-family: 'Seravek Italic';
            font-style: normal;
            font-weight: 400;
            src: url('{{env("STORAGE_URL_VIEW") }}{{("assets/fonts/Seravek-Italic.ttf?")}}') format('truetype');
        }

        @font-face {
            font-family: 'Roboto Regular';
            font-style: normal;
            font-weight: 400;
            src: url('{{env("STORAGE_URL_VIEW") }}{{("assets/fonts/Roboto-Regular.ttf?")}}') format('truetype');
        }

    	.kotak1 {
    		padding-top: 0px;
    		padding-bottom: 10px;
    		padding-left: 26.3px;
    		padding-right: 26.3px;
			background: #fff;
			font-family: 'Seravek', sans-serif;
    	}

    	.kotak2 {
    		padding-top: 10px;
    		padding-bottom: 10px;
    		padding-left: 26.3px;
    		padding-right: 26.3px;
			background: #fff;
			font-family: 'Seravek', sans-serif;
      height: 100%
    	}

    	.brown div {
    		color: #6c5648;
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
    	}

    	.min-left {
    		margin-left: -15px;
    		margin-right: 10px;
    	}

    	.line-bottom {
    		border-bottom: 0.3px solid #dbdbdb;
    		margin-bottom: 5px;
		    padding-bottom: 10px;
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

		.text-grey-2{
			color: #a9a9a9;
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

    	.open-sans-font {
    		font-family: 'Open Sans', sans-serif;
    	}

    	.questrial-font {
    		font-family: 'Questrial', sans-serif;
    	}
		.seravek-font {
            font-family: 'Seravek';
        }

        .seravek-light-font {
            font-family: 'Seravek Light';
        }

        .seravek-medium-font {
            font-family: 'Seravek Medium';
        }

        .seravek-italic-font {
            font-family: 'Seravek Italic';
        }

        .roboto-regular-font {
            font-family: 'Roboto Regular';
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

	.kelas-hr {
	    margin-top: -5px;
	}

	.kelas-input {
	    width: 100%;
	 	overflow: hidden;
	    border-radius: 11px;
	    /* margin: 10px; */
	    -webkit-box-shadow: 0px 1px 3.3px 0px rgba(168,168,168,1);
	    -moz-box-shadow: 0px 1px 3.3px 0px rgba(168,168,168,1);
	    box-shadow: 0px 0px 3.3px 0px rgba(168,168,168,1);
	    /* outline: 0; */

	}

	.linkTidakAda:hover {
		text-decoration: none;
	}

	.kelas-input .input-group-text {
	    background: #df151500;
	    border: none;
	    border-left: 0;
	    padding: 6px;
	}

	.kelas-input .form-control {
	    border: none;
	    outline: 0;
	    padding: 6px 18px;
	}

	.kelas-input .form-control:focus {
	    box-shadow: none;
	    outline: 0;
	}

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css?">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css?">
  </head>
  <body>
    {{ csrf_field() }}
  	<div class="kotak1">
   		<div class="row" style="margin-top: -20px;">
   			<div class="col-12 mb-4 mt-4">
	   			<div class="input-group kelas-input">
				  <input type="text" id="id-input" class="form-control text-12-7px seravek-font" placeholder="Cari Outlet">
				  <div class="input-group-append">
				    <span class="input-group-text"><img src="{{ env('STORAGE_URL_VIEW') }}{{ ('images/search.png') }}" style="width: 20px;"></span>
				  </div>
				</div>
			</div>
   			<div id="result" style="width: 100%">
   				@foreach ($outlet as $out)
	   			<div class="col-12">
	   				<a href="{{ $out['deep_link'] }}" class="row linkTidakAda">
			   			<div class="col-6 text-black text-12-7px seravek-font"><span> {{ $out['outlet_name'] }} </span></div>
			   			<div class="col-6 text-12-7px seravek-light-font text-right" style="color: #bbb5b5"><span> {{ $out['distance'] }} </span></div>
			   			<div class="col-12 kelas-hr"><hr></div>
		   			</a>
	   			</div>
		   		@endforeach
   			</div>

   		</div>
   		<input type="hidden" class="data" value="{{ json_encode($outlet) }}">
  	</div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js?" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js?" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js?" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCOHBNv3Td9_zb_7uW-AJDU6DHFYk-8e9Y&v=3.exp&signed_in=true&libraries=places"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js?"></script>
  </body>

  <script>
  	$(document).on('keyup', '#id-input', function() {
  		var data = JSON.parse($('.data').val());
	  	var input = $(this).val();
	  	var apa = data.filter(function(item) {
	  		return item.outlet_name.toLowerCase().search(input.toLowerCase()) !== -1;
	  	});

	  	$('#result').html('');

	  	apa.map(function(value, key) {
	  		$('#result').append('<div class="col-12"><a href="'+value.deep_link+'" class="row linkTidakAda">\
		   			<div class="col-6 text-black text-12-7px seravek-font"><span> '+value.outlet_name+' </span></div>\
		   			<div class="col-6 text-12-7px seravek-light-font text-right" style="color: #bbb5b5"><span> '+value.distance+'</span></div>\
		   			<div class="col-12 kelas-hr"><hr></div>\
	   			</a></div>')
	  	});
  	});



  </script>
</html>