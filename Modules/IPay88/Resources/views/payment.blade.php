<html>
<head>
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
		  border: 4px solid #12f;
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
		    opacity: 1;
		  }
		  100% {
		    top: 0px;
		    left: 0px;
		    width: 72px;
		    height: 72px;
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
		}
	</style>
</head>
<body>
	<form action="{{$action_url}}" name="myForm" id="myForm" method="POST">
		@foreach($data as $key => $field)
		<input type="hidden" name="{{$key}}" value="{{$field}}">
		@endforeach
		<div class="loading-container">
			<div>
				<div class="lds-ripple"><div></div><div></div></div>
			</div>
		</div>
	</form>
	<script type="text/javascript">
		function submitForm(){
			if(!document.forms["myForm"].dataset.submitted){
				document.forms["myForm"].dataset.submitted = true;
		        document.forms["myForm"].submit();				
			}
		}
		document.addEventListener("click", submitForm);
		window.onload = submitForm;
	</script>
</body>
</html>