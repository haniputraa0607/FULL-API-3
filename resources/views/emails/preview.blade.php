@include('emails.email_header')
<table style="border-collapse:collapse;border-spacing:0;margin:0;padding:0" width="100%" align="left">
   <tbody>
     <tr>
       <td style="border-collapse:collapse;border-spacing:0;color:#000;font-family:'Arial',sans-serif;line-height:1.5;margin:0;padding:0">
            <p style="color:#000;font-family:'Arial',sans-serif;font-size:14px;line-height:1.5;margin:5px 2px;padding:0" id="detail_content">
                <?php echo $html_message;?>
            </p>
       </td>
     </tr>
   </tbody>
</table>
@include('emails.email_footer')