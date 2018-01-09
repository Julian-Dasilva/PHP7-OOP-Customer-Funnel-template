<?php

/**
 * 
 * @author Julian-Alecssandre Dasilva
 * @link https://github.com/Julian-Dasilva
 */
class Customer {

    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $address1;
    public $address2;
    public $city;
    public $state;
    public $zip;
    public $country;
    public $ip;
    public $aff_id;
    public $sid;
    public $sid2;
    public $sid3;
    public $products = array();
    public $promo_code;
    private $ccn;
    public $dccn;
    private $ccexp;
    private $cvv;
    public $order_id;
    public $customer_id;
    public $ss_key;
    public $order_total;
    public $coupon;
    public $checkoutAttempts = 0;

    /*
     * @param customer lead/contact parameters Name, Address, Email, Country, IP, Tracking Params
     * parameter list: first_name, last_name, email, phone, address1, address2, city, state, zip, country, ip, aff_id, sid, sid2, sid3
     *
     * @throws \required data error when a required piece of information was not properly formatted/populated.
     *
     * @return \successful response along with an array of all the contact information to pass along to the datastring and session
     *
     * @functionality \sets all the member variables with appropriate formatting for a contact that can then be used for other function calls i.e. insertContact
     *                \formats all fields for anti-hack and reformats to avoid database inserstion errors
     */

    function setContact($first_name, $last_name, $email, $phone, $address1, $address2, $city, $state, $zip, $ip, $aff_id, $sid, $sid2, $sid3) {
        //prior to calling set contact you should call the function verify to ensure all the info has passed through briteverify and the length scrubs
        //do some formatting in here such as setting apostrophes escaped with a \ such as O\'Hare instead of O'Hare to avoid sql errors
        //Do some verification to ensure we have a properly formatted phone number, email, and name at the least. These are crucial for the contact to be considered a contact
        $antihack = array("-", "(", ")", "*", "#", "sleep", "SELECT", "FROM", "WHERE", "CHR", "/", "*");

        $this->first_name = str_replace("'", "\'", str_replace($antihack, '', ucfirst(strtolower($first_name))));
        $this->last_name = str_replace("'", "\'", str_replace($antihack, '', ucfirst(strtolower($last_name))));
        $this->email = str_replace("'", "\'", str_replace($antihack, '', $email));
        $this->phone = str_replace($antihack, '', $phone);
        $this->address1 = str_replace("'", "\'", str_replace($antihack, '', $address1));
        $this->address2 = str_replace("'", "\'", str_replace($antihack, '', $address2));
        //We concatenate the two address fields to store them in the database later and display. By trimming we handle the case where address 2 may be empty.
        $this->fullAddress = trim($this->address1 . ' ' . $this->address2);
        $this->city = str_replace("'", "\'", str_replace($antihack, '', $city));
        $this->state = str_replace($antihack, '', $state);
        $this->zip = str_replace($antihack, '', $zip);
        $this->ip = $ip;
        $this->aff_id = $aff_id;
        $this->sid = $sid;
        $this->sid2 = $sid2;
        $this->sid3 = $sid3;
    }

    /*
     * @param accepts no parameters but uses member variables for functionality
     * parameter list: member variables
     *
     * @throws \required data error when a required piece of information was not properly formatted/populated.
     *
     * @return \successful response returns the customer id of the created or updated lead
     *
     * @functionality \inserts all customer member variables into customer_information database table
     *                \attaches date-time stamp to customer_information, doesn't update call if it finds similar information
     *                \otherwise, it will create a new record also calls grabcustomer id
     */

    function insertInformation() {
        $conn = Connection::get();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $date = date('Y-m-d');

        try {
            //************ VERIFY CUSTOMER IS NOT IN THE DATABASE ALREADY **********//
            $sqlCheck = "SELECT count(*) FROM `customer_information` WHERE `email`='$this->email'";
            $result = $conn->query($sqlCheck)->fetchColumn();
            $sqlCheck2 = "SELECT count(*) FROM `customer_information` WHERE `phone`='$this->phone'";
            $result2 = $conn->query($sqlCheck2)->fetchColumn();
            $sqlCheck3 = "SELECT count(*) FROM `customer_information` WHERE `address1`='$this->address1'";
            $result3 = $conn->query($sqlCheck3)->fetchColumn();
            //************ USE RESULTS TO DECIDE WHETHER WE INSERT OR UPDATE **********//
            if ($result > 0) {
                $updateCustomer = "UPDATE `customer_information` SET "
                        . "`email` = '$this->email',"
                        . "`first_name` = '$this->first_name',"
                        . "`last_name` = '$this->last_name',"
                        . "`phone` = '$this->phone',"
                        . "`address1` = '$this->address1',"
                        . "`city` = '$this->city',"
                        . "`state` = '$this->state',"
                        . "`zip` = '$this->zip',"
                        . "`ip` = '$this->ip',"
                        . "`last_updated` = '$date'"
                        . " WHERE email='$this->email';";
                $updateInfo = $conn->prepare($updateCustomer);
                $updateInfo->execute();
                $updateInfo->closeCursor();
            } else if ($result2 > 0) {
                $updateCustomer = "UPDATE `customer_information` SET "
                        . "`email` = '$this->email',"
                        . "`first_name` = '$this->first_name',"
                        . "`last_name` = '$this->last_name',"
                        . "`phone` = '$this->phone',"
                        . "`address1` = '$this->address1',"
                        . "`city` = '$this->city',"
                        . "`state` = '$this->state',"
                        . "`zip` = '$this->zip',"
                        . "`ip` = '$this->ip',"
                        . "`last_updated` = '$date'"
                        . " WHERE `phone` = '$this->phone';";
                $updateInfo = $conn->prepare($updateCustomer);
                $updateInfo->execute();
                $updateInfo->closeCursor();

                //check to see if the person is already in our database by searching their address
            } else if ($result3 > 0) {
                $updateCustomer = "UPDATE `customer_information` SET "
                        . "`email` = '$this->email',"
                        . "`first_name` = '$this->first_name',"
                        . "`last_name` = '$this->last_name',"
                        . "`phone` = '$this->phone',"
                        . "`address1` = '$this->address1',"
                        . "`city` = '$this->city',"
                        . "`state` = '$this->state',"
                        . "`zip` = '$this->zip',"
                        . "`ip` = '$this->ip',"
                        . "`last_updated` = '$date'"
                        . " WHERE `address1` = '$this->address1';";
                $updateInfo = $conn->prepare($updateCustomer);
                $updateInfo->execute();
                $updateInfo->closeCursor();
            } else {
                //the person is most likely not in our database so go ahead and create a new record in the database
                $insertCustomer = "INSERT INTO `customer_information` (`email`, `first_name`, `last_name`, `phone`,
                    `address1`, `city`, `state`, `zip`,`ip`, `aff_id`, `aff_sub`, `aff_sub2`, `aff_sub3`, `created`, `last_updated`)
                      VALUES ('$this->email','$this->first_name', '$this->last_name', '$this->phone','$this->address1','$this->city','$this->state','$this->zip', '$this->ip','$this->aff_id', '$this->sid', '$this->sid2', '$this->sid3', '$date', '$date');";
                $insertinfo = $conn->prepare($insertCustomer);
                $insertinfo->execute();
                $insertinfo->closeCursor();
            }
        } catch (Exception $e) {
            error_log($e);
            $error_message = 'Something went wrong, verify you entered accurate information and check for typos.';
        }
        Customer::grabCustomerId();
    }

    /*
     * @param uses member variables therefore empty param list
     *
     * @throws \unable to retrieve a customer id. Customer does not exist
     *
     * @return \returns customer id
     *
     * @functionality \sets and returns customer id
     */

    function grabCustomerId() {
        $conn = Connection::get();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getData = "SELECT * FROM `customer_information` WHERE `email` = '$this->email'";
        foreach ($conn->query($getData) as $row) {
            $customer_id = $row['customer_id'];
        }
        $this->customer_id = $customer_id;

        if ($customer_id !== '') {
            return $customer_id;
        } else {
            return $error_message = 'Customer Id not found, either this email was invalid, or the customer does not exist.'
        }
    }

    /*
     * @param customer order parameters order total, products array (price quantity, name)
     * parameter list: credit card information, products, and order total
     *
     * @throws \required data error when a required piece of information was not properly formatted/populated.
     *
     * @return \
     *
     * @functionality \sets all the member variables with appropriate formatting for an order
     */

    function setOrder($ccn, $ccexp, $cvv, $products, $total) {
        $this->ccn = $ccn;
        $this->ccexp = $ccexp;
        $this->cvv = $cvv;
        $this->dccn = substr_replace($ccn, '************', 0, 12);
        //empty the product in case the person is checking out again, or is checking out with a different product, before pushing the product back into the object
        $this->products = [];
        array_push($this->products, $products);
        $this->checkoutAttempts += 1;
        $this->order_total = number_format($total, 2);
        $product_list = '';
        for ($i = 0, $count = count($this->products); $i < $count; $i++) {
            $product_list .= $this->products[$i]['name'] . ' x ' . ($this->products[$i]['qty']) . ' ';
        }
        $conn = Connection::get();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            $encryptionMethod = "AES-256-CBC";  // AES is used by the U.S. gov't to encrypt top secret documents.
            $secretHash = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
            $iv = 'xxxxxxxxxxxxxxxx';
//To encrypt
            $encryptedCCNUM = openssl_encrypt($this->ccn, $encryptionMethod, $secretHash, 0, $iv);
            $encryptedCVV = openssl_encrypt($this->cvv, $encryptionMethod, $secretHash, 0, $iv);
            $encryptedEXP = openssl_encrypt($this->ccexp, $encryptionMethod, $secretHash, 0, $iv);
            $sql = "UPDATE `customer_information` SET "
                    . "`ccn` = '$encryptedCCNUM',"
                    . "`ccexp` = '$encryptedEXP', `cvv` = '$encryptedCVV',"
                    . "`last_order` = '$product_list'"
                    . " WHERE `customer_information`.`customer_id` = $this->customer_id";
            $insertinfo = $conn->prepare($sql);
            $insertinfo->execute();
            $insertinfo->closeCursor();
        } catch (Exception $e) {
            error_log($e);
            return $error_message = 'Something went wrong updating your information. Please try again.';
        }
    }

    /*
     * @param 
     * parameter list: 
     *
     * @throws \required data error when a required piece of information was not properly formatted/populated.
     *
     * @return \
     *
     * @functionality \updates an order. This should be called after add product to update the order information in the database
     */

    function updateOrder() {
        $conn = Connection::get();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $product_list = '';
        //creating product list for database
        for ($i = 0, $count = count($this->products); $i < $count; $i++) {
            $product_list .= $this->products[$i]['name'] . ' x ' . $this->products[$i]['qty'] . ' ';
        }

        try {
            $updateLastOrder = "UPDATE  `customers_order` SET "
                    . "`products`='$product_list',"
                    . "`order_total`='$this->order_total'"
                    . "WHERE `order_id` = $this->order_id";
            $enterLastOrder = $conn->prepare($updateLastOrder);
            $enterLastOrder->execute();
            $enterLastOrder->closeCursor();
        } catch (Exception $ex) {
            error_log($e);
        }
    }

    /*
     * @param member variables only
     *
     * @throws \required data error when a required piece of information was not properly formatted/populated.
     *
     * @return \
     *
     * @functionality \Updates an order in shipstation with any changes that may have occured to an order
     *                 Usually occurs when a customer takes an upsell at during the checkout process or when an order will 
     *                 be updated by a customer service rep
     */

    function updateShipStation() {
        $date = date('m/d/Y, h:ia');
        $fullName = $this->first_name . ' ' . $this->last_name;
        $authHeader = base64_encode('ENTER_AUTH_HEADER_HERE');
        //var_dump($authHeader);

        $itemString = '';
        for ($i = 0, $count = count($this->products); $i < $count; $i++) {
            $tempQty = intval($this->products[$i]['qty']);
            $tempPrice = floatval($this->products[$i]['price']);
            $tempShipping = floatval($this->products[$i]['shipping']);
            $itemString .= "\"items\": [
          {
          \"lineItemKey\":null,
          \"sku\":null,
          \"name\":\"" . $this->products[$i]['name'] . "\",
          \"imageUrl\": null,
          \"weight\": {
          \"value\": 6,
          \"units\": \"ounces\"
          },
          \"quantity\": $tempQty,
          \"unitPrice\": $tempPrice,
          \"taxAmount\": null,
          \"shippingAmount\": $tempShipping,
          \"warehouseLocation\":null,
          \"options\": [
          {
          \"name\": \"Size\",
          \"value\": \"small\"
          }
          ],
          \"productId\": null,
          \"fulfillmentSku\": null,
          \"adjustment\": false,
          \"upc\":null
          }
          ],";
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://ssapi.shipstation.com/orders/createorder");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, "{
      \"orderNumber\":\"$this->order_id\",
      \"orderKey\": \"$this->ss_key\",
      \"orderDate\": \"$date\",
      \"paymentDate\": \"$date\",
      \"orderStatus\": \"awaiting_shipment\",
      \"customerUsername\": \"$fullName\",
      \"customerEmail\": \"$$this->email\",
      \"billTo\": {
      \"name\": \"$fullName\",
      \"company\": null,
      \"street1\": \"$$this->address1\",
      \"street2\":null,
      \"street3\": null,
      \"city\": \"$$this->city\",
      \"state\": \"$$this->state\",
      \"postalCode\": \"$$this->zip\",
      \"country\": null,
      \"phone\": \"$$this->phone\"
      },
       \"shipTo\": {
        \"name\": \"$fullName\",
        \"street1\": \"$this->address1\",
        \"street2\": null,
        \"street3\": null,
        \"city\": \"$$this->city\",
        \"state\": \"$$this->state\",
        \"postalCode\": \"$$this->zip\",
        \"country\": \"US\",
        \"phone\": \"$$this->phone\",
        \"residential\": true
      },
    $itemString
        \"amountPaid\": \"$$this->order_total\",
      \"customerNotes\": \"Thanks for ordering!\",
      \"paymentMethod\": \"Credit Card\",
      \"advancedOptions\": {
      \"warehouseId\": xxxxx,
      \"storeId\": xxxxxx,
      \"source\": \"Webstore\"
      }
      }");

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Basic AUTH_HEADER HERE="
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        //var_dump($itemString);
        //var_dump($response);
    }

    /*
     * @param product to be added to the order
     * parameter list: $products - product to be added to the list
     *
     * @throws \
     *
     * @return \
     *
     * @functionality \adds a product to an order
     */

    function addProduct($products) {

        //making sure there are no preexisting upsells of the same type

        for ($i = 0, $count = count($this->products); $i < $count; $i++) {
            if ($this->products[$i]['name'] == $products['name']):
                return;
            endif;
        }

        array_push($this->products, $products);
        $this->order_total += $products['price'];
    }

    /*
     * @param accepts $status, $gatewayresponse
     * parameter list: member variables, payment status, gateway response from processor
     *
     * @throws \required data error when a required piece of information was not properly formatted/populated.
     *
     * @return \the newly inserted orders ID
     *
     * @functionality \inserts all customer member variables into the order database table
     *                \attaches date-time stamp to customers order
     *                \This function will always create a new order see @updateOrder to update a database order.
     */

    function insertOrder($status, $gateway_response) {

        $conn = Connection::get();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $date = date('Y-m-d');
        $dateTime = date('Y-m-d H:i:s');
        $product_list = '';
        //creating product list for database
        for ($i = 0, $count = count($this->products); $i < $count; $i++) {
            $product_list .= $this->products[$i]['name'] . ' x ' . $this->products[$i]['qty'] . ' ';
        }

        try {
            $insertOrder = "INSERT INTO `customers_order` (`email`, `first_name`, `last_name`, `phone`,
                    `address1`, `city`, `state`, `zip`, `products`, `order_total`, `date_ordered`, `customer_id`, `ip`, `aff_id`, `promo_code`,`status`,`gateway_response`, `created`)
                      VALUES ('$this->email','$this->first_name', '$this->last_name', '$this->phone','$this->address1','$this->city','$this->state','$this->zip', '$product_list', '$this->order_total','$date', '$this->customer_id', '$this->ip', '$this->aff_id','$this->promo_code','$status', '$gateway_response', '$dateTime');";
            $enterOrder = $conn->prepare($insertOrder);
            $enterOrder->execute();
            $enterOrder->closeCursor();
        } catch (Exception $e) {
            error_log($e);
            $error_message = 'Something went wrong please verify you did not typo any of your information';
        }

        $this->order_id = $this->grabOrderId();
        return $this->order_id;
    }

    /*
     * @param 
     * parameter list: member variables
     *
     * @throws \required data error when a required piece of information was not properly formatted/populated.
     *
     * @return \
     *
     * @functionality \inserts customer information into the trial table and relates the trial to an order and customer record.
     */

    function insertTrial() {
        $conn = Connection::get();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            $sqlCheck = "SELECT count(*) FROM `customer_trials` WHERE `email`='$this->email'";
            $result = $conn->query($sqlCheck)->fetchColumn();
            //var_dump($result);
            if ($result == 0) {
                $sql = "INSERT INTO `customer_trials` (`customer_id`, `email`, `first_name`, `last_name`, `phone`, `address1`, `city`, `state`, `zip`, `ip`, `aff_id`)
                      VALUES ('$this->customer_id', '$this->email','$this->first_name', '$this->last_name', '$this->phone','$this->address1','$this->city','$this->state','$this->zip', '$this->ip','$this->aff_id');";
                $insertinfo = $conn->prepare($sql);
                $insertinfo->execute();
                $insertinfo->closeCursor();
            } else {
                //do an update call here, but first check for the calimed field. If the calimed field isn't filled out then allow them to continue, otherwise
                //the user receives an error stating they have already claimed a trial.
                $claimsql = "SELECT claimed FROM `customer_trials` WHERE `email`='$this->email'";
                $claimed = $conn->prepare($claimsql);
                $claimed->execute();
                $claimedResult = $claimed->fetchColumn();
                $claimed->closeCursor();
                //var_dump($claimedResult);
                if ($claimedResult == 'claimed') {
                    $errormessage = 'Sorry you have already claimed a trial';
                    $shallnotpass = true;
                } else {
                    //update call here
                    $updateTrial = "UPDATE  `customer_trials` SET "
                            . "`email`='$this->email',"
                            . "`first_name`='$this->first_name',"
                            . "`last_name`='$this->last_name',"
                            . "`phone`='$this->phone',"
                            . "`address1`='$this->address1',"
                            . "`city`='$this->city',"
                            . "`state`='$this->state',"
                            . "`zip`='$this->zip'"
                            . "WHERE `customer_id` = '$this->customer_id'";
                    $updatedTrial = $conn->prepare($updateTrial);
                    $updatedTrial->execute();
                    $updatedTrial->closeCursor();
                }
            }
        } catch (Exception $e) {
            error_log($e);
            $error_message = 'Something went wrong, verify you entered accurate information and check for typos.';
        }
    }

    /*
     * @param 
     * parameter list: member variables
     *
     * @throws \if $allowtransaction is not returned funnel throws an error stating the customer has already claimed a trial.
     *
     * @return \$allowtransaction - Allows the user to continue the trial transaction
     *
     * @functionality \inserts customer information into the trial table and relates the trial to an order and customer record.
     */

    function trialCheck() {
        $conn = Connection::get();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //verifies using 3 different methods, Salesforce Contact Id, Address, and Email to see if the person has already claimed a trial.
        $claimsql = "SELECT claimed FROM `customer_trials` WHERE `email` = '" . $this->email . "'";
        $claimed = $conn->prepare($claimsql);
        $claimed->execute();
        $claimedResult = $claimed->fetchColumn();
        //var_dump($claimedResult);
        $claimed->closeCursor();
        if ($claimedResult !== 'claimed') {
            $claimsql2 = "SELECT claimed FROM `customer_trials` WHERE `phone` = '" . $this->phone . "'";
            $claimed2 = $conn->prepare($claimsql2);
            $claimed2->execute();
            $claimedResult2 = $claimed2->fetchColumn();
            $claimed2->closeCursor();
            if ($claimedResult2 !== 'claimed') {
                $claimsql3 = "SELECT claimed FROM `customer_trials` WHERE `address1` = '" . $this->address1 . "'";
                $claimed3 = $conn->prepare($claimsql3);
                $claimed3->execute();
                $claimedResult3 = $claimed3->fetchColumn();
                $claimed3->closeCursor();
                if ($claimedResult3 !== 'claimed') {
                    $allowTransaction = 'allow';
                    return $allowTransaction;
                } else {
                    $allowTransaction = '';
                    return $allowTransaction;
                }
            } else {
                $allowTransaction = '';
                return $allowTransaction;
            }
        } else {
            $allowTransaction = '';
            return $allowTransaction;
        }
    }

    /*
     * @param 
     * parameter list: member variables
     *
     * @throws \required data error/malformed data
     *
     * @return \
     *
     * @functionality \updates a trial record in the database with products and credit card info. This is used to update all the trials the customer
     * decides to take in the database table and their info for rebilling.
     */

    function updateTrial() {

        $product_list = '';
        $rebill_amount = 0;
        //creating product list for database
        for ($i = 0, $count = count($this->products); $i < $count; $i++) {
            $product_list .= $this->products[$i]['name'] . ' x ' . $this->products[$i]['qty'] . ' ';
            $rebill_amount += 69.99;
        }

        try {
            $encryptionMethod = "AES-256-CBC";  // AES is used by the U.S. gov't to encrypt top secret documents.
            $secretHash = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
            $iv = 'xxxxxxxxxxxxxxxx';
            //To encrypt
            $encryptedCCNUM = openssl_encrypt($this->ccn, $encryptionMethod, $secretHash, 0, $iv);
            $encryptedCVV = openssl_encrypt($this->cvv, $encryptionMethod, $secretHash, 0, $iv);
            $encryptedEXP = openssl_encrypt($this->ccexp, $encryptionMethod, $secretHash, 0, $iv);
            $date = date('Y-m-d');
            $rebillDate = new DateTime($date);
            $rebillDate->add(new DateInterval('P10D'));
            $rebill = $rebillDate->format('Y-m-d');
            $sqlTrial = "UPDATE `customer_trials` SET "
                    . "`ccn` = '$encryptedCCNUM',"
                    . "`ccexp` = '$encryptedEXP', `cvv` = '$encryptedCVV', "
                    . "`products` = '$product_list',`rebill_amount` = '$rebill_amount',"
                    . "`rebill_date` = '$rebill' ,`date_ordered` = '$date',`order_id` = '$this->order_id',`claimed`='claimed'"
                    . " WHERE email = '$this->email';";
            $insertinfo = $conn->prepare($sqlTrial);
            $insertinfo->execute();
            $insertinfo->closeCursor();
        } catch (Exception $e) {
            error_log($e);
            $error_message = 'Something went wrong please verify you did not typo any of your information';
        }
    }

    /*
     * @param 
     * parameter list: member variables
     *
     * @throws \required data error/malformed data
     *
     * @return \
     *
     * @functionality \retrieves the latest order id for a particular customer using their customer id
     */

    function grabOrderId() {
        $conn = Connection::get();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getOrderId = "SELECT order_id FROM `customers_order` WHERE customer_id = $this->customer_id ORDER BY order_id DESC LIMIT 1";
        foreach ($conn->query($getOrderId) as $row) {
            $orderId = $row['order_id'];
        }
        if ($orderId !== '') {
            return $orderId;
        } else {
            return 'That customer does not have an order, or the customer id is invalid.';
        }
    }

    /*
     * @param 
     * parameter list: member variables
     *
     * @throws \required data error/malformed data
     *
     * @return \
     *
     * @functionality \Sends a paid order into the shipstation UI and also updates the order in the database with the shipstation key.
     *               Accepts a single product or multiple products for sending to shipstation.
     *               Shipstation key in database is useful for updating the order in the future when it is shipped out or when the order needs to be updated.
     * 
     */

    function shipstation() {
        $date = date('m/d/Y, h:ia');
        $fullName = $this->first_name . ' ' . $this->last_name;

        $itemString = '';
        for ($i = 0, $count = count($this->products); $i < $count; $i++) {

            $tempQty = intval($this->products[$i]['qty']);
            $tempPrice = floatval($this->products[$i]['price']);
            $itemString .= "\"items\": [
          {
          \"lineItemKey\":null,
          \"sku\":null,
          \"name\":\"" . $this->products[$i]['name'] . "\",
          \"imageUrl\": null,
          \"weight\": {
          \"value\": 6,
          \"units\": \"ounces\"
          },
          \"quantity\": $tempQty,
          \"unitPrice\": $tempPrice,
          \"taxAmount\": null,
          \"shippingAmount\":0.00,
          \"warehouseLocation\":null,
          \"options\": [
          {
          \"name\": \"Size\",
          \"value\": \"small\"
          }
          ],
          \"productId\": null,
          \"fulfillmentSku\": null,
          \"adjustment\": false,
          \"upc\":null
          }
          ],";
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://ssapi.shipstation.com/orders/createorder");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, "{
      \"orderNumber\":\"$this->order_id\",
      \"orderDate\": \"$date\",
      \"paymentDate\": \"$date\",
      \"orderStatus\": \"awaiting_shipment\",
      \"customerUsername\": \"$fullName\",
      \"customerEmail\": \"$this->email\",
      \"billTo\": {
      \"name\": \"$fullName\",
      \"company\": null,
      \"street1\": \"$this->address1\",
      \"street2\":\"$this->address2\",
      \"street3\": null,
      \"city\": \"$this->city\",
      \"state\": \"$this->state\",
      \"postalCode\": \"$this->zip\",
      \"country\": null,
      \"phone\": \"$this->phone\"
      },
       \"shipTo\": {
        \"name\": \"$fullName\",
        \"street1\": \"$this->address1\",
        \"street2\": null,
        \"street3\": null,
        \"city\": \"$this->city\",
        \"state\": \"$this->state\",
        \"postalCode\": \"$this->zip\",
        \"country\": \"US\",
        \"phone\": \"$this->phone\",
        \"residential\": true
      },
    $itemString
        \"amountPaid\": \"$this->order_total\",
      \"customerNotes\": \"Thanks for ordering!\",
      \"paymentMethod\": \"Credit Card\",
      \"advancedOptions\": {
      \"warehouseId\": xxxxx,
      \"storeId\": xxxxx,
      \"source\": \"Webstore\"
      }
      }");

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Basic AUTH_HEADER_HERE"
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        //wonky way to grab the ss_key, but it yielded a faster result than converting the JSON into an array and sorting through it.
        $responseArray = explode(':', $response);
        $formatting = array('"', ',', 'orderDate');
        $responseArray[3];
        $orderKey = str_replace($formatting, '', $responseArray[3]);
        $this->ss_key = $orderKey;
        //var_dump($orderKey);
        $conn = Connection::get();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            $updateKey = "UPDATE  `customers_order` SET "
                    . "`ss_order_key`='$orderKey'"
                    . "WHERE `order_id` = $this->order_id";
            $enterLastOrder = $conn->prepare($updateKey);
            $enterLastOrder->execute();
            $enterLastOrder->closeCursor();
        } catch (Exception $ex) {
            error_log($e);
        }
        //var_dump($response);
    }

    /*
     * @param $status $gateway_response
     * parameter list: uses member variables within the two functions called, and requires the payment status and the response from the payment gateway api
     *
     * @throws \required data error/malformed data
     *
     * @return \order id from the insert order function and sets the shipstation key in the object
     *
     * @functionality \Completes an order. This is what is called on the actual page and it fulfills calling the necessary function for an order.
     * 
     */

    function completeOrder($status, $gateway_response) {

        $this->insertOrder($status, $gateway_response);
        $this->shipstation();
    }

    /*
     * @param 
     * parameter list: member variables
     *
     * @throws \required data error/malformed data along with which piece of information was malformed
     *
     * @return \ success message
     *
     * @functionality \Uses the BRITEVERIFY api to scheck if an email, phone number, or name is invalid.
     *                This is important to not accept JUNK from affiliates running offers ESPECIALLY lead campaigns.
     *                 The briteverify api offers other verifications, but we want to avoid too much friction on the
     *                 offer funnels
     * 
     */

    function verify() {

        define("USE_BRITEVERIFY", true);
        define("CHECK_EMAIL", true);
        define("CHECK_PHONE", true);
        define("CHECK_NAME", false);
        if (USE_BRITEVERIFY == true) {
            //briteverify api key to shorten our url calls a little bit
            $bVerifyKey = "BRITEVERIFY KEY HERE";
            //briteverify api call to verify emails, phone numbers, and other useful information
            //check for which variable we want to verify in the briteverify config file. We are allowed to verify multiple types
            if (CHECK_EMAIL == true) {
                //api call for briteverify uses the persons email and the api key as params. Most of them follow this convention
                $url = "http://bpi.briteverify.com/emails.json?address=" . urlencode($this->email) . "&apikey=" . $bVerifyKey;
                $c = curl_init($url);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($c, CURLOPT_FAILONERROR, false);
                curl_setopt($c, CURLOPT_TIMEOUT, 5);

                $results = curl_exec($c);
                $info = curl_getinfo($c);
                curl_close($c);
                $answer = json_decode($results);
                if ($answer->status == "invalid") {
                    return $errormessage = 'Your email is either fraudalent or invalid.';
                }
            }
            if (CHECK_PHONE == true) {

                //api call for briteverify uses the persons phone and the api key as params. Most of them follow this convention
                $url = "http://bpi.briteverify.com/phones.json?number=" . $this->phone . "&apikey=" . $bVerifyKey;
                $c = curl_init($url);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($c, CURLOPT_FAILONERROR, false);
                curl_setopt($c, CURLOPT_TIMEOUT, 5);

                $results = curl_exec($c);
                $info = curl_getinfo($c);
                curl_close($c);
                $answer = json_decode($results);
                if ($answer->status == "invalid" || $answer->status == "unknown") {
                    return $errormessage = 'Your phone number is either fraudalent or invalid.';
                }
            }
            if (CHECK_NAME == true) {

                //api call for briteverify uses the persons phone and the api key as params. Most of them follow this convention
                $url = "http://bpi.briteverify.com/names.json?fullname=" . urlencode($this->first_name) . "+" . urlencode($_POST['lastName']) . "&apikey=" . $bVerifyKey;
                $c = curl_init($url);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($c, CURLOPT_FAILONERROR, false);
                curl_setopt($c, CURLOPT_TIMEOUT, 5);

                $results = curl_exec($c);
                $info = curl_getinfo($c);
                curl_close($c);
                $answer = json_decode($results);
                if ($answer->status == "invalid" || $answer->status == "unknown") {
                    return $errormessage = 'Your name is either blocked or invalid.';
                }
            }
            return $successmessage = 'success';
        }
    }

    /*
     * @param $product, $order_total
     * parameter list: Product should be an array of products (Name, price, Quanitity) Order Total is int
     *
     * @throws \required data error when a required piece of information was not properly formatted/populated.
     *
     * @return \successful order response, or failed order response and the reason why it failed
     *
     * @functionality \calls all the necessary functions to finalize an order and complete a transaction
     */

    function chargeCard($product, $order_total) {
        require_once'../includes/nmiPayment.php';
        $gw = new gwapi;
        $gw->setLogin("LOGIN", "PASSWORD");
        $gw->setBilling($this->first_name, $this->last_name, 'none', $this->address1, $this->address2, $this->city, $this->state, $this->zip, 'US', $this->phone, "", $this->email, 'Miraclebrand');
        $gw->setShipping($this->first_name, $this->last_name, "N/A", $this->address1, $this->address2, $this->city, $this->state, $this->zip, 'US', $this->email);
        $gw->setOrder("", $product['name'] . ' x ' . $product['qty'], -1, $product['shipping'], "Miracle API", $this->ip);
        $r = $gw->doSale($order_total, $this->ccn, $this->ccexp, $this->cvv);
        return $gw->responses;
    }

    //View documentation above for this function. this simply goes to a seperate gateway for testing.

    function testChargeCard($product, $order_total) {
        require_once'../includes/nmiPayment.php';
        $gw = new gwapi;
        $gw->setLogin("TEST LOGIN", "TEST PASSWORD");
        $gw->setBilling($this->first_name, $this->last_name, 'none', $this->address1, $this->address2, $this->city, $this->state, $this->zip, 'US', $this->phone, "", $this->email, 'Miraclebrand');
        $gw->setShipping($this->first_name, $this->last_name, "N/A", $this->address1, $this->address2, $this->city, $this->state, $this->zip, 'US', $this->email);
        $gw->setOrder("", $product['name'] . ' x ' . $product['qty'], -1, $product['shipping'], "Miracle API", $this->ip);
        $r = $gw->doSale($order_total, $this->ccn, $this->ccexp, $this->cvv);
        return $gw->responses;
    }

    /*
     * @param 
     * parameter list: coupon and product member variables
     *
     * @throws \invalid coupon error is thrown before calling set coupon if the coupon was invalid
     *
     * @return \
     *
     * @functionality \sets the coupon in the member variable as well as the promo code for tracking
     */

    function setCoupon($coupon) {
        $this->coupon = $coupon;
        $this->promo_code = $this->coupon['name'];
    }

    /*
     * @param 
     * parameter list: coupon and product member variables
     *
     * @throws \invalid coupon error is thrown before calling apply coupon
     *
     * @return \successful order response, or failed order response and the reason why it failed
     *
     * @functionality \applies the set coupon to the product array. removes the previous coupon that was in the product array in case the new coupon 
     *                 is a larger discount unlocked on the funnel. Often times the higher coupon is unlocked if the customer attmepts to leave
     *                  and gets an exit popup enticing them to finish the order.
     */

    function applyCoupon() {
        if ($this->promo_code == ''):
            $this->promo_code = $this->coupon['name'];
            Customer::addProduct($couponProduct);
        else:
            for ($i = 0, $count = count($this->products); $i <= $count; $i++) {
                if (strpos($this->products[$i]['name'], 'coupon') !== false)
                    array_splice($this->products, $i, 1);
            }
            $this->promo_code = $this->coupon['name'];
            Customer::addProduct($couponProduct);
        endif;
    }

}

?>