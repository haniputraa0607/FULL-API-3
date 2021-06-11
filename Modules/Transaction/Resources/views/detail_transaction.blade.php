<!DOCTYPE html>
<html>
<body>

<table style="border: 1px solid black">
    <thead>
    <tr>
        <th style="background-color: #dcdcdc;" width="10"> Outlet Code </th>
        <th style="background-color: #dcdcdc;" width="20"> Outlet Name </th>
        <th style="background-color: #dcdcdc;" width="10"> Province </th>
        <th style="background-color: #dcdcdc;" width="10"> City </th>
        <th style="background-color: #dcdcdc;" width="20"> Receipt Number </th>
        <th style="background-color: #dcdcdc;" width="10"> Transaction Status </th>
        @if(isset($data['show_reject_reason']) && $data['show_reject_reason'] == 1)
        	<th style="background-color: #dcdcdc;" width="10"> Reject Reason </th>
        @endif
        <th style="background-color: #dcdcdc;" width="10"> Transaction Date </th>
        <th style="background-color: #dcdcdc;" width="10"> Transaction Time </th>
        <th style="background-color: #dcdcdc;" width="10"> Brand </th>
        <th style="background-color: #dcdcdc;" width="10"> Category </th>
        @if(isset($data['show_product_code']) && $data['show_product_code'] == 1)
            <th style="background-color: #dcdcdc;" width="10"> Items Code </th>
        @endif
        <th style="background-color: #dcdcdc;" width="10"> Items </th>
        @if(isset($data['add_column']))
            <?php echo $data['add_column'] ?>
        @endif
        <th style="background-color: #dcdcdc;" width="10"> Bundling Name </th>
        <th style="background-color: #dcdcdc;" width="10"> Modifier Group </th>
        <th style="background-color: #dcdcdc;" width="10"> Modifier </th>
        <th style="background-color: #dcdcdc;" width="10"> Item Price </th>
        <th style="background-color: #dcdcdc;" width="10"> Modifier Price </th>
        <th style="background-color: #dcdcdc;" width="10"> Notes </th>
        <th style="background-color: #dcdcdc;" width="10"> Promo Name </th>
        <th style="background-color: #dcdcdc;" width="10"> Promo Code </th>
        <th style="background-color: #dcdcdc;" width="10"> Sub Total </th>
        <th style="background-color: #dcdcdc;" width="10"> Discounts </th>
        <th style="background-color: #dcdcdc;" width="10"> Grand Total </th>
        <th style="background-color: #dcdcdc;" width="10"> Cashback </th>
        <th style="background-color: #dcdcdc;" width="10"> Total Transaction </th>
        <th style="background-color: #dcdcdc;" width="10"> Biaya Jasa </th>
        <th style="background-color: #dcdcdc;" width="10"> MDR PG </th>
        @if(isset($data['show_another_income']) && $data['show_another_income'] == 1)
        <th style="background-color: #dcdcdc;" width="10"> Income Promo </th>
        <th style="background-color: #dcdcdc;" width="10"> Income Subscription </th>
        <th style="background-color: #dcdcdc;" width="10"> Income Bundling Product </th>
        <th style="background-color: #dcdcdc;" width="10"> Income Promo Cashback </th>
        @endif
        <th style="background-color: #dcdcdc;" width="10"> Income Outlet </th>
        <th style="background-color: #dcdcdc;" width="10"> Payment </th>
        <th style="background-color: #dcdcdc;" width="10"> Point Use </th>
        <th style="background-color: #dcdcdc;" width="10"> Point Cashback </th>
        <th style="background-color: #dcdcdc;" width="10"> Point Refund </th>
        <th style="background-color: #dcdcdc;" width="10"> Refund </th>
        <th style="background-color: #dcdcdc;" width="10"> Sales Type </th>
        <th style="background-color: #dcdcdc;" width="10"> Received Time </th>
        <th style="background-color: #dcdcdc;" width="10"> Ready Time </th>
        <th style="background-color: #dcdcdc;" width="10"> Taken Time </th>
        <th style="background-color: #dcdcdc;" width="10"> Arrived Time </th>
    </tr>
    </thead>
    <tbody>
    @if(isset($data['list']) && !empty($data['list']))
        <?php
            libxml_use_internal_errors(true);
            echo $data['list'];
        ?>
    @else
        <tr><td colspan="10" style="text-align: center">Data Not Available</td></tr>
    @endif
    </tbody>
</table>

</body>
</html>

