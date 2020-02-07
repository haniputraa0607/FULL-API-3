<head>
        <link href="https://fonts.googleapis.com/css?family=Open+Sans|Source+Sans+Pro" rel="stylesheet">
        <style>
            .table-bordered {
                font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
                border-collapse: collapse;
            }
            .table-bordered td, .table-bordered th {
                border: 1px solid #ddd;
                padding: 8px;
            }

            #table-fraud-list {
                font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
                border-collapse: collapse;
            }

            #table-fraud-list td, #table-fraud-list th {
                border: 1px solid #ddd;
                padding: 8px;
            }

            #table-fraud-list th {
                padding-top: 12px;
                padding-bottom: 12px;
                text-align: left;
                background-color: #BFBFBF;
                color: white;
            }
        </style>
</head>
<table style="border-collapse:collapse;margin:0;padding:0;background-color:#f2f2f2;height:100%!important;width:100%!important" align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
    <tbody><tr>
          <td style="margin:0;padding:20px;border-top:0;height:100%!important;width:100%!important" align="center" valign="top">

              <table style="border-collapse:collapse;border:0" border="0" cellpadding="0" cellspacing="0" width="600">
                 <tbody><tr>
                      <td align="center" valign="top">

                      <table style="border-collapse:collapse;background-color:#ffffff;border-top:0;border-bottom:0" border="0" cellpadding="0" cellspacing="0" width="600">
                          <tbody><tr>
                             <td valign="top">
                              <table style="border-collapse:collapse" border="0" cellpadding="0" cellspacing="0" width="100%" border="3px">
                            <tbody>
                               <tr style="height: 120px">
                                   @if(isset($setting) && $setting['email_logo'] != null)
                                       <td id="logoheader_center" style="border-collapse:collapse;border-spacing:0;color:#000;font-family:'Arial',sans-serif;line-height:1.5;margin:0;padding:0;text-align:center" width="33%">
                                           @if($setting['email_logo_position'] == 'left')
                                               <img class="CToWUd" id="detail_logo_center" @if(stristr($setting['email_logo'], 'http')) src="{{$setting['email_logo']}}" @else src="{{env('S3_URL_API')}}{{$setting['email_logo']}}" @endif style="border:0 none;line-height:100%;outline:none;text-decoration:none;margin-bottom: -60px;height: 85px;margin-top: 5px;" alt="" height="75px">
                                           @endif
                                       </td>
                                       <td id="logoheader_center" style="border-collapse:collapse;border-spacing:0;color:#000;font-family:'Arial',sans-serif;line-height:1.5;margin:0;padding:0;text-align:center" width="33%">
                                           @if($setting['email_logo_position'] == 'center')
                                               <img class="CToWUd" id="detail_logo_center" @if(stristr($setting['email_logo'], 'http')) src="{{$setting['email_logo']}}" @else src="{{env('S3_URL_API')}}{{$setting['email_logo']}}" @endif style="border:0 none;line-height:100%;outline:none;text-decoration:none;margin-bottom: -60px;height: 85px;margin-top: 5px;" alt="" height="75px">
                                           @endif
                                       </td>
                                       <td id="logoheader_center" style="border-collapse:collapse;border-spacing:0;color:#000;font-family:'Arial',sans-serif;line-height:1.5;margin:0;padding:0;text-align:center" width="33%">
                                           @if($setting['email_logo_position'] == 'right')
                                               <img class="CToWUd" id="detail_logo_center" @if(stristr($setting['email_logo'], 'http')) src="{{$setting['email_logo']}}" @else src="{{env('S3_URL_API')}}{{$setting['email_logo']}}" @endif style="border:0 none;line-height:100%;outline:none;text-decoration:none;margin-bottom: -60px;height: 85px;margin-top: 5px;" alt="" height="75px">
                                           @endif
                                       </td>
                                   @else
                                       <td id="logoheader_center" style="border-collapse:collapse;border-spacing:0;color:#000;font-family:'Arial',sans-serif;line-height:1.5;margin:0;padding:0;text-align:center" width="100%">
                                           <img class="CToWUd" id="detail_logo_center" @if(stristr($setting['email_logo'], 'http')) src="{{$setting['email_logo']}}" @else src="{{env('S3_URL_API')}}{{('img/logo.jpg')}}" @endif style="border:0 none;line-height:100%;outline:none;text-decoration:none;margin-bottom: -60px;height: 85px; margin-top: 5px;" alt="" height="75px">
                                       </td>
                                   @endif
                               </tr>
                               <tr>
                                  <td style="border-collapse:collapse;border-spacing:0;color:#000;font-family:'Arial',sans-serif;line-height:1.5;margin:0;padding:0" width="15"></td>
                                  <td style="border-collapse:collapse;border-spacing:0;color:#000;font-family:'Arial',sans-serif;line-height:1.5;margin:0;padding:0" width="550"></td>
                                  <td style="border-collapse:collapse;border-spacing:0;color:#000;font-family:'Arial',sans-serif;line-height:1.5;margin:0;padding:0" width="15"></td>
                               </tr>
                            </tbody>
                         </table>
                      </td>
                   </tr>
                   <tr>
                      <td style="border-collapse:collapse;border-spacing:0;color:#000;font-family:'Arial',sans-serif;line-height:1.5;margin:0;padding:0" bgcolor="#ffffff">
                         <table style="border-collapse:collapse;border-spacing:0;margin:0;padding:0" width="100%" align="left">
                            <tbody>
                               <tr>
                                  <td style="border-collapse:collapse;border-spacing:0;color:#000;font-family:'Arial',sans-serif;line-height:1.5;margin:0;padding:0" width="15"></td>
                                  <td style="border-collapse:collapse;border-spacing:0;color:#000;font-family:'Arial',sans-serif;line-height:1.5;margin:0;padding:0" width="550" align="left">