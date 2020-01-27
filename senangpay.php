<?php
/**
 * This is a sample code for manual integration with senangPay
 * It is so simple that you can do it in a single file
 * Make sure that in senangPay Dashboard you have key in the return URL referring to this file for example http://myserver.com/senangpay_sample.php
 */

# please fill in the required info as below
$merchant_id = '748155737732231';
$secretkey = '14370-277';


# this part is to process data from the form that user key in, make sure that all of the info is passed so that we can process the payment
if(isset($_POST['detail']) && isset($_POST['amount']) && isset($_POST['order_id']) && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['phone']))
{
    # assuming all of the data passed is correct and no validation required. Preferably you will need to validate the data passed
    $hashed_string = md5($secretkey.urldecode($_POST['detail']).urldecode($_POST['amount']).urldecode($_POST['order_id']));
    
    # now we send the data to senangPay by using post method
    ?>
    <html>
    <head>
    <title>senangPay Sample Code</title>
    </head>
    <body>
        <form name="order" method="post" action="https://app.senangpay.my/payment/<?php echo $merchant_id; ?>">
            <input type="hidden" name="detail" value="<?php echo $_POST['detail']; ?>">
            <input type="hidden" name="amount" value="<?php echo $_POST['amount']; ?>">
            <input type="hidden" name="order_id" value="<?php echo $_POST['order_id']; ?>">
            <input type="hidden" name="name" value="<?php echo $_POST['name']; ?>">
            <input type="hidden" name="email" value="<?php echo $_POST['email']; ?>">
            <input type="hidden" name="phone" value="<?php echo $_POST['phone']; ?>">
            <input type="hidden" name="hash" value="<?php echo $hashed_string; ?>">
            <input type="submit" />
        </form>
    </body>
    </html>
    <?php
}
# this part is to process the response received from senangPay, make sure we receive all required info
else if(isset($_GET['status_id']) && isset($_GET['order_id']) && isset($_GET['msg']) && isset($_GET['transaction_id']) && isset($_GET['hash']))
{
    # verify that the data was not tempered, verify the hash
    $hashed_string = md5($secretkey.urldecode($_GET['status_id']).urldecode($_GET['order_id']).urldecode($_GET['transaction_id']).urldecode($_GET['msg']));
    
    # if hash is the same then we know the data is valid
    if($hashed_string == urldecode($_GET['hash']))
    {
        # this is a simple result page showing either the payment was successful or failed. In real life you will need to process the order made by the customer
        if(urldecode($_GET['status_id']) == '1')
            echo 'Payment was successful with message: '.urldecode($_GET['msg']);
        else
            echo 'Payment failed with messag e: '.urldecode($_GET['msg']);
    }
    else
        echo 'Hashed value is not correct';
}
# this part is to show the form where customer can key in their information
else
{
    # by right the detail, amount and order ID must be populated by the system, in this example you can key in the value yourself
?>
    <html>
    <head>
    <title>senangPay Sample Code</title>
    </head>
    <body>
        <form method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>">
            <table>
                <tr>
                    <td colspan="2">Please fill up the detail below in order to test the payment. Order ID is defaulted to timestamp.</td>
                </tr>
                <tr>
                    <td>Detail</td>
                    <td>: <input type="text" name="detail" value="" placeholder="Description of the transaction" size="30"></td>
                </tr>
                <tr>
                    <td>Amount</td>
                    <td>: <input type="text" name="amount" value="" placeholder="Amount to pay, for example 12.20" size="30"></td>
                </tr>
                <tr>
                    <td>Order ID</td>
                    <td>: <input type="text" name="order_id" value="<?php echo time(); ?>" placeholder="Unique id to reference the transaction or order" size="30"></td>
                </tr>
                <tr>
                    <td>Customer Name</td>
                    <td>: <input type="text" name="name" value="" placeholder="Name of the customer" size="30"></td>
                </tr>
                <tr>
                    <td>Customer Email</td>
                    <td>: <input type="text" name="email" value="" placeholder="Email of the customer" size="30"></td>
                </tr>
                <tr>
                    <td>Customer Contact No</td>
                    <td>: <input type="text" name="phone" value="" placeholder="Contact number of customer" size="30"></td>
                </tr>
                <tr>
                    <td><input type="submit" value="Submit"></td>
                </tr>
            </table>
        </form>
    </body>
    </html>
<?php
}
?>