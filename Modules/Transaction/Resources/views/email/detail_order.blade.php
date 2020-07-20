
<table style="margin-left: auto;margin-right: auto;max-width: 1000px;float: none;background:#ffffff;" width="500px" cellspacing="0" cellpadding="5" border="0" bgcolor="#FFFFFF">
    <tbody>
    <tr>
        <td colspan="5"></td>
    </tr>
    <tr>
        <td colspan="5"></td>
    </tr>

    <tr>
        <td colspan="3" style="border-bottom-style:none;text-align:center">
            <h2 style="color:#000000;font-family:\'Source Sans Pro\',sans-serif;font-size:16px;line-height:1.5;margin:0;padding:5px 0">{{$data['status']}}</h2>
        </td>
    </tr>
    <tr>
        <td colspan="3" style="border-bottom-style:none;text-align:center">
            <span style="color:#b3b3b3;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">#{{$data['transaction_receipt_number']}}<br>
                Order ID : {{$data['detail']['order_id'] }}<br>
                {{date('d M Y H:i', strtotime($data['transaction_date']))}}</span>
        </td>
    </tr>
    <tr>
        <th colspan="3" style="border-bottom-style:none;padding-left:10px;padding-right:10px">
        </th>
    </tr>
    <tr>
        <td colspan="5" style="background:#ffffff;border-top: 2px dashed #8fd6bd;padding-left:10px;padding-right:10px" bgcolor="background: rgb(143, 214, 189)">
        </td>
    </tr>
    <tr>
        <td colspan="3" width="50%" style="background:#ffffff;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:15px 10px" valign="top" bgcolor="#FFFFFF" align="center">
            <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:18px;line-height:1.5;margin:0;padding:0">Detail Outlet</span>
        </td>
    </tr>
    <tr>
        <td  colspan="3" style="background:#ffffff;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:15px 10px" valign="top" bgcolor="#FFFFFF" align="center">
            <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">
                {{ $data['outlet']['outlet_name'] }}
            </span><br>
            <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">
                {{ $data['outlet']['outlet_address'] }}
            </span>
        </td>
    </tr>
    <tr>
        <td colspan="5"></td>
    </tr>
    <tr>
        <td colspan="5"></td>
    </tr>
    </tbody>
</table>

