<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Chat with Doctor</title>
</head>
<style type="text/css">
  html, body {
    padding: 0 !important;
    margin: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    overflow-y: hidden;
  }
  #loading {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 21370002;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .lds-ring {
    display: inline-block;
    position: relative;
    width: 80px;
    height: 80px;
  }
  .lds-ring div {
    box-sizing: border-box;
    display: block;
    position: absolute;
    width: 64px;
    height: 64px;
    margin: 8px;
    border: 8px solid rgb(156 31 96);
    border-radius: 50%;
    animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
    border-color: rgb(156 31 96) transparent transparent transparent;
  }
  .lds-ring div:nth-child(1) {
    animation-delay: -0.45s;
  }
  .lds-ring div:nth-child(2) {
    animation-delay: -0.3s;
  }
  .lds-ring div:nth-child(3) {
    animation-delay: -0.15s;
  }
  @keyframes lds-ring {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }
</style>
<body>

<div id="loading"><div class="lds-ring"><div></div><div></div><div></div><div></div></div></div>

<script>
function removeLoading() {
  document.getElementById('loading').remove();
}
(function(I,n,f,o,b,i,p){
I[b]=I[b]||function(){(I[b].q=I[b].q||[]).push(arguments)};
I[b].t=1*new Date();i=n.createElement(f);i.async=1;i.src=o;
p=n.getElementsByTagName(f)[0];p.parentNode.insertBefore(i,p)})
(window,document,'script','https://livechat.infobip.com/widget.js','liveChat');

@if (request()->logged_out)
liveChat('auth', '{{$token}}', function(error, result) {
  if (error) {
    console.log(error.code, error.message)
  } else {
  	console.log('Sukses login', result);
    // $.ajax({
		// 		type: "POST",
		// 		url: "{{url('api/consultation/detail/chat/updateIdUserInfobip')}}",
		// 		dataType: "json",
    //     data: {'id_transaction': {{request()->id_transaction}},},
		// 		success: function(data){
		// 			if (data.status == 'fail') {
		// 				$.ajax(this)
		// 				return
		// 			}
    //       return data;
    //     }
		// 	});

    (async () => {
      const rawResponse = await fetch('{{url('api/consultation/detail/chat/updateIdUserInfobip')}}', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({'id_transaction': {{request()->id_transaction}}})
      });
      const content = await rawResponse.json();

      console.log(content);
    })();
  }
  liveChat('init', '{{config('infobip.widget_id')}}', () => {
    // console.log('Initiated');
    const widgetWrapper = document.getElementsByClassName('ib-widget-wrapper')[0];
    const button = document.getElementById('ib-button-messaging');
    const body = document.getElementsByTagName('body')[0];
    const buttonMobile = document.getElementsByClassName('ib-close-mobile-button')[0];
    const iframe = document.getElementById('ib-iframe-messaging');
    button.remove();
    buttonMobile.remove();

    widgetWrapper.style.bottom = 'auto';
    widgetWrapper.style.right = '0';
    widgetWrapper.style.visibility = 'visible';
    widgetWrapper.style.position = 'relative';
    widgetWrapper.style.width = '100%';
    widgetWrapper.style.height = '100vh';
    widgetWrapper.style['min-height'] = 'none';
    widgetWrapper.style['max-height'] = 'none';
    widgetWrapper.style['border-radius'] = '0';
    widgetWrapper.style['box-shadow'] = '#fff 0 0 0';
    widgetWrapper.style['z-index'] = '21370001';

    iframe.style.bottom = '0';
    iframe.style.height = 'calc(100vh + 80px)';
    iframe.style.position = 'fixed';
    iframe.style.top = '-80px';

    removeLoading();
  });
});
@else
liveChat('init', '{{config('infobip.widget_id')}}', () => {
  liveChat('logout', null,(error, result) => {
    // console.log(error, result);
    window.location.href = '{{url('api/consultation/detail/chat.html')}}?id_transaction={{request()->id_transaction}}&auth={{request()->auth_code}}&logged_out=1';
  });
});
@endif
</script>
</body>
</html>
