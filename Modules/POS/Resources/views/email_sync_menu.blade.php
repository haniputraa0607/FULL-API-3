@include('emails.email_header')
    <h3 align="center" style="color:#000; font-size:18px">
        Rejected Product From Menu Sync POS
    </h3>
    <div align="left" style="color:#000; font-size:14px">Sync Datetime : {{$content['sync_datetime']}}</div>
    <div align="left" style="color:#000; font-size:14px">Outlet Code : {{$content['outlet_code']}}<br></div>
    <div align="left" style="color:#000; font-size:14px">Outlet Name : {{$content['outlet_name']}}<br></div>
    <div align="left" style="color:#000; font-size:14px">Total Rejected Product : {{$content['total_rejected']}}<br></div>
    @foreach($content['rejected_menu'] as $reject)
    <table style= 'width: 100%;margin-bottom: 20px;margin-top:30px;border-collapse: collapse;border-spacing: 0;'>
        <tbody>
            <tr>
                <td style="border: 1px solid #e7ecf1;padding: 8px;line-height: 1.42857;vertical-align: top;font-sixe:14px; color:#000">
                </td>
                <td style="border: 1px solid #e7ecf1;padding: 8px;line-height: 1.42857;vertical-align: top;font-sixe:14px; color:#000">
                    <b>Plu ID</b>
                </td>
                <td style="border: 1px solid #e7ecf1;padding: 8px;line-height: 1.42857;vertical-align: top;font-sixe:14px; color:#000">
                    <b>Product POS Name</b>
                </td>
                <td style="border: 1px solid #e7ecf1;padding: 8px;line-height: 1.42857;vertical-align: top;font-sixe:14px; color:#000">
                    <b>Product Price</b>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #e7ecf1;padding: 8px;line-height: 1.42857;vertical-align: top;font-sixe:14px; color:#000">
                    <b>Data Backend</b>
                </td>
                <td style="border: 1px solid #e7ecf1;padding: 8px;line-height: 1.42857;vertical-align: top;font-sixe:14px; color:#000">
                    {{$reject['backend']['plu_id']}}
                </td>
                <td style="border: 1px solid #e7ecf1;padding: 8px;line-height: 1.42857;vertical-align: top;font-sixe:14px; color:#000">
                    {{$reject['backend']['name']}}
                </td>
                <td style="border: 1px solid #e7ecf1;padding: 8px;line-height: 1.42857;vertical-align: top;font-sixe:14px; color:#000">
                    {{$reject['backend']['price']}}
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #e7ecf1;padding: 8px;line-height: 1.42857;vertical-align: top;font-sixe:14px; color:#000">
                    <b>Data POS</b>
                </td>
                <td style="border: 1px solid #e7ecf1;padding: 8px;line-height: 1.42857;vertical-align: top;font-sixe:14px; color:#000">
                    {{$reject['raptor']['plu_id']}}
                </td>
                <td style="border: 1px solid #e7ecf1;padding: 8px;line-height: 1.42857;vertical-align: top;font-sixe:14px; color:#000">
                    {{$reject['raptor']['name']}}
                </td>
                <td style="border: 1px solid #e7ecf1;padding: 8px;line-height: 1.42857;vertical-align: top;font-sixe:14px; color:#000">
                    {{$reject['raptor']['price']}}    
                </td>
            </tr>
        </tbody>
    </table>
    @endforeach
@include('emails.email_footer')