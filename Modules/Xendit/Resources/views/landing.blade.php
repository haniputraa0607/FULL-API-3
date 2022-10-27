<html>
<head>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Mohon Tunggu</title>
	<style>
		* {
			padding: 0;
			margin: 0;
		}
		.lds-ripple {
		  display: inline-block;
		  position: relative;
		  width: 80px;
		  height: 80px;
		}
		.lds-ripple div {
		  position: absolute;
		  border: 4px solid rgb(156 31 96);
		  opacity: 1;
		  border-radius: 50%;
		  animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
		}
		.lds-ripple div:nth-child(2) {
		  animation-delay: -0.5s;
		}
		@keyframes lds-ripple {
		  0% {
		    top: 36px;
		    left: 36px;
		    width: 0;
		    height: 0;
		    opacity: 0;
		  }
		  1% {
		    top: 36px;
		    left: 36px;
		    width: 0px;
		    height: 0px;
		    opacity: 1;
		  }
		  90% {
		    top: 0px;
		    left: 0px;
		    width: 72px;
		    height: 72px;
		    opacity: 0;
		  }
		  100% {
		    top: 36px;
		    left: 36px;
		    width: 0px;
		    height: 0px;
		    opacity: 0;
		  }
		}
		.loading-container {
			display: flex;
			justify-content: center;
			align-items: center;
			width: 100%;
			height: 100%;
			text-align: center;
			flex-direction: column;
		}
		body{
			padding: 10px;
		}
		#text-muted {
			color: #303030
		}
		#text-muted a {
			color: #303030;
			display: none;
		}
		#text-muted.clicked a {
			color: #303030;
			display: block;
		}
	</style>
</head>
<body>
	<div class="loading-container">
		<div>
			<div class="lds-ripple"><div></div><div></div></div>
		</div>
		<div id="text-muted">Mohon Tunggu...<br/><br/> Silahkan tutup halaman ini jika anda tidak segera dialihkan ke aplikasi</div>
	</div>
	<script type="text/javascript">
		function submitForm(){
			@if(strpos(request()->header('User-Agent'), 'iPhone')!==FALSE)
			//window.close();
			@else
			window.location.href = '{{env('APP_DEEPLINK')}}/{{request()->page}}';
			@endif
		}
		document.addEventListener("click", submitForm);
		setTimeout(function(){ document.getElementById('text-muted').classList.add('clicked'); }, 3000);
		window.onload = submitForm;
	</script>
</body>
</html>
