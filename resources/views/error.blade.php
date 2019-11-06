<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
	<div style="display: flex;justify-content: center;align-items: center;height: 100vh">{{ $msg }}</div>
	<input type="hidden" class="msg" value="{{ $msg }}">
	<script src="{{ env('S3_URL_VIEW') }}{{ ('assets/global/plugins/jquery.min.js') }}" type="text/javascript"></script>
	<script type="text/javascript">
		$( document ).ready(function() {
			var messages = $('.msg').val();
		    alert(messages);
		});
	</script>
</body>
</html>